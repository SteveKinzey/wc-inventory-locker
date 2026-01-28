<?php
/**
 * Plugin Name: Inventory Locker
 * Plugin URI: https://github.com/SteveKinzey/inventory-locker
 * Description: Locks inventory when a product is added to the cart, preventing overselling during high-demand periods. Supports WooCommerce and SureCart.
 * Version: 2.2.0
 * Author: Steve Kinzey
 * Author URI: https://sk-america.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: inventory-locker
 * GitHub Plugin URI: https://github.com/SteveKinzey/inventory-locker
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

define('INVENTORY_LOCKER_VERSION', '2.2.0');
define('INVENTORY_LOCKER_DEFAULT_DURATION', 15);
define('INVENTORY_LOCKER_FILE', __FILE__);

/**
 * ============================================================================
 * PLATFORM DETECTION
 * ============================================================================
 */

/**
 * Check if WooCommerce is active.
 */
function inventory_locker_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * Check if SureCart is active.
 */
function inventory_locker_surecart_active() {
    return defined('SURECART_PLUGIN_FILE') || class_exists('SureCart');
}

/**
 * Get list of active platforms.
 */
function inventory_locker_get_active_platforms() {
    $platforms = [];
    if (inventory_locker_woocommerce_active()) {
        $platforms[] = 'woocommerce';
    }
    if (inventory_locker_surecart_active()) {
        $platforms[] = 'surecart';
    }
    return $platforms;
}

/**
 * ============================================================================
 * CORE FUNCTIONS (Shared across all platforms)
 * ============================================================================
 */

/**
 * Get the configured lock duration in seconds.
 */
function inventory_locker_get_lock_duration() {
    $minutes = get_option('inventory_locker_duration', 0);
    if ($minutes <= 0) {
        return INVENTORY_LOCKER_DEFAULT_DURATION * MINUTE_IN_SECONDS;
    }
    return intval($minutes) * MINUTE_IN_SECONDS;
}

/**
 * Backwards compatibility alias.
 */
function wc_inventory_locker_get_lock_duration() {
    return inventory_locker_get_lock_duration();
}

/**
 * Check if the plugin has been configured.
 */
function inventory_locker_is_configured() {
    return get_option('inventory_locker_configured', false) || get_option('wc_inventory_locker_configured', false);
}

function wc_inventory_locker_is_configured() {
    return inventory_locker_is_configured();
}

/**
 * Get a unique session identifier (platform-agnostic).
 */
function inventory_locker_get_session_id() {
    if (inventory_locker_woocommerce_active() && function_exists('WC') && WC()->session) {
        return 'wc_' . WC()->session->get_customer_id();
    }
    
    if (inventory_locker_surecart_active()) {
        if (is_user_logged_in()) {
            return 'sc_user_' . get_current_user_id();
        }
        if (isset($_COOKIE['sc_customer_id'])) {
            return 'sc_' . sanitize_text_field($_COOKIE['sc_customer_id']);
        }
        if (!isset($_COOKIE['inventory_locker_session'])) {
            $session_id = wp_generate_uuid4();
            setcookie('inventory_locker_session', $session_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE['inventory_locker_session'] = $session_id;
        }
        return 'sc_' . sanitize_text_field($_COOKIE['inventory_locker_session']);
    }
    
    return null;
}

/**
 * Admin menu and settings page.
 */
add_action('admin_menu', 'inventory_locker_admin_menu');

function inventory_locker_admin_menu() {
    $parent_slug = 'options-general.php';
    $capability = 'manage_options';
    
    if (inventory_locker_woocommerce_active()) {
        $parent_slug = 'woocommerce';
        $capability = 'manage_woocommerce';
    } elseif (inventory_locker_surecart_active()) {
        $parent_slug = 'sc-dashboard';
    }
    
    add_submenu_page(
        $parent_slug,
        __('Inventory Locker Settings', 'inventory-locker'),
        __('Inventory Locker', 'inventory-locker'),
        $capability,
        'inventory-locker',
        'inventory_locker_settings_page'
    );
}

/**
 * Add settings link to plugins page.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'inventory_locker_settings_link');

function inventory_locker_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=inventory-locker') . '">' . __('Settings', 'inventory-locker') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Settings page HTML.
 */
function inventory_locker_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'inventory-locker'));
    }
    
    $message = '';
    $error = '';
    $current_duration = get_option('inventory_locker_duration', get_option('wc_inventory_locker_duration', INVENTORY_LOCKER_DEFAULT_DURATION));
    $is_configured = inventory_locker_is_configured();
    $active_platforms = inventory_locker_get_active_platforms();
    
    if (isset($_POST['inventory_locker_save']) && wp_verify_nonce($_POST['inventory_locker_nonce'], 'inventory_locker_settings')) {
        $new_duration = isset($_POST['lock_duration']) ? intval($_POST['lock_duration']) : INVENTORY_LOCKER_DEFAULT_DURATION;
        $password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
        
        if ($new_duration < 1 || $new_duration > 1440) {
            $error = __('Lock duration must be between 1 and 1440 minutes (24 hours).', 'inventory-locker');
        } elseif ($is_configured && empty($password)) {
            $error = __('Please enter your password to confirm changes.', 'inventory-locker');
        } else {
            $password_valid = true;
            
            if ($is_configured) {
                $user = wp_get_current_user();
                $password_valid = wp_check_password($password, $user->user_pass, $user->ID);
            }
            
            if (!$password_valid) {
                $error = __('Incorrect password. Please try again.', 'inventory-locker');
            } else {
                update_option('inventory_locker_duration', $new_duration);
                update_option('inventory_locker_configured', true);
                $current_duration = $new_duration;
                $is_configured = true;
                $message = __('Settings saved successfully.', 'inventory-locker');
            }
        }
    }
    
    $tracked_products = get_option('inventory_locker_tracked_products', get_option('wc_inventory_locker_tracked_products', []));
    $active_locks_count = 0;
    foreach ($tracked_products as $product_id) {
        $locks = inventory_locker_get_product_locks($product_id);
        $active_locks_count += count($locks);
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('Inventory Locker Settings', 'inventory-locker'); ?></h1>
        
        <?php if (empty($active_platforms)): ?>
        <div class="notice notice-error">
            <p><strong><?php _e('No E-commerce Platform Detected:', 'inventory-locker'); ?></strong> <?php _e('Please install and activate WooCommerce or SureCart.', 'inventory-locker'); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!$is_configured): ?>
        <div class="notice notice-warning">
            <p><strong><?php _e('Setup Required:', 'inventory-locker'); ?></strong> <?php _e('Please configure the lock duration to activate the plugin.', 'inventory-locker'); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Current Status', 'inventory-locker'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Active Platforms', 'inventory-locker'); ?></th>
                    <td>
                        <?php if (in_array('woocommerce', $active_platforms)): ?>
                        <span style="color: green; font-weight: bold;">● WooCommerce</span><br>
                        <?php endif; ?>
                        <?php if (in_array('surecart', $active_platforms)): ?>
                        <span style="color: green; font-weight: bold;">● SureCart</span><br>
                        <?php endif; ?>
                        <?php if (empty($active_platforms)): ?>
                        <span style="color: red; font-weight: bold;">● <?php _e('None detected', 'inventory-locker'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Lock Duration', 'inventory-locker'); ?></th>
                    <td><strong><?php echo esc_html($current_duration); ?> <?php _e('minutes', 'inventory-locker'); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Active Locks', 'inventory-locker'); ?></th>
                    <td><strong><?php echo esc_html($active_locks_count); ?></strong> <?php _e('items currently reserved', 'inventory-locker'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Products with Locks', 'inventory-locker'); ?></th>
                    <td><strong><?php echo count($tracked_products); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Plugin Status', 'inventory-locker'); ?></th>
                    <td>
                        <?php if ($is_configured && !empty($active_platforms)): ?>
                        <span style="color: green; font-weight: bold;">● <?php _e('Active', 'inventory-locker'); ?></span>
                        <?php elseif ($is_configured && empty($active_platforms)): ?>
                        <span style="color: red; font-weight: bold;">● <?php _e('No Platform', 'inventory-locker'); ?></span>
                        <?php else: ?>
                        <span style="color: orange; font-weight: bold;">● <?php _e('Pending Configuration', 'inventory-locker'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php echo $is_configured ? __('Update Settings', 'inventory-locker') : __('Initial Setup', 'inventory-locker'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('inventory_locker_settings', 'inventory_locker_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lock_duration"><?php _e('Lock Duration (minutes)', 'inventory-locker'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="lock_duration" name="lock_duration" value="<?php echo esc_attr($current_duration); ?>" min="1" max="1440" class="small-text" required />
                            <p class="description"><?php _e('How long inventory is reserved when added to cart. Recommended: 10-30 minutes.', 'inventory-locker'); ?></p>
                        </td>
                    </tr>
                    
                    <?php if ($is_configured): ?>
                    <tr>
                        <th scope="row">
                            <label for="admin_password"><?php _e('Confirm Password', 'inventory-locker'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="admin_password" name="admin_password" class="regular-text" required />
                            <p class="description"><?php _e('Enter your WordPress password to confirm changes.', 'inventory-locker'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" name="inventory_locker_save" class="button button-primary" value="<?php echo $is_configured ? __('Update Settings', 'inventory-locker') : __('Activate Plugin', 'inventory-locker'); ?>" />
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('How It Works', 'inventory-locker'); ?></h2>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php _e('When a customer adds a product to their cart, the inventory is temporarily "locked" for them.', 'inventory-locker'); ?></li>
                <li><?php _e('Other customers cannot add more than the available (unlocked) stock.', 'inventory-locker'); ?></li>
                <li><?php _e('Locks automatically expire after the configured duration if the customer doesn\'t complete checkout.', 'inventory-locker'); ?></li>
                <li><?php _e('Locks are released immediately when items are removed from cart or checkout is completed.', 'inventory-locker'); ?></li>
            </ul>
            <h3><?php _e('Supported Platforms', 'inventory-locker'); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>WooCommerce</strong> - <?php _e('Full support for simple and variable products', 'inventory-locker'); ?></li>
                <li><strong>SureCart</strong> - <?php _e('Support for products with stock management enabled', 'inventory-locker'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Show setup notice if not configured.
 */
add_action('admin_notices', 'inventory_locker_setup_notice');

function inventory_locker_setup_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (inventory_locker_is_configured()) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'inventory-locker') !== false) {
        return;
    }
    
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Inventory Locker:', 'inventory-locker'); ?></strong>
            <?php _e('Please complete the initial setup to activate inventory locking.', 'inventory-locker'); ?>
            <a href="<?php echo admin_url('admin.php?page=inventory-locker'); ?>" class="button button-primary" style="margin-left: 10px;"><?php _e('Configure Now', 'inventory-locker'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Only run locking functionality if configured.
 */
function inventory_locker_should_run() {
    return inventory_locker_is_configured() && !empty(inventory_locker_get_active_platforms());
}

function wc_inventory_locker_should_run() {
    return inventory_locker_should_run();
}

/**
 * Get a unique session identifier for the current user (WooCommerce alias).
 */
function wc_inventory_locker_get_session_id() {
    return inventory_locker_get_session_id();
}

/**
 * Get all locks for a product from the shared transient store.
 * Returns array of locks: [ session_id => [ 'quantity' => int, 'expires' => timestamp ] ]
 */
function inventory_locker_get_product_locks($product_id) {
    $locks = get_transient("inventory_locks_{$product_id}");
    if (!is_array($locks)) {
        $locks = get_transient("wc_inventory_locks_{$product_id}");
    }
    if (!is_array($locks)) {
        $locks = [];
    }
    
    $now = time();
    $changed = false;
    foreach ($locks as $session_id => $lock_data) {
        if ($lock_data['expires'] < $now) {
            unset($locks[$session_id]);
            $changed = true;
        }
    }
    
    if ($changed) {
        inventory_locker_save_product_locks($product_id, $locks);
    }
    
    return $locks;
}

function wc_inventory_locker_get_product_locks($product_id) {
    return inventory_locker_get_product_locks($product_id);
}

/**
 * Save locks for a product to the shared transient store.
 */
function inventory_locker_save_product_locks($product_id, $locks) {
    if (empty($locks)) {
        delete_transient("inventory_locks_{$product_id}");
        delete_transient("wc_inventory_locks_{$product_id}");
        $tracked = get_option('inventory_locker_tracked_products', get_option('wc_inventory_locker_tracked_products', []));
        $tracked = array_diff($tracked, [$product_id]);
        update_option('inventory_locker_tracked_products', array_values($tracked), false);
    } else {
        $duration = inventory_locker_get_lock_duration();
        set_transient("inventory_locks_{$product_id}", $locks, $duration + MINUTE_IN_SECONDS);
        $tracked = get_option('inventory_locker_tracked_products', get_option('wc_inventory_locker_tracked_products', []));
        if (!in_array($product_id, $tracked)) {
            $tracked[] = $product_id;
            update_option('inventory_locker_tracked_products', $tracked, false);
        }
    }
}

function wc_inventory_locker_save_product_locks($product_id, $locks) {
    return inventory_locker_save_product_locks($product_id, $locks);
}

/**
 * Get total locked quantity for a product (excluding current user's locks).
 */
function wc_inventory_locker_get_locked_quantity($product_id, $exclude_session_id = null) {
    $locks = wc_inventory_locker_get_product_locks($product_id);
    $total = 0;
    
    foreach ($locks as $session_id => $lock_data) {
        if ($exclude_session_id && $session_id === $exclude_session_id) {
            continue;
        }
        $total += $lock_data['quantity'];
    }
    
    return $total;
}

/**
 * Get available stock for a product (actual stock minus locked quantity by others).
 */
function wc_inventory_locker_get_available_stock($product_id, $exclude_session_id = null) {
    $product = wc_get_product($product_id);
    if (!$product || !$product->managing_stock()) {
        return PHP_INT_MAX;
    }
    
    $stock = $product->get_stock_quantity();
    $locked = wc_inventory_locker_get_locked_quantity($product_id, $exclude_session_id);
    
    return max(0, $stock - $locked);
}

/**
 * Validate add-to-cart against available stock (checking shared locks).
 */
add_filter('woocommerce_add_to_cart_validation', 'wc_inventory_locker_validate_add_to_cart', 10, 5);

function wc_inventory_locker_validate_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = []) {
    if (!wc_inventory_locker_should_run()) {
        return $passed;
    }
    
    $check_product_id = $variation_id ? $variation_id : $product_id;
    $product = wc_get_product($check_product_id);
    
    if (!$product || !$product->managing_stock()) {
        return $passed;
    }
    
    $session_id = wc_inventory_locker_get_session_id();
    $available = wc_inventory_locker_get_available_stock($check_product_id, $session_id);
    
    $cart_quantity = 0;
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $cart_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
            if ($cart_product_id == $check_product_id) {
                $cart_quantity += $cart_item['quantity'];
            }
        }
    }
    
    $total_requested = $cart_quantity + $quantity;
    
    if ($total_requested > $available) {
        wc_add_notice(
            sprintf(
                __('Sorry, only %d units of "%s" are currently available. Other customers have items reserved in their carts.', 'inventory-locker'),
                $available,
                $product->get_name()
            ),
            'error'
        );
        return false;
    }
    
    return $passed;
}

/**
 * Lock stock when product is added to cart (shared transient store).
 */
add_action('woocommerce_add_to_cart', 'wc_inventory_locker_lock_stock', 10, 6);

function wc_inventory_locker_lock_stock($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (!wc_inventory_locker_should_run()) {
        return;
    }
    
    $check_product_id = $variation_id ? $variation_id : $product_id;
    $product = wc_get_product($check_product_id);
    
    if (!$product || !$product->managing_stock()) {
        return;
    }
    
    $session_id = wc_inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    wc_inventory_locker_update_session_locks($session_id);
    
    do_action('wc_inventory_locker_product_locked', $check_product_id);
}

/**
 * Update all locks for a session based on current cart contents.
 */
function wc_inventory_locker_update_session_locks($session_id) {
    if (!WC()->cart) {
        return;
    }
    
    $cart_products = [];
    foreach (WC()->cart->get_cart() as $cart_item) {
        $check_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        $product = wc_get_product($check_product_id);
        
        if ($product && $product->managing_stock()) {
            if (!isset($cart_products[$check_product_id])) {
                $cart_products[$check_product_id] = 0;
            }
            $cart_products[$check_product_id] += $cart_item['quantity'];
        }
    }
    
    $duration = wc_inventory_locker_get_lock_duration();
    foreach ($cart_products as $product_id => $quantity) {
        $locks = wc_inventory_locker_get_product_locks($product_id);
        $locks[$session_id] = [
            'quantity' => $quantity,
            'expires' => time() + $duration,
        ];
        wc_inventory_locker_save_product_locks($product_id, $locks);
    }
}

/**
 * Remove lock when item is removed from cart.
 */
add_action('woocommerce_cart_item_removed', 'wc_inventory_locker_restore_stock', 10, 2);

function wc_inventory_locker_restore_stock($cart_item_key, $cart) {
    $item = $cart->removed_cart_contents[$cart_item_key];
    $check_product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
    
    $session_id = wc_inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    $remaining_quantity = 0;
    foreach ($cart->get_cart() as $cart_item) {
        $cart_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        if ($cart_product_id == $check_product_id) {
            $remaining_quantity += $cart_item['quantity'];
        }
    }
    
    $locks = wc_inventory_locker_get_product_locks($check_product_id);
    
    if ($remaining_quantity > 0) {
        $locks[$session_id] = [
            'quantity' => $remaining_quantity,
            'expires' => time() + wc_inventory_locker_get_lock_duration(),
        ];
    } else {
        unset($locks[$session_id]);
    }
    
    wc_inventory_locker_save_product_locks($check_product_id, $locks);
    do_action('wc_inventory_locker_stock_restored', $check_product_id);
}

/**
 * Remove all locks when cart is emptied.
 */
add_action('woocommerce_cart_emptied', 'wc_inventory_locker_restore_all_stock');

function wc_inventory_locker_restore_all_stock() {
    $session_id = wc_inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    if (!WC()->cart) {
        return;
    }
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $check_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        $locks = wc_inventory_locker_get_product_locks($check_product_id);
        unset($locks[$session_id]);
        wc_inventory_locker_save_product_locks($check_product_id, $locks);
        do_action('wc_inventory_locker_stock_restored', $check_product_id);
    }
}

/**
 * Release locks after successful order completion.
 * Multiple hooks to ensure locks are released regardless of how checkout completes.
 */
add_action('woocommerce_thankyou', 'wc_inventory_locker_release_on_order', 10, 1);
add_action('woocommerce_payment_complete', 'wc_inventory_locker_release_on_payment_complete', 10, 1);
add_action('woocommerce_order_status_processing', 'wc_inventory_locker_release_on_order_status', 10, 1);
add_action('woocommerce_order_status_completed', 'wc_inventory_locker_release_on_order_status', 10, 1);
add_action('woocommerce_order_status_on-hold', 'wc_inventory_locker_release_on_order_status', 10, 1);
add_action('woocommerce_checkout_order_processed', 'wc_inventory_locker_release_on_checkout_processed', 10, 3);

function wc_inventory_locker_release_on_order($order_id) {
    $session_id = wc_inventory_locker_get_session_id();
    wc_inventory_locker_release_locks_for_order($order_id, $session_id);
}

function wc_inventory_locker_release_on_payment_complete($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    $session_id = $order->get_meta('_wc_inventory_locker_session_id');
    wc_inventory_locker_release_locks_for_order($order_id, $session_id);
}

function wc_inventory_locker_release_on_order_status($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    $session_id = $order->get_meta('_wc_inventory_locker_session_id');
    wc_inventory_locker_release_locks_for_order($order_id, $session_id);
}

function wc_inventory_locker_release_on_checkout_processed($order_id, $posted_data, $order) {
    $session_id = wc_inventory_locker_get_session_id();
    
    if ($session_id && $order) {
        $order->update_meta_data('_wc_inventory_locker_session_id', $session_id);
        $order->save();
    }
    
    wc_inventory_locker_release_locks_for_order($order_id, $session_id);
}

/**
 * Core function to release locks for an order.
 */
function wc_inventory_locker_release_locks_for_order($order_id, $session_id = null) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    if ($order->get_meta('_wc_inventory_locker_released')) {
        return;
    }
    
    if (!$session_id) {
        $session_id = $order->get_meta('_wc_inventory_locker_session_id');
    }
    
    if (!$session_id) {
        return;
    }
    
    foreach ($order->get_items() as $item) {
        $check_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $locks = wc_inventory_locker_get_product_locks($check_product_id);
        unset($locks[$session_id]);
        wc_inventory_locker_save_product_locks($check_product_id, $locks);
    }
    
    $order->update_meta_data('_wc_inventory_locker_released', true);
    $order->save();
}

/**
 * Update lock expiration on cart updates to keep locks alive while user is active.
 */
add_action('woocommerce_cart_updated', 'wc_inventory_locker_refresh_locks');

function wc_inventory_locker_refresh_locks() {
    $session_id = wc_inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    wc_inventory_locker_update_session_locks($session_id);
}

/**
 * Handle quantity updates in cart.
 */
add_filter('woocommerce_update_cart_validation', 'wc_inventory_locker_validate_cart_update', 10, 4);

function wc_inventory_locker_validate_cart_update($passed, $cart_item_key, $values, $quantity) {
    if (!wc_inventory_locker_should_run()) {
        return $passed;
    }
    
    $check_product_id = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];
    $product = wc_get_product($check_product_id);
    
    if (!$product || !$product->managing_stock()) {
        return $passed;
    }
    
    $session_id = wc_inventory_locker_get_session_id();
    $available = wc_inventory_locker_get_available_stock($check_product_id, $session_id);
    
    $other_cart_quantity = 0;
    foreach (WC()->cart->get_cart() as $key => $cart_item) {
        if ($key === $cart_item_key) {
            continue;
        }
        $cart_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        if ($cart_product_id == $check_product_id) {
            $other_cart_quantity += $cart_item['quantity'];
        }
    }
    
    $total_requested = $other_cart_quantity + $quantity;
    
    if ($total_requested > $available) {
        wc_add_notice(
            sprintf(
                __('Sorry, only %d units of "%s" are currently available. Other customers have items reserved in their carts.', 'inventory-locker'),
                $available,
                $product->get_name()
            ),
            'error'
        );
        return false;
    }
    
    return $passed;
}

/**
 * Track all product IDs that have locks (for cleanup purposes).
 */
function wc_inventory_locker_track_locked_product($product_id) {
    $tracked = get_option('wc_inventory_locker_tracked_products', []);
    if (!in_array($product_id, $tracked)) {
        $tracked[] = $product_id;
        update_option('wc_inventory_locker_tracked_products', $tracked, false);
    }
}

/**
 * Clean up tracking for products with no locks.
 */
function wc_inventory_locker_untrack_product($product_id) {
    $locks = wc_inventory_locker_get_product_locks($product_id);
    if (empty($locks)) {
        $tracked = get_option('wc_inventory_locker_tracked_products', []);
        $tracked = array_diff($tracked, [$product_id]);
        update_option('wc_inventory_locker_tracked_products', array_values($tracked), false);
    }
}

/**
 * Clean up locks for a specific session across all tracked products.
 */
function wc_inventory_locker_cleanup_session_locks($session_id) {
    if (!$session_id) {
        return;
    }
    
    $tracked = get_option('wc_inventory_locker_tracked_products', []);
    
    foreach ($tracked as $product_id) {
        $locks = wc_inventory_locker_get_product_locks($product_id);
        if (isset($locks[$session_id])) {
            unset($locks[$session_id]);
            wc_inventory_locker_save_product_locks($product_id, $locks);
            do_action('wc_inventory_locker_stock_restored', $product_id);
        }
        wc_inventory_locker_untrack_product($product_id);
    }
}

/**
 * Hook into WooCommerce session destruction to clean up locks.
 */
add_action('woocommerce_cleanup_sessions', 'wc_inventory_locker_cleanup_expired_sessions');

function wc_inventory_locker_cleanup_expired_sessions() {
    wc_inventory_locker_run_cleanup();
}

/**
 * Schedule cleanup cron job on plugin activation.
 */
register_activation_hook(__FILE__, 'wc_inventory_locker_schedule_cleanup');

function wc_inventory_locker_schedule_cleanup() {
    if (!wp_next_scheduled('wc_inventory_locker_cleanup_cron')) {
        wp_schedule_event(time(), 'hourly', 'wc_inventory_locker_cleanup_cron');
    }
}

/**
 * Clear scheduled cron on plugin deactivation.
 */
register_deactivation_hook(__FILE__, 'wc_inventory_locker_unschedule_cleanup');

function wc_inventory_locker_unschedule_cleanup() {
    wp_clear_scheduled_hook('wc_inventory_locker_cleanup_cron');
}

/**
 * Cron job to clean up expired locks.
 */
add_action('wc_inventory_locker_cleanup_cron', 'wc_inventory_locker_run_cleanup');

function wc_inventory_locker_run_cleanup() {
    $tracked = get_option('wc_inventory_locker_tracked_products', []);
    
    foreach ($tracked as $product_id) {
        $locks = wc_inventory_locker_get_product_locks($product_id);
        wc_inventory_locker_untrack_product($product_id);
    }
}

/**
 * Clean up locks when user logs out.
 */
add_action('wp_logout', 'wc_inventory_locker_cleanup_on_logout');

function wc_inventory_locker_cleanup_on_logout() {
    $session_id = wc_inventory_locker_get_session_id();
    wc_inventory_locker_cleanup_session_locks($session_id);
}

/**
 * Clean up locks before WC session is destroyed.
 */
add_action('woocommerce_set_cart_cookies', 'wc_inventory_locker_maybe_cleanup_on_session_change', 10, 1);

function wc_inventory_locker_maybe_cleanup_on_session_change($set) {
    if (!$set) {
        $session_id = wc_inventory_locker_get_session_id();
        wc_inventory_locker_cleanup_session_locks($session_id);
    }
}

add_action('admin_notices', 'inventory_locker_github_updater_notice');

function inventory_locker_github_updater_notice() {
    if (!is_admin() || !current_user_can('manage_options')) return;
    
    if (!inventory_locker_is_configured()) return;

    if (!class_exists('GitHub_Updater\Bootstrap') && !class_exists('github_updater')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Inventory Locker:</strong> To enable automatic updates, install the <a href="https://github.com/afragen/github-updater" target="_blank">GitHub Updater plugin</a>.</p>';
        echo '</div>';
    }
}

/**
 * ============================================================================
 * SURECART INTEGRATION
 * ============================================================================
 */

/**
 * Get SureCart product stock quantity.
 */
function inventory_locker_sc_get_stock($product_id) {
    if (!function_exists('sc_get_product')) {
        return PHP_INT_MAX;
    }
    
    $product = sc_get_product($product_id);
    if (!$product || empty($product->stock_enabled)) {
        return PHP_INT_MAX;
    }
    
    return isset($product->available_stock) ? intval($product->available_stock) : PHP_INT_MAX;
}

/**
 * Get available stock for SureCart product (actual stock minus locked quantity).
 */
function inventory_locker_sc_get_available_stock($product_id, $exclude_session_id = null) {
    $stock = inventory_locker_sc_get_stock($product_id);
    if ($stock === PHP_INT_MAX) {
        return PHP_INT_MAX;
    }
    
    $locked = 0;
    $locks = inventory_locker_get_product_locks('sc_' . $product_id);
    
    foreach ($locks as $session_id => $lock_data) {
        if ($exclude_session_id && $session_id === $exclude_session_id) {
            continue;
        }
        $locked += $lock_data['quantity'];
    }
    
    return max(0, $stock - $locked);
}

/**
 * Lock SureCart product stock.
 */
function inventory_locker_sc_lock_stock($product_id, $quantity) {
    if (!inventory_locker_should_run()) {
        return true;
    }
    
    $session_id = inventory_locker_get_session_id();
    if (!$session_id) {
        return true;
    }
    
    $lock_key = 'sc_' . $product_id;
    $locks = inventory_locker_get_product_locks($lock_key);
    
    $current_quantity = isset($locks[$session_id]) ? $locks[$session_id]['quantity'] : 0;
    $new_quantity = $current_quantity + $quantity;
    
    $locks[$session_id] = [
        'quantity' => $new_quantity,
        'expires' => time() + inventory_locker_get_lock_duration(),
    ];
    
    inventory_locker_save_product_locks($lock_key, $locks);
    
    do_action('inventory_locker_product_locked', $lock_key);
    
    return true;
}

/**
 * Release SureCart product lock.
 */
function inventory_locker_sc_release_lock($product_id, $quantity = null) {
    $session_id = inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    $lock_key = 'sc_' . $product_id;
    $locks = inventory_locker_get_product_locks($lock_key);
    
    if ($quantity === null) {
        unset($locks[$session_id]);
    } else {
        if (isset($locks[$session_id])) {
            $new_quantity = $locks[$session_id]['quantity'] - $quantity;
            if ($new_quantity <= 0) {
                unset($locks[$session_id]);
            } else {
                $locks[$session_id]['quantity'] = $new_quantity;
                $locks[$session_id]['expires'] = time() + inventory_locker_get_lock_duration();
            }
        }
    }
    
    inventory_locker_save_product_locks($lock_key, $locks);
    do_action('inventory_locker_stock_restored', $lock_key);
}

/**
 * Hook into SureCart purchase creation to release locks.
 */
add_action('surecart/purchase_created', 'inventory_locker_sc_on_purchase_created', 10, 1);

function inventory_locker_sc_on_purchase_created($purchase) {
    if (!inventory_locker_surecart_active() || !inventory_locker_should_run()) {
        return;
    }
    
    $session_id = inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    if (isset($purchase->line_items) && is_array($purchase->line_items)) {
        foreach ($purchase->line_items as $item) {
            if (isset($item->price->product_id)) {
                inventory_locker_sc_release_lock($item->price->product_id);
            }
        }
    }
}

/**
 * Hook into SureCart order completion.
 */
add_action('surecart/order_created', 'inventory_locker_sc_on_order_created', 10, 1);

function inventory_locker_sc_on_order_created($order) {
    if (!inventory_locker_surecart_active() || !inventory_locker_should_run()) {
        return;
    }
    
    $session_id = inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    if (isset($order->line_items) && is_array($order->line_items)) {
        foreach ($order->line_items as $item) {
            if (isset($item->price->product_id)) {
                inventory_locker_sc_release_lock($item->price->product_id);
            }
        }
    }
}

/**
 * Register SureCart REST API endpoint for stock validation.
 */
add_action('rest_api_init', 'inventory_locker_sc_register_rest_routes');

function inventory_locker_sc_register_rest_routes() {
    register_rest_route('inventory-locker/v1', '/validate-stock', [
        'methods' => 'POST',
        'callback' => 'inventory_locker_sc_validate_stock_rest',
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('inventory-locker/v1', '/lock-stock', [
        'methods' => 'POST',
        'callback' => 'inventory_locker_sc_lock_stock_rest',
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('inventory-locker/v1', '/release-stock', [
        'methods' => 'POST',
        'callback' => 'inventory_locker_sc_release_stock_rest',
        'permission_callback' => '__return_true',
    ]);
}

/**
 * REST endpoint to validate stock availability.
 */
function inventory_locker_sc_validate_stock_rest($request) {
    $product_id = $request->get_param('product_id');
    $quantity = intval($request->get_param('quantity'));
    
    if (!$product_id || $quantity < 1) {
        return new WP_REST_Response(['valid' => false, 'message' => 'Invalid parameters'], 400);
    }
    
    $session_id = inventory_locker_get_session_id();
    $available = inventory_locker_sc_get_available_stock($product_id, $session_id);
    
    if ($quantity > $available) {
        return new WP_REST_Response([
            'valid' => false,
            'available' => $available,
            'message' => sprintf(
                __('Sorry, only %d units are currently available. Other customers have items reserved.', 'inventory-locker'),
                $available
            ),
        ], 200);
    }
    
    return new WP_REST_Response(['valid' => true, 'available' => $available], 200);
}

/**
 * REST endpoint to lock stock.
 */
function inventory_locker_sc_lock_stock_rest($request) {
    $product_id = $request->get_param('product_id');
    $quantity = intval($request->get_param('quantity'));
    
    if (!$product_id || $quantity < 1) {
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid parameters'], 400);
    }
    
    $session_id = inventory_locker_get_session_id();
    $available = inventory_locker_sc_get_available_stock($product_id, $session_id);
    
    if ($quantity > $available) {
        return new WP_REST_Response([
            'success' => false,
            'available' => $available,
            'message' => sprintf(
                __('Sorry, only %d units are currently available.', 'inventory-locker'),
                $available
            ),
        ], 200);
    }
    
    inventory_locker_sc_lock_stock($product_id, $quantity);
    
    return new WP_REST_Response(['success' => true], 200);
}

/**
 * REST endpoint to release stock.
 */
function inventory_locker_sc_release_stock_rest($request) {
    $product_id = $request->get_param('product_id');
    $quantity = $request->get_param('quantity');
    
    if (!$product_id) {
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid parameters'], 400);
    }
    
    inventory_locker_sc_release_lock($product_id, $quantity ? intval($quantity) : null);
    
    return new WP_REST_Response(['success' => true], 200);
}

/**
 * Enqueue SureCart frontend JavaScript for stock validation.
 */
add_action('wp_enqueue_scripts', 'inventory_locker_sc_enqueue_scripts');

function inventory_locker_sc_enqueue_scripts() {
    if (!inventory_locker_surecart_active() || !inventory_locker_should_run()) {
        return;
    }
    
    wp_add_inline_script('surecart', inventory_locker_sc_get_inline_script(), 'after');
}

/**
 * Get inline JavaScript for SureCart integration.
 */
function inventory_locker_sc_get_inline_script() {
    $rest_url = rest_url('inventory-locker/v1/');
    $nonce = wp_create_nonce('wp_rest');
    
    return "
    (function() {
        if (typeof window.inventoryLocker !== 'undefined') return;
        
        window.inventoryLocker = {
            restUrl: '{$rest_url}',
            nonce: '{$nonce}',
            
            validateStock: async function(productId, quantity) {
                try {
                    const response = await fetch(this.restUrl + 'validate-stock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': this.nonce
                        },
                        body: JSON.stringify({ product_id: productId, quantity: quantity })
                    });
                    return await response.json();
                } catch (e) {
                    console.error('Inventory Locker validation error:', e);
                    return { valid: true };
                }
            },
            
            lockStock: async function(productId, quantity) {
                try {
                    const response = await fetch(this.restUrl + 'lock-stock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': this.nonce
                        },
                        body: JSON.stringify({ product_id: productId, quantity: quantity })
                    });
                    return await response.json();
                } catch (e) {
                    console.error('Inventory Locker lock error:', e);
                    return { success: false };
                }
            },
            
            releaseStock: async function(productId, quantity) {
                try {
                    const response = await fetch(this.restUrl + 'release-stock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': this.nonce
                        },
                        body: JSON.stringify({ product_id: productId, quantity: quantity || null })
                    });
                    return await response.json();
                } catch (e) {
                    console.error('Inventory Locker release error:', e);
                    return { success: false };
                }
            }
        };
        
        document.addEventListener('surecart:cart:item:added', function(e) {
            if (e.detail && e.detail.price && e.detail.price.product_id) {
                window.inventoryLocker.lockStock(e.detail.price.product_id, e.detail.quantity || 1);
            }
        });
        
        document.addEventListener('surecart:cart:item:removed', function(e) {
            if (e.detail && e.detail.price && e.detail.price.product_id) {
                window.inventoryLocker.releaseStock(e.detail.price.product_id, e.detail.quantity || null);
            }
        });
        
        document.addEventListener('surecart:checkout:success', function(e) {
            console.log('Inventory Locker: Checkout complete, locks released server-side');
        });
    })();
    ";
}

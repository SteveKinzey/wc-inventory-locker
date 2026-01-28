<?php
/**
 * Plugin Name: WooCommerce Inventory Locker
 * Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Description: Locks WooCommerce inventory when a product is added to the cart, preventing overselling during high-demand periods.
 * Version: 1.5
 * Author: Steve Kinzey
 * Author URI: https://sk-america.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

define('WC_INVENTORY_LOCKER_VERSION', '1.5');
define('WC_INVENTORY_LOCKER_DEFAULT_DURATION', 15);

/**
 * Get the configured lock duration in seconds.
 */
function wc_inventory_locker_get_lock_duration() {
    $minutes = get_option('wc_inventory_locker_duration', 0);
    if ($minutes <= 0) {
        return WC_INVENTORY_LOCKER_DEFAULT_DURATION * MINUTE_IN_SECONDS;
    }
    return intval($minutes) * MINUTE_IN_SECONDS;
}

/**
 * Check if the plugin has been configured.
 */
function wc_inventory_locker_is_configured() {
    return get_option('wc_inventory_locker_configured', false);
}

/**
 * Admin menu and settings page.
 */
add_action('admin_menu', 'wc_inventory_locker_admin_menu');

function wc_inventory_locker_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Inventory Locker Settings', 'wc-inventory-locker'),
        __('Inventory Locker', 'wc-inventory-locker'),
        'manage_woocommerce',
        'wc-inventory-locker',
        'wc_inventory_locker_settings_page'
    );
}

/**
 * Add settings link to plugins page.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_inventory_locker_settings_link');

function wc_inventory_locker_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-inventory-locker') . '">' . __('Settings', 'wc-inventory-locker') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Settings page HTML.
 */
function wc_inventory_locker_settings_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wc-inventory-locker'));
    }
    
    $message = '';
    $error = '';
    $current_duration = get_option('wc_inventory_locker_duration', WC_INVENTORY_LOCKER_DEFAULT_DURATION);
    $is_configured = wc_inventory_locker_is_configured();
    
    if (isset($_POST['wc_inventory_locker_save']) && wp_verify_nonce($_POST['wc_inventory_locker_nonce'], 'wc_inventory_locker_settings')) {
        $new_duration = isset($_POST['lock_duration']) ? intval($_POST['lock_duration']) : WC_INVENTORY_LOCKER_DEFAULT_DURATION;
        $password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
        
        if ($new_duration < 1 || $new_duration > 1440) {
            $error = __('Lock duration must be between 1 and 1440 minutes (24 hours).', 'wc-inventory-locker');
        } elseif ($is_configured && empty($password)) {
            $error = __('Please enter your password to confirm changes.', 'wc-inventory-locker');
        } else {
            $password_valid = true;
            
            if ($is_configured) {
                $user = wp_get_current_user();
                $password_valid = wp_check_password($password, $user->user_pass, $user->ID);
            }
            
            if (!$password_valid) {
                $error = __('Incorrect password. Please try again.', 'wc-inventory-locker');
            } else {
                update_option('wc_inventory_locker_duration', $new_duration);
                update_option('wc_inventory_locker_configured', true);
                $current_duration = $new_duration;
                $is_configured = true;
                $message = __('Settings saved successfully.', 'wc-inventory-locker');
            }
        }
    }
    
    $tracked_products = get_option('wc_inventory_locker_tracked_products', []);
    $active_locks_count = 0;
    foreach ($tracked_products as $product_id) {
        $locks = wc_inventory_locker_get_product_locks($product_id);
        $active_locks_count += count($locks);
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('WooCommerce Inventory Locker Settings', 'wc-inventory-locker'); ?></h1>
        
        <?php if (!$is_configured): ?>
        <div class="notice notice-warning">
            <p><strong><?php _e('Setup Required:', 'wc-inventory-locker'); ?></strong> <?php _e('Please configure the lock duration to activate the plugin.', 'wc-inventory-locker'); ?></p>
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
            <h2 style="margin-top: 0;"><?php _e('Current Status', 'wc-inventory-locker'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Lock Duration', 'wc-inventory-locker'); ?></th>
                    <td><strong><?php echo esc_html($current_duration); ?> <?php _e('minutes', 'wc-inventory-locker'); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Active Locks', 'wc-inventory-locker'); ?></th>
                    <td><strong><?php echo esc_html($active_locks_count); ?></strong> <?php _e('items currently reserved', 'wc-inventory-locker'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Products with Locks', 'wc-inventory-locker'); ?></th>
                    <td><strong><?php echo count($tracked_products); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Plugin Status', 'wc-inventory-locker'); ?></th>
                    <td>
                        <?php if ($is_configured): ?>
                        <span style="color: green; font-weight: bold;">● <?php _e('Active', 'wc-inventory-locker'); ?></span>
                        <?php else: ?>
                        <span style="color: orange; font-weight: bold;">● <?php _e('Pending Configuration', 'wc-inventory-locker'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php echo $is_configured ? __('Update Settings', 'wc-inventory-locker') : __('Initial Setup', 'wc-inventory-locker'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('wc_inventory_locker_settings', 'wc_inventory_locker_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lock_duration"><?php _e('Lock Duration (minutes)', 'wc-inventory-locker'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="lock_duration" name="lock_duration" value="<?php echo esc_attr($current_duration); ?>" min="1" max="1440" class="small-text" required />
                            <p class="description"><?php _e('How long inventory is reserved when added to cart. Recommended: 10-30 minutes.', 'wc-inventory-locker'); ?></p>
                        </td>
                    </tr>
                    
                    <?php if ($is_configured): ?>
                    <tr>
                        <th scope="row">
                            <label for="admin_password"><?php _e('Confirm Password', 'wc-inventory-locker'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="admin_password" name="admin_password" class="regular-text" required />
                            <p class="description"><?php _e('Enter your WordPress password to confirm changes.', 'wc-inventory-locker'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wc_inventory_locker_save" class="button button-primary" value="<?php echo $is_configured ? __('Update Settings', 'wc-inventory-locker') : __('Activate Plugin', 'wc-inventory-locker'); ?>" />
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('How It Works', 'wc-inventory-locker'); ?></h2>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php _e('When a customer adds a product to their cart, the inventory is temporarily "locked" for them.', 'wc-inventory-locker'); ?></li>
                <li><?php _e('Other customers cannot add more than the available (unlocked) stock.', 'wc-inventory-locker'); ?></li>
                <li><?php _e('Locks automatically expire after the configured duration if the customer doesn\'t complete checkout.', 'wc-inventory-locker'); ?></li>
                <li><?php _e('Locks are released immediately when items are removed from cart or checkout is completed.', 'wc-inventory-locker'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Show setup notice if not configured.
 */
add_action('admin_notices', 'wc_inventory_locker_setup_notice');

function wc_inventory_locker_setup_notice() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    if (wc_inventory_locker_is_configured()) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->id === 'woocommerce_page_wc-inventory-locker') {
        return;
    }
    
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('WooCommerce Inventory Locker:', 'wc-inventory-locker'); ?></strong>
            <?php _e('Please complete the initial setup to activate inventory locking.', 'wc-inventory-locker'); ?>
            <a href="<?php echo admin_url('admin.php?page=wc-inventory-locker'); ?>" class="button button-primary" style="margin-left: 10px;"><?php _e('Configure Now', 'wc-inventory-locker'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Only run locking functionality if configured.
 */
function wc_inventory_locker_should_run() {
    return wc_inventory_locker_is_configured();
}

/**
 * Get a unique session identifier for the current user.
 */
function wc_inventory_locker_get_session_id() {
    if (WC()->session) {
        return WC()->session->get_customer_id();
    }
    return null;
}

/**
 * Get all locks for a product from the shared transient store.
 * Returns array of locks: [ session_id => [ 'quantity' => int, 'expires' => timestamp ] ]
 */
function wc_inventory_locker_get_product_locks($product_id) {
    $locks = get_transient("wc_inventory_locks_{$product_id}");
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
        wc_inventory_locker_save_product_locks($product_id, $locks);
    }
    
    return $locks;
}

/**
 * Save locks for a product to the shared transient store.
 */
function wc_inventory_locker_save_product_locks($product_id, $locks) {
    if (empty($locks)) {
        delete_transient("wc_inventory_locks_{$product_id}");
        $tracked = get_option('wc_inventory_locker_tracked_products', []);
        $tracked = array_diff($tracked, [$product_id]);
        update_option('wc_inventory_locker_tracked_products', array_values($tracked), false);
    } else {
        $duration = wc_inventory_locker_get_lock_duration();
        set_transient("wc_inventory_locks_{$product_id}", $locks, $duration + MINUTE_IN_SECONDS);
        $tracked = get_option('wc_inventory_locker_tracked_products', []);
        if (!in_array($product_id, $tracked)) {
            $tracked[] = $product_id;
            update_option('wc_inventory_locker_tracked_products', $tracked, false);
        }
    }
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
                __('Sorry, only %d units of "%s" are currently available. Other customers have items reserved in their carts.', 'wc-inventory-locker'),
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
 */
add_action('woocommerce_thankyou', 'wc_inventory_locker_release_on_order', 10, 1);

function wc_inventory_locker_release_on_order($order_id) {
    $session_id = wc_inventory_locker_get_session_id();
    if (!$session_id) {
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    foreach ($order->get_items() as $item) {
        $check_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $locks = wc_inventory_locker_get_product_locks($check_product_id);
        unset($locks[$session_id]);
        wc_inventory_locker_save_product_locks($check_product_id, $locks);
    }
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
                __('Sorry, only %d units of "%s" are currently available. Other customers have items reserved in their carts.', 'wc-inventory-locker'),
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

add_action('admin_notices', 'wc_inventory_github_updater_notice');

function wc_inventory_github_updater_notice() {
    if (!is_admin() || !current_user_can('manage_options')) return;
    
    if (!wc_inventory_locker_is_configured()) return;

    if (!class_exists('GitHub_Updater\Bootstrap') && !class_exists('github_updater')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>WooCommerce Inventory Locker:</strong> To enable automatic updates, install the <a href="https://github.com/afragen/github-updater" target="_blank">GitHub Updater plugin</a>.</p>';
        echo '</div>';
    }
}

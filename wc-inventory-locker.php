<?php
/**
 * Plugin Name: WooCommerce Inventory Locker
 * Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Description: Locks WooCommerce inventory when a product is added to the cart, preventing overselling during high-demand periods.
 * Version: 1.3
 * Author: Steve Kinzey
 * Author URI: https://sk-america.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

define('WC_INVENTORY_LOCKER_LOCK_DURATION', 15 * MINUTE_IN_SECONDS);

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
    } else {
        set_transient("wc_inventory_locks_{$product_id}", $locks, WC_INVENTORY_LOCKER_LOCK_DURATION + MINUTE_IN_SECONDS);
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
    
    foreach ($cart_products as $product_id => $quantity) {
        $locks = wc_inventory_locker_get_product_locks($product_id);
        $locks[$session_id] = [
            'quantity' => $quantity,
            'expires' => time() + WC_INVENTORY_LOCKER_LOCK_DURATION,
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
            'expires' => time() + WC_INVENTORY_LOCKER_LOCK_DURATION,
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

add_action('admin_notices', 'wc_inventory_github_updater_notice');

function wc_inventory_github_updater_notice() {
    if (!is_admin() || !current_user_can('manage_options')) return;

    if (!class_exists('GitHub_Updater\Bootstrap') && !class_exists('github_updater')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>WooCommerce Inventory Locker:</strong> To enable automatic updates, install the <a href="https://github.com/afragen/github-updater" target="_blank">GitHub Updater plugin</a>.</p>';
        echo '</div>';
    }
}

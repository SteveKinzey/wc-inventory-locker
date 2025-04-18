<?php
/**
 * Plugin Name: WooCommerce Inventory Locker
 * Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Description: Locks inventory when checkout is initiated to prevent overselling.
 * Version: 1.0
 * Author: Steve Kinzey
 * Author URI: https://sk-america.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

add_action('woocommerce_checkout_init', 'wc_lock_inventory_on_checkout');

function wc_lock_inventory_on_checkout() {
    if (!WC()->cart) return;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);
        if (!$product || !$product->managing_stock()) continue;

        $qty = $cart_item['quantity'];
        $stock = $product->get_stock_quantity();

        if ($stock < $qty) {
            wc_add_notice(__('Sorry, someone else is already checking out with this item.'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        }

        wc_update_product_stock($product, -$qty);
        WC()->session->set("locked_stock_{$product_id}", $qty);
    }
}

add_action('woocommerce_checkout_order_processed', 'wc_clear_locked_stock');

function wc_clear_locked_stock($order_id) {
    $session = WC()->session;
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $session->set("locked_stock_{$product_id}", null);
    }
}

add_action('woocommerce_checkout_destroyed', 'wc_restore_locked_stock');
add_action('woocommerce_cart_emptied', 'wc_restore_locked_stock');

function wc_restore_locked_stock() {
    $session = WC()->session;
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);
        if (!$product || !$product->managing_stock()) continue;

        $locked_qty = $session->get("locked_stock_{$product_id}");
        if ($locked_qty) {
            wc_update_product_stock($product, $locked_qty);
            $session->set("locked_stock_{$product_id}", null);
        }
    }
}

add_shortcode('wc_lock_status', 'wc_inventory_locker_status_shortcode');

function wc_inventory_locker_status_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => 0,
    ], $atts, 'wc_lock_status');

    $product_id = intval($atts['id']);
    if (!$product_id && is_product()) {
        global $product;
        $product_id = $product ? $product->get_id() : 0;
    }

    if (!$product_id) return '';

    $locked_qty = WC()->session->get("locked_stock_{$product_id}");

    if ($locked_qty && $locked_qty > 0) {
        return '<div class="wc-lock-status locked">🛑 This item is currently locked in another customer’s cart.</div>';
    }

    return '<div class="wc-lock-status available">✅ This item is available for purchase.</div>';
}

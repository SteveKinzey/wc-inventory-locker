<?php
/**
 * Plugin Name: WooCommerce Inventory Locker
 * Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Description: Locks WooCommerce inventory when a product is added to the cart, preventing overselling during high-demand periods.
 * Version: 1.2
 * Author: Steve Kinzey
 * Author URI: https://sk-america.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/SteveKinzey/wc-inventory-locker
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

add_action('woocommerce_add_to_cart', 'wc_inventory_locker_lock_stock', 10, 6);

function wc_inventory_locker_lock_stock($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    $product = wc_get_product($product_id);
    if (!$product || !$product->managing_stock()) return;

    $stock = $product->get_stock_quantity();
    if ($stock < $quantity) {
        wc_add_notice(__('Not enough stock available for this product.'), 'error');
        return;
    }

    $locked_key = "locked_stock_{$product_id}";
    $existing_locked = WC()->session->get($locked_key, 0);

    if ($existing_locked < $quantity) {
        WC()->session->set($locked_key, $quantity);
        do_action('wc_inventory_locker_product_locked', $product_id);
    }
}

add_action('woocommerce_cart_item_removed', 'wc_inventory_locker_restore_stock', 10, 2);
add_action('woocommerce_cart_emptied', 'wc_inventory_locker_restore_all_stock');

function wc_inventory_locker_restore_stock($cart_item_key, $cart) {
    $item = $cart->removed_cart_contents[$cart_item_key];
    $product_id = $item['product_id'];
    $locked_key = "locked_stock_{$product_id}";
    WC()->session->__unset($locked_key);
    do_action('wc_inventory_locker_stock_restored', $product_id);
}

function wc_inventory_locker_restore_all_stock() {
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $locked_key = "locked_stock_{$product_id}";
        WC()->session->__unset($locked_key);
        do_action('wc_inventory_locker_stock_restored', $product_id);
    }
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

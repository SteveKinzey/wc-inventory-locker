=== WooCommerce Inventory Locker ===
Contributors: stevekinzey
Donate link: https://sk-america.com
Tags: woocommerce, inventory, cart, stock management, reserve stock, lock stock
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: trunk
License: MIT
License URI: https://opensource.org/licenses/MIT

Lock WooCommerce stock as soon as a product is added to the cart to prevent overselling. Ideal for limited inventory or high-demand launches.

== Description ==

**WooCommerce Inventory Locker** prevents overselling by reserving product stock the moment it's added to the cart. This plugin is ideal for high-traffic sales, limited edition drops, and scarcity-driven eCommerce.

- Locks stock at cart-add stage
- Frees up stock if cart is abandoned or order is cancelled
- Compatible with simple and variable products
- Does not conflict with native WooCommerce stock management

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wc-inventory-locker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. That's it! Stock will now be locked when added to the cart.

== Frequently Asked Questions ==

= Does this plugin hold inventory forever? =
No. Stock is held temporarily in session and released if the cart is abandoned or the order fails.

= Will it work with product variations? =
Yes, variable products are supported out of the box.

= Is it compatible with other cart/session plugins? =
Yes, it uses WooCommerce's built-in session and cart logic.

== Screenshots ==

1. Product added to cart with stock locked
2. Plugin icon in admin

== Changelog ==

= 1.0.0 =
* Initial release
* Simple and variable product support
* Hook-based logic for lock and restore
* Screenshot and icon assets included

== Upgrade Notice ==

= 1.0.0 =
Initial release for stable WooCommerce versions 7.x and 8.x

== Credits ==

Plugin developed and maintained by [Steve Kinzey](https://github.com/SteveKinzey)

== License ==

This plugin is released under the MIT License. See LICENSE.txt for details.

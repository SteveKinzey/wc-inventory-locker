=== WooCommerce Inventory Locker ===
Contributors: SteveKinzey
Tags: woocommerce, inventory, stock, checkout, one-off products, oversell
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lock the inventory when a customer begins checkout to prevent overselling unique or one-off products.

== Description ==
WooCommerce Inventory Locker is a simple but powerful plugin that prevents overselling limited-stock products. 

When a customer begins the checkout process, the plugin automatically locks the product inventory so that no other customers can purchase the same item until the transaction is complete or abandoned. 

This is ideal for stores that sell one-off, limited edition, or low-inventory products where two users cannot check out simultaneously with the same item.

== Features ==
* Locks inventory when checkout is initialized
* Prevents simultaneous checkouts of the same item
* Automatically restores stock if checkout fails or is abandoned
* Lightweight and reliable — no database bloat

== Installation ==
1. Download the latest version of the plugin as a ZIP file (`wc-inventory-locker.zip`).
2. In your WordPress admin dashboard, go to **Plugins > Add New**.
3. Click **Upload Plugin**, then select the `wc-inventory-locker.zip` file.
4. Click **Install Now**, then **Activate** the plugin.
5. No additional configuration is required.

Optional:
- Use the `[wc_lock_status id=123]` shortcode to display whether a product is currently locked in another customer’s cart.
- You may also use `[wc_lock_status]` on a product page without an ID to detect the current product.

== Frequently Asked Questions ==

= Does this plugin create any front-end content? =
Only if you use the shortcode `[wc_lock_status]` to display lock status messages. Otherwise, it operates invisibly during checkout.

= Can I use this plugin with variable products? =
Currently, it works best with simple products. Variable product compatibility is being explored.

= Does it conflict with caching or security plugins? =
As long as WooCommerce’s cart and checkout pages are excluded from caching (which they should be), there are no known issues.

== Upgrade Notice ==
= 1.0 =
* Initial release of WooCommerce Inventory Locker. 
* Prevents overselling of unique products during simultaneous checkout.

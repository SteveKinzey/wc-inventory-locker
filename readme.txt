=== Inventory Locker ===
Contributors: stevekinzey
Donate link: https://sk-america.com
Tags: woocommerce, surecart, inventory, cart, stock management, reserve stock, lock stock, overselling
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stop overselling! Lock inventory when products are added to cart. Works with WooCommerce and SureCart.

== Description ==

**Inventory Locker** prevents overselling by reserving product stock the moment it's added to the cart. Perfect for flash sales, limited editions, and high-demand product launches.

= The Problem: Overselling Destroys Customer Trust =

Picture this: You're running a flash sale on a limited-edition sneaker with 50 pairs in stock. Within minutes, 200 customers add it to their cart. Without inventory locking, all 200 proceed to checkout—but only 50 orders can be fulfilled.

The result? 150 angry customers, cancellation emails, refund requests, negative reviews, and lasting damage to your brand reputation.

= Real-World Use Cases =

**Concert Merchandise Stores** - Sell limited tour merchandise without overselling. When it's gone, it's gone.

**Sneaker & Streetwear Resellers** - Handle hyped releases where demand exceeds supply by 10x or more.

**Gaming & Collectibles Shops** - Manage pre-orders for rare figures and limited runs fairly.

**Wine Clubs & Allocated Products** - First to cart, first to buy. No more fighting over the last bottles at checkout.

**Event Tickets** - Reserve seats the moment they're selected, preventing double-bookings.

= Key Features =

* **Real-time stock locking** - Reserve inventory instantly when added to cart
* **Configurable lock duration** - Set timeout from 1 minute to 24 hours
* **Automatic release** - Stock returns if cart is abandoned or item removed
* **Multi-platform support** - Works with WooCommerce AND SureCart
* **Variable product support** - Locks at variation level for accurate inventory
* **Admin dashboard** - Monitor active locks and configure settings
* **Password-protected settings** - Prevent accidental changes
* **Session-aware** - Each customer's lock is independent and secure

= How It Works =

1. Customer adds product to cart → Stock locked immediately
2. Other customers see reduced available quantity
3. If customer checks out → Lock released, normal stock reduction occurs
4. If customer abandons cart → Lock expires, stock returns to available

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/inventory-locker`, or install through the WordPress admin.
2. Activate through the 'Plugins' menu.
3. Go to WooCommerce > Inventory Locker (or SureCart > Inventory Locker) to configure.
4. Set your desired lock duration and click "Activate Plugin".

**Important:** The plugin requires initial configuration before it becomes active. This ensures you consciously choose the right lock timeout for your store.

== Frequently Asked Questions ==

= Will this reserve stock indefinitely? =
No. Stock is held for your configured duration (default: 15 minutes) and automatically released if the customer doesn't complete checkout.

= Does it support variable products? =
Yes! Inventory is locked at the variation level, so each size/color/option is tracked independently.

= What happens if a customer abandons their cart? =
The lock expires after your configured duration, and the stock automatically returns to available inventory.

= Can I see how many items are currently locked? =
Yes! The admin settings page shows active locks count and which products have reservations.

= Does it work with SureCart? =
Yes! Version 2.0+ supports both WooCommerce and SureCart with automatic platform detection.

= What lock duration should I use? =
* Flash sales / Hype drops: 5-10 minutes
* Standard e-commerce: 15-30 minutes  
* High-value items: 30-60 minutes
* B2B / Quote-based: 60+ minutes

== Screenshots ==

1. Admin settings page showing lock configuration
2. Active locks monitoring dashboard
3. Customer notification when stock is limited

== Changelog ==

= 2.1 =
* Renamed plugin to "Inventory Locker"
* Updated all file and folder references

= 2.0 =
* Added SureCart support with REST API integration
* Multi-platform architecture with automatic detection
* JavaScript integration for SureCart cart events
* Backwards compatible with existing WooCommerce installations

= 1.6 =
* Improved lock release reliability on checkout
* Added multiple checkout hooks for various payment gateways
* Session ID stored on order for async payment processing

= 1.5 =
* Added admin settings page with configurable lock duration
* Password confirmation required to change settings
* Setup wizard for first-time configuration

= 1.4 =
* Added session expiration handling
* Hourly cleanup cron job for orphaned locks
* Improved lock cleanup on logout and session changes

= 1.3 =
* Fixed lock enforcement with shared transient storage
* Added add-to-cart validation against locked inventory
* Full variation support

= 1.2 =
* Changed license to GPLv2 for WordPress.org compliance
* Improved locking and restoring logic

= 1.1 =
* Initial stable release

== Upgrade Notice ==

= 2.1 =
Plugin renamed to "Inventory Locker". All functionality preserved, backwards compatible.

= 2.0 =
Major update! Now supports SureCart in addition to WooCommerce. Existing WooCommerce users: no action required.

== Credits ==

Plugin developed and maintained by [Steve Kinzey](https://github.com/SteveKinzey)

== License ==

This plugin is released under the GPLv2 License. See LICENSE.txt for details.

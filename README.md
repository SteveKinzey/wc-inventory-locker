# Inventory Locker

![License](https://img.shields.io/badge/license-GPLv2-blue)
![WordPress Tested](https://img.shields.io/badge/WordPress-6.5%2B-blue)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-8.x-blue)
![SureCart Compatible](https://img.shields.io/badge/SureCart-Compatible-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)

**Stop losing sales to overselling.** Inventory Locker reserves stock the moment a customer adds a product to their cart, preventing other shoppers from purchasing the same item. Works with WooCommerce and SureCart.

---

## 🎯 Why You Need This Plugin

### The Problem: Overselling Kills Trust

Imagine this scenario: You're running a flash sale on a limited-edition sneaker. You have 50 pairs in stock. Within minutes, 200 people add the sneaker to their cart. Without inventory locking, all 200 can proceed to checkout—but only 50 orders can actually be fulfilled. The result?

- **150 angry customers** receive cancellation emails
- **Negative reviews** flood your store
- **Chargebacks and refunds** eat into your profits
- **Brand reputation damage** that takes months to repair

### Real-World Examples

**🎸 Concert Merchandise Store**

> "We sold 500 limited tour t-shirts in 3 minutes. Before Inventory Locker, we'd oversell by 40% and spend days issuing refunds. Now, when it's gone, it's gone—and customers trust that their order will ship."

**👟 Sneaker Reseller**

> "On hyped releases, we'd have 10x more carts than inventory. Customers would complete checkout only to get a 'sorry, out of stock' email. Inventory Locker eliminated that completely."

**🎮 Gaming Collectibles Shop**

> "Pre-orders for rare figures would oversell constantly. Now stock is locked for 15 minutes when added to cart. If they don't checkout, it releases back. No more overselling, no more angry collectors."

**🍷 Wine Club**

> "Allocated wines sell out in hours. We had customers fighting over the last bottles at checkout. Inventory Locker made our releases fair—first to cart, first to buy."

---

## ✨ Features

- **Real-time stock locking** — Reserve inventory the instant it's added to cart
- **Configurable lock duration** — Set how long items stay reserved (1-1440 minutes)
- **Automatic release** — Stock returns to availability if cart is abandoned or item removed
- **Multi-platform support** — Works with WooCommerce and SureCart
- **Variable product support** — Locks at the variation level for accurate inventory
- **Admin dashboard** — See active locks, configure settings, monitor status
- **Password-protected settings** — Prevent accidental configuration changes
- **Session-aware** — Each customer's lock is independent and secure

---

## 📦 Installation

1. Download the latest release from the [Releases page](https://github.com/SteveKinzey/inventory-locker/releases)
2. In WordPress, go to **Plugins > Add New > Upload Plugin**
3. Upload the `.zip` file and click **Activate**
4. Go to **WooCommerce > Inventory Locker** (or **SureCart > Inventory Locker**) to configure

### First-Time Setup Required

After activation, you must configure the lock duration before the plugin becomes active. This ensures you consciously choose the right timeout for your store.

---

## 🧠 How It Works

```
Customer A adds "Limited Sneaker" to cart (5 in stock)
    ↓
Inventory Locker reserves 1 unit for Customer A
    ↓
Customer B sees only 4 available
    ↓
If Customer A checks out → Stock decremented normally
If Customer A abandons cart → Lock expires, stock returns to 5
```

### Lock Lifecycle

1. **Add to Cart** — Stock locked immediately
2. **Update Quantity** — Lock adjusted to match cart
3. **Remove from Cart** — Lock released instantly
4. **Complete Checkout** — Lock released, WooCommerce/SureCart handles actual stock reduction
5. **Abandon Cart** — Lock expires after configured duration (default: 15 minutes)
6. **Session Expires** — Locks cleaned up automatically

---

## ⚙️ Configuration

| Setting               | Description                                     | Default    |
| --------------------- | ----------------------------------------------- | ---------- |
| Lock Duration         | How long inventory stays reserved               | 15 minutes |
| Password Confirmation | Required to change settings after initial setup | Enabled    |

**Recommended durations by store type:**

- **Flash sales / Hype drops**: 5-10 minutes
- **Standard e-commerce**: 15-30 minutes
- **High-value items**: 30-60 minutes
- **B2B / Quote-based**: 60+ minutes

---

## 🔌 Developer Hooks

```php
// Fired when stock is locked
do_action('inventory_locker_product_locked', $product_id);

// Fired when stock is released
do_action('inventory_locker_stock_restored', $product_id);
```

### REST API (SureCart)

```
POST /wp-json/inventory-locker/v1/validate-stock
POST /wp-json/inventory-locker/v1/lock-stock
POST /wp-json/inventory-locker/v1/release-stock
```

---

## 🛒 Supported Platforms

| Platform    | Status          | Notes                          |
| ----------- | --------------- | ------------------------------ |
| WooCommerce | ✅ Full Support | Simple & variable products     |
| SureCart    | ✅ Full Support | Products with stock management |

---

## 📄 License

This plugin is licensed under the GNU General Public License v2.0 or later.  
See [LICENSE.txt](LICENSE.txt) for full license text.

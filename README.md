# WooCommerce Inventory Locker

![License](https://img.shields.io/badge/license-GPLv2-blue)
![WordPress Tested](https://img.shields.io/badge/WordPress-6.5%2B-blue)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-8.x-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)
![Made with ❤️](https://img.shields.io/badge/Made%20with-%E2%9D%A4-red)

Lock WooCommerce stock quantities as soon as a product is added to the cart, reducing the risk of overselling limited inventory. Ideal for high-demand or limited-release products.

---

## 🔧 Features

- Reserve stock when added to cart
- Automatically restore stock if cart is abandoned or item is removed
- Works with both simple and variable products
- Compatible with GitHub Updater
- Built for high-conversion WooCommerce shops

---

## 📦 Installation

1. Download the latest release from the [Releases page](https://github.com/SteveKinzey/wc-inventory-locker/releases).
2. In your WordPress dashboard, go to **Plugins > Add New > Upload Plugin**.
3. Upload the `.zip` file.
4. Click **Activate** after upload completes.

---

## 🧠 How It Works

When a product is added to the cart:

- Stock is locked immediately using WooCommerce’s session system
- Locked stock is restored if the product is removed or the cart is emptied
- Prevents checkout if stock is insufficient

---

## 🔌 Hooks

```php
do_action('wc_inventory_locker_product_locked', $product_id);
do_action('wc_inventory_locker_stock_restored', $product_id);
```

---

## 📄 License

This plugin is licensed under the GNU General Public License v2.0 or later.  
See [LICENSE.txt](LICENSE.txt) for full license text.

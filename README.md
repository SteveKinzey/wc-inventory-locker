# WooCommerce Inventory Locker

![License](https://img.shields.io/github/license/SteveKinzey/wc-inventory-locker)
![WordPress Tested](https://img.shields.io/badge/WordPress-6.5%2B-blue)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-8.x-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)
![Made with ❤️](https://img.shields.io/badge/Made%20with-%E2%9D%A4-red)

Lock WooCommerce stock quantities as soon as a product is added to the cart, reducing the risk of overselling limited inventory. Ideal for high-demand or limited-release products.

---

## 🔧 Features

- Reserve stock when added to cart
- Automatically restore stock if cart is abandoned or order is canceled
- Works with both simple and variable products
- Customizable timeout logic (planned feature)

---

## 📦 Installation

1. Download the latest release from the [Releases page](https://github.com/SteveKinzey/wc-inventory-locker/releases).
2. In your WordPress dashboard, go to **Plugins > Add New > Upload Plugin**.
3. Upload the `.zip` file.
4. Click **Activate** after upload completes.

---

## 🧠 How It Works

When a product is added to the cart:

- It checks stock quantity via native WooCommerce logic
- It "reserves" the quantity in a transient or session
- That product quantity is blocked from being sold to others
- If the cart expires or order is canceled, the stock is restored

### 🔌 Available Hooks

```php
do_action( 'wc_inventory_locker_product_locked', $product_id );
do_action( 'wc_inventory_locker_stock_restored', $product_id );
```

Use these for custom logging or analytics integration.

---

## 🧰 Composer Support (Optional)

To install via Composer:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/SteveKinzey/wc-inventory-locker"
  }
],
"require": {
  "stevekinzey/wc-inventory-locker": "dev-main"
}
```

---

## 📸 Screenshots

### Product gets locked when added to cart
![Screenshot 1](plugin-assets/wc-inventory-locker-banner-772x250.jpg)

### Plugin icon
![Plugin Icon](plugin-assets/wc-inventory-locker-icon-256x256.jpg)

---

## 📄 License

This plugin is licensed under the MIT License.  
See [LICENSE.txt](LICENSE.txt) for full license text.

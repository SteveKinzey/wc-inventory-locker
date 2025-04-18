# WooCommerce Inventory Locker

**Prevent overselling one-off WooCommerce products.**

This plugin locks inventory when a user enters the checkout process, reducing the available stock immediately and restoring it only if the order fails or is abandoned. It is ideal for rare, limited-edition, or high-demand one-off products.

## Features
- Locks inventory at checkout start
- Automatically restores inventory if the order is not completed
- Prevents race conditions and double sales
- Works with simple products (expandable for variations)

## Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate it through the WordPress admin dashboard
3. Ensure stock management is enabled in WooCommerce and per product

## GitHub Installation

```bash
git clone https://github.com/SteveKinzey/wc-inventory-locker.git
```

Then manually upload to your /wp-content/plugins/ directory or install using [Git Updater](https://github.com/afragen/git-updater).

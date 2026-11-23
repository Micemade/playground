# playground
Files, blueprints, WXR and other files for Micemade WP plugins or themes

## Personalized Offers for Spacefast

[![Test in Playground](https://img.shields.io/badge/Test%20in-Playground-3858e9?style=for-the-badge&logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Micemade/playground/main/blueprints/blueprint-pofsf.json)

A WooCommerce demo store in [WordPress Playground](https://wordpress.org/playground/) with the Ollie theme, 100 products, 20 customers and 50 orders, and Personalized Offers for Spacefast with ready-made offers.

| File | What it is |
|---|---|
| `blueprints/blueprint-pofsf.json` | The blueprint. It installs WooCommerce, the importer, the plugin and Ollie, imports the products, then runs the setup script. |
| `blueprints/pofsf-demo-setup.php` | The setup script. It finishes the products, creates the customers and orders (dated relative to today), builds customer profiles, captures the brand and creates three single offers, a group offer for the "Best customers" segment and a public offer. |
| `WXR-imports/pofsf-playground.xml` | 100 products (60 variable clothing items with sizes and colours, 40 gear and accessories products) exported from the development store, with their categories. |
| `WXR-imports/pofsf-customers-orders.json` | 20 fictional customers and 50 orders (46 from customers, 4 from guests). These can't go in the WXR: WordPress's export format doesn't carry customer accounts or WooCommerce orders. |
| `media/pofsf-playground/` | The products' main images, resized to 800 px. |
| `releases/personalized-offers-for-spacefast-v1.0.0.zip` | The plugin. |

Every person, email address and street address in the demo is fictional (example.com, example.org, example.net).

### Button for other pages

HTML:

```html
<a href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Micemade/playground/main/blueprints/blueprint-pofsf.json" target="_blank" rel="noopener noreferrer">Test in Playground</a>
```

Markdown:

```markdown
[![Test in Playground](https://img.shields.io/badge/Test%20in-Playground-3858e9?style=for-the-badge&logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Micemade/playground/main/blueprints/blueprint-pofsf.json)
```

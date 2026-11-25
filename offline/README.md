# AmarShop Counter POS

This folder is a self-contained, browser-based point-of-sale app for walk-in
customers. Its PHP pages, styles, scripts, setup/login, reports, and receipts
are all inside this folder; it does not load application code or assets from
another AmarShop folder or from a CDN.

## Database connection

The POS uses its own database configuration in `config.php`. Set `POS_DB_HOST`,
`POS_DB_NAME`, `POS_DB_USER`, and `POS_DB_PASS` there, or provide the matching
`AMARSHOP_POS_DB_*` environment variables. The default local XAMPP settings
target the existing `amarshop` database.

The database must be MySQL/MariaDB and contain the shop's existing `products`
table with product code, name, size, stock, price, and deleted-at fields. This
is intentional: counter bills are kept in the POS's own `offline_pos_*` tables,
while completed counter sales decrement the shared `products.stock` value in
the same database transaction. Online checkout and counter checkout therefore
cannot sell more stock than is available.

On first visit to `index.php`, create the POS owner account. That setup is
one-time and requires a one-time `POS_SETUP_KEY` value set in `config.php` (or
the `AMARSHOP_POS_SETUP_KEY` environment variable) before it can be used; do not
leave the setup key configured after creating the owner. Sign in as owner to
add cashier accounts, review sales, or reset and deactivate cashiers. Cashiers
can create bills and view sales history.

## Features

- Search products by name or code; barcode scanners that type a code and press
  Enter work with the same search.
- Customer name and phone are optional. The cashier can change quantities,
  apply a bill-level cash discount, enter cash received, and see change due.
- Saving a bill rechecks the live product price and stock, locks inventory rows,
  then records the receipt and reduces stock atomically.
- Print a compact receipt from the browser or save it as PDF.
- Review date-filtered sales totals, discounts, bill counts, and individual
  receipts. The dashboard shows today's/month-to-date counter sales and low
  shared stock.
- POS sales do not create online customer orders.

## Run and move the folder

Place `offline` anywhere served by PHP and open
`http://localhost/<path-to-offline>/index.php` or the equivalent hosted URL.
When moving the folder, update its `config.php` or its `AMARSHOP_POS_DB_*`
environment variables to point to a database with the required shared products
table. No `../auth/config.php`, parent application include, Composer package,
or vendor folder is used. Use HTTPS on a public website and restrict access to
trusted store staff.

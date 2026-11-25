# amarshop

CEO and MD accounts can manage SMTP settings from Admin > Settings. The settings
are stored in the `auth` database table; the SMTP password is encrypted before it
is stored and is never displayed back in the admin form. Use the admin settings
page over HTTPS on a live server. Its AES key is generated
at `.amarshop-private/amarshop-smtp.key` one level above the web document root,
so keep that key with secure backups. When moving the site and database to
another server, either restore that private key or re-enter the SMTP password
from Admin > Settings.
Configure the actual SMTP host, port, encryption mode, sender account, password,
and from address provided by Gmail or the hosting provider. PHPMailer is required
through Composer's `vendor/autoload.php`. New customer accounts require email
OTP verification before login; OTPs expire after 10 minutes and can be resent on
the verification page. Set `AMARSHOP_BASE_URL` to the site's absolute base URL
(for example, `https://shop.example.com`) so password reset emails point to the
correct site.

The admin area initializes the `admin_users` and `deliveryman` tables when it is
opened. Existing admin/manager accounts in `users` are copied into `admin_users`
with their existing IDs and credentials; customer accounts remain in `users`.
Admin staff roles are CEO, MD, HR Admin, Manager, Assistant Manager, Data Entry
Operator, and Salesman. Only the CEO, MD, HR Admin, Manager, and Assistant
Manager can create staff, and each can only create roles below their own level.
All staff roles currently share the same admin-panel access. The Assign section
supports order lookup and delivery-bag creation/assignment. Deliveryman records
have separate rider-portal credentials and can be assigned to in-house bags.

The Assign > Bag screen now builds bags from checked, packed order and issue
shipments. Bag contents are stored in `order_quantity`; bags track their zone,
shipment count, rider, and open/assigned/closed state. An order waiting inside a
bag has the internal `inbag` status in the admin timeline; customers continue to
see it as Packed.

Deliverymen sign in separately at `/amarshop/delivery/login.php`. Admins set an
initial rider password or reset it under Admin > Deliverymen; rider passwords
are stored as password hashes. Rider sessions use a separate cookie scoped to
the delivery portal and only expose Delivering and Delivered views. COD delivery
requires recording the exact amount received and issues a printable receipt;
online-paid deliveries can be marked delivered directly. Riders submit recorded
cash for manager review, then submit their bag. CEO, MD, or Manager accounts
confirm/reject collections and close submitted bags. Closing a bag returns any
undelivered order or issue shipment to Packed and makes it eligible for another
bag.

The logistics tables (`delivery_bags`, `order_quantity`, and
`delivery_bag_cash_collections`) are initialized automatically as admin and
delivery pages are opened. Existing deliverymen need a password set by an
administrator before they can use the rider portal.

Admin > Purchases manages suppliers and received stock purchases. Each purchase
records products, quantities, unit costs, supplier invoice, discounts, extra
costs and cash/bank/mobile/cheque payments. Saving a purchase adds the received
quantities to product stock and updates the product's latest purchase cost in a
single transaction. New products require a separate selling price. Purchase
details track supplier balances and further payments; purchase returns are
recorded against original lines, reduce available stock and credit the supplier
for the returned items' proportional share of the purchase discount. Inventory
movements are retained for purchase and return history. Customer orders store
the selected delivery area separately from its shipping charge.

The standalone `offline/` folder contains a browser-based walk-in counter POS.
It uses its own database configuration and billing tables while decrementing
the shared product stock. See [offline/README.md](./offline/README.md) for setup
and deployment details.

Order-request conversation and delivered-order issue tables are initialized
when their customer/admin pages are used. Only admins can create return, missing
item, and replacement issue shipments for delivered orders; each shipment has
its own product lines, deliveryman assignment, tracking details, and status
timeline. Assigning a deliveryman moves a pending issue shipment to packed;
only pending or packed shipments can be cancelled, which restores reserved
outgoing stock. Main shipments use an `A` suffix (for example `003A`); issue
shipments continue with `B`, `C`, and so on. The customer timeline shows only
that an issue was created, the assigned deliveryman's name and phone, and its
final delivered or cancelled state. Customer order requests and replies appear
chronologically in the order timeline. Customers can download an order invoice
from the order details page by choosing “Save as PDF” in the browser's print
dialog. Older unreferenced page variants are kept under `backup/` and are not
site entry points.

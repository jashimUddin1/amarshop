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
Admin staff roles are CEO, MD, Manager, Assistant Manager, and Salesman. These
roles currently share the same admin-panel access. Deliveryman records are
delivery contact records and can be selected when assigning an in-house rider.

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

# Orders and Notifications

## Order identity and success URLs

- Order numbers use the database ID padded to five digits: `ORD00001`, `ORD00002`, etc. Do not add a hyphen, date, or random suffix.
- The storefront success route binds by `order_number`, so success URLs look like `/order-success/ORD00002`, never `/order-success/2`.
- Both cart checkout and direct-order checkout must follow the same numbering rule.

## Order management page

- Orders are paginated at 15 per page; never load the entire order table for list/grid rendering.
- Preserve query strings across pagination, status cards, date filtering, and CSV export.
- The page supports both list and grid views, with the selection stored in `localStorage`.
- List and grid views must expose the same essential data: customer contact, all products, delivery area/address, order note, subtotal, delivery charge, total, status, and date/time.
- Product titles link to the storefront product page when the product still exists. Deleted products remain plain text.
- Order IDs link to the admin order detail page.
- Keep Order and Products columns compact without reducing the normal body font size.
- Delivery area is currently derived from shipping cost: `50 = Dhaka City`, `80 = Outside Dhaka City`, `100 = Outside Dhaka District`.
- In the list table, the Delivery column shows only the address; the derived delivery area remains in the Amount breakdown.
- Highlight only the delivery-area title and truck icon in the Amount breakdown with a compact sky badge; keep the delivery charge value unhighlighted in both list and grid views.
- Status count cards exclude an `All` card and have no hover movement or hover shadow.
- Header controls, status cards, table/grid, and pagination belong inside one card.
- Identify repeat customers when orders share the same phone number or non-null email. Show a compact clickable `Repeat` badge beside the phone number in list, grid, and detail views; keep the order count in its title and never merge or delete their individual orders.
- Customer phone and email lookup columns are indexed. Avoid N+1 queries when calculating repeat-customer counts.

## Notification behavior

- New orders have `viewed_at = null` and are unread. Opening the admin order detail page marks the order viewed.
- Header notification polling runs every 15 seconds only while the browser tab is visible. Returning to a visible tab triggers one refresh.
- Notification payloads are shared-cached for 10 seconds and invalidated whenever an order is saved or deleted.
- The dropdown includes every unread order plus only the five most recent viewed orders.
- Header and desktop/mobile sidebar unread badges update from the same `order-notifications-updated` browser event.
- Do not automatically reload the Orders page when a new order arrives. Show the lightweight `New orders available` banner and let the admin choose `Refresh list`.
- Keep the notification endpoint lightweight. Do not return order items or full addresses in the notification feed.

## Storefront order confirmation

- Keep the complete confirmation content inside one outer card.
- The summary separately shows item count/subtotal, delivery charge, and grand total.

## Verification

- Run `vendor/bin/pint --format agent` after PHP changes. This workspace may not be a Git repository, so do not rely on Pint's `--dirty` option.
- Run `php artisan test --compact tests/Feature/OrderManagementTest.php` for admin order changes.
- Run the relevant filtered `StorefrontTest` tests for checkout, success URL, or confirmation-page changes.
- Run `npm.cmd run build` after Blade/Tailwind/JavaScript UI changes on Windows.

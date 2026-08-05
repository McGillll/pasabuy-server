# Order API (admin)

Admin-side order management. Orders themselves are created and edited by customers via the customer API (`customer-api.md`), which also covers the customer-facing cancel endpoint — this module only covers the seller reviewing and updating status. To list orders scoped to a single schedule, use `GET /api/admin/pasabuy-schedules/{uuid}/orders` (documented in `pasabuy-schedule-api.md`). To list orders across all schedules, use `GET /api/admin/orders` below.

## Endpoints overview

| Method | Endpoint                                     | Description                        |
|--------|-----------------------------------------------|----------------------------------------|
| GET    | `/api/admin/orders`                           | List all orders across all schedules, paginated and filterable |
| PATCH  | `/api/admin/orders/{uuid}/status`             | Move an order to `confirmed` or `cancelled` |
| PATCH  | `/api/admin/orders/{uuid}/delivery-status`    | Toggle delivery status to `delivered` or `not_delivered` |

`Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Body is JSON or form-urlencoded (no file upload involved, so no FormData/method-spoofing needed here).

## Two-phase pricing

Every order item snapshots the schedule product's price at order time as `estimated_price`. When the seller later confirms the real in-store price for that product (`confirm-price` in `pasabuy-schedule-api.md`), the system recalculates every live item immediately, with no customer confirmation step:

- Product not out of stock → the confirmed `actual_price` is auto-applied to every live item for that product (`is_out_of_stock: false`, `subtotal` recomputed).
- Product marked `out_of_stock` → every live item for that product is zeroed out instead (`is_out_of_stock: true`, `actual_price: null`, `subtotal: 0`).

There is no order-level status change from this recalculation — an order's `status` only changes via this endpoint (`confirmed`/`cancelled`) or the customer cancelling. Cancelling by the customer is only allowed while the order is still `pending` and the parent schedule is still open (see `is_cancellable` below).

## Order object

| Field                      | Type          | Notes                                             |
|-----------------------------|---------------|--------------------------------------------------|
| uuid                       | string        | Public identifier                                  |
| order_no                   | string        | Human-readable order reference, format `PSBY-YYYYMMDD-RNDM` (e.g. `PSBY-20260716-4F7A2C`) — unique, auto-generated on creation, never set directly |
| customer_name              | string        | Snapshot of the owning customer's name at order time |
| customer_contact           | string        | Snapshot of the owning customer's contact number at order time |
| status                     | string        | `pending` \| `confirmed` \| `cancelled` |
| total_amount               | string        | Decimal string, server-computed — never trust a client-sent total |
| amount_paid                | number\|null  | Sum of the order's recorded payments — same figure as `amount_paid` on the invoice (`invoice-api.md`). Only populated on `GET /api/admin/orders` (the cross-schedule list) and `GET /api/admin/pasabuy-schedules/{uuid}/orders` (the per-schedule list); `null` on responses from other order endpoints |
| amount_due                 | number\|null  | Outstanding balance — same calculation as the invoice's `balance_due` (markup + overdue compounding interest, minus `amount_paid`). Only populated on `GET /api/admin/orders` and `GET /api/admin/pasabuy-schedules/{uuid}/orders`; `null` elsewhere |
| payment_status             | string        | `unpaid` \| `partially_paid` \| `paid` — server-computed from the order-payments ledger; never set directly. Recalculated whenever a payment is created or deleted: `unpaid` when paid sum is `0`, `paid` when the invoice's `balance_due` is `<= 0` (accounts for markup, handling fee, and interest accrued to date — mode-aware for both `full_total` and `outstanding_balance` interest bases), otherwise `partially_paid` |
| delivery_status            | string        | `not_delivered` \| `delivered` — toggled directly via `PATCH /api/admin/orders/{uuid}/delivery-status`. Independent of `status` and `payment_status` |
| is_editable                | boolean       | `true` only while `status` is `pending` and the parent schedule is still open/before cutoff — matches the customer-side edit gate |
| is_cancellable              | boolean       | `true` while `status` is `pending`, **and** the parent schedule is still open/before cutoff — same gate as `is_editable`, so cancelling disappears once the seller closes the schedule or the order is `confirmed`/`cancelled` |
| pasabuy_schedule            | object\|null  | Present when loaded — see `PasabuyScheduleResource` in `pasabuy-schedule-api.md` |
| items                       | array         | See `OrderItem` object below                        |
| created_at                  | datetime      |                                                     |

Every order now belongs to a customer account (`user_id` internally) — there is no more anonymous/guest ordering. See `customer-api.md` for registration, approval, and the customer-facing order placement/edit/list endpoints.

### OrderItem object

| Field                  | Type          | Notes                                                             |
|-------------------------|---------------|--------------------------------------------------------------------|
| uuid                    | string        |                                                                      |
| product_uuid            | string\|null  | The live `Product`'s uuid (when loaded) — use this, not `product_name`, to match against the schedule's current product list when editing |
| product_name            | string        | Snapshot at order time — won't change if the product is later renamed |
| estimated_price          | string        | Snapshot of the schedule's listing-time price when this item was ordered/last edited |
| actual_price             | string\|null  | `null` until the seller confirms in-store pricing for this product, or if the product is `out_of_stock` |
| is_out_of_stock          | boolean       | `true` if the seller marked this product out of stock in-store — `actual_price` is `null` and `subtotal` is `0` while this is `true` |
| quantity                 | int           | Quantity actually paid for                                          |
| bonus_quantity           | int           | Extra units granted free on top of `quantity` — `> 0` while the schedule product is `b1t1`, `0` otherwise. Not included in `subtotal` |
| subtotal                 | string        | `(actual_price ?? estimated_price) * quantity`, or `0` while `is_out_of_stock` is `true` |

---

## GET /api/admin/orders

Lists orders across all schedules (paginated), for the cross-schedule admin Orders view. `Accept: application/json`, `Authorization: Bearer {token}` (admin ability).

### Query params

| Field           | Type   | Required | Notes                                                       |
|-----------------|--------|----------|--------------------------------------------------------------|
| page            | int    | No       | Default `1`                                                   |
| per_page        | int    | No       | Default `15`                                                   |
| status          | string | No       | `pending` \| `confirmed` \| `cancelled`                        |
| payment_status  | string | No       | `unpaid` \| `partially_paid` \| `paid`                          |
| delivery_status | string | No       | `not_delivered` \| `delivered`                                  |
| schedule_uuid   | string | No       | Restrict to orders belonging to a single `PasabuySchedule` (by its uuid) |
| search          | string | No       | Matches against `customer_name`, `customer_contact`, or `order_no`          |

### Success response — 200

```json
{
  "success": true,
  "message": null,
  "data": {
    "data": [ { "uuid": "...", "customer_name": "Juan Dela Cruz", "status": "pending", "total_amount": "1500.00", "amount_paid": 0, "amount_due": 1650, "payment_status": "unpaid", "delivery_status": "not_delivered", "pasabuy_schedule": { "uuid": "...", "title": "..." }, "...": "..." } ],
    "current_page": 1,
    "last_page": 3,
    "total": 42
  }
}
```

## PATCH /api/admin/orders/{uuid}/status

### Request body

| Field  | Type   | Required | Notes                        |
|--------|--------|----------|----------------------------------|
| status | string | Yes      | Must be `confirmed` or `cancelled` — an order can't be set back to `pending` via this endpoint |

### Example request

```json
PATCH /api/admin/orders/36d53541-.../status
{ "status": "confirmed" }
```

### Success response — 200

```json
{ "success": true, "message": "Order status updated successfully", "data": { "uuid": "...", "customer_name": "Juan Dela Cruz", "customer_contact": "09171234567", "status": "confirmed", "total_amount": "50.00", "created_at": "..." } }
```

### Error responses

- **404** — no order with that uuid: `{ "success": false, "message": "Order not found.", "data": null }`
- **422** — `status` missing or not one of `confirmed`/`cancelled`.

## PATCH /api/admin/orders/{uuid}/delivery-status

### Request body

| Field           | Type   | Required | Notes                                      |
|-----------------|--------|----------|-----------------------------------------------|
| delivery_status | string | Yes      | Must be `not_delivered` or `delivered`         |

### Example request

```json
PATCH /api/admin/orders/36d53541-.../delivery-status
{ "delivery_status": "delivered" }
```

### Success response — 200

```json
{ "success": true, "message": "Order delivery status updated successfully", "data": { "uuid": "...", "status": "confirmed", "payment_status": "paid", "delivery_status": "delivered", "total_amount": "50.00", "created_at": "..." } }
```

### Error responses

- **404** — no order with that uuid: `{ "success": false, "message": "Order not found.", "data": null }`
- **422** — `delivery_status` missing or not one of `not_delivered`/`delivered`.

Setting `delivery_status` has no invoice side effect — invoice emails are only ever queued once, at schedule finish (see `POST .../finish` in `pasabuy-schedule-api.md`).

## Seller order-item editor

Sellers correct a customer's order (add/remove items, change quantities) once the schedule is `closed`, via `PUT /api/admin/pasabuy-schedules/{scheduleUuid}/orders/{orderUuid}/items` — see `pasabuy-schedule-api.md` for the full request/response shape and error cases.

## Order payments (admin)

A payment ledger against an order — supports multiple partial/installment payments per order. Only the admin (seller) records payments; there is no customer-submitted payment workflow. `Order.status` (`pending`/`confirmed`/`cancelled`) is unaffected by payments — this is a separate ledger, not an order-lifecycle state. `Order.payment_status` (`unpaid`/`partially_paid`/`paid`) **is** derived from this ledger and is automatically recalculated after every payment create/delete — it can never be set directly via any endpoint.

| Method | Endpoint                                             | Description                    |
|--------|-------------------------------------------------------|---------------------------------|
| GET    | `/api/admin/orders/{orderUuid}/payments`             | List payments for an order, newest `paid_at` first |
| POST   | `/api/admin/orders/{orderUuid}/payments`             | Record a payment against an order |
| DELETE | `/api/admin/orders/{orderUuid}/payments/{uuid}`      | Void/remove a mis-recorded payment |

`Accept: application/json`, `Authorization: Bearer {token}` (admin ability).

### OrderPayment object

| Field             | Type          | Notes                                            |
|-------------------|---------------|---------------------------------------------------|
| uuid              | string        | Public identifier                                  |
| amount            | string        | Decimal string                                     |
| method            | string        | `cash` \| `gcash` \| `bank_transfer` \| `maya` \| `other` |
| reference_number  | string\|null  | e.g. GCash/bank transfer reference                 |
| notes             | string\|null  |                                                     |
| paid_at           | datetime      | When the payment was made (client-supplied, not `created_at`) |
| recorded_by       | object\|null  | `{ uuid, full_name }` of the admin who recorded it, when loaded |
| created_at        | datetime      |                                                     |

### POST /api/admin/orders/{orderUuid}/payments — request body

| Field             | Type   | Required | Notes                                       |
|-------------------|--------|----------|-----------------------------------------------|
| amount            | number | Yes      | Must be > 0                                    |
| method            | string | Yes      | One of `cash`, `gcash`, `bank_transfer`, `maya`, `other` |
| reference_number  | string | No       |                                                 |
| notes             | string | No       |                                                 |
| paid_at           | date   | Yes      |                                                 |

No validation currently caps total payments at the order's `total_amount` — overpayment (tips, rounding, later price adjustments) is allowed by design.

`POST` is only allowed once the order's pasabuy schedule status is `finished` — returns **422** `"Payments can only be recorded once the pasabuy schedule is finished."` otherwise. `DELETE` (voiding a payment) has no such restriction.

Deleting an order (cascade) removes all its payments.

**Side effect (create):** when recording a payment transitions `Order.payment_status` from something other than `paid` to `paid` (i.e. the ledger sum first reaches `total_amount`), a `PaymentConfirmationMail` (queued, `database` connection) is dispatched to the order's customer email (`order.user.email`), built from `InvoiceService::buildForOrder()`. It does **not** fire again on subsequent payments while already `paid` (e.g. an overpayment), and is skipped and logged (never fails the request) if the order has no customer email on file or the invoice fails to build.

**Side effect (delete):** voiding a payment never sends any customer-facing email — reversing a payment is an admin correction, and an automatic "you now owe money again" email would be confusing (often the admin is about to re-enter the corrected payment anyway). If deleting the payment drops `Order.payment_status` out of `paid`, a warning is logged (`order_uuid`, `voided_payment_uuid`, `voided_amount`, `new_payment_status`) so it's visible in logs/admin activity without alarming the customer.

## Error status summary

| Status | Meaning               | Applies to    |
|--------|------------------------|-----------------|
| 401    | Missing/invalid token  | index, updateStatus, updateDeliveryStatus, order payments     |
| 404    | Order not found        | updateStatus, updateDeliveryStatus, order payments     |
| 404    | Payment not found      | payments destroy |
| 422    | Validation failure     | updateStatus, updateDeliveryStatus, payments store |

# Order API (admin)

Admin-side order management. Orders themselves are created and edited by customers via the customer API (`customer-api.md`), which also covers the customer-facing cancel endpoint — this module only covers the seller reviewing and updating status. To list orders for a schedule, use `GET /api/admin/pasabuy-schedules/{uuid}/orders` (documented in `pasabuy-schedule-api.md`).

## Endpoints overview

| Method | Endpoint                                     | Description                        |
|--------|-----------------------------------------------|----------------------------------------|
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
| customer_name              | string        | Snapshot of the owning customer's name at order time |
| customer_contact           | string        | Snapshot of the owning customer's contact number at order time |
| status                     | string        | `pending` \| `confirmed` \| `cancelled` |
| total_amount               | string        | Decimal string, server-computed — never trust a client-sent total |
| payment_status             | string        | `unpaid` \| `partially_paid` \| `paid` — server-computed from the order-payments ledger; never set directly. Recalculated whenever a payment is created or deleted: `unpaid` when paid sum is `0`, `paid` when paid sum `>= total_amount`, otherwise `partially_paid` |
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
| is_bonus                 | boolean       | `true` for a B1T1 bonus line — always priced `0` |
| is_out_of_stock          | boolean       | `true` if the seller marked this product out of stock in-store — `actual_price` is `null` and `subtotal` is `0` while this is `true` |
| quantity                 | int           |                                                                      |
| subtotal                 | string        | `(actual_price ?? estimated_price) * quantity`, or `0` while `is_out_of_stock` is `true` |

---

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

Deleting an order (cascade) removes all its payments.

## Error status summary

| Status | Meaning               | Applies to    |
|--------|------------------------|-----------------|
| 401    | Missing/invalid token  | updateStatus, updateDeliveryStatus, order payments     |
| 404    | Order not found        | updateStatus, updateDeliveryStatus, order payments     |
| 404    | Payment not found      | payments destroy |
| 422    | Validation failure     | updateStatus, updateDeliveryStatus, payments store |

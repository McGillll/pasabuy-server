# Order API (admin)

Admin-side order management. Orders themselves are created and edited by customers via the customer API (`customer-api.md`), which also covers the customer-facing cancel and price-confirmation-resolution endpoints — this module only covers the seller reviewing and updating status. To list orders for a schedule, use `GET /api/admin/pasabuy-schedules/{uuid}/orders` (documented in `pasabuy-schedule-api.md`).

## Endpoints overview

| Method | Endpoint                          | Description                        |
|--------|-------------------------------------|----------------------------------------|
| PATCH  | `/api/admin/orders/{uuid}/status`   | Move an order to `confirmed` or `cancelled` |

`Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Body is JSON or form-urlencoded (no file upload involved, so no FormData/method-spoofing needed here).

## Two-phase pricing & the `needs_confirmation` status

Every order item snapshots the schedule product's price at order time as `estimated_price`. When the seller later confirms the real in-store price for that product (`confirm-price` in `pasabuy-schedule-api.md`), the system recalculates every live item:

- No price increase (or the customer's `auto_buy_if_under_max` preference covers it) → auto-applied, item stays resolved, order status untouched.
- Price increase with no covering preference, or the item is out of stock → item flagged `confirmation_status: 'pending'`, **order status flips to `needs_confirmation`**.

While an order is `needs_confirmation`, **the admin cannot force it to `confirmed`** via this endpoint (see error responses below) — the customer must resolve every pending item first (accept or remove — see `customer-api.md`), which reverts the order back to `pending` once nothing is left pending. Cancelling is still allowed at any time regardless of status.

## Order object

| Field                      | Type          | Notes                                             |
|-----------------------------|---------------|--------------------------------------------------|
| uuid                       | string        | Public identifier                                  |
| customer_name              | string        | Snapshot of the owning customer's name at order time |
| customer_contact           | string        | Snapshot of the owning customer's contact number at order time |
| status                     | string        | `pending` \| `needs_confirmation` \| `confirmed` \| `cancelled` |
| total_amount               | string        | Decimal string, server-computed — never trust a client-sent total. Items flagged `pending` keep contributing their *last-agreed* subtotal until resolved, so this won't jump until the customer acts |
| is_editable                | boolean       | `true` only while `status` is `pending` and the parent schedule is still open/before cutoff — matches the customer-side edit gate |
| is_cancellable              | boolean       | `true` while `status` is `pending` or `needs_confirmation` — customers can cancel in either state |
| needs_confirmation_count    | int           | Count of items with `confirmation_status: 'pending'` on this order — only present when `items` is loaded |
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
| actual_price             | string\|null  | `null` until the seller confirms in-store pricing for this product. While `confirmation_status` is `pending`, this holds the *proposed* new price for display — it is not yet reflected in `subtotal` |
| is_bonus                 | boolean       | `true` for a B1T1 bonus line — always priced `0`, never needs confirmation |
| confirmation_status      | string        | `none` \| `pending` \| `accepted` \| `removed` — `pending` means the seller's confirmed price differs from what the customer was charged and they must act (see `customer-api.md`) |
| max_price                | string\|null  | Customer's optional per-item ceiling, set at order time |
| auto_buy_if_under_max    | boolean       | If true and the seller's new price is `<= max_price`, the price change is auto-applied without asking the customer |
| quantity                 | int           |                                                                      |
| subtotal                 | string        | `(actual_price ?? estimated_price) * quantity` — frozen at the last-agreed amount while `confirmation_status` is `pending` |

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
- **422** — `status` missing or not one of `confirmed`/`cancelled`, **or** trying to set `confirmed` while the order's current status is `needs_confirmation`: `{ "success": false, "message": "This order still has items awaiting the customer's confirmation.", "data": null }`

Note: cancelling an order frees up its quantity against the schedule's `max_quantity` stock cap for future orders — `pending`, `needs_confirmation`, and `confirmed` orders all count against stock (see `public-pasabuy-api.md`).

## Error status summary

| Status | Meaning               | Applies to    |
|--------|------------------------|-----------------|
| 401    | Missing/invalid token  | updateStatus     |
| 404    | Order not found        | updateStatus     |
| 422    | Validation failure, or attempting to confirm a `needs_confirmation` order | updateStatus |

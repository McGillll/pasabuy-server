# Order API (admin)

Admin-side order management. Orders themselves are created by customers via the public API (`public-pasabuy-api.md`) — this module only covers the seller reviewing and updating them. To list orders for a schedule, use `GET /api/admin/pasabuy-schedules/{uuid}/orders` (documented in `pasabuy-schedule-api.md`).

## Endpoints overview

| Method | Endpoint                          | Description                        |
|--------|-------------------------------------|----------------------------------------|
| PATCH  | `/api/admin/orders/{uuid}/status`   | Move an order to `confirmed` or `cancelled` |

`Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Body is JSON or form-urlencoded (no file upload involved, so no FormData/method-spoofing needed here).

## Order object

| Field            | Type     | Notes                                             |
|------------------|----------|--------------------------------------------------|
| uuid             | string   | Public identifier                                  |
| customer_name    | string   | As entered by the customer, no account behind it   |
| customer_contact | string   | Phone/Messenger/etc., free text                    |
| status           | string   | `pending` \| `confirmed` \| `cancelled`             |
| total_amount     | string   | Decimal string, server-computed — never trust a client-sent total |
| items            | array    | See `OrderItem` object below                        |
| created_at       | datetime |                                                     |

### OrderItem object

| Field         | Type   | Notes                                                             |
|---------------|--------|--------------------------------------------------------------------|
| uuid          | string |                                                                      |
| product_name  | string | Snapshot at order time — won't change if the product is later renamed |
| product_price | string | Snapshot of the schedule's per-item price at order time              |
| quantity      | int    |                                                                      |
| subtotal      | string | `product_price * quantity`                                          |

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

Note: cancelling an order frees up its quantity against the schedule's `max_quantity` stock cap for future orders — only `pending`/`confirmed` orders count against stock (see `public-pasabuy-api.md`).

## Error status summary

| Status | Meaning               | Applies to    |
|--------|------------------------|-----------------|
| 401    | Missing/invalid token  | updateStatus     |
| 404    | Order not found        | updateStatus     |
| 422    | Validation failure     | updateStatus     |

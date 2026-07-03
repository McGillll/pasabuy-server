# Public Pasabuy API

Customer-facing endpoints behind a pasabuy schedule's shareable link. **No authentication** — anyone with the link can view and order. Customers identify themselves per-order with just a name and a contact (phone/Messenger/etc.) — there is no account/login on this side.

## Endpoints overview

| Method | Endpoint                                          | Description                              |
|--------|------------------------------------------------------|--------------------------------------------|
| GET    | `/api/public/pasabuy-schedules/{uuid}`                | View a schedule and its available products |
| POST   | `/api/public/pasabuy-schedules/{uuid}/orders`         | Submit an order                            |

`Accept: application/json` on both. The `POST` body is `multipart/form-data` (matches the frontend's FormData-only convention; no file uploads involved but the shape is the same).

## Response envelope

Standard `{success, message, data}` (see `auth-api.md`).

---

## GET /api/public/pasabuy-schedules/{uuid}

Returns the schedule regardless of its `status` — a `draft` or `closed` link still resolves so the frontend can show a friendly "not open" message instead of a dead link. **Check `data.is_open` before letting the customer add items to cart.**

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "uuid": "e9400549-...",
    "title": "Grocery Run July",
    "description": null,
    "cutoff_at": null,
    "status": "open",
    "is_open": true,
    "products": [
      {
        "uuid": "a5a95025-...",
        "price": "25.00",
        "max_quantity": 2,
        "product": { "uuid": "abe9e0a5-...", "name": "Instant Noodles", "description": "Spicy pack", "image_url": null, "is_active": true }
      }
    ],
    "created_at": "..."
  }
}
```

Use each product entry's `product.uuid` (not the schedule-product `uuid`) as the identifier when submitting an order — see below.

### Error responses

- **404** — no schedule with that uuid: `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

---

## POST /api/public/pasabuy-schedules/{uuid}/orders

Creates the order and its items in one call. The server computes `subtotal`/`total_amount` itself from each product's current schedule price — **never send a total from the client, it's ignored**. Also re-validates `is_open` and remaining stock server-side even though the frontend should already be gating on them, since this is the actual write path.

Rate-limited to 10 requests/minute per IP (`throttle:public-order`). Exceeding it returns a standard Laravel `429` response.

### Request body (multipart/form-data)

| Field                    | Type   | Required | Notes                                      |
|--------------------------|--------|----------|-----------------------------------------------|
| customer_name            | string | Yes      |                                                 |
| customer_contact         | string | Yes      | Phone/Messenger/etc., free text                |
| items                    | array  | Yes      | At least 1 entry                                |
| items[].product_uuid     | string | Yes      | The `product.uuid` from the `GET`, not the schedule-product uuid |
| items[].quantity         | int    | Yes      | >= 1                                            |

### Example request

```
customer_name=Juan Dela Cruz
customer_contact=09171234567
items[0][product_uuid]=abe9e0a5-...
items[0][quantity]=2
```

### Success response — 201

```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "uuid": "36d53541-...",
    "customer_name": "Juan Dela Cruz",
    "customer_contact": "09171234567",
    "status": "pending",
    "total_amount": "50.00",
    "items": [ { "uuid": "...", "product_name": "Instant Noodles", "product_price": "25.00", "quantity": 2, "subtotal": "50.00" } ],
    "created_at": "..."
  }
}
```

### Error responses

All business-rule failures return **422** with a human-readable `message` (not the standard Laravel validation shape):

- `"This pasabuy is closed for orders."` — schedule `status` isn't `open`, or `cutoff_at` has passed.
- `"Product {uuid} is not available in this pasabuy."` — the product isn't attached to this schedule.
- `"Only {n} of \"{product name}\" remaining."` — requested quantity would exceed `max_quantity` (accounts for every `pending`/`confirmed` order already placed against this schedule; `cancelled` orders don't count against stock).

Standard field validation (missing `customer_name`, empty `items`, etc.) also returns **422** with the usual Laravel `{message, errors}` shape.

- **404** — no schedule with that uuid, or a `product_uuid` used in `items` doesn't resolve to a real product.

## Error status summary

| Status | Meaning                                  | Applies to    |
|--------|---------------------------------------------|-----------------|
| 404    | Schedule not found                          | show, storeOrder |
| 422    | Validation failure or business-rule rejection | storeOrder       |
| 429    | Rate limited (10/minute per IP)             | storeOrder       |

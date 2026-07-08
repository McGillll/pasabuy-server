# Pasabuy Schedule API

A pasabuy schedule is the seller's shareable "batch" — a subset of the global product catalog, each with a price and optional stock cap for that specific run, plus a public link customers use to order. All endpoints here are admin-only; the customer-facing read/order endpoints live in `public-pasabuy-api.md`.

**Two-phase pricing.** A schedule-product's price starts as an `estimated_price`, set by the seller at listing time before she's physically been to the store — it is never guaranteed. Once she's in-store, she calls the `confirm-price` endpoint to set the real `actual_price` (and flag any promo like B1T1, or mark the item out of stock). Confirming a price triggers server-side recalculation of every live order referencing that product — see `order-api.md` for what happens to affected orders/items.

## Endpoints overview

| Method | Endpoint                                              | Description                                    |
|--------|--------------------------------------------------------|--------------------------------------------------|
| GET    | `/api/admin/pasabuy-schedules`                          | List schedules (paginated)                      |
| POST   | `/api/admin/pasabuy-schedules`                          | Create a schedule                                |
| GET    | `/api/admin/pasabuy-schedules/{uuid}`                   | Get one schedule with its attached products      |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}`                   | Update schedule fields/status                    |
| DELETE | `/api/admin/pasabuy-schedules/{uuid}`                   | Delete a schedule                                |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}/products`          | Replace the attached product set                 |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/orders`            | List orders placed against this schedule         |
| PATCH  | `/api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price` | Seller sets the in-store actual price + promo status for one schedule product |

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Bodies are JSON (no file uploads on this module, so `PUT` is sent as a real `PUT`).

## Schedule object

| Field       | Type          | Notes                                                                 |
|-------------|---------------|--------------------------------------------------------------------------|
| uuid        | string        | Public identifier — this is what goes in the customer-facing link, e.g. `https://{frontend}/pasabuy/{uuid}` |
| title       | string        |                                                                            |
| description | string\|null  |                                                                            |
| cutoff_at   | datetime\|null| Optional deadline; ordering is blocked once passed even if `status` is still `open` |
| status      | string        | `draft` \| `open` \| `closed`. Only `open` (and before `cutoff_at`) accepts customer orders |
| is_open     | boolean       | Computed: `status === 'open' && (!cutoff_at || cutoff_at is in the future)` — use this to gate the UI |
| products    | array         | Only present when the schedule was loaded with products (`show`, `products` sync, and the public `GET`) — see schedule-product object below |
| created_at  | datetime      |                                                                            |

### Schedule-product object (an entry in `products`)

| Field              | Type         | Notes                                             |
|--------------------|--------------|------------------------------------------------------|
| uuid               | string       | Pivot row's own identifier                            |
| estimated_price    | string       | Decimal string, e.g. `"25.00"` — the seller's listing-time guess, independent of any other schedule |
| actual_price        | string\|null | Set once the seller confirms in-store; `null` until confirmed, or if `promo_status` is `out_of_stock` |
| promo_status       | string       | `none` \| `b1t1` \| `out_of_stock` — only meaningful once `price_confirmed_at` is set |
| price_confirmed_at | datetime\|null | `null` = still just an estimate; once set, `actual_price`/`promo_status` reflect the confirmed in-store truth |
| max_quantity       | int\|null    | `null` = unlimited                                     |
| product            | object       | The `Product` object (see `product-api.md`) — `uuid`, `name`, `description`, `image_url`, `is_active` |

---

## GET /api/admin/pasabuy-schedules

### Query params

| Field    | Type   | Required | Notes                        |
|----------|--------|----------|---------------------------------|
| search   | string | No       | Filter by title (partial match) |
| per_page | int    | No       | Default 15                      |

Returns a standard Laravel paginator (see `product-api.md` for shape); items do **not** include `products` (call `show` for that).

---

## POST /api/admin/pasabuy-schedules

### Request body

| Field       | Type     | Required | Notes                              |
|-------------|----------|----------|----------------------------------------|
| title       | string   | Yes      |                                          |
| description | string   | No       |                                          |
| cutoff_at   | datetime | No       | Any format `strtotime()`/Carbon accepts |
| status      | string   | No       | `draft`\|`open`\|`closed`, default `draft` |

### Success response — 201

```json
{ "success": true, "message": "Pasabuy schedule created successfully", "data": { "uuid": "...", "title": "Grocery Run July", "description": null, "cutoff_at": null, "status": "open", "is_open": true, "created_at": "..." } }
```

### Error responses

- **422** — validation failure (missing `title`, invalid `status`/`cutoff_at`).

---

## GET /api/admin/pasabuy-schedules/{uuid}

Includes `products`. Use this to render the schedule-builder page.

### Error responses

- **404** — `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

---

## PUT /api/admin/pasabuy-schedules/{uuid}

Same body as `POST`; all fields required except `description`/`cutoff_at`/`status` as noted above (this is a full replace, not a partial patch — always send `title` too).

### Error responses

Same as `POST`, plus **404**.

---

## DELETE /api/admin/pasabuy-schedules/{uuid}

Hard-deletes the schedule and cascades to its attached products and its orders (`ON DELETE CASCADE`). There is no confirmation step server-side — confirm in the UI before calling this.

### Error responses

- **404**

---

## PUT /api/admin/pasabuy-schedules/{uuid}/products

Replaces the full attached-product set in one call — send every product you want attached, not just changes. Rows for products left out of the request are removed **unless** a customer has already ordered that product on this schedule, in which case the row is flagged `is_disabled` instead of deleted (so historical orders stay resolvable) and stops appearing in this endpoint's `products` array and in the public schedule view. Re-including that same `product_uuid` in a later `PUT` call reactivates (un-disables) the existing row rather than creating a duplicate.

### Request body

| Field                       | Type   | Required | Notes                          |
|-----------------------------|--------|----------|------------------------------------|
| products                    | array  | No       | Omit entirely (or send empty) to detach all — as multipart/form-data, an empty array can't be represented by a literal key, so just send no `products[...]` entries |
| products[].product_uuid     | string | Yes      | Must be an existing product's uuid |
| products[].estimated_price  | number | Yes      | >= 0 — this only ever sets the *estimate*; use `confirm-price` below to set the in-store actual price |
| products[].max_quantity     | int    | No       | >= 1, omit/null for unlimited       |

### Example request (form-encoded array)

```
_method=PUT
products[0][product_uuid]=abe9e0a5-...
products[0][estimated_price]=25
products[0][max_quantity]=2
```

### Success response — 200

Returns the full `PasabuyScheduleResource` with the updated `products` array.

### Error responses

- **404** — schedule not found, or one of the `product_uuid`s doesn't exist (`"Product {uuid} not found."`).
- **422** — a `price` is missing/negative (`"A valid price is required for product {name}."`), or basic field validation (`products.*.quantity` etc.).

---

## PATCH /api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price

Called by the seller once she's physically in the store standing in front of the item. Not gated by schedule status (works while `draft`/`open`/`closed`) — she can confirm truth for individual items while still shopping. Setting this triggers server-side recalculation of every **live** (`pending`/`needs_confirmation`) order that references this product on this schedule:

- **Price same or lower than the order's snapshotted `estimated_price`** → auto-applied; the order item's `actual_price`/`subtotal` update immediately, no customer action needed.
- **Price higher, and the item has `auto_buy_if_under_max=true` with the new price `<= max_price`** → also auto-applied (evaluated synchronously here, not on a timer).
- **Price higher, no matching auto-buy preference** → the item is flagged `confirmation_status: 'pending'` (its `actual_price` is set to the *proposed* price for display, but `subtotal`/`quantity` stay at the last-agreed values) and the order's `status` flips to `needs_confirmation`. See `order-api.md` for how the customer resolves this.
- **`promo_status: 'out_of_stock'`** → every live item for this product is flagged `pending` regardless of price (no valid price to auto-apply); `actual_price` stays `null`.
- **`promo_status: 'b1t1'`** → independent of the price branch above, one bonus `OrderItem` per live order is created/updated 1:1 with the paid item's quantity (`is_bonus: true`, price `0`, no confirmation needed) — removed automatically if a later confirm moves the promo off `b1t1`.

### Request body

| Field        | Type   | Required                          | Notes                                        |
|--------------|--------|------------------------------------|---------------------------------------------|
| actual_price | number | Yes, unless `promo_status=out_of_stock` | >= 0                                    |
| promo_status | string | Yes                                 | `none` \| `b1t1` \| `out_of_stock`           |

### Example request

```
_method=PATCH
actual_price=130
promo_status=none
```

### Success response — 200

```json
{
  "success": true,
  "message": "Price confirmed",
  "data": {
    "schedule_product": { "uuid": "...", "estimated_price": "100.00", "actual_price": "130.00", "promo_status": "none", "price_confirmed_at": "...", "max_quantity": 10, "product": { ... } },
    "summary": { "auto_applied": 1, "needs_confirmation": 0, "bonus_applied": 0 }
  }
}
```

`summary` counts, across every live order touched, how many items were auto-applied, how many now need the customer's confirmation, and how many got a B1T1 bonus line — use it to show the seller immediate feedback.

### Error responses

- **404** — schedule not found, or `scheduleProductUuid` doesn't belong to it: `{ "success": false, "message": "Schedule product not found.", "data": null }`.
- **422** — `actual_price` missing/negative when `promo_status` isn't `out_of_stock`, or `promo_status` not one of the three allowed values.

---

## GET /api/admin/pasabuy-schedules/{uuid}/orders

Returns every order placed against this schedule, newest first, each with its `items` (see `order-api.md` for the `Order`/`OrderItem` shape).

### Error responses

- **404** — schedule not found.

## Error status summary

| Status | Meaning              | Applies to                        |
|--------|-----------------------|-------------------------------------|
| 401    | Missing/invalid token | all endpoints                        |
| 404    | Not found             | show, update, destroy, products, orders, confirm-price |
| 422    | Validation failure    | store, update, products, confirm-price |

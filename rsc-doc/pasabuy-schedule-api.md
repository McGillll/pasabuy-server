# Pasabuy Schedule API

A pasabuy schedule is the seller's shareable "batch" — a subset of the global product catalog (pricing always comes from the product's own `default_price`, see `product-api.md`), plus a public link customers use to order. All endpoints here are admin-only and scoped to the schedule's owner (the admin who created it — see the 403 note below); the customer-facing read/order endpoints live in `public-pasabuy-api.md`.

**Two-phase pricing.** A schedule-product's price is the product's `default_price` until the seller is physically in-store, at which point she calls `confirm-price` to set the real `actual_price` (and flag any promo like B1T1, or mark the item out of stock). Confirming a price triggers server-side recalculation of every live order referencing that product — see `order-api.md` for what happens to affected orders/items.

**Closing is a dedicated action**, not a generic status update — use `close`/`reopen` rather than setting `status` via `PUT`. Closing only blocks customer-side ordering/editing (see `public-pasabuy-api.md`/`order-api.md`); it does **not** lock the seller out of managing products, prices, or (once closed) editing customer order items — see the seller order-items endpoint below.

## Endpoints overview

| Method | Endpoint                                              | Description                                    |
|--------|--------------------------------------------------------|--------------------------------------------------|
| GET    | `/api/admin/pasabuy-schedules`                          | List schedules (paginated)                      |
| POST   | `/api/admin/pasabuy-schedules`                          | Create a schedule                                |
| GET    | `/api/admin/pasabuy-schedules/{uuid}`                   | Get one schedule with its attached products      |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}`                   | Update schedule fields (title/description/cutoff; status only via a valid transition) |
| DELETE | `/api/admin/pasabuy-schedules/{uuid}`                   | Delete a schedule                                |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/close`             | Close the schedule (`open` → `closed`)           |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/reopen`            | Reopen the schedule (`closed` → `open`)          |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}/products`          | Replace the attached product set                 |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/orders`            | List orders placed against this schedule         |
| PATCH  | `/api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price` | Seller sets the in-store actual price + promo status for one schedule product |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/summary`           | Aggregated per-product quantity checklist across all orders |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items` | Seller edits a customer's order items (closed schedules only) |

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Bodies are JSON (no file uploads on this module, so `PUT`/`POST` action endpoints are sent as real verbs, no `_method` spoofing needed).

**Ownership.** Every endpoint here (except `index`) requires the acting admin to be the schedule's creator. Acting on someone else's schedule returns **403**: `{ "success": false, "message": "You do not own this pasabuy schedule.", "data": null }`.

## Schedule object

| Field       | Type          | Notes                                                                 |
|-------------|---------------|--------------------------------------------------------------------------|
| uuid        | string        | Public identifier — this is what goes in the customer-facing link, e.g. `https://{frontend}/pasabuy/{uuid}` |
| title       | string        |                                                                            |
| description | string\|null  |                                                                            |
| cutoff_at   | datetime\|null| Optional deadline; ordering is blocked once passed even if `status` is still `open` |
| status      | string        | `draft` \| `open` \| `closed`. Only `open` (and before `cutoff_at`) accepts customer orders. Valid transitions: `draft`→`open`, `open`→`closed`, `closed`→`open` |
| is_open     | boolean       | Computed: `status === 'open' && (!cutoff_at || cutoff_at is in the future)` — use this to gate the UI |
| products    | array         | Only present when the schedule was loaded with products (`show`, `products` sync, and the public `GET`) — see schedule-product object below |
| created_at  | datetime      |                                                                            |

### Schedule-product object (an entry in `products`)

| Field              | Type         | Notes                                             |
|--------------------|--------------|--------------------------------------------------------|
| uuid               | string       | Pivot row's own identifier                            |
| actual_price        | string\|null | Set once the seller confirms in-store; `null` until confirmed, or if `promo_status` is `out_of_stock` |
| promo_status       | string       | `none` \| `b1t1` \| `out_of_stock` — only meaningful once `price_confirmed_at` is set |
| price_confirmed_at | datetime\|null | `null` = still just the product's `default_price`; once set, `actual_price`/`promo_status` reflect the confirmed in-store truth |
| product            | object       | The `Product` object (see `product-api.md`) — includes `default_price`, the price to display before confirmation |

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

Same body as `POST`; this is a full replace, not a partial patch — always send `title` too. If `status` is included and differs from the current value, it's validated against the same transition table as `close`/`reopen` — prefer those dedicated endpoints for status changes; use this one for title/description/cutoff.

### Error responses

Same as `POST`, plus **403** (not owner), **404**, and **422** if `status` names an invalid transition (`"Cannot change schedule status from \"{from}\" to \"{to}\"."`).

---

## DELETE /api/admin/pasabuy-schedules/{uuid}

Hard-deletes the schedule and cascades to its attached products and its orders (`ON DELETE CASCADE`). There is no confirmation step server-side — confirm in the UI before calling this.

### Error responses

- **403** (not owner), **404**

---

## POST /api/admin/pasabuy-schedules/{uuid}/close

Transitions `open` → `closed`. No request body. Blocks customer-side ordering/editing (already enforced elsewhere); does not affect the seller's own ability to manage products/prices, or (now that it's closed) edit customer orders via the endpoint below.

### Success response — 200

```json
{ "success": true, "message": "Pasabuy schedule closed", "data": { "uuid": "...", "status": "closed", ... } }
```

### Error responses

- **403** (not owner), **404**, **422** — invalid transition (e.g. already closed, or still `draft`).

---

## POST /api/admin/pasabuy-schedules/{uuid}/reopen

Transitions `closed` → `open`. Same shape/errors as `close`.

---

## PUT /api/admin/pasabuy-schedules/{uuid}/products

Replaces the full attached-product set in one call — send every product you want attached, not just changes. Rows for products left out of the request are removed **unless** a customer has already ordered that product on this schedule, in which case the row is flagged `is_disabled` instead of deleted (so historical orders stay resolvable) and stops appearing in this endpoint's `products` array and in the public schedule view. Re-including that same `product_uuid` in a later `PUT` call reactivates (un-disables) the existing row rather than creating a duplicate. Pricing is never sent here — it always comes from the product's own `default_price`.

### Request body

| Field                       | Type   | Required | Notes                          |
|-----------------------------|--------|----------|------------------------------------|
| products                    | array  | No       | Omit entirely (or send empty) to detach all — as multipart/form-data, an empty array can't be represented by a literal key, so just send no `products[...]` entries |
| products[].product_uuid     | string | Yes      | Must be an existing product's uuid |

### Example request (form-encoded array)

```
_method=PUT
products[0][product_uuid]=abe9e0a5-...
```

### Success response — 200

Returns the full `PasabuyScheduleResource` with the updated `products` array.

### Error responses

- **403** (not owner)
- **404** — schedule not found, or one of the `product_uuid`s doesn't exist (`"Product {uuid} not found."`).
- **422** — basic field validation.

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
    "schedule_product": { "uuid": "...", "actual_price": "130.00", "promo_status": "none", "price_confirmed_at": "...", "product": { ... } },
    "summary": { "auto_applied": 1, "needs_confirmation": 0, "bonus_applied": 0 }
  }
}
```

`summary` counts, across every live order touched, how many items were auto-applied, how many now need the customer's confirmation, and how many got a B1T1 bonus line — use it to show the seller immediate feedback.

### Error responses

- **403** (not owner)
- **404** — schedule not found, or `scheduleProductUuid` doesn't belong to it: `{ "success": false, "message": "Schedule product not found.", "data": null }`.
- **422** — `actual_price` missing/negative when `promo_status` isn't `out_of_stock`, or `promo_status` not one of the three allowed values.

---

## GET /api/admin/pasabuy-schedules/{uuid}/orders

Returns every order placed against this schedule, newest first, each with its `items` (see `order-api.md` for the `Order`/`OrderItem` shape).

### Error responses

- **404** — schedule not found.

---

## GET /api/admin/pasabuy-schedules/{uuid}/summary

The seller's shopping checklist — aggregates ordered quantity per product across every **live** order (`pending`/`needs_confirmation`/`confirmed`; cancelled orders and removed/bonus items are excluded) on this schedule. Use this right before heading to the store to know total quantities to buy.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "product_uuid": "...", "product_name": "Instant Noodles", "category_name": "Noodles & Canned Goods", "default_price": "25.00", "total_quantity": 8, "subtotal": "200.00" }
  ]
}
```

`subtotal` is `default_price * total_quantity` (not the confirmed `actual_price` — this is a pre-shopping estimate). Sum every item's `subtotal` client-side for a grand total.

### Error responses

- **404** — schedule not found.

---

## PUT /api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items

Seller-side order correction tool — full replace of one customer's order items (add, remove, change quantity). Only allowed once the schedule's `status` is `closed`; no quantity cap is enforced (the seller is trusted to know real-world stock). Recalculates the order's `subtotal`/`total_amount` the same way customer edits do, sourcing price from the schedule-product's confirmed `actual_price` if set, else the product's `default_price`.

### Request body

| Field                  | Type   | Required | Notes                          |
|------------------------|--------|----------|------------------------------------|
| items                  | array  | Yes      | Min 1 — full replacement list, not a diff |
| items[].product_uuid   | string | Yes      | Must be attached to this schedule   |
| items[].quantity       | int    | Yes      | >= 1                                 |

### Success response — 200

Returns the updated `OrderResource` (see `order-api.md`).

### Error responses

- **404** — schedule not found, order not found (or doesn't belong to this schedule), or a `product_uuid` isn't attached to this schedule.
- **422** — schedule isn't `closed` (`"Order items can only be edited by the seller while the schedule is closed."`), or basic field validation.

## Error status summary

| Status | Meaning              | Applies to                        |
|--------|-----------------------|-------------------------------------|
| 401    | Missing/invalid token | all endpoints                        |
| 403    | Not the schedule's owner | update, destroy, close, reopen, products, confirm-price |
| 404    | Not found             | show, update, destroy, close, reopen, products, orders, confirm-price, summary, order items |
| 422    | Validation failure    | store, update, close, reopen, products, confirm-price, order items |

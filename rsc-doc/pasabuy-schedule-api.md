# Pasabuy Schedule API

A pasabuy schedule is the seller's shareable "batch" — customers can order **any active catalog product** against it (pricing always starts from the product's own `default_price`, see `product-api.md`), plus a public link customers use to order. All endpoints here are admin-only and scoped to the schedule's owner (the admin who created it — see the 403 note below); the customer-facing read/order endpoints live in `public-pasabuy-api.md`.

**Products are attached reactively, not curated.** There is no "add products to this schedule" step. A `PasabuyScheduleProduct` row (the per-schedule pricing/promo state) is created automatically, the first time any customer (or the seller editing an order) orders that product on this schedule — seeded from the product's own `default_product_status`. The `products` array on a schedule therefore only ever contains products someone has actually ordered.

**Two-phase pricing.** A schedule-product's price is the product's `default_price` until the seller is physically in-store, at which point she calls `confirm-price` to set the real `actual_price` (and flag any status like B1T1, or mark the item out of stock). Confirming a price triggers server-side recalculation of every live order referencing that product — see `order-api.md` for what happens to affected orders/items.

**Closing is a dedicated action**, not a generic status update — use `close`/`reopen` rather than setting `status` via `PUT`. Closing only blocks customer-side ordering/editing (see `public-pasabuy-api.md`/`order-api.md`); it does **not** lock the seller out of managing prices or editing customer order items. **Closing also (re)snapshots the shopping checklist** — see the checklist endpoints below.

## Endpoints overview

| Method | Endpoint                                              | Description                                    |
|--------|--------------------------------------------------------|--------------------------------------------------|
| GET    | `/api/admin/pasabuy-schedules`                          | List schedules (paginated)                      |
| POST   | `/api/admin/pasabuy-schedules`                          | Create a schedule                                |
| GET    | `/api/admin/pasabuy-schedules/{uuid}`                   | Get one schedule with its (reactively attached) products |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}`                   | Update schedule fields (title/description/cutoff; status only via a valid transition) |
| DELETE | `/api/admin/pasabuy-schedules/{uuid}`                   | Delete a schedule                                |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/close`             | Close the schedule (`open` → `closed`); also (re)snapshots the checklist |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/reopen`            | Reopen the schedule (`closed` → `open`)          |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/finish`            | Mark the schedule finished (`closed` → `finished`, terminal — no transitions out) |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/orders`            | List orders placed against this schedule         |
| PATCH  | `/api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price` | Seller sets the in-store actual price + product status for one schedule product |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/summary`           | Aggregated per-product quantity/subtotal across all live orders |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/products/{productUuid}/buyers` | Every buyer of one product on this schedule, for the Summary-row detail view |
| GET    | `/api/admin/pasabuy-schedules/{uuid}/checklist`         | The seller's shopping checklist (empty until the schedule has been closed at least once) |
| PATCH  | `/api/admin/pasabuy-schedules/{uuid}/checklist/{productUuid}` | Check/uncheck one checklist item                |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/checklist/regenerate` | Manually re-run the checklist sync (same logic as `close`) against current order totals, without changing schedule status |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items` | Seller edits a customer's order items (closed schedules only) |

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Bodies are JSON (no file uploads on this module, so `PUT`/`POST`/`PATCH` action endpoints are sent as real verbs, no `_method` spoofing needed).

**Ownership.** Every mutating endpoint here requires the acting admin to be the schedule's creator. Acting on someone else's schedule returns **403**: `{ "success": false, "message": "You do not own this pasabuy schedule.", "data": null }`. Read-only endpoints (`show`, `orders`, `summary`, `buyers`, `checklist` GET) are not ownership-gated.

## Schedule object

| Field       | Type          | Notes                                                                 |
|-------------|---------------|--------------------------------------------------------------------------|
| uuid        | string        | Public identifier — this is what goes in the customer-facing link, e.g. `https://{frontend}/pasabuy/{uuid}` |
| title       | string        |                                                                            |
| description | string\|null  |                                                                            |
| cutoff_at   | datetime\|null| Optional deadline; ordering is blocked once passed even if `status` is still `open` |
| pasabuy_schedule_date | date\|null | The date the seller physically goes to the store — distinct from `cutoff_at` (the ordering deadline) and `created_at` (record creation). Drives the `month`/`year` list filters. Required on `POST`/`PUT`; only `null` for schedules created before this field existed. |
| status      | string        | `open` \| `closed` \| `finished`, default `open`. Only `open` (and before `cutoff_at`) accepts customer orders. Valid transitions: `open`→`closed`, `closed`→`open`, `closed`→`finished`. `finished` is terminal. |
| is_open     | boolean       | Computed: `status === 'open' && (!cutoff_at || cutoff_at is in the future)` — use this to gate the UI |
| products    | array         | Only the products someone has actually ordered so far on this schedule — see schedule-product object below |
| created_at  | datetime      |                                                                            |

### Schedule-product object (an entry in `products`)

| Field              | Type         | Notes                                             |
|--------------------|--------------|--------------------------------------------------------|
| uuid               | string       | Row's own identifier                            |
| actual_price        | string\|null | Set once the seller confirms in-store; `null` until confirmed, or if `product_status` is `out_of_stock` |
| product_status       | string       | `none` \| `b1t1` \| `out_of_stock` — seeded from the product's `default_product_status` when the row is first created; only meaningful for pricing once `price_confirmed_at` is set |
| price_confirmed_at | datetime\|null | `null` = still just the product's `default_price`; once set, `actual_price`/`product_status` reflect the confirmed in-store truth |
| product            | object       | The `Product` object (see `product-api.md`) — includes `default_price`, the price to display before confirmation |

---

## GET /api/admin/pasabuy-schedules

### Query params

| Field    | Type   | Required | Notes                        |
|----------|--------|----------|---------------------------------|
| search   | string | No       | Filter by title (partial match) |
| month    | int    | No       | 1-12, filters by `pasabuy_schedule_date` month |
| year     | int    | No       | Filters by `pasabuy_schedule_date` year |
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
| pasabuy_schedule_date | date | Yes | The date the seller physically goes to the store |
| status      | string   | No       | `open`\|`closed`\|`finished`, default `open` |

No product-picking step — customers can order any active catalog product against this schedule as soon as it's `open`.

### Success response — 201

```json
{ "success": true, "message": "Pasabuy schedule created successfully", "data": { "uuid": "...", "title": "Grocery Run July", "description": null, "cutoff_at": null, "status": "open", "is_open": true, "created_at": "..." } }
```

### Error responses

- **422** — validation failure (missing `title`, invalid `status`/`cutoff_at`).

---

## GET /api/admin/pasabuy-schedules/{uuid}

Includes `products` — only the ones actually ordered so far (see the module note at the top).

### Error responses

- **404** — `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

---

## PUT /api/admin/pasabuy-schedules/{uuid}

Same body as `POST`; this is a full replace, not a partial patch — always send `title` too. If `status` is included and differs from the current value, it's validated against the same transition table as `close`/`reopen` — prefer those dedicated endpoints for status changes; use this one for title/description/cutoff.

### Error responses

Same as `POST`, plus **403** (not owner), **404**, and **422** if `status` names an invalid transition (`"Cannot change schedule status from \"{from}\" to \"{to}\"."`).

---

## DELETE /api/admin/pasabuy-schedules/{uuid}

Hard-deletes the schedule and cascades to its attached products, checklist, and its orders (`ON DELETE CASCADE`). There is no confirmation step server-side — confirm in the UI before calling this.

### Error responses

- **403** (not owner), **404**

---

## POST /api/admin/pasabuy-schedules/{uuid}/close

Transitions `open` → `closed`. No request body. Blocks customer-side ordering/editing (already enforced elsewhere); does not affect the seller's own ability to confirm prices, or (now that it's closed) edit customer orders via the endpoint below.

**Also (re)snapshots the checklist** (see `GET .../checklist` below) from the current live order totals:
- First close ever → creates one checklist row per product currently ordered, unchecked, `quantity` = current total ordered.
- Any later close (after a reopen let more orders in) → unchecked rows just get `quantity` refreshed to the new total; checked rows keep their original `quantity` frozen and instead get `additional_quantity`/`return_quantity` populated with the delta against the new total.
- `quantity` is the **physical shopping quantity**, not the raw paid total: products confirmed `product_status: 'out_of_stock'` are skipped entirely (no checklist row — nothing to buy), and products confirmed `product_status: 'b1t1'` have their bonus (`is_bonus: true`) item quantity added on top of the paid quantity, since the seller needs stock to cover the free unit too. This differs from `GET .../summary`, which reports raw paid quantity only and leaves out-of-stock/b1t1 adjustments to the client.
- A product with a computed quantity of `0` (e.g. its only orders were cancelled/removed) never gets a row either. If it already had an **unchecked** row from an earlier close, that row is deleted outright rather than left at `quantity: 0`. A **checked** row is never deleted this way — it's preserved with `return_quantity` set to its full frozen `quantity`, since the seller already bought it and needs to return everything.

### Success response — 200

```json
{ "success": true, "message": "Pasabuy schedule closed", "data": { "uuid": "...", "status": "closed", ... } }
```

### Error responses

- **403** (not owner), **404**, **422** — invalid transition (e.g. already closed, or already `finished`).

---

## POST /api/admin/pasabuy-schedules/{uuid}/reopen

Transitions `closed` → `open`. Same shape/errors as `close`. Does not touch the checklist — that only changes on `close`.

---

## POST /api/admin/pasabuy-schedules/{uuid}/finish

Transitions `closed` → `finished`. No request body. `finished` is a terminal state — there is no transition back to `open`/`closed` from it. Use this once the batch has been fully delivered/settled. Does not touch the checklist or pricing.

### Success response — 200

```json
{ "success": true, "message": "Pasabuy schedule finished", "data": { "uuid": "...", "status": "finished", ... } }
```

### Error responses

- **403** (not owner), **404**, **422** — invalid transition (e.g. schedule isn't `closed` yet).

---

## PATCH /api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price

Called by the seller once she's physically in the store standing in front of the item. Not gated by schedule status (works while `open`/`closed`/`finished`) — she can confirm truth for individual items while still shopping. Setting this triggers server-side recalculation of every **live** (`pending`, i.e. not yet `confirmed`/`cancelled`) order item that references this product on this schedule, applied immediately with no customer confirmation step:

- **Product not out of stock** → the confirmed `actual_price` is auto-applied to every live item for this product; `actual_price`/`subtotal` update immediately (`is_out_of_stock: false`).
- **`product_status: 'out_of_stock'`** → every live item for this product is zeroed instead: `is_out_of_stock: true`, `actual_price: null`, `subtotal: 0`.
- **`product_status: 'b1t1'`** → independent of the price branch above, one bonus `OrderItem` per live order is created/updated 1:1 with the paid item's quantity (`is_bonus: true`, price `0`) — removed automatically if a later confirm moves the status off `b1t1`.

### Request body

| Field        | Type   | Required                          | Notes                                        |
|--------------|--------|------------------------------------|---------------------------------------------|
| actual_price | number | Yes, unless `product_status=out_of_stock` | >= 0                                    |
| product_status | string | Yes                                 | `none` \| `b1t1` \| `out_of_stock`           |

### Example request

```
_method=PATCH
actual_price=130
product_status=none
```

### Success response — 200

```json
{
  "success": true,
  "message": "Price confirmed",
  "data": {
    "schedule_product": { "uuid": "...", "actual_price": "130.00", "product_status": "none", "price_confirmed_at": "...", "product": { ... } },
    "summary": { "auto_applied": 1, "bonus_applied": 0, "out_of_stock": 0 }
  }
}
```

`summary` counts, across every live order touched, how many items had the price auto-applied, how many got a B1T1 bonus line, and how many were zeroed out for being out of stock — use it to show the seller immediate feedback.

### Error responses

- **403** (not owner)
- **404** — schedule not found, or `scheduleProductUuid` doesn't belong to it: `{ "success": false, "message": "Schedule product not found.", "data": null }`.
- **422** — `actual_price` missing/negative when `product_status` isn't `out_of_stock`, or `product_status` not one of the three allowed values.

---

## GET /api/admin/pasabuy-schedules/{uuid}/orders

Returns every order placed against this schedule, newest first, each with its `items` (see `order-api.md` for the `Order`/`OrderItem` shape).

### Error responses

- **404** — schedule not found.

---

## GET /api/admin/pasabuy-schedules/{uuid}/summary

Aggregates ordered quantity per product across every **live** order (`pending`/`confirmed`; cancelled orders and bonus items are excluded) on this schedule. Products marked `is_out_of_stock` still appear here with their originally ordered quantity — the row isn't dropped just because the seller marked it out of stock in-store. This is the "Price & Totals" view — click through to `buyers` below for the per-buyer breakdown behind one row.

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

## GET /api/admin/pasabuy-schedules/{uuid}/products/{productUuid}/buyers

Every live (`pending`/`confirmed`) order item for one product on this schedule, including items already marked `is_out_of_stock` — feeds the modal opened by clicking a Summary row, so the seller can see who ordered before (or after) confirming a price.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "customer_name": "Jane Dela Cruz", "quantity": 3, "estimated_price": "25.00", "actual_price": null, "subtotal": "75.00", "status": "pending" }
  ]
}
```

### Error responses

- **404** — schedule not found, or product not found.

---

## GET /api/admin/pasabuy-schedules/{uuid}/checklist

The seller's shopping checklist. **Empty until the schedule has been closed at least once** — see `close` above for how/when it's populated. Once populated, items persist across reopen/close cycles per the merge rules described there.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "product_uuid": "...", "product_name": "Instant Noodles", "category_name": "Noodles & Canned Goods", "quantity": 8, "is_checked": false, "checked_at": null, "additional_quantity": 0, "return_quantity": 0 }
  ]
}
```

`quantity` is the amount to buy while unchecked; once checked it freezes at whatever quantity it was checked at. `additional_quantity`/`return_quantity` are only ever populated on a checked item, by a later `close` recalculation — buy `additional_quantity` more, or return `return_quantity` of what was already bought. Products currently `product_status: 'out_of_stock'` never have a row here; `b1t1` products have their `quantity` inflated by the bonus item count (see `close` above).

### Error responses

- **404** — schedule not found.

---

## PATCH /api/admin/pasabuy-schedules/{uuid}/checklist/{productUuid}

Check or uncheck one item. Nothing in the system auto-unchecks an item — this is the only way `is_checked` changes.

### Request body

| Field      | Type    | Required | Notes |
|------------|---------|----------|-------|
| is_checked | boolean | Yes      |       |

### Success response — 200

Returns the updated checklist item (same shape as one entry above).

### Error responses

- **403** (not owner)
- **404** — schedule not found, or no checklist item exists yet for that product (schedule never closed, or product never ordered).
- **422** — `is_checked` missing/not boolean.

---

## POST /api/admin/pasabuy-schedules/{uuid}/checklist/regenerate

Manually re-runs the same sync `close` performs (see above), without changing the schedule's status. For when orders were edited or new orders confirmed after the initial close and the checklist quantities/deltas have gone stale. No request body. Safe to call repeatedly — unchecked rows just get `quantity` refreshed; checked rows keep their frozen `quantity` and get `additional_quantity`/`return_quantity` recomputed against the new total.

Only callable once the schedule has been closed at least once (`status` is `closed` or `finished`).

### Success response — 200

Returns the refreshed checklist (same shape as `GET .../checklist`).

### Error responses

- **403** (not owner)
- **404** — schedule not found.
- **422** — schedule is still `open` (never closed, so there's no checklist to regenerate).

---

## PUT /api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items

Seller-side order correction tool — full replace of one customer's order items (add, remove, change quantity). Only allowed once the schedule's `status` is `closed`; no quantity cap is enforced (the seller is trusted to know real-world stock). Recalculates the order's `subtotal`/`total_amount` the same way customer edits do, sourcing price from the schedule-product's confirmed `actual_price` if set, else the product's `default_price`. Adding a product not yet ordered on this schedule creates its `PasabuyScheduleProduct` row on the fly, same as a customer ordering it for the first time.

### Request body

| Field                  | Type   | Required | Notes                          |
|------------------------|--------|----------|------------------------------------|
| items                  | array  | Yes      | Min 1 — full replacement list, not a diff |
| items[].product_uuid   | string | Yes      | Must be an active catalog product   |
| items[].quantity       | int    | Yes      | >= 1                                 |

### Success response — 200

Returns the updated `OrderResource` (see `order-api.md`).

### Error responses

- **404** — schedule not found, order not found (or doesn't belong to this schedule), or a `product_uuid` doesn't exist / isn't active.
- **422** — schedule isn't `closed` (`"Order items can only be edited by the seller while the schedule is closed."`), or basic field validation.

## Error status summary

| Status | Meaning              | Applies to                        |
|--------|-----------------------|-------------------------------------|
| 401    | Missing/invalid token | all endpoints                        |
| 403    | Not the schedule's owner | update, destroy, close, reopen, finish, confirm-price, checklist toggle, checklist regenerate |
| 404    | Not found             | show, update, destroy, close, reopen, finish, orders, confirm-price, summary, buyers, checklist, order items |
| 422    | Validation failure    | store, update, close, reopen, finish, confirm-price, checklist toggle, checklist regenerate (still open), order items |

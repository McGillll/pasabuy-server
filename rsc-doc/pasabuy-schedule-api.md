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
| PUT    | `/api/admin/pasabuy-schedules/{uuid}`                   | Update schedule fields (title/description/pasabuy_schedule_date; status only via a valid transition) |
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
| GET    | `/api/admin/pasabuy-schedules/{uuid}/checklist/share-link` | Get (or lazily create) the checklist's public share token |
| POST   | `/api/admin/pasabuy-schedules/{uuid}/checklist/share-link/regenerate` | Rotate the checklist share token, invalidating any previously shared link |
| PUT    | `/api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items` | Seller edits an order's items (manual orders: anytime not finished; customer orders: closed schedules only) |

Unauthenticated helper access to the checklist (view + check off items, no login required) is documented separately below under **Public checklist (unauthenticated)**.

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Bodies are JSON (no file uploads on this module, so `PUT`/`POST`/`PATCH` action endpoints are sent as real verbs, no `_method` spoofing needed).

**Ownership.** Every mutating endpoint here requires the acting admin to be the schedule's creator. Acting on someone else's schedule returns **403**: `{ "success": false, "message": "You do not own this pasabuy schedule.", "data": null }`. Read-only endpoints (`show`, `orders`, `summary`, `buyers`, `checklist` GET) are not ownership-gated.

## Schedule object

| Field       | Type          | Notes                                                                 |
|-------------|---------------|--------------------------------------------------------------------------|
| uuid        | string        | Public identifier — this is what goes in the customer-facing link, e.g. `https://{frontend}/pasabuy/{uuid}` |
| title       | string        |                                                                            |
| description | string\|null  |                                                                            |
| pasabuy_schedule_date | date\|null | The date the seller physically goes to the store — also doubles as the ordering deadline: ordering closes as soon as this date arrives. Distinct from `created_at` (record creation). Drives the `month`/`year` list filters. Required on `POST`/`PUT`. |
| status      | string        | `open` \| `closed` \| `finished`, default `open`. Only `open` (and before `pasabuy_schedule_date`) accepts customer orders. Valid transitions: `open`→`closed`, `closed`→`open`, `closed`→`finished`. `finished` is terminal. |
| is_open     | boolean       | Computed: `status === 'open' && (!pasabuy_schedule_date || pasabuy_schedule_date is in the future)` — use this to gate the UI |
| due_date    | date\|null    | Invoice due date for every order on this schedule. `null` until `finish` is called — set mandatorily then, immutable afterward |
| seller_name | string\|null  | Owning seller's full name — only present when the `user` relation is loaded (e.g. `GET /api/customer/pasabuy-schedules`, see `customer-api.md`); `null`/omitted elsewhere |
| product_count | int\|null   | Count of attached schedule products — only present when loaded with a count (e.g. `GET /api/customer/pasabuy-schedules`); `null`/omitted elsewhere |
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
| pasabuy_schedule_date | date | Yes | The date the seller physically goes to the store — also the ordering deadline |
| status      | string   | No       | `open`\|`closed`\|`finished`, default `open` |

No product-picking step — customers can order any active catalog product against this schedule as soon as it's `open`.

### Success response — 201

```json
{ "success": true, "message": "Pasabuy schedule created successfully", "data": { "uuid": "...", "title": "Grocery Run July", "description": null, "pasabuy_schedule_date": "2026-07-20", "status": "open", "is_open": true, "created_at": "..." } }
```

### Error responses

- **422** — validation failure (missing `title`/`pasabuy_schedule_date`, invalid `status`).

---

## GET /api/admin/pasabuy-schedules/{uuid}

Includes `products` — only the ones actually ordered so far (see the module note at the top).

### Error responses

- **404** — `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

---

## PUT /api/admin/pasabuy-schedules/{uuid}

Same body as `POST`; this is a full replace, not a partial patch — always send `title` too. If `status` is included and differs from the current value, it's validated against the same transition table as `close`/`reopen` — prefer those dedicated endpoints for status changes; use this one for title/description/pasabuy_schedule_date.

Setting `status` to `finished` this way is subject to the same pending-orders guard as the dedicated `finish` endpoint below — it's blocked while the schedule still has any `pending` order.

### Error responses

Same as `POST`, plus **403** (not owner), **404**, and **422** if `status` names an invalid transition (`"Cannot change schedule status from \"{from}\" to \"{to}\"."`) or if transitioning to `finished` while `pending` orders remain (`"Cannot finish this schedule while it has pending orders that have not been confirmed or cancelled."`).

---

## DELETE /api/admin/pasabuy-schedules/{uuid}

Hard-deletes the schedule and cascades to its attached products, checklist, and its orders — including their order items and payments (`ON DELETE CASCADE`). There is no confirmation step server-side — confirm in the UI before calling this.

Only allowed while the schedule is **open**. Returns **422** `"This pasabuy schedule is no longer open and can no longer be deleted."` once `closed` or `finished`, since a closed schedule can already have confirmed orders and a finished one can have paid ones — this endpoint must never be reachable once real commitments exist.

### Error responses

- **403** (not owner), **404**, **422** (not open)

---

## POST /api/admin/pasabuy-schedules/{uuid}/close

Transitions `open` → `closed`. No request body. Blocks customer-side ordering/editing (already enforced elsewhere); does not affect the seller's own ability to confirm prices, or (now that it's closed) edit customer orders via the endpoint below.

**Also (re)snapshots the checklist** (see `GET .../checklist` below) from the current live order totals:
- First close ever → creates one checklist row per product currently ordered, unchecked, `quantity` = current total ordered, `picked_quantity` = 0.
- Any later close (after a reopen let more orders in) → if the row's demand still matches `picked_quantity` (nothing changed since it was last checked/recomputed), it's left alone. If demand changed:
  - An **unchecked** row (never picked anything, or still carrying an earlier unresolved delta) gets `quantity` recomputed as the outstanding amount against `picked_quantity` (`0` for a row that was never checked, so this is the full new total), plus `additional_quantity`/`return_quantity` set from the same delta.
  - A **checked** row is flipped back to unchecked (`checked_at` reset to `null`) and its `quantity` becomes just the outstanding delta against `picked_quantity` — not the new grand total — so the seller only sees what's left to act on, not a re-ask for what they already bought. `picked_quantity` itself is untouched until the row is checked again.
  - A previously-delta'd unchecked row whose demand settles back to exactly `picked_quantity` (e.g. the extra order that caused the delta gets cancelled) is auto-checked again, `quantity` reset to `picked_quantity`, deltas cleared.
- `quantity` is the **physical shopping quantity**, not the raw paid total: products confirmed `product_status: 'out_of_stock'` are skipped entirely (no checklist row — nothing to buy), and products confirmed `product_status: 'b1t1'` have their `bonus_quantity` added on top of the paid quantity, since the seller needs stock to cover the free unit too. This differs from `GET .../summary`, which reports raw paid quantity only and leaves out-of-stock/b1t1 adjustments to the client.
- A product with a computed quantity of `0` (e.g. its only orders were cancelled/removed) never gets a row either. If the row has no `picked_quantity` (never actually bought anything), it's deleted outright. Otherwise it's flipped back to unchecked with `return_quantity` set to its full `picked_quantity` — the seller already bought that amount and now needs to return all of it.

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

Transitions `closed` → `finished`. `finished` is a terminal state — there is no transition back to `open`/`closed` from it. Use this once the batch has been fully delivered/settled. Does not touch the checklist or pricing.

**Requires a `due_date` in the request body** — this becomes the due date for every order's invoice on this schedule (`pasabuy_schedules.due_date`). There is no other way to set it; it cannot be changed after the schedule is finished.

### Request body

| Field    | Type   | Required | Notes |
|----------|--------|----------|-------|
| due_date | string | Yes      | ISO date (`YYYY-MM-DD`). Must be today or later — a past date returns **422** |

**Blocked while any order on this schedule is still `pending`** — the seller must confirm or cancel every order first (`confirmed` orders are fine; only `pending` blocks the transition). This guard applies to both this endpoint and setting `status: "finished"` via the generic `PUT` update above.

**Side effect:** once the status change and `due_date` are persisted, every `confirmed`/`pending`-turned-`confirmed` order on this schedule is passed to `InvoiceMailerService::sendIfDue($order)`, which queues `PasabuyInvoiceMail` (built from `InvoiceService`, now reading `due_date` straight off the schedule). Each order is only ever emailed once (`Order.invoice_sent_at` guards against duplicates on retries or re-triggers). Orders without a customer email, or whose invoice fails to build, are skipped and logged — this never fails the `finish` request itself.

### Example request

```json
POST /api/admin/pasabuy-schedules/{uuid}/finish
{ "due_date": "2026-08-15" }
```

### Success response — 200

```json
{ "success": true, "message": "Pasabuy schedule finished", "data": { "uuid": "...", "status": "finished", "due_date": "2026-08-15", ... } }
```

### Error responses

- **403** (not owner), **404**, **422** — invalid transition (e.g. schedule isn't `closed` yet), `pending` orders still exist (`"Cannot finish this schedule while it has pending orders that have not been confirmed or cancelled."`), `due_date` missing/invalid, or `due_date` in the past (`"The invoice due date cannot be in the past."`).

---

## PATCH /api/admin/pasabuy-schedules/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price

Called by the seller once she's physically in the store standing in front of the item. Not gated by schedule status (works while `open`/`closed`/`finished`) — she can confirm truth for individual items while still shopping. Setting this triggers server-side recalculation of every **live** (`pending`, i.e. not yet `confirmed`/`cancelled`) order item that references this product on this schedule, applied immediately with no customer confirmation step:

- **Product not out of stock** → the confirmed `actual_price` is auto-applied to every live item for this product; `actual_price`/`subtotal` update immediately (`is_out_of_stock: false`).
- **`product_status: 'out_of_stock'`** → every live item for this product is zeroed instead: `is_out_of_stock: true`, `actual_price: null`, `subtotal: 0`.
- **`product_status: 'b1t1'`** → independent of the price branch above, every live order item for this product has `bonus_quantity` set to match its own `quantity` (extra free units, not counted in `subtotal`) — reset back to `0` automatically if a later confirm moves the status off `b1t1`.

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

Returns every order placed against this schedule, newest first, each with its `items` (see `order-api.md` for the `Order`/`OrderItem` shape). Like `GET /api/admin/orders`, each order here is also populated with `amount_paid`/`amount_due` (computed via the invoice summary, see `order-api.md`) rather than serializing them as `null`.

### Error responses

- **404** — schedule not found.

---

## GET /api/admin/pasabuy-schedules/{uuid}/summary

Aggregates ordered quantity per product across every **live** order (`pending`/`confirmed`; cancelled orders and bonus items are excluded) on this schedule. Products marked out of stock still appear here with their originally ordered quantity — the row isn't dropped just because the seller marked it out of stock in-store. This is the "Price & Totals" view — click through to `buyers` below for the per-buyer breakdown behind one row.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "product_uuid": "...",
      "product_name": "Instant Noodles",
      "category_name": "Noodles & Canned Goods",
      "default_price": "25.00",
      "unit_price": "27.00",
      "actual_price": "27.00",
      "price_confirmed_at": "2026-08-07T09:30:00.000000Z",
      "product_status": "none",
      "total_quantity": 8,
      "subtotal": "216.00"
    }
  ]
}
```

`unit_price` is the effective row price: confirmed `actual_price` when set, otherwise the product's `default_price`. `subtotal` is `unit_price * total_quantity`, except `product_status: "out_of_stock"` returns `0`. B1T1 does not double the charged subtotal; it only affects the physical shopping quantity shown by the client/checklist.

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
    { "product_uuid": "...", "product_name": "Instant Noodles", "category_name": "Noodles & Canned Goods", "quantity": 8, "picked_quantity": 0, "is_checked": false, "checked_at": null, "additional_quantity": 0, "return_quantity": 0 }
  ]
}
```

`quantity` is always the amount still needing action right now: the full amount to buy while unchecked-and-fresh, or just the outstanding delta while unchecked-with-a-pending-adjustment. `picked_quantity` is the cumulative amount already confirmed bought as of the last check — it only changes when the item is checked, never on a `close`/`regenerate` recalculation. `additional_quantity`/`return_quantity` are only ever populated on a row that's currently unchecked because demand shifted since it was last checked (see `close` above) — a checked row never carries a nonzero delta. Products currently `product_status: 'out_of_stock'` never have a row here; `b1t1` products have their `quantity` inflated by the bonus item count (see `close` above).

### Error responses

- **404** — schedule not found.

---

## PATCH /api/admin/pasabuy-schedules/{uuid}/checklist/{productUuid}

Check or uncheck one item. Checking (`is_checked: true`) folds any pending `additional_quantity`/`return_quantity` delta into `picked_quantity` and clears both; unchecking just clears `checked_at` and leaves the quantities as they are. A `close`/`regenerate` recalculation can also auto-uncheck (or, if demand settles back to `picked_quantity`, auto-check) a row on its own — see `close` above — so `is_checked` is not exclusively client-driven.

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

Manually re-runs the same sync `close` performs (see above), without changing the schedule's status. For when orders were edited or new orders confirmed after the initial close and the checklist quantities/deltas have gone stale. No request body. Safe to call repeatedly — same merge/auto-uncheck rules as `close` above.

Only callable once the schedule has been closed at least once (`status` is `closed` or `finished`).

### Success response — 200

Returns the refreshed checklist (same shape as `GET .../checklist`).

### Error responses

- **403** (not owner)
- **404** — schedule not found.
- **422** — schedule is still `open` (never closed, so there's no checklist to regenerate).

---

## GET /api/admin/pasabuy-schedules/{uuid}/checklist/share-link

Returns the schedule's checklist share token, generating one on first call if it doesn't exist yet. Give this to a helper as `{client_origin}/pasabuy/checklist/{token}` — a fully public page (no login) where they can view quantities and check items off while shopping. The token is a random 40-character string, independent of the schedule's own `uuid` (which is also public, via the customer ordering link) — anyone holding just the ordering link cannot derive or use it to touch the checklist.

### Success response — 200

```json
{ "success": true, "message": "Success", "data": { "token": "..." } }
```

### Error responses

- **403** (not owner)
- **404** — schedule not found.

---

## POST /api/admin/pasabuy-schedules/{uuid}/checklist/share-link/regenerate

Rotates the checklist share token. The previous token stops working immediately (any helper still using the old link gets a 404 on their next request/poll) — use this if a link leaked or the helper no longer needs access. Same response/error shape as the `GET` above, with message `"Checklist share link regenerated"`.

---

## Public checklist (unauthenticated)

Two routes under `/api/public/checklist/{token}`, keyed by the share token above instead of the schedule `uuid` — no `Authorization` header, no ownership check (possession of the token *is* the authorization). Both are rate-limited via the `checklist-toggle` limiter (60 requests/minute per IP). Neither exposes a way to regenerate the checklist from order totals — that stays a seller-only action; the intended client polls `GET` on an interval instead to pick up changes the seller makes.

### GET /api/public/checklist/{token}

Same response shape as `GET /api/admin/pasabuy-schedules/{uuid}/checklist`.

- **404** — token doesn't exist (never generated, or rotated away).
- **422** — the schedule behind this token isn't `closed`/`finished` yet (checklist doesn't exist until the seller closes ordering).

### PATCH /api/public/checklist/{token}/{productUuid}

Same request body / response shape / behavior as `PATCH /api/admin/pasabuy-schedules/{uuid}/checklist/{productUuid}` (sent as `POST` with `_method: 'PATCH'` spoofing, per the client's FormData convention). Same 404/422 conditions as the `GET` above, plus **404** if `productUuid` has no checklist row.

---

## POST /api/admin/pasabuy-schedules/{uuid}/orders

Seller-placed order — lets the seller record an order on behalf of a customer who won't use the app themselves (walk-in, phone/chat order, etc). Same item-resolution/pricing as a customer order (`createForSchedule` in `order-api.md`), but skips the customer-only guards (no "already has a live order" or unpaid-balance block, since there's no customer account behind it) and, since this is a seller/admin action rather than a customer-facing one, is **not** gated by `is_open` — the seller can still place a walk-in order after closing the schedule. Requires the caller to own the schedule and the schedule to not yet be `finished`. The resulting order has `channel: "seller"` and `user_id: null` — it isn't tied to any customer account.

### Request body

| Field                  | Type   | Required | Notes                          |
|------------------------|--------|----------|------------------------------------|
| customer_name          | string | Yes      | Free-text label for who the order is for (name, nickname, "Unit 5B", etc) |
| customer_contact       | string | No       |                                       |
| items                  | array  | Yes      | Min 1                                |
| items[].product_uuid   | string | Yes      | Must be an active catalog product   |
| items[].quantity       | int    | Yes      | >= 1                                 |

### Success response — 201

Returns the created `OrderResource` (see `order-api.md`), with `channel: "seller"`.

### Error responses

- **403** — caller doesn't own this schedule.
- **404** — schedule not found, or a `product_uuid` doesn't exist / isn't active.
- **422** — schedule is `finished` (`"This pasabuy is finished and no longer accepts new orders."`), or basic field validation (`customer_name` missing, `items` empty, etc).

---

## PUT /api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items

Seller-side order correction tool — full replace of one order's items (add, remove, change quantity). For a **manual order** (`channel` = `seller`, created via the "Add order" flow), this is allowed anytime the schedule isn't `finished` (i.e. `open` or `closed`). For a **customer order** (`channel` = `customer`), it's only allowed once the schedule's `status` is `closed`. No quantity cap is enforced (the seller is trusted to know real-world stock). Recalculates the order's `subtotal`/`total_amount` the same way customer edits do, sourcing price from the schedule-product's confirmed `actual_price` if set, else the product's `default_price`. Adding a product not yet ordered on this schedule creates its `PasabuyScheduleProduct` row on the fly, same as a customer ordering it for the first time.

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
- **422** — customer order and schedule isn't `closed` (`"Order items can only be edited by the seller while the schedule is closed."`), manual order and schedule is `finished` (`"Order items can no longer be edited once the schedule is finished."`), or basic field validation.

## Error status summary

| Status | Meaning              | Applies to                        |
|--------|-----------------------|-------------------------------------|
| 401    | Missing/invalid token | all admin endpoints (not applicable to the public checklist endpoints, which use the share token instead) |
| 403    | Not the schedule's owner | update, destroy, close, reopen, finish, confirm-price, checklist toggle, checklist regenerate, checklist share-link (get/regenerate), seller-placed order |
| 404    | Not found             | show, update, destroy, close, reopen, finish, orders, confirm-price, summary, buyers, checklist, order items, checklist share-link, public checklist (bad/rotated token), seller-placed order |
| 422    | Validation failure    | store, update, close, reopen, finish, confirm-price, checklist toggle, checklist regenerate (still open), order items, public checklist (schedule not closed yet), seller-placed order (schedule finished) |
| 429    | Rate limit exceeded   | public checklist GET/PATCH (`checklist-toggle`: 60/min per IP) |

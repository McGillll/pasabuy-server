# Pasabuy Schedule API

A pasabuy schedule is the seller's shareable "batch" — a subset of the global product catalog, each with a price and optional stock cap for that specific run, plus a public link customers use to order. All endpoints here are admin-only; the customer-facing read/order endpoints live in `public-pasabuy-api.md`.

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

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). Any endpoint accepting a body is `multipart/form-data`; send `PUT` as `POST` + `_method=PUT`.

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

| Field        | Type         | Notes                                             |
|--------------|--------------|------------------------------------------------------|
| uuid         | string       | Pivot row's own identifier                            |
| price        | string       | Decimal string, e.g. `"25.00"` — the price for this schedule, independent of any other schedule |
| max_quantity | int\|null    | `null` = unlimited                                     |
| product      | object       | The `Product` object (see `product-api.md`) — `uuid`, `name`, `description`, `image_url`, `is_active` |

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
| products[].price            | number | Yes      | >= 0                                |
| products[].max_quantity     | int    | No       | >= 1, omit/null for unlimited       |

### Example request (form-encoded array)

```
_method=PUT
products[0][product_uuid]=abe9e0a5-...
products[0][price]=25
products[0][max_quantity]=2
```

### Success response — 200

Returns the full `PasabuyScheduleResource` with the updated `products` array.

### Error responses

- **404** — schedule not found, or one of the `product_uuid`s doesn't exist (`"Product {uuid} not found."`).
- **422** — a `price` is missing/negative (`"A valid price is required for product {name}."`), or basic field validation (`products.*.quantity` etc.).

---

## GET /api/admin/pasabuy-schedules/{uuid}/orders

Returns every order placed against this schedule, newest first, each with its `items` (see `order-api.md` for the `Order`/`OrderItem` shape).

### Error responses

- **404** — schedule not found.

## Error status summary

| Status | Meaning              | Applies to                        |
|--------|-----------------------|-------------------------------------|
| 401    | Missing/invalid token | all endpoints                        |
| 404    | Not found             | show, update, destroy, products, orders |
| 422    | Validation failure    | store, update, products              |

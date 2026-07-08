# Product API

Global product catalog, managed by the seller/admin. Each product has its own authoritative `default_price` and an optional `category` (see `product-category-api.md`) — pasabuy schedules no longer set their own price estimate; they just reference the product's `default_price`.

## Endpoints overview

| Method | Endpoint                     | Auth required | Description             |
|--------|-------------------------------|----------------|--------------------------|
| GET    | `/api/admin/products`         | Yes (admin)    | List products (paginated) |
| POST   | `/api/admin/products`         | Yes (admin)    | Create a product          |
| GET    | `/api/admin/products/{uuid}`  | Yes (admin)    | Get one product           |
| POST   | `/api/admin/products/{uuid}`  | Yes (admin)    | Update a product          |
| DELETE | `/api/admin/products/{uuid}`  | Yes (admin)    | Soft-delete a product     |

All requests send `Accept: application/json` and `Authorization: Bearer {token}` (token must carry the `admin` ability — see `auth-api.md`). `POST` bodies are `multipart/form-data`. Update also uses `POST` (not `PUT`) because PHP does not parse multipart bodies on real `PUT` requests.

## Response envelope

Standard `{success, message, data}` envelope (see `auth-api.md`).

## Product object

| Field       | Type            | Notes                                    |
|-------------|-----------------|--------------------------------------------|
| uuid        | string          | Public identifier                          |
| name        | string          |                                              |
| description | string\|null    |                                              |
| image_url   | string\|null    | Full public URL, or `null` if no image set |
| is_active   | boolean         | Informational only — inactive products can still be attached to a schedule; use it to hide discontinued items from your own catalog view |
| default_price | string        | Decimal string, e.g. `"25.00"` — the authoritative catalog price, used everywhere the product is scheduled/ordered/summarized |
| category    | object\|null    | The `ProductCategory` object (see `product-category-api.md`), or `null` if uncategorized |
| last_confirmed_price | string\|null | The most recent in-store `actual_price` confirmed for this product on any past pasabuy schedule. Only present on `GET /api/admin/products` (list); omitted on `show`/`store`/`update` responses. `null` if never confirmed. Purely informational history — does not affect `default_price`. |
| last_promo_status | string\|null | The promo status (`none`\|`b1t1`\|`out_of_stock`) tied to `last_confirmed_price`. Same availability caveat. |

Soft-deleted products are excluded from `index`/`show` automatically but remain referenced (by name/price snapshot) on any historical `order_items`.

---

## GET /api/admin/products

### Query params

| Field    | Type   | Required | Notes                          |
|----------|--------|----------|----------------------------------|
| search   | string | No       | Filter by name (partial match)   |
| per_page | int    | No       | Default 15                       |

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "data": [ { "uuid": "...", "name": "Instant Noodles", "description": "Spicy pack", "image_url": null, "is_active": true, "default_price": "25.00", "category": { "uuid": "...", "name": "Noodles & Canned Goods", "description": null, "is_active": true }, "last_confirmed_price": "25.00", "last_promo_status": "none" } ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

`data` is a standard Laravel paginator; use `data.data` for the array of products.

---

## POST /api/admin/products

### Request body (multipart/form-data)

| Field         | Type   | Required | Notes                    |
|---------------|--------|----------|----------------------------|
| name          | string | Yes      |                             |
| description   | string | No       |                             |
| image         | file   | No       | Image file, max 4MB        |
| is_active     | bool   | No       | Defaults to `true`          |
| default_price | number | Yes      | >= 0                        |
| category_uuid | string | No       | Must be an existing `ProductCategory` uuid; omit for uncategorized |

### Success response — 201

```json
{ "success": true, "message": "Product created successfully", "data": { "uuid": "...", "name": "Instant Noodles", "description": "Spicy pack", "image_url": null, "is_active": true, "default_price": "25.00", "category": null } }
```

### Error responses

- **404** — `category_uuid` doesn't match an existing category.
- **422** — validation failure (missing `name`/`default_price`, or `image` not a valid image).

---

## GET /api/admin/products/{uuid}

### Success response — 200

Same shape as one item in the `POST` response.

### Error responses

- **404** — no product with that uuid: `{ "success": false, "message": "Product not found.", "data": null }`

---

## POST /api/admin/products/{uuid}

Same body fields as `POST /api/admin/products`. Omitting `image` leaves the existing image untouched; sending a new one replaces and deletes the old file from storage.

### Success response — 200

```json
{ "success": true, "message": "Product updated successfully", "data": { ... } }
```

### Error responses

Same as `POST`, plus **404** if the uuid doesn't exist.

---

## DELETE /api/admin/products/{uuid}

Soft-deletes the product (reversible in the database — no restore endpoint is exposed yet). Safe to call even if the product is referenced by past orders, since `order_items` stores its own name/price snapshot.

### Success response — 200

```json
{ "success": true, "message": "Product deleted successfully", "data": null }
```

### Error responses

- **404** — no product with that uuid.

## Error status summary

| Status | Meaning               | Applies to                  |
|--------|------------------------|-------------------------------|
| 401    | Missing/invalid token  | all endpoints                  |
| 404    | Product or category not found | show, update, destroy, store, update |
| 422    | Validation failure     | store, update                  |

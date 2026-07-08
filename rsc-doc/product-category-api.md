# Product Category API

Simple catalog for organizing products (e.g. "Beverages", "Household"). Managed by the seller/admin; assigned to a `Product` via `category_uuid` (see `product-api.md`).

## Endpoints overview

| Method | Endpoint                              | Auth required | Description                  |
|--------|-----------------------------------------|----------------|---------------------------------|
| GET    | `/api/admin/product-categories`         | Yes (admin)    | List categories (paginated)     |
| POST   | `/api/admin/product-categories`         | Yes (admin)    | Create a category                |
| GET    | `/api/admin/product-categories/{uuid}`  | Yes (admin)    | Get one category                 |
| PUT    | `/api/admin/product-categories/{uuid}`  | Yes (admin)    | Update a category                |
| DELETE | `/api/admin/product-categories/{uuid}`  | Yes (admin)    | Soft-delete a category            |

All requests: `Accept: application/json`, `Authorization: Bearer {token}` (admin ability). No file uploads on this module, so bodies are plain JSON/form-encoded and `update` uses a real `PUT`.

## Response envelope

Standard `{success, message, data}` envelope (see `auth-api.md`).

## ProductCategory object

| Field       | Type         | Notes            |
|-------------|--------------|--------------------|
| uuid        | string       | Public identifier   |
| name        | string       |                      |
| description | string\|null |                      |
| is_active   | boolean      | Informational only — inactive categories can still be assigned to products |

Soft-deleted categories are excluded from `index`/`show`; products already assigned to a deleted category keep their `category_id` (set to `null` only if the category row is hard-deleted, which isn't exposed here).

---

## GET /api/admin/product-categories

### Query params

| Field    | Type   | Required | Notes                        |
|----------|--------|----------|---------------------------------|
| search   | string | No       | Filter by name (partial match)  |
| per_page | int    | No       | Default 15                      |

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "data": [ { "uuid": "...", "name": "Beverages", "description": null, "is_active": true } ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

## POST /api/admin/product-categories

### Request body

| Field       | Type   | Required | Notes            |
|-------------|--------|----------|--------------------|
| name        | string | Yes      | Max 255 chars       |
| description | string | No       |                      |
| is_active   | bool   | No       | Defaults to `true`   |

### Success response — 201

```json
{ "success": true, "message": "Product category created successfully", "data": { "uuid": "...", "name": "Beverages", "description": null, "is_active": true } }
```

### Error responses

- **422** — validation failure (missing `name`).

---

## GET /api/admin/product-categories/{uuid}

### Error responses

- **404** — `{ "success": false, "message": "Product category not found.", "data": null }`

---

## PUT /api/admin/product-categories/{uuid}

Same body as `POST` (full replace — always send `name`).

### Error responses

Same as `POST`, plus **404**.

---

## DELETE /api/admin/product-categories/{uuid}

### Success response — 200

```json
{ "success": true, "message": "Product category deleted successfully", "data": null }
```

### Error responses

- **404**

## Error status summary

| Status | Meaning               | Applies to             |
|--------|------------------------|---------------------------|
| 401    | Missing/invalid token  | all endpoints               |
| 404    | Category not found     | show, update, destroy       |
| 422    | Validation failure     | store, update                |

# Product Category API

Folder-style catalog for organizing products (e.g. "Beverages" > "Soda"). Categories can nest to unlimited depth via `parent_uuid`. Managed by the seller/admin; assigned to a `Product` via `category_uuid` (see `product-api.md`) — **only leaf categories (no children) can be assigned to a product.**

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

| Field        | Type         | Notes            |
|--------------|--------------|--------------------|
| uuid         | string       | Public identifier   |
| name         | string       |                      |
| description  | string\|null |                      |
| image_url    | string\|null | A direct link to an externally-hosted image (e.g. copied from the web). Not a file upload — just stored and returned as-is. |
| is_active    | boolean      | Informational only — inactive categories can still be assigned to products |
| parent_uuid  | string\|null | `null` for a top-level category |
| has_children | boolean      | `true` if this category has at least one subcategory. Only categories with `has_children: false` can be assigned to a product. |

Soft-deleted categories are excluded from `index`/`show`; products already assigned to a deleted category keep their `category_id` (set to `null` only if the category row is hard-deleted, which isn't exposed here). A category with subcategories cannot be deleted until those subcategories are moved or deleted first (**422**).

### Browsing the tree

`index` does **not** return the whole tree at once — it stays flat and paginated at each level, one folder at a time:

- Call it with no `parent_uuid` to get top-level categories only (`parent_id IS NULL`).
- When the seller opens a category, call it again with `parent_uuid` set to that category's `uuid` to get its direct children.
- Keep drilling down until an item's `has_children` is `false` — that's a leaf, and the only kind assignable to a product.

---

## GET /api/admin/product-categories

### Query params

| Field       | Type   | Required | Notes                                                              |
|-------------|--------|----------|---------------------------------------------------------------------|
| search      | string | No       | Filter by name (partial match)                                       |
| per_page    | int    | No       | Default 15                                                           |
| parent_uuid | string | No       | Omit for top-level categories; pass a category's `uuid` to list its direct children. Ignored if `leaf_only` is set. |
| leaf_only   | bool   | No       | Returns every leaf category (`has_children: false`) across the whole tree, flat, regardless of depth. Overrides the default top-level scoping and `parent_uuid` — use this to populate a "pick a category to assign a product to" list. |

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "data": [
      { "uuid": "cat_beverages", "name": "Beverages", "description": null, "image_url": null, "is_active": true, "parent_uuid": null, "has_children": true }
    ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

### Error responses

- **404** — `parent_uuid` was passed but doesn't match any category.

---

## POST /api/admin/product-categories

### Request body

| Field        | Type   | Required | Notes                                     |
|--------------|--------|----------|----------------------------------------------|
| name         | string | Yes      | Max 255 chars                                  |
| description  | string | No       |                                                 |
| image_url    | string | No       | Max 2048 chars. A direct link, not a file upload |
| is_active    | bool   | No       | Defaults to `true`                             |
| parent_uuid  | string | No       | Must reference an existing category. Omit/`null` to create a top-level category |

### Success response — 201

```json
{ "success": true, "message": "Product category created successfully", "data": { "uuid": "cat_soda", "name": "Soda", "description": null, "image_url": null, "is_active": true, "parent_uuid": "cat_beverages", "has_children": false } }
```

### Error responses

- **422** — validation failure (missing `name`, or `parent_uuid` doesn't exist).
- **404** — `parent_uuid` doesn't match any category (existence is also caught by validation as a 422; this only surfaces if the parent is deleted between validation and processing).

---

## GET /api/admin/product-categories/{uuid}

### Error responses

- **404** — `{ "success": false, "message": "Product category not found.", "data": null }`

---

## PUT /api/admin/product-categories/{uuid}

Same body as `POST` (full replace — always send `name`).

A category cannot be re-parented under itself or under one of its own subcategories (**422**).

### Error responses

Same as `POST`, plus **404**, plus:

- **422** — `{ "success": false, "message": "A category cannot be moved under itself or one of its own subcategories.", "data": null }`

---

## DELETE /api/admin/product-categories/{uuid}

### Success response — 200

```json
{ "success": true, "message": "Product category deleted successfully", "data": null }
```

### Error responses

- **404**
- **422** — `{ "success": false, "message": "This category still has subcategories. Move or delete them first.", "data": null }`

## Error status summary

| Status | Meaning               | Applies to             |
|--------|------------------------|---------------------------|
| 401    | Missing/invalid token  | all endpoints               |
| 404    | Category not found     | index (bad `parent_uuid`), show, update, destroy |
| 422    | Validation failure     | store, update, destroy       |

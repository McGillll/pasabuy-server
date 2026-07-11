# Settings API

Single-row business settings (shop name, contact info, address) used across the app — customer-facing order pages read it publicly, admins update it from "Settings". There is exactly one settings record; endpoints never take an identifier.

## Endpoints overview

| Method | Endpoint              | Auth required        | Description                        |
|--------|------------------------|------------------------|--------------------------------------|
| GET    | `/api/public/settings` | No                     | Get current business info            |
| PATCH  | `/api/admin/settings`  | Yes (admin ability)   | Update business info                 |

`PATCH /api/admin/settings` requires `Authorization: Bearer {token}` for a token issued with the `admin` ability (see `auth-api.md`).

## Response envelope

Every response — success or failure — uses:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { }
}
```

## Settings object

| Field         | Type          | Notes                                  |
|----------------|---------------|------------------------------------------|
| business_name  | string        | Required, always present                 |
| contact_email  | string\|null  |                                            |
| contact_phone  | string\|null  |                                            |
| address        | string\|null  |                                            |

There is no `uuid`/`id` in the response — this is a singleton resource, not addressable by identifier.

---

## GET /api/public/settings

No authentication required. Returns the current business info. Use on customer-facing order/share pages to display shop name and contact details.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "business_name": "My Pasabuy Shop",
    "contact_email": null,
    "contact_phone": null,
    "address": null
  }
}
```

### Error responses

- **404** — settings row missing (should not happen in normal operation; the row is seeded by migration):
  ```json
  { "success": false, "message": "Settings not found.", "data": null }
  ```

---

## PATCH /api/admin/settings

Requires `Authorization: Bearer {token}` with the `admin` ability. Updates the singleton settings row. All fields are sent every time (full replace, not partial merge) — the client should pre-fill the form from `GET /api/public/settings`.

### Request body

| Field         | Type   | Required | Notes            |
|----------------|--------|----------|-------------------|
| business_name  | string | Yes      | Max 255           |
| contact_email  | string | No       | Nullable, valid email, max 255 |
| contact_phone  | string | No       | Nullable, max 50  |
| address        | string | No       | Nullable          |

### Example request

```json
PATCH /api/admin/settings
{
  "business_name": "Neil's Pasabuy Store",
  "contact_email": "shop@pasabuy.test",
  "contact_phone": "+63 900 000 0000",
  "address": "123 Sample St., Quezon City"
}
```

### Success response — 200

```json
{
  "success": true,
  "message": "Settings updated successfully",
  "data": {
    "business_name": "Neil's Pasabuy Store",
    "contact_email": "shop@pasabuy.test",
    "contact_phone": "+63 900 000 0000",
    "address": "123 Sample St., Quezon City"
  }
}
```

### Error responses

- **401** — missing/invalid/expired token, or token missing the `admin` ability:
  ```json
  { "success": false, "message": "Unauthenticated.", "data": null }
  ```
- **403** — authenticated but token lacks the `admin` ability (customer token).
- **422** — validation failure (missing `business_name`, invalid `contact_email`, etc). Standard Laravel validation error shape.

---

## Error status summary

| Status | Meaning                            | Applies to              |
|--------|--------------------------------------|---------------------------|
| 401    | Missing/invalid/expired token        | PATCH /admin/settings     |
| 403    | Token lacks `admin` ability          | PATCH /admin/settings     |
| 404    | Settings row missing (unexpected)    | GET /public/settings      |
| 422    | Validation failure                   | PATCH /admin/settings     |

## Seed data

The `create_settings_table` migration inserts one default row: `business_name` = `"My Pasabuy Shop"`, all contact fields `null`. Update it via `PATCH /api/admin/settings` as an admin.

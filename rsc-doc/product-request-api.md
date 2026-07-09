# Product Request API

Customers submit free-form requests for products they'd like the seller to consider adding to a future pasabuy run (optionally tied to an existing schedule). Sellers (admin) review these and mark them as reviewed.

## Endpoints overview

| Method | Endpoint                                  | Description                              | Auth                          |
|--------|--------------------------------------------|-------------------------------------------|--------------------------------|
| GET    | `/api/admin/product-requests`             | List all product requests                  | admin                           |
| PATCH  | `/api/admin/product-requests/{uuid}/reviewed` | Mark a request as reviewed              | admin                           |
| GET    | `/api/customer/product-requests`          | List the authenticated customer's requests | customer (approved)             |
| POST   | `/api/customer/product-requests`          | Submit a new product request               | customer (approved)             |

`Accept: application/json`, `Authorization: Bearer {token}`. No file uploads are involved, so all bodies are JSON or form-urlencoded — no FormData/method-spoofing needed.

## Who can do what

- **Admin** (`abilities:admin`) — list all requests, mark a request as reviewed.
- **Customer** (`abilities:customer`, and must be `is_approved`) — list their own requests, submit new requests. An unapproved customer hitting either customer endpoint gets a 403 (see error summary).

## ProductRequest object

| Field             | Type          | Notes                                                                 |
|-------------------|---------------|------------------------------------------------------------------------|
| uuid              | string        | Public identifier                                                      |
| note              | string        | Free-form text describing the requested product                        |
| status            | string        | `open` \| `reviewed`                                                   |
| customer          | object\|null  | Present when loaded (admin `index` only) — `{ name, contact_number }`  |
| pasabuy_schedule  | object\|null  | Present when loaded and the request was tied to a schedule — `{ uuid, title }` |
| created_at        | datetime      |                                                                          |

---

## GET /api/admin/product-requests

Paginated list of all product requests, newest first, with `customer` and `pasabuy_schedule` loaded.

### Query parameters

| Field    | Type | Required | Notes                    |
|----------|------|----------|----------------------------|
| per_page | int  | No       | Defaults to 15             |

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "data": [
      {
        "uuid": "...",
        "note": "Do you carry oat milk?",
        "status": "open",
        "customer": { "name": "Juan Dela Cruz", "contact_number": "09171234567" },
        "pasabuy_schedule": { "uuid": "...", "title": "March Grocery Run" },
        "created_at": "..."
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

## PATCH /api/admin/product-requests/{uuid}/reviewed

Marks a request's status as `reviewed`. No request body.

### Success response — 200

```json
{ "success": true, "message": "Marked as reviewed", "data": { "uuid": "...", "note": "...", "status": "reviewed", "created_at": "..." } }
```

### Error responses

- **404** — no request with that uuid: `{ "success": false, "message": "Product request not found.", "data": null }`

## GET /api/customer/product-requests

Paginated list of the authenticated customer's own requests, newest first, with `pasabuy_schedule` loaded (no `customer` block — it's implicitly the caller).

### Query parameters

| Field    | Type | Required | Notes                    |
|----------|------|----------|----------------------------|
| per_page | int  | No       | Defaults to 15             |

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "data": [
      { "uuid": "...", "note": "...", "status": "open", "pasabuy_schedule": null, "created_at": "..." }
    ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

## POST /api/customer/product-requests

Submits a new product request on behalf of the authenticated customer. Status is always created as `open`.

### Request body

| Field                    | Type   | Required | Notes                                                        |
|---------------------------|--------|----------|-----------------------------------------------------------------|
| note                      | string | Yes      | Max 1000 characters                                              |
| pasabuy_schedule_uuid     | string | No       | If provided, must match an existing schedule; ties the request to it |

### Example request

```json
POST /api/customer/product-requests
{ "note": "Do you carry oat milk?", "pasabuy_schedule_uuid": "..." }
```

### Success response — 201

```json
{ "success": true, "message": "Request submitted successfully", "data": { "uuid": "...", "note": "Do you carry oat milk?", "status": "open", "created_at": "..." } }
```

### Error responses

- **404** — `pasabuy_schedule_uuid` provided but doesn't match any schedule: `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`
- **422** — `note` missing or exceeds 1000 characters (standard Laravel validation error envelope)
- **403** — authenticated customer is not yet `is_approved`: `{ "success": false, "message": "Your account is pending admin approval.", "data": null }`

## Error status summary

| Status | Meaning                                   | Applies to                          |
|--------|---------------------------------------------|----------------------------------------|
| 401    | Missing/invalid token                        | all endpoints                          |
| 403    | Customer not yet approved                     | customer `index`, `store`              |
| 404    | Request not found / schedule not found        | `markReviewed`, `store`                |
| 422    | Validation failure (`note`)                    | `store`                                |

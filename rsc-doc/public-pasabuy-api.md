# Public Pasabuy API

Unauthenticated preview of a pasabuy schedule's shareable link. **No authentication** — anyone with the link can view the schedule and its products. Placing an order now requires a logged-in, admin-approved customer account — see `customer-api.md` for registration/login and the order-placement endpoint.

## Endpoints overview

| Method | Endpoint                                          | Description                              |
|--------|------------------------------------------------------|--------------------------------------------|
| GET    | `/api/public/pasabuy-schedules/{uuid}`                | View a schedule and its available products |

`Accept: application/json`.

## Response envelope

Standard `{success, message, data}` (see `auth-api.md`).

---

## GET /api/public/pasabuy-schedules/{uuid}

Returns the schedule regardless of its `status` — a `draft` or `closed` link still resolves so the frontend can show a friendly "not open" message instead of a dead link. **Check `data.is_open` before showing the login/order form.**

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "uuid": "e9400549-...",
    "title": "Grocery Run July",
    "description": null,
    "cutoff_at": null,
    "status": "open",
    "is_open": true,
    "products": [
      {
        "uuid": "a5a95025-...",
        "price": "25.00",
        "max_quantity": 2,
        "product": { "uuid": "abe9e0a5-...", "name": "Instant Noodles", "description": "Spicy pack", "image_url": null, "is_active": true }
      }
    ],
    "created_at": "..."
  }
}
```

Use each product entry's `product.uuid` (not the schedule-product `uuid`) as the identifier when placing an order — see `customer-api.md`.

### Error responses

- **404** — no schedule with that uuid: `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

## Error status summary

| Status | Meaning                                  | Applies to    |
|--------|---------------------------------------------|-----------------|
| 404    | Schedule not found                          | show |

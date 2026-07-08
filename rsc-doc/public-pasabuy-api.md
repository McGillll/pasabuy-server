# Public Pasabuy API

Unauthenticated preview of a pasabuy schedule's shareable link. **No authentication** — anyone with the link can view the schedule. Placing an order now requires a logged-in, admin-approved customer account — see `customer-api.md` for registration/login and the order-placement endpoint.

**This endpoint's `products` array is not the customer's order-form source.** It only lists products someone has already ordered on this schedule (see the reactive-attach note in `pasabuy-schedule-api.md`) — a brand new schedule has an empty array here even though it's fully orderable. The order form should be built from `GET /api/public/products` (see `product-api.md`), the full active catalog; use this endpoint just for the schedule's own fields (`title`, `status`, `is_open`, `cutoff_at`) plus, for any product already ordered, its confirmed `actual_price`/`product_status`.

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
        "actual_price": null,
        "product_status": "none",
        "price_confirmed_at": null,
        "product": { "uuid": "abe9e0a5-...", "name": "Instant Noodles", "description": "Spicy pack", "image_url": null, "is_active": true, "default_price": "25.00", "category": { "uuid": "...", "name": "Noodles & Canned Goods", "description": null, "is_active": true } }
      }
    ],
    "created_at": "..."
  }
}
```

`products` here only ever contains products already ordered on this schedule (see the note above) — do not use it to build the order form. For any entry that IS present, `product.default_price` is the catalog price and should be labeled as an estimate in the UI until the seller confirms in-store — never present it as final. Once the seller confirms (see `confirm-price` in `pasabuy-schedule-api.md`), `actual_price`/`product_status`/`price_confirmed_at` reflect the confirmed truth; show `actual_price` in place of `product.default_price` once `price_confirmed_at` is set. These fields are safe to expose publicly (pre-order informational data, not sensitive).

Use each product entry's `product.uuid` (not the schedule-product `uuid`) as the identifier when placing an order — see `customer-api.md`.

### Error responses

- **404** — no schedule with that uuid: `{ "success": false, "message": "Pasabuy schedule not found.", "data": null }`

## Error status summary

| Status | Meaning                                  | Applies to    |
|--------|---------------------------------------------|-----------------|
| 404    | Schedule not found                          | show |

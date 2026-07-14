# Invoice Setting API

Per-seller invoice rules: markup calculation and overdue compounding interest. Every seller (`admin`-ability user) has exactly one invoice-settings row, auto-created with sensible defaults on first access. Endpoints always operate on "my own settings" — there is no identifier in the URL.

## Endpoints overview

| Method | Endpoint                     | Auth required      | Description                       |
|--------|-------------------------------|----------------------|--------------------------------------|
| GET    | `/api/admin/invoice-settings`  | Yes (admin ability) | Get my invoice rules (auto-created if missing) |
| PATCH  | `/api/admin/invoice-settings`  | Yes (admin ability) | Update my invoice rules             |

## Response envelope

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { }
}
```

## Invoice Setting object

| Field                     | Type    | Default              | Notes |
|---------------------------|---------|-----------------------|-------|
| uuid                      | string  |                       | External identifier |
| markup_threshold_amount   | decimal | 1000.00               | Order total above this uses percentage markup, at/below uses the fixed markup |
| markup_percentage         | decimal | 10.00                 | Percent applied when total > threshold |
| markup_fixed_amount       | decimal | 100.00                | Flat amount applied when total ≤ threshold |
| due_days                  | integer | 15                    | Days after the `due_date_reference` event before the invoice is due |
| due_date_reference        | enum    | `delivery`            | One of `delivery` (count from the order's own `delivered_at`) or `schedule_finish` (count from the schedule's `finished_at`) — chooses which event starts the due-date clock |
| interest_rate             | decimal | 1.00                  | Percent compounded per period once overdue |
| interest_period           | enum    | `month`               | One of `day`, `week`, `month` — the compounding period unit |
| interest_base             | enum    | `outstanding_balance` | One of `outstanding_balance` (total minus payments so far) or `full_total` (ignores partial payments) |
| is_active                 | boolean | true                  | When false, no markup-driven interest is applied (markup itself always applies) |

---

## GET /api/admin/invoice-settings

Returns the authenticated seller's invoice rules. If the seller has never configured them, a row is created on the fly with the defaults above.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "uuid": "b3f1...",
    "markup_threshold_amount": "1000.00",
    "markup_percentage": "10.00",
    "markup_fixed_amount": "100.00",
    "due_days": 15,
    "due_date_reference": "delivery",
    "interest_rate": "1.00",
    "interest_period": "month",
    "interest_base": "outstanding_balance",
    "is_active": true
  }
}
```

---

## PATCH /api/admin/invoice-settings

Full replace — send every field each time (pre-fill the form from `GET`).

### Request body

| Field                     | Type    | Required | Notes |
|---------------------------|---------|----------|-------|
| markup_threshold_amount   | numeric | Yes      | Min 0 |
| markup_percentage         | numeric | Yes      | 0–100 |
| markup_fixed_amount       | numeric | Yes      | Min 0 |
| due_days                  | integer | Yes      | Min 1 |
| due_date_reference        | string  | Yes      | `delivery` or `schedule_finish` |
| interest_rate             | numeric | Yes      | Min 0 |
| interest_period           | string  | Yes      | `day`, `week`, or `month` |
| interest_base             | string  | Yes      | `outstanding_balance` or `full_total` |
| is_active                 | boolean | No       |       |

### Example request

```json
PATCH /api/admin/invoice-settings
{
  "markup_threshold_amount": 1000,
  "markup_percentage": 10,
  "markup_fixed_amount": 100,
  "due_days": 15,
  "due_date_reference": "delivery",
  "interest_rate": 1,
  "interest_period": "month",
  "interest_base": "outstanding_balance",
  "is_active": true
}
```

### Error responses

- **401** — missing/invalid token, or token missing the `admin` ability.
- **422** — validation failure.

## Error status summary

| Status | Meaning                        | Applies to                    |
|--------|----------------------------------|----------------------------------|
| 401    | Missing/invalid token, or wrong ability | GET/PATCH /admin/invoice-settings |
| 422    | Validation failure                | PATCH /admin/invoice-settings     |

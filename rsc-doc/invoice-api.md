# Invoice API

Computes an order's invoice breakdown — markup, due date, and overdue compounding interest — on the fly, using the seller's `invoice-settings` (see `invoice-setting-api.md`). Nothing is persisted; the numbers always reflect the current settings and recorded payments as of "now".

## Endpoints overview

| Method | Endpoint                               | Auth required      | Description                  |
|--------|------------------------------------------|----------------------|----------------------------------|
| GET    | `/api/admin/orders/{orderUuid}/invoice`  | Yes (admin ability) | Get computed invoice for an order |

## Calculation

1. `subtotal` = order's `total_amount`.
2. `markup_amount` = `subtotal > markup_threshold_amount ? subtotal * markup_percentage / 100 : markup_fixed_amount`.
3. `invoice_total` = `subtotal + markup_amount`.
4. `due_date` = the seller's chosen reference date + `due_days`. Which reference date is used depends on the seller's `due_date_reference` setting: `delivery` uses `order.delivered_at`, `schedule_finish` uses `schedule.finished_at`. `due_date` is `null` if that reference event hasn't happened yet — it does **not** fall back to the other date.
5. `amount_paid` = sum of the order's recorded payments.
6. `interest_base_amount` = `invoice_total - amount_paid` (if `interest_base` is `outstanding_balance`) or `invoice_total` (if `full_total`).
7. If `is_active`, `due_date` is set, `now() > due_date`, and `interest_base_amount > 0`: elapsed whole periods (`day`/`week`/`month`, per seller setting, minimum 1) compound at `interest_rate`% — `interest_amount = interest_base_amount * ((1 + rate/100)^periods - 1)`. Otherwise `interest_amount = 0`.
8. `total_payable` = `invoice_total + interest_amount`; `balance_due` = `total_payable - amount_paid`.

## Response envelope

```json
{
  "success": true,
  "message": "Success",
  "data": { }
}
```

## Invoice object

| Field            | Type          | Notes |
|-------------------|---------------|-------|
| subtotal          | number        | Order's `total_amount` |
| markup_amount     | number        |       |
| invoice_total     | number        | `subtotal + markup_amount` |
| due_date          | string\|null  | ISO 8601, null if the seller's chosen `due_date_reference` event hasn't happened yet |
| is_overdue        | boolean       | `now() > due_date` |
| elapsed_periods   | integer       | Whole compounding periods elapsed past due_date (0 if not overdue) |
| interest_amount   | number        |       |
| total_payable     | number        | `invoice_total + interest_amount` |
| amount_paid       | number        | Sum of recorded payments |
| balance_due       | number        | `total_payable - amount_paid` |

---

## GET /api/admin/orders/{orderUuid}/invoice

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "subtotal": 1500,
    "markup_amount": 150,
    "invoice_total": 1650,
    "due_date": "2026-08-01T00:00:00+00:00",
    "is_overdue": false,
    "elapsed_periods": 0,
    "interest_amount": 0,
    "total_payable": 1650,
    "amount_paid": 0,
    "balance_due": 1650
  }
}
```

### Error responses

- **401** — missing/invalid token, or token missing the `admin` ability.
- **404** — order not found:
  ```json
  { "success": false, "message": "Order not found.", "data": null }
  ```

## Error status summary

| Status | Meaning                        | Applies to                         |
|--------|----------------------------------|----------------------------------------|
| 401    | Missing/invalid token, or wrong ability | GET /admin/orders/{orderUuid}/invoice |
| 404    | Order not found                   | GET /admin/orders/{orderUuid}/invoice  |

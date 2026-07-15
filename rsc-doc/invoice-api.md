# Invoice API

Computes an order's invoice breakdown — markup, due date, and overdue compounding interest — on the fly, using the seller's `invoice-settings` (see `invoice-setting-api.md`). Nothing is persisted; the numbers always reflect the current settings and recorded payments as of "now".

## Endpoints overview

| Method | Endpoint                                   | Auth required                          | Description                            |
|--------|---------------------------------------------|------------------------------------------|----------------------------------------|
| GET    | `/api/admin/orders/{orderUuid}/invoice`      | Yes (admin ability, seller must own the order's schedule) | Get computed invoice for an order (seller view) |
| GET    | `/api/customer/orders/{uuid}/invoice`        | Yes (customer ability, approved, customer must own the order) | Get computed invoice for an order (customer view) |

Both endpoints return the identical response shape (`InvoiceResource`) — the customer is not shown a trimmed-down version.

**Ownership scoping:** the admin endpoint scopes the lookup to orders whose `pasabuy_schedule.user_id` matches the authenticated seller (mirrors the ownership check used by `PATCH /admin/orders/{uuid}/status`). Requesting an order that belongs to another seller now returns `404` (order not resolved outside the authenticated seller's scope) instead of returning any order by uuid. The customer endpoint scopes the lookup to orders whose `user_id` matches the authenticated customer, same as `GET /customer/orders/{uuid}`.

The backend does not gate on the pasabuy schedule's status — the customer endpoint may be called at any time. Values are simply less meaningful before the schedule finishes (e.g. `due_date` stays `null` until `finished_at` is set, if the seller's `due_date_reference` is `schedule_finish`). The frontend is expected to only surface/fetch this endpoint once the schedule is `finished`.

## Calculation

1. `subtotal` = order's `total_amount`.
2. `markup_amount` = `subtotal > markup_threshold_amount ? subtotal * markup_percentage / 100 : markup_fixed_amount`.
3. `total` = `subtotal + markup_amount`.
4. `due_date` = the seller's chosen reference date + `due_days`. Which reference date is used depends on the seller's `due_date_reference` setting: `delivery` uses `order.delivered_at`, `schedule_finish` uses `schedule.finished_at`. `due_date` is `null` if that reference event hasn't happened yet — it does **not** fall back to the other date.
5. `amount_paid` = sum of the order's recorded payments.
6. `interest_base_amount` = `total - amount_paid` (if `interest_base` is `outstanding_balance`) or `total` (if `full_total`).
7. If `is_active`, `due_date` is set, `now() > due_date`, and `interest_base_amount > 0`: elapsed whole periods (`day`/`week`/`month`, per seller setting, minimum 1) compound at `interest_rate`% per period, producing one `interest_lines` entry per elapsed period with a running total. Otherwise `interest_lines` is empty and `interest_total` is `0`.
8. `grand_total` = running total after the last interest line, or `total` if there are no interest lines; `balance_due` = `grand_total - amount_paid`.

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
| items             | array         | `product_name`, `quantity` (incl. bonus), `unit_price`, `line_total` per order item |
| subtotal          | number        | Order's `total_amount` |
| markup_amount     | number        |       |
| total             | number        | `subtotal + markup_amount` |
| due_date          | string\|null  | ISO 8601, null if the seller's chosen `due_date_reference` event hasn't happened yet |
| is_overdue        | boolean       | `now() > due_date` |
| interest_base     | string        | Seller setting: `outstanding_balance` or `full_total` |
| interest_lines    | array         | One entry per elapsed compounding period: `period`, `label`, `interest_amount`, `running_total` |
| interest_total    | number        | Sum of `interest_lines[].interest_amount` |
| grand_total       | number        | `total` plus compounded interest |
| payments          | array         | Recorded payments: `paid_at`, `amount`, `method`, `reference_number` |
| amount_paid       | number        | Sum of recorded payments |
| balance_due       | number        | `grand_total - amount_paid` |

---

## GET /api/admin/orders/{orderUuid}/invoice

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      { "product_name": "Item A", "quantity": 3, "unit_price": 500, "line_total": 1500 }
    ],
    "subtotal": 1500,
    "markup_amount": 150,
    "total": 1650,
    "due_date": "2026-08-01T00:00:00+00:00",
    "is_overdue": false,
    "interest_base": "outstanding_balance",
    "interest_lines": [],
    "interest_total": 0,
    "grand_total": 1650,
    "payments": [],
    "amount_paid": 0,
    "balance_due": 1650
  }
}
```

### Error responses

- **401** — missing/invalid token, or token missing the `admin` ability.
- **403** — order exists but its schedule belongs to a different seller:
  ```json
  { "success": false, "message": "You do not own this order.", "data": null }
  ```
- **404** — order not found:
  ```json
  { "success": false, "message": "Order not found.", "data": null }
  ```

## GET /api/customer/orders/{uuid}/invoice

Same response shape as the admin endpoint above (`InvoiceResource`, no trimming).

### Error responses

- **401** — missing/invalid token, token missing the `customer` ability, or customer not yet approved.
- **404** — order not found, or the order does not belong to the authenticated customer:
  ```json
  { "success": false, "message": "Order not found.", "data": null }
  ```

## Error status summary

| Status | Meaning                                    | Applies to                              |
|--------|----------------------------------------------|------------------------------------------|
| 401    | Missing/invalid token, or wrong ability       | Both endpoints                           |
| 403    | Order belongs to a different seller           | GET /admin/orders/{orderUuid}/invoice    |
| 404    | Order not found (or not owned by requester)    | Both endpoints                           |

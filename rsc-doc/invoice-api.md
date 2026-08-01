# Invoice API

Computes an order's invoice breakdown — markup, due date, and overdue compounding interest — using the seller's invoice rules and recorded payments as of "now". While the order's schedule is still `open`/`closed`, the rules are read live from `invoice-settings` (see `invoice-setting-api.md`), so seller edits immediately affect the preview. Once the schedule is `finished`, a snapshot of the invoice rules taken at finish time is used instead — later edits to `invoice-settings` no longer change the due date or markup for orders on already-finished schedules. Interest ledger calculations still walk forward from "now" using that frozen rule set.

## Endpoints overview

| Method | Endpoint                                   | Auth required                          | Description                            |
|--------|---------------------------------------------|------------------------------------------|----------------------------------------|
| GET    | `/api/admin/orders/{orderUuid}/invoice`      | Yes (admin ability, seller must own the order's schedule) | Get computed invoice for an order (seller view) |
| GET    | `/api/customer/orders/{uuid}/invoice`        | Yes (customer ability, approved, customer must own the order) | Get computed invoice for an order (customer view) |

Both endpoints return the identical response shape (`InvoiceResource`) — the customer is not shown a trimmed-down version.

**Ownership scoping:** the admin endpoint scopes the lookup to orders whose `pasabuy_schedule.user_id` matches the authenticated seller (mirrors the ownership check used by `PATCH /admin/orders/{uuid}/status`). Requesting an order that belongs to another seller now returns `404` (order not resolved outside the authenticated seller's scope) instead of returning any order by uuid. The customer endpoint scopes the lookup to orders whose `user_id` matches the authenticated customer, same as `GET /customer/orders/{uuid}`.

The backend does not gate on the pasabuy schedule's status — the customer endpoint may be called at any time. Values are simply less meaningful before the schedule finishes (`due_date` stays `null` until the seller sets `pasabuy_schedules.due_date` at finish time). The frontend is expected to only surface/fetch this endpoint once the schedule is `finished`.

## Calculation

1. `subtotal` = order's `total_amount`.
2. `markup_amount` = `subtotal > markup_threshold_amount ? subtotal * markup_percentage / 100 : markup_fixed_amount`.
3. `handling_fee` = seller's `handling_fee` setting, added flat on every order regardless of threshold.
4. `total` = `subtotal + markup_amount + handling_fee`.
5. `due_date` = the order's pasabuy schedule's `due_date` directly. `due_date` is `null` until the seller finishes the schedule and sets it — it is not computed from any offset.
6. `amount_paid` = sum of the order's recorded payments.
7. If `is_active`, `due_date` is set, and `now() > due_date`: elapsed whole periods (`day`/`week`/`month`, per seller setting, minimum 1) are walked from the due date. Otherwise `interest_lines` is empty, `interest_total` is `0`, `grand_total` = `total`, and `balance_due` = `total - amount_paid`.
8. **`full_total` mode** (interest ignores payments entirely): each elapsed period compounds `interest_rate`% on the running total, which only ever grows — one `interest_lines` entry per period (`type: "interest"`, labeled by calendar date for `day` periods). `grand_total` = running total after the last entry; `balance_due` = `grand_total - amount_paid`.
9. **`outstanding_balance` mode** (interest never accrues on money already paid): the running total starts at `total` and the timeline is walked period by period from the due date. Any payment received on/before the due date is netted first. Within each period, that period's interest compounds first on the balance as it stood entering the period (`type: "interest"`), then any payment received during that period's window is applied (`type: "payment"`, negative `amount`) — reducing the balance for the next period's interest, not this one. `grand_total` = running total after the last entry, which already reflects every payment — `balance_due` = `grand_total` directly (no second subtraction).

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
| items             | array         | `product_name`, `quantity` (paid quantity only, excludes bonus), `bonus_quantity`, `unit_price`, `line_total` per order item. Out-of-stock items are omitted entirely (they contribute `0` to every total and showing their pre-out-of-stock `unit_price` next to a `line_total` of `0` reads as a billing error) |
| subtotal          | number        | Order's `total_amount` |
| markup_amount     | number        |       |
| handling_fee      | number        | Flat fee from the seller's invoice settings, added on every order |
| total             | number        | `subtotal + markup_amount + handling_fee` |
| due_date          | string\|null  | ISO 8601, null until the schedule is finished (the seller sets it mandatorily on finish) |
| is_overdue        | boolean       | `now() > due_date` |
| interest_base     | string        | Seller setting: `outstanding_balance` or `full_total` |
| interest_lines    | array         | Chronological ledger entries: `type` (`interest` or `payment`), `label`, `amount` (signed; negative for payments), `running_total`. Empty if not overdue |
| interest_total    | number        | Sum of `interest_lines[]` entries where `type` is `interest` |
| grand_total       | number        | `total` plus compounded interest |
| payments          | array         | Recorded payments: `paid_at`, `amount`, `method`, `reference_number` |
| amount_paid       | number        | Sum of recorded payments |
| balance_due       | number        | `full_total`/not-overdue: `grand_total - amount_paid`. `outstanding_balance` while overdue: `grand_total` (already net of every payment via the ledger) |

---

## GET /api/admin/orders/{orderUuid}/invoice

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      { "product_name": "Item A", "quantity": 2, "bonus_quantity": 1, "unit_price": 500, "line_total": 1000 }
    ],
    "subtotal": 1500,
    "markup_amount": 150,
    "handling_fee": 0,
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

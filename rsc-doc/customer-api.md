# Customer API

Customer registration, admin approval, and everything a logged-in customer can do: place an order, view/edit their own orders. Login itself reuses the shared Auth API (`POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout` — see `auth-api.md`); this doc covers what's customer-specific.

## Who can do what

- **Anyone** (no auth) can register via `POST /api/public/customers/register`.
- **Any logged-in customer**, even one pending approval, can call `/auth/login`, `/auth/me`, `/auth/logout` — this lets the frontend show a "pending approval" screen instead of a dead login.
- **Only an approved customer** (`is_approved: true`) can place orders, edit orders, or view their own orders. Every route below except registration requires `Authorization: Bearer {token}` from a token whose ability is `customer`, **and** `is_approved: true` — otherwise every one of them returns **403** with `"Your account is pending admin approval."`
- **Only an admin** can list customers and approve/reject them (`admin/customers/*`) — see the admin section at the bottom.

## Endpoints overview

| Method | Endpoint                                                    | Auth required            | Description                        |
|--------|--------------------------------------------------------------|---------------------------|--------------------------------------|
| POST   | `/api/public/customers/register`                              | No                         | Register a new customer account (pending approval) |
| GET    | `/api/customer/pasabuy-schedules`                              | Yes (customer, approved)  | Browse open pasabuy schedules available to order from |
| POST   | `/api/customer/pasabuy-schedules/{scheduleUuid}/orders`       | Yes (customer, approved)  | Place an order against an open schedule |
| GET    | `/api/customer/orders`                                        | Yes (customer, approved)  | List the authenticated customer's own orders |
| GET    | `/api/customer/orders/{uuid}`                                 | Yes (customer, approved)  | View one of the authenticated customer's own orders |
| PUT    | `/api/customer/orders/{uuid}`                                 | Yes (customer, approved)  | Replace the items on a still-editable order |
| PATCH  | `/api/customer/orders/{uuid}/cancel`                          | Yes (customer, approved)  | Cancel an order outright (only while `pending`, and while the schedule is still open) |
| GET    | `/api/admin/customers`                                        | Yes (admin)                | List customers (paginated, filterable) |
| GET    | `/api/admin/customers/{uuid}/orders`                           | Yes (admin)                | List every order placed by one customer |
| GET    | `/api/admin/customers/top-buyers`                              | Yes (admin)                | Top 5 customers by total spend, across all of the seller's schedules, scoped to a calendar month (defaults to current) |
| POST   | `/api/admin/customers/{uuid}/approve`                          | Yes (admin)                | Approve a pending customer |
| POST   | `/api/admin/customers/{uuid}/reject`                           | Yes (admin)                | Revoke a customer's approval |

All requests send `Accept: application/json`; authenticated ones also send `Authorization: Bearer {token}`. `POST /api/public/customers/register` is rate-limited to 5/minute per IP (`throttle:register`).

## Response envelope

Standard `{success, message, data}` (see `auth-api.md`).

## Customer object

Returned by admin-side customer endpoints (a customer is just a `User` with `role: "customer"` — see `auth-api.md`'s User object for the shape returned by `/auth/me`).

| Field          | Type          | Notes                                    |
|----------------|---------------|--------------------------------------------|
| uuid           | string        | Public identifier                          |
| first_name     | string        |                                              |
| last_name      | string        |                                              |
| email          | string        |                                              |
| contact_number | string\|null  |                                              |
| messenger_name | string\|null  | Facebook Messenger display name             |
| is_approved    | boolean       |                                              |
| approved_at    | datetime\|null|                                              |
| terms_accepted_at | datetime\|null | When the customer accepted the Terms of Service / Privacy Policy |
| created_at     | datetime      | When the customer registered                |

## Order object

Same shape as the admin `Order` object in `order-api.md`, including `is_editable`, `is_cancellable`, and (when loaded) `pasabuy_schedule`. See `order-api.md` for the full field table and the two-phase-pricing flow. Once a schedule closes, the customer sees a read-only cart summary (polled every 30s) with no cancel action; if the seller needs to correct items after that point, they edit the order directly (see `order-api.md`'s "Seller order-item editor" section).

---

## POST /api/public/customers/register

### Request body

| Field                 | Type   | Required | Notes                          |
|-----------------------|--------|----------|-----------------------------------|
| first_name            | string | Yes      |                                     |
| last_name             | string | Yes      |                                     |
| email                 | string | Yes      | Must be unique across all users    |
| password              | string | Yes      | Min 8 chars, must be confirmed (`password_confirmation`) |
| contact_number        | string | No       |                                     |
| messenger_name        | string | No       | Facebook Messenger display name    |
| terms_accepted        | boolean| Yes      | Must be `true` — customer must accept the Terms of Service / Privacy Policy to register |

### Example request

```json
POST /api/public/customers/register
{
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "contact_number": "09171234567",
  "terms_accepted": true
}
```

### Success response — 201

```json
{
  "success": true,
  "message": "Registration submitted. Please wait for admin approval.",
  "data": { "uuid": "...", "first_name": "Juan", "last_name": "Dela Cruz", "email": "juan@example.com", "contact_number": "09171234567", "messenger_name": null, "is_approved": false, "approved_at": null, "terms_accepted_at": "2026-07-21T12:00:00.000000Z" }
}
```

The seller verifies the applicant manually, outside the system, before approving — there is no automated verification step. `terms_accepted_at` is stamped server-side at registration time and is not accepted as an input field.

### Error responses

- **422** — validation failure (including `"You must accept the Terms of Service and Privacy Policy to register."` if `terms_accepted` is missing/false), or `"An account with this email already exists."`
- **429** — rate limited (5/minute per IP).

---

## GET /api/customer/pasabuy-schedules

Lists pasabuy schedules currently open for ordering (paginated), so a customer can browse and pick one before placing an order. Only schedules with `status: "open"` **and** (no `pasabuy_schedule_date` or that date still in the future) are returned — i.e. only schedules where `is_open` would be `true`. Closed/finished schedules and schedules past their cutoff date never appear here.

### Query params

| Field           | Type   | Required | Notes                                                       |
|-----------------|--------|----------|--------------------------------------------------------------|
| page            | int    | No       | Default `1`                                                   |
| per_page        | int    | No       | Default `15`                                                   |
| search          | string | No       | Matches against `title`                                       |
| month           | int    | No       | Filters by `pasabuy_schedule_date` month                       |
| year            | int    | No       | Filters by `pasabuy_schedule_date` year                        |

### Success response — 200

```json
{
  "success": true,
  "message": null,
  "data": {
    "data": [ { "uuid": "...", "title": "Grocery Run", "description": "...", "pasabuy_schedule_date": "2026-07-20", "status": "open", "is_open": true, "seller_name": "Maria Santos", "product_count": 12, "created_at": "..." } ],
    "current_page": 1,
    "last_page": 2,
    "total": 18
  }
}
```

`products` is omitted here (not loaded) — to view a single schedule's full product list before ordering, use the existing public single-schedule lookup `GET /api/public/pasabuy-schedules/{uuid}` (see `public-pasabuy-api.md`), which returns the full `products` array.

### Error responses

- **403** — `"Your account is pending admin approval."`

---

## POST /api/customer/pasabuy-schedules/{scheduleUuid}/orders

Replaces the old anonymous public order-placement endpoint. Creates the order and its items in one call, owned by the authenticated customer — `customer_name`/`customer_contact` are snapshotted from the customer's account, not sent in the request body. The server computes `subtotal`/`total_amount` itself from each product's current schedule price.

**One active order per customer per schedule.** A customer can only have one `pending`/`confirmed` order against a given schedule at a time — placing a second one returns **422**. To change items, use `PUT /api/customer/orders/{uuid}` on the existing order instead (see below). A new order is only allowed again if the previous one was `cancelled`.

**No new orders while any order is unpaid.** If the customer has any `pending`/`confirmed` order (in any schedule) whose `payment_status` is not `paid`, placing a new order anywhere returns **422**. The customer must settle (or the seller must cancel) the outstanding order first.

### Request body (JSON)

| Field                            | Type    | Required | Notes                                      |
|-----------------------------------|---------|----------|-----------------------------------------------|
| items                             | array   | Yes      | At least 1 entry                                |
| items[].product_uuid              | string  | Yes      | The `product.uuid` from the schedule preview, not the schedule-product uuid |
| items[].quantity                  | int     | Yes      | >= 1                                            |

### Example request

```json
POST /api/customer/pasabuy-schedules/e9400549-.../orders
{ "items": [ { "product_uuid": "abe9e0a5-...", "quantity": 2 } ] }
```

### Success response — 201

```json
{ "success": true, "message": "Order placed successfully", "data": { "uuid": "...", "customer_name": "Juan Dela Cruz", "customer_contact": "09171234567", "status": "pending", "total_amount": "50.00", "is_editable": true, "is_cancellable": true, "items": [ { "uuid": "...", "product_name": "Instant Noodles", "estimated_price": "25.00", "actual_price": null, "is_out_of_stock": false, "quantity": 2, "bonus_quantity": 0, "subtotal": "50.00" } ], "created_at": "..." } }
```

### Error responses

- **403** — `"Your account is pending admin approval."`
- **404** — no schedule with that uuid.
- **422** — `"This pasabuy is closed for orders."` / `"You already have an order for this pasabuy. Edit your existing order instead."` / `"You have an unpaid order pending. Please settle it before placing a new order."` / `"Product {uuid} is not available in this pasabuy."` / standard field validation.

---

## GET /api/customer/orders

### Query params

| Field    | Type   | Required | Notes       |
|----------|--------|----------|----------------|
| per_page | int    | No       | Default 15      |
| status   | string | No       | `pending` \| `confirmed` \| `cancelled` |
| payment_status | string | No | `unpaid` \| `partially_paid` \| `paid` |
| delivery_status | string | No | `not_delivered` \| `delivered` |
| date_from | date (`Y-m-d`) | No | Restrict to orders with `created_at` on/after this date (start of day) |
| date_to | date (`Y-m-d`) | No | Restrict to orders with `created_at` on/before this date (end of day) |
| schedule_title | string | No | Case-insensitive match against the parent pasabuy schedule's `title` |

### Success response — 200

Same paginator shape as `product-api.md`'s index endpoint, with `Order` objects in `data.data`. `amount_paid`/`amount_due` are now populated here too (same calculation as `order-api.md`'s admin list).

---

## GET /api/customer/orders/{uuid}

### Success response — 200

One `Order` object (only if it belongs to the authenticated customer).

### Error responses

- **404** — `"Order not found."` — also returned if the uuid belongs to a different customer (ownership is never leaked).

---

## PUT /api/customer/orders/{uuid}

Full edit: replaces every item on the order (add/remove/change quantity) in one call, same validation as placing a new order.

Only allowed while `status` is `pending` **and** the parent schedule is still open and before cutoff (matches `is_editable` on the order object). Once the schedule closes, this always returns 422 — the customer's list is final.

### Request body (JSON)

Same shape as the order-placement endpoint's `items` field.

### Example request

```json
PUT /api/customer/orders/36d53541-.../
{ "items": [ { "product_uuid": "abe9e0a5-...", "quantity": 3 } ] }
```

### Success response — 200

```json
{ "success": true, "message": "Order updated successfully", "data": { "uuid": "...", "status": "pending", "total_amount": "75.00", "is_editable": true, "items": [ ... ], "created_at": "..." } }
```

### Error responses

- **403** — `"Your account is pending admin approval."`
- **404** — `"Order not found."` (doesn't exist, or not owned by this customer).
- **422** — `"This order can no longer be edited."` / `"This pasabuy is closed for orders."` / product-availability messages (same as order placement).

---

## PATCH /api/customer/orders/{uuid}/cancel

Cancels the order outright — the first customer-initiated cancel action (previously only admins could cancel). Allowed while `status` is `pending` **and** the parent schedule is still open/before cutoff (i.e. whenever `is_cancellable` is `true`).

No request body.

### Example request

```
PATCH /api/customer/orders/36d53541-.../cancel
```

### Success response — 200

```json
{ "success": true, "message": "Order cancelled", "data": { "uuid": "...", "status": "cancelled", "is_cancellable": false, "is_editable": false, "items": [ ... ], "created_at": "..." } }
```

### Error responses

- **404** — `"Order not found."` (doesn't exist, or not owned by this customer).
- **422** — `"This order can no longer be cancelled."` (already `confirmed` or `cancelled`).
- **422** — `"This pasabuy is closed for orders."` (schedule is no longer open/before cutoff).

---

## GET /api/admin/customers

Admin-only. Paginated list of customer accounts.

### Query params

| Field        | Type    | Required | Notes                          |
|--------------|---------|----------|------------------------------------|
| search       | string  | No       | Matches first/last name or email (partial) |
| is_approved  | boolean | No       | Filter to pending (`false`) or approved (`true`) |
| per_page     | int     | No       | Default 15                          |

### Success response — 200

Paginator of `Customer` objects.

---

## GET /api/admin/customers/{uuid}/orders

Admin-only. Every order the customer has ever placed, across all schedules, newest first — each with its `items` and `pasabuy_schedule` loaded.

### Success response — 200

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "uuid": "36d53541-...",
      "customer_name": "Juan Dela Cruz",
      "customer_contact": "09171234567",
      "status": "confirmed",
      "total_amount": "50.00",
      "is_editable": false,
      "is_cancellable": false,
      "pasabuy_schedule": { "uuid": "e9400549-...", "title": "Grocery Run July", "status": "closed", "is_open": false, "created_at": "..." },
      "items": [ { "uuid": "...", "product_uuid": "abe9e0a5-...", "product_name": "Instant Noodles", "estimated_price": "25.00", "actual_price": "25.00", "is_out_of_stock": false, "quantity": 2, "bonus_quantity": 0, "subtotal": "50.00" } ],
      "created_at": "..."
    }
  ]
}
```

Not paginated — returns the customer's full order history in one array, same as `GET /api/admin/pasabuy-schedules/{uuid}/orders` in `pasabuy-schedule-api.md`.

### Error responses

- **404** — `"Customer not found."`

---

## GET /api/admin/customers/top-buyers

Admin-only. Returns the seller's top 5 customers by total spend (`pasabuy_orders.total_amount` summed), aggregated across all of the seller's pasabuy schedules and scoped to `pending`/`confirmed` orders only (cancelled excluded, same as `order-api.md`'s `LIVE_STATUSES`), within a single calendar month (defaults to the current month). Grouped by the order's real `user_id` FK, not the denormalized `customer_name` snapshot.

### Query params

| Field | Type | Required | Notes            |
|-------|------|----------|----------------------|
| limit | int  | No       | Default `5`           |
| month | int  | No       | 1-12. Defaults to the current month. |
| year  | int  | No       | Defaults to the current year. |

### Success response — 200

```json
{
  "success": true,
  "message": null,
  "data": [
    { "uuid": "...", "name": "Juan Dela Cruz", "total_spend": "4500.00", "order_count": 6 }
  ]
}
```

Not paginated — fixed top-N list.

---

## POST /api/admin/customers/{uuid}/approve

Flips `is_approved` to `true` and stamps `approved_at`. The seller does this only after manually verifying the applicant outside the system.

### Success response — 200

```json
{ "success": true, "message": "Customer approved successfully", "data": { "uuid": "...", "is_approved": true, "approved_at": "..." } }
```

### Error responses

- **404** — no customer with that uuid.

---

## POST /api/admin/customers/{uuid}/reject

Sets `is_approved` back to `false` and clears `approved_at` — revokes access without deleting the account (e.g. to suspend a previously-approved customer).

### Success response — 200

```json
{ "success": true, "message": "Customer rejected successfully", "data": { "uuid": "...", "is_approved": false, "approved_at": null } }
```

### Error responses

- **404** — no customer with that uuid.

## Error status summary

| Status | Meaning                                       | Applies to                              |
|--------|------------------------------------------------|--------------------------------------------|
| 401    | Missing/invalid/expired token                   | all authenticated endpoints                 |
| 403    | Customer account pending admin approval          | all customer endpoints except registration  |
| 404    | Resource not found or not owned                 | orders show/update/cancel, admin approve/reject/orders |
| 422    | Validation failure or business-rule rejection    | register, order placement/edit/cancel |
| 429    | Rate limited                                     | register (5/min/IP)                         |

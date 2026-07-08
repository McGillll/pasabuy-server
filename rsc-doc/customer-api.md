# Customer API

Customer registration, admin approval, and everything a logged-in customer can do: place an order, view/edit their own orders, and submit product suggestions. Login itself reuses the shared Auth API (`POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout` — see `auth-api.md`); this doc covers what's customer-specific.

## Who can do what

- **Anyone** (no auth) can register via `POST /api/public/customers/register`.
- **Any logged-in customer**, even one pending approval, can call `/auth/login`, `/auth/me`, `/auth/logout` — this lets the frontend show a "pending approval" screen instead of a dead login.
- **Only an approved customer** (`is_approved: true`) can place orders, edit orders, view their own orders, or submit product requests. Every route below except registration requires `Authorization: Bearer {token}` from a token whose ability is `customer`, **and** `is_approved: true` — otherwise every one of them returns **403** with `"Your account is pending admin approval."`
- **Only an admin** can list customers and approve/reject them (`admin/customers/*`) — see the admin section at the bottom.

## Endpoints overview

| Method | Endpoint                                                    | Auth required            | Description                        |
|--------|--------------------------------------------------------------|---------------------------|--------------------------------------|
| POST   | `/api/public/customers/register`                              | No                         | Register a new customer account (pending approval) |
| POST   | `/api/customer/pasabuy-schedules/{scheduleUuid}/orders`       | Yes (customer, approved)  | Place an order against an open schedule |
| GET    | `/api/customer/orders`                                        | Yes (customer, approved)  | List the authenticated customer's own orders |
| GET    | `/api/customer/orders/{uuid}`                                 | Yes (customer, approved)  | View one of the authenticated customer's own orders |
| PUT    | `/api/customer/orders/{uuid}`                                 | Yes (customer, approved)  | Replace the items on a still-editable order |
| PATCH  | `/api/customer/orders/{uuid}/cancel`                          | Yes (customer, approved)  | Cancel an order outright (`pending` or `needs_confirmation`) |
| PATCH  | `/api/customer/orders/{uuid}/items/{itemUuid}/resolve`        | Yes (customer, approved)  | Accept (optionally adjusting quantity) or remove one item flagged for price confirmation |
| GET    | `/api/customer/product-requests`                              | Yes (customer, approved)  | List the authenticated customer's own product requests |
| POST   | `/api/customer/product-requests`                              | Yes (customer, approved)  | Submit a product suggestion |
| GET    | `/api/admin/customers`                                        | Yes (admin)                | List customers (paginated, filterable) |
| GET    | `/api/admin/customers/{uuid}/orders`                           | Yes (admin)                | List every order placed by one customer |
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
| created_at     | datetime      | When the customer registered                |

## Order object

Same shape as the admin `Order` object in `order-api.md`, including `is_editable`, `is_cancellable`, `needs_confirmation_count`, and (when loaded) `pasabuy_schedule`. See `order-api.md` for the full field table and the `needs_confirmation` two-phase-pricing flow — this doc only covers the customer-side actions for resolving it.

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

### Example request

```json
POST /api/public/customers/register
{
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "contact_number": "09171234567"
}
```

### Success response — 201

```json
{
  "success": true,
  "message": "Registration submitted. Please wait for admin approval.",
  "data": { "uuid": "...", "first_name": "Juan", "last_name": "Dela Cruz", "email": "juan@example.com", "contact_number": "09171234567", "messenger_name": null, "is_approved": false, "approved_at": null }
}
```

The seller verifies the applicant manually, outside the system, before approving — there is no automated verification step.

### Error responses

- **422** — validation failure, or `"An account with this email already exists."`
- **429** — rate limited (5/minute per IP).

---

## POST /api/customer/pasabuy-schedules/{scheduleUuid}/orders

Replaces the old anonymous public order-placement endpoint. Creates the order and its items in one call, owned by the authenticated customer — `customer_name`/`customer_contact` are snapshotted from the customer's account, not sent in the request body. The server computes `subtotal`/`total_amount` itself from each product's current schedule price.

**One active order per customer per schedule.** A customer can only have one `pending`/`confirmed` order against a given schedule at a time — placing a second one returns **422**. To change items, use `PUT /api/customer/orders/{uuid}` on the existing order instead (see below). A new order is only allowed again if the previous one was `cancelled`.

### Request body (JSON)

| Field                            | Type    | Required | Notes                                      |
|-----------------------------------|---------|----------|-----------------------------------------------|
| items                             | array   | Yes      | At least 1 entry                                |
| items[].product_uuid              | string  | Yes      | The `product.uuid` from the schedule preview, not the schedule-product uuid |
| items[].quantity                  | int     | Yes      | >= 1                                            |
| items[].max_price                 | number  | No       | >= 0 — the ceiling the customer is willing to pay if the seller's in-store price comes in higher than the estimate |
| items[].auto_buy_if_under_max     | boolean | No       | If true, a price increase up to `max_price` is auto-applied without asking again; otherwise the item is always flagged for confirmation on any increase |

### Example request

```json
POST /api/customer/pasabuy-schedules/e9400549-.../orders
{ "items": [ { "product_uuid": "abe9e0a5-...", "quantity": 2, "max_price": 30, "auto_buy_if_under_max": true } ] }
```

### Success response — 201

```json
{ "success": true, "message": "Order placed successfully", "data": { "uuid": "...", "customer_name": "Juan Dela Cruz", "customer_contact": "09171234567", "status": "pending", "total_amount": "50.00", "is_editable": true, "is_cancellable": true, "items": [ { "uuid": "...", "product_name": "Instant Noodles", "estimated_price": "25.00", "actual_price": null, "is_bonus": false, "confirmation_status": "none", "max_price": "30.00", "auto_buy_if_under_max": true, "quantity": 2, "subtotal": "50.00" } ], "created_at": "..." } }
```

### Error responses

- **403** — `"Your account is pending admin approval."`
- **404** — no schedule with that uuid.
- **422** — `"This pasabuy is closed for orders."` / `"You already have an order for this pasabuy. Edit your existing order instead."` / `"Product {uuid} is not available in this pasabuy."` / `"Only {n} of \"{product name}\" remaining."` / standard field validation.

---

## GET /api/customer/orders

### Query params

| Field    | Type | Required | Notes       |
|----------|------|----------|----------------|
| per_page | int  | No       | Default 15      |

### Success response — 200

Same paginator shape as `product-api.md`'s index endpoint, with `Order` objects in `data.data`.

---

## GET /api/customer/orders/{uuid}

### Success response — 200

One `Order` object (only if it belongs to the authenticated customer).

### Error responses

- **404** — `"Order not found."` — also returned if the uuid belongs to a different customer (ownership is never leaked).

---

## PUT /api/customer/orders/{uuid}

Full edit: replaces every item on the order (add/remove/change quantity) in one call, same validation/stock-cap rules as placing a new order — except this order's own current quantities are excluded from the stock-cap tally against itself.

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
- **422** — `"This order can no longer be edited."` / `"This pasabuy is closed for orders."` / product-availability and stock-cap messages (same as order placement).

---

## PATCH /api/customer/orders/{uuid}/cancel

Cancels the order outright — the first customer-initiated cancel action (previously only admins could cancel). Allowed while `status` is `pending` or `needs_confirmation` (i.e. whenever `is_cancellable` is `true`); frees up the order's items against the schedule's `max_quantity` stock cap immediately.

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

---

## PATCH /api/customer/orders/{uuid}/items/{itemUuid}/resolve

Resolves one item flagged `confirmation_status: 'pending'` after the seller confirmed a price increase (or marked the item out of stock) in-store. Only valid while the order's `status` is `needs_confirmation` and the target item is `pending`. Once every pending item on the order has been resolved, the order's `status` automatically reverts to `pending` (ready for the seller's normal confirm/cancel).

### Request body

| Field    | Type    | Required | Notes                                        |
|----------|---------|----------|---------------------------------------------|
| action   | string  | Yes      | `accept` \| `remove`                          |
| quantity | int     | No       | Only meaningful with `accept` — lets the customer reduce (or otherwise change) the quantity while accepting the new price; defaults to the item's current quantity if omitted. Still validated against the product's `max_quantity` stock cap |

- **`accept`** — locks in the seller's confirmed `actual_price` (at the given or existing quantity), sets `confirmation_status: 'accepted'`, and recomputes `subtotal`/`total_amount`.
- **`remove`** — sets `confirmation_status: 'removed'`, zeroes the item's `subtotal`, and recomputes `total_amount`. The item stays on the order (for history) but no longer contributes to the total.

### Example request

```
PATCH /api/customer/orders/36d53541-.../items/91057f89-.../resolve
{ "action": "accept", "quantity": 1 }
```

### Success response — 200

```json
{ "success": true, "message": "Order item updated", "data": { "uuid": "...", "status": "pending", "total_amount": "130.00", "items": [ { "uuid": "91057f89-...", "confirmation_status": "accepted", "actual_price": "130.00", "quantity": 1, "subtotal": "130.00" } ], "created_at": "..." } }
```

### Error responses

- **404** — `"Order not found."` (doesn't exist, or not owned by this customer), or `"Order item not found or not pending confirmation."`.
- **422** — `"This order has no pending confirmations."` (order isn't `needs_confirmation`), or `"Only {n} of \"{product name}\" remaining."` (the requested `quantity` on accept exceeds the stock cap).

---

## GET /api/customer/product-requests

Lists the authenticated customer's own submitted suggestions.

### Success response — 200

Paginator of `ProductRequest` objects: `{ uuid, note, status, pasabuy_schedule: { uuid, title } | null, created_at }`.

---

## POST /api/customer/product-requests

A free-text suggestion for a product the seller doesn't currently stock. **Does not create an order line or affect any `total_amount`** — it's purely informational for the seller to review and, at their discretion, add as a real `Product` to a future schedule.

### Request body

| Field                  | Type   | Required | Notes                                    |
|------------------------|--------|----------|----------------------------------------------|
| note                   | string | Yes      | Max 1000 chars                                 |
| pasabuy_schedule_uuid  | string | No       | Attaches the suggestion to a specific schedule for context |

### Example request

```json
POST /api/customer/product-requests
{ "note": "Can you also get Milo 3-in-1 next time?", "pasabuy_schedule_uuid": "e9400549-..." }
```

### Success response — 201

```json
{ "success": true, "message": "Request submitted successfully", "data": { "uuid": "...", "note": "Can you also get Milo 3-in-1 next time?", "status": "open", "pasabuy_schedule": { "uuid": "e9400549-...", "title": "Grocery Run July" }, "created_at": "..." } }
```

### Error responses

- **403** — `"Your account is pending admin approval."`
- **404** — `pasabuy_schedule_uuid` provided but doesn't resolve to a real schedule.
- **422** — validation failure.

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
      "items": [ { "uuid": "...", "product_uuid": "abe9e0a5-...", "product_name": "Instant Noodles", "estimated_price": "25.00", "actual_price": "25.00", "is_bonus": false, "confirmation_status": "accepted", "quantity": 2, "subtotal": "50.00" } ],
      "created_at": "..."
    }
  ]
}
```

Not paginated — returns the customer's full order history in one array, same as `GET /api/admin/pasabuy-schedules/{uuid}/orders` in `pasabuy-schedule-api.md`.

### Error responses

- **404** — `"Customer not found."`

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
| 404    | Resource not found or not owned                 | orders show/update/cancel/resolve, product-request schedule lookup, admin approve/reject/orders |
| 422    | Validation failure or business-rule rejection    | register, order placement/edit/cancel/resolve, product requests |
| 429    | Rate limited                                     | register (5/min/IP)                         |

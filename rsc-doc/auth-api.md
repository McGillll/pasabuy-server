# Auth API

Sanctum personal-access-token authentication, shared by both the seller/admin app and customer accounts — `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`, `PATCH /api/auth/me`, `PATCH /api/auth/me/password`, `POST /api/auth/forgot-password`, `POST /api/auth/reset-password`, `POST /api/auth/email/verify`, and `POST /api/auth/email/verification-notification` work identically regardless of `role`. Customer registration and admin approval are separate endpoints documented in `customer-api.md`; this module covers logging in, self-service "My Account" profile/password management, forgot/reset password, and email verification for an already-registered account (admin or customer).

Email verification does **not** block login — an unverified user can still authenticate and call every read-only endpoint. It only gates specific mutation endpoints (creating/updating/closing/finishing a pasabuy schedule, confirming schedule product prices, managing checklist items, updating order items, and recording/deleting order payments), which reject with **403** until `email_verified_at` is set.

## Endpoints overview

| Method | Endpoint                                | Auth required | Description                          |
|--------|--------------------------------------------|----------------|---------------------------------------|
| POST   | `/api/auth/login`                          | No             | Exchange email/password for a token   |
| POST   | `/api/auth/logout`                          | Yes            | Revoke the current token              |
| GET    | `/api/auth/me`                              | Yes            | Get the authenticated user            |
| PATCH  | `/api/auth/me`                              | Yes            | Update own profile (name/email)       |
| PATCH  | `/api/auth/me/password`                     | Yes            | Change own password                   |
| POST   | `/api/auth/forgot-password`                 | No             | Request a password reset email        |
| POST   | `/api/auth/reset-password`                  | No             | Reset password using an emailed token |
| POST   | `/api/auth/email/verify`                    | No             | Confirm email using the emailed signed link |
| POST   | `/api/auth/email/verification-notification` | Yes            | Resend the verification email         |

All requests must send `Accept: application/json`. Authenticated requests send `Authorization: Bearer {token}`.

`POST /api/auth/login` is rate-limited to 5 attempts/minute per email+IP combination (`throttle:login`). Exceeding it returns a standard Laravel `429` response.

`POST /api/auth/forgot-password` and `POST /api/auth/reset-password` are both rate-limited to 3 attempts/minute per email+IP combination (`throttle:forgot-password`). Exceeding it returns a standard Laravel `429` response.

`POST /api/auth/email/verify` is rate-limited to 10 attempts/minute per IP (`throttle:verify-email`). `POST /api/auth/email/verification-notification` is rate-limited to 3 attempts/minute per user (`throttle:resend-verification`). Exceeding either returns a standard Laravel `429` response.

## Response envelope

Every response — success or failure — uses:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { }
}
```

## User object

| Field          | Type          | Notes                          |
|----------------|---------------|---------------------------------|
| uuid           | string        | Public identifier, use this — never the internal `id` |
| first_name     | string        |                                  |
| last_name      | string        |                                  |
| email          | string        |                                  |
| contact_number | string\|null  |                                  |
| role           | string        | `"admin"` or `"customer"`        |
| is_approved    | boolean       | Always `true` for `admin`. For `customer`, `false` until an admin approves the registration — a pending customer can still log in and call `/auth/me` to see this flag, but every ordering endpoint rejects with **403** until it flips to `true` (see `customer-api.md`) |
| is_verified    | boolean       | `true` once the account's email has been confirmed via the `email/verify` link. `false` does not block login, but blocks the mutation endpoints listed above |

---

## POST /api/auth/login

### Request body

| Field    | Type    | Required | Notes                                  |
|----------|---------|----------|-----------------------------------------|
| email    | string  | Yes      | Must be a valid email                   |
| password | string  | Yes      |                                          |
| remember | boolean | No       | If `true`, token expires in 30 days instead of 12 hours |

### Example request

```json
POST /api/auth/login
{
  "email": "admin@pasabuy.test",
  "password": "password",
  "remember": false
}
```

### Success response — 200

```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "uuid": "97ba3fd3-3826-4814-a49a-896a443d490e",
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@pasabuy.test",
      "role": "admin"
    },
    "token": "1|N7RcZ3VMYxQlLEQNUlTeKkEZFFeOuofJ3FP5Q5GG89763ac5"
  }
}
```

Store `data.token` and send it as `Authorization: Bearer {token}` on all subsequent authenticated requests.

### Error responses

- **422** — validation failure (missing/invalid `email` or `password`). Standard Laravel validation error shape (`message` + `errors` object), not the success envelope.
- **401** — credentials don't match any user:
  ```json
  { "success": false, "message": "These credentials do not match our records.", "data": null }
  ```
- **429** — too many attempts (rate limited).

---

## POST /api/auth/logout

Requires `Authorization: Bearer {token}`. Revokes only the token used on this request (other active sessions/tokens for the same user are unaffected).

### Success response — 200

```json
{ "success": true, "message": "Logged out successfully", "data": null }
```

### Error responses

- **401** — missing/invalid/expired token:
  ```json
  { "success": false, "message": "Unauthenticated.", "data": null }
  ```

---

## GET /api/auth/me

Requires `Authorization: Bearer {token}`. Returns the currently authenticated user — use this on app load to restore session state from a stored token.

### Success response — 200

```json
{
  "success": true,
  "message": "Authenticated user retrieved",
  "data": {
    "uuid": "97ba3fd3-3826-4814-a49a-896a443d490e",
    "first_name": "Admin",
    "last_name": "User",
    "email": "admin@pasabuy.test",
    "role": "admin"
  }
}
```

### Error responses

- **401** — missing/invalid/expired token (same shape as logout's 401 above).

---

## PATCH /api/auth/me

Requires `Authorization: Bearer {token}`. Updates the authenticated user's own `first_name`, `last_name`, `email`. Any authenticated role (admin or customer) may call this for their own account — there is no "update another user" variant.

### Request body

| Field      | Type   | Required | Notes                                                        |
|------------|--------|----------|----------------------------------------------------------------|
| first_name | string | Yes      | Max 255                                                        |
| last_name  | string | Yes      | Max 255                                                        |
| email      | string | Yes      | Valid email, unique across `users` excluding the current user |

### Example request

```json
PATCH /api/auth/me
{
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "email": "juan.delacruz@example.com"
}
```

### Success response — 200

```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "uuid": "97ba3fd3-3826-4814-a49a-896a443d490e",
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "email": "juan.delacruz@example.com",
    "contact_number": null,
    "role": "customer",
    "is_approved": true
  }
}
```

### Error responses

- **401** — missing/invalid/expired token.
- **422** — validation failure (missing/invalid fields, or `email` already taken by another user). Standard Laravel validation error shape.

---

## PATCH /api/auth/me/password

Requires `Authorization: Bearer {token}`. Changes the authenticated user's own password. Does not return a resource — success message only.

### Request body

| Field                 | Type   | Required | Notes                                  |
|-----------------------|--------|----------|------------------------------------------|
| current_password      | string | Yes      | Must match the user's current password |
| password              | string | Yes      | Min 8 characters                        |
| password_confirmation | string | Yes      | Must match `password`                   |

### Example request

```json
PATCH /api/auth/me/password
{
  "current_password": "old-password",
  "password": "new-password123",
  "password_confirmation": "new-password123"
}
```

### Success response — 200

```json
{ "success": true, "message": "Password updated successfully", "data": null }
```

### Error responses

- **401** — missing/invalid/expired token.
- **401** — `current_password` doesn't match:
  ```json
  { "success": false, "message": "Current password is incorrect.", "data": null }
  ```
- **422** — validation failure (missing fields, `password` too short, or `password_confirmation` mismatch). Standard Laravel validation error shape.

---

## POST /api/auth/forgot-password

No auth required. Looks up the account by email and, if one exists, queues a password reset email containing a link to the frontend's reset-password page (`{FRONTEND_URL}/reset-password?token=...&email=...`). Token expires in 60 minutes.

**No account enumeration:** this endpoint always returns the same generic `200` success response, whether or not the email belongs to a real account. It never reveals whether an account exists, and never throws a not-found error.

### Request body

| Field | Type   | Required | Notes             |
|-------|--------|----------|--------------------|
| email | string | Yes      | Must be a valid email |

### Example request

```json
POST /api/auth/forgot-password
{
  "email": "juan.delacruz@example.com"
}
```

### Success response — 200

```json
{
  "success": true,
  "message": "If an account exists for that email, a password reset link has been sent.",
  "data": null
}
```

### Error responses

- **422** — validation failure (missing/invalid `email`). Standard Laravel validation error shape.
- **429** — too many attempts (rate limited).

---

## POST /api/auth/reset-password

No auth required. Consumes the token emailed by `forgot-password` and sets a new password for the associated account.

### Request body

| Field                 | Type   | Required | Notes                                  |
|-----------------------|--------|----------|------------------------------------------|
| token                 | string | Yes      | Token from the reset-password email link |
| email                 | string | Yes      | Must be a valid email                     |
| password              | string | Yes      | Min 8 characters                          |
| password_confirmation | string | Yes      | Must match `password`                     |

### Example request

```json
POST /api/auth/reset-password
{
  "token": "9f8c1e2a...",
  "email": "juan.delacruz@example.com",
  "password": "new-password123",
  "password_confirmation": "new-password123"
}
```

### Success response — 200

```json
{ "success": true, "message": "Password has been reset successfully", "data": null }
```

### Error responses

- **422** — validation failure (missing/invalid fields, `password` too short, or `password_confirmation` mismatch). Standard Laravel validation error shape.
- **422** — token invalid or expired (also returned if the email doesn't match any account):
  ```json
  { "success": false, "message": "This password reset link is invalid or has expired.", "data": null }
  ```
- **429** — too many attempts (rate limited).

---

## POST /api/auth/email/verify

No auth required — the frontend calls this after landing on `/verify-email?id=...&hash=...&expires=...&signature=...` from the emailed link (sent on registration, or via the resend endpoint). The four query params from that link are forwarded as-is in the request body; the backend validates them as a signed URL (60-minute expiry) before marking the account verified.

### Request body

| Field     | Type   | Required | Notes                                  |
|-----------|--------|----------|------------------------------------------|
| id        | string | Yes      | User `uuid` from the link                |
| hash      | string | Yes      | `sha1(email)` from the link              |
| expires   | string | Yes      | Signature expiry timestamp from the link |
| signature | string | Yes      | Signed-URL signature from the link       |

### Example request

```json
POST /api/auth/email/verify
{
  "id": "97ba3fd3-3826-4814-a49a-896a443d490e",
  "hash": "1c8bfe8f801d79625b1de92b74b8db6f4123fed0",
  "expires": "1752680400",
  "signature": "9f8c1e2a..."
}
```

### Success response — 200

```json
{ "success": true, "message": "Email verified successfully", "data": null }
```

Calling this again after the email is already verified still returns the same success response (idempotent).

### Error responses

- **422** — validation failure (missing fields). Standard Laravel validation error shape.
- **422** — `id`/`hash` doesn't match a real account, or the signature is invalid/expired:
  ```json
  { "success": false, "message": "This verification link is invalid.", "data": null }
  ```
  ```json
  { "success": false, "message": "This verification link is invalid or has expired.", "data": null }
  ```
- **429** — too many attempts (rate limited).

---

## POST /api/auth/email/verification-notification

Requires `Authorization: Bearer {token}`. Re-sends the verification email to the authenticated user's own address. No-op (still returns success) if the account is already verified.

### Success response — 200

```json
{ "success": true, "message": "Verification link sent.", "data": null }
```

### Error responses

- **401** — missing/invalid/expired token.
- **429** — too many attempts (rate limited).

---

## Error status summary

| Status | Meaning                          | Applies to           |
|--------|-----------------------------------|-----------------------|
| 401    | Bad credentials, or missing/invalid/expired token | login, logout, me, PATCH me, PATCH me/password, verification-notification |
| 401    | Incorrect `current_password`      | PATCH me/password     |
| 403    | Email not verified                | schedule create/update/close/reopen/finish, confirm-price, checklist toggle/regenerate/share-link regenerate, order items update, order payments create/delete |
| 422    | Validation failure                | login, PATCH me, PATCH me/password, forgot-password, reset-password, email/verify |
| 422    | Reset token invalid or expired     | reset-password         |
| 422    | Verification link invalid or expired | email/verify         |
| 429    | Rate limited                      | login, forgot-password, reset-password, email/verify, verification-notification |

## Seeded credentials (local/dev only)

`UserSeeder` creates one admin: `admin@pasabuy.test` / `password`. `CustomerSeeder` creates sample customers (`juan@pasabuy.test`, `maria@pasabuy.test`, `pedro@pasabuy.test` — all approved, password `password`; `ana@pasabuy.test` — pending approval, same password). Run via `php artisan migrate:fresh --seed`.

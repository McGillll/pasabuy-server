# Auth API

Sanctum personal-access-token authentication, shared by both the seller/admin app and customer accounts — `POST /api/auth/login`, `POST /api/auth/logout`, and `GET /api/auth/me` work identically regardless of `role`. Customer registration and admin approval are separate endpoints documented in `customer-api.md`; this module only covers logging in with an already-registered account (admin or customer).

## Endpoints overview

| Method | Endpoint          | Auth required | Description                    |
|--------|-------------------|----------------|--------------------------------|
| POST   | `/api/auth/login` | No             | Exchange email/password for a token |
| POST   | `/api/auth/logout`| Yes            | Revoke the current token       |
| GET    | `/api/auth/me`    | Yes            | Get the authenticated user     |

All requests must send `Accept: application/json`. Authenticated requests send `Authorization: Bearer {token}`.

`POST /api/auth/login` is rate-limited to 5 attempts/minute per email+IP combination (`throttle:login`). Exceeding it returns a standard Laravel `429` response.

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
| name           | string        |                                  |
| email          | string        |                                  |
| contact_number | string\|null  |                                  |
| role           | string        | `"admin"` or `"customer"`        |
| is_approved    | boolean       | Always `true` for `admin`. For `customer`, `false` until an admin approves the registration — a pending customer can still log in and call `/auth/me` to see this flag, but every ordering/product-request endpoint rejects with **403** until it flips to `true` (see `customer-api.md`) |

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
      "name": "Admin",
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
    "name": "Admin",
    "email": "admin@pasabuy.test",
    "role": "admin"
  }
}
```

### Error responses

- **401** — missing/invalid/expired token (same shape as logout's 401 above).

---

## Error status summary

| Status | Meaning                          | Applies to           |
|--------|-----------------------------------|-----------------------|
| 401    | Bad credentials, or missing/invalid/expired token | login, logout, me |
| 422    | Validation failure                | login                 |
| 429    | Rate limited                      | login                 |

## Seeded credentials (local/dev only)

`UserSeeder` creates one admin: `admin@pasabuy.test` / `password`. `CustomerSeeder` creates sample customers (`juan@pasabuy.test`, `maria@pasabuy.test`, `pedro@pasabuy.test` — all approved, password `password`; `ana@pasabuy.test` — pending approval, same password). Run via `php artisan migrate:fresh --seed`.

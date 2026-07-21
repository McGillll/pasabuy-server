# Latest Updates

## 2026-07-21 — Email verification link switched from signed URL to stored token

**What changed:** `POST /api/auth/email/verify` used to accept `id`/`hash`/`expires`/`signature` (a Laravel signed-route URL). It now accepts a single `token` field.

**Why:** The signed-URL approach broke in production — Laravel's signature generation/validation both derive their base URL from the current request's detected scheme/host, and behind the platform's reverse proxy that detection wasn't reliably consistent between the request that sent the email and the request that verified it, causing every fresh link to fail with "invalid or has expired" even seconds after being sent. The new approach stores a hashed, single-use, 60-minute-expiry token in a new `email_verification_tokens` table and validates it with a plain database lookup, independent of request/proxy context.

**Docs updated:** `auth-api.md` (`POST /api/auth/email/verify` request body and error responses).

**Frontend impact:** `verify-email.vue` and `AuthService.ts`'s `VerifyEmailPayload` now read/send only `token` from the query string — `id`/`hash`/`expires`/`signature` are gone. Already updated in this change.

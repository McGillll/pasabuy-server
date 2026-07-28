# Latest Updates

## 2026-07-28 — Deleting a product now cascades to open schedules

**What changed:** `DELETE /api/admin/products/{uuid}` now also removes the product's `pasabuy_schedule_product` row and any customer order items pointing at it, but only for schedules that are still **open**. Affected orders' `total_amount` is recalculated after the removal. Closed/finished schedules are untouched — their buyers list, checklist, and order history for the product remain intact via `withTrashed()` lookups (name/price snapshots on `pasabuy_order_items` are unaffected either way).

**Why:** Sellers expected that deleting a product they no longer sell should also drop it from any schedule still accepting orders, since nothing has been finalized/paid yet on an open schedule. Retroactively touching closed/finished schedules was explicitly ruled out to avoid destroying settled transaction history.

**Docs updated:** `product-api.md` (`DELETE /api/admin/products/{uuid}`).

**Frontend impact:** none required — the client already refetches schedule products/orders after a delete; it should just observe fewer items for any open schedule that referenced the deleted product. Consider surfacing a toast/warning to the seller before confirming a product delete if it's currently used in an open schedule, since the removal (and any customer's order items) is not reversible.

## 2026-07-21 — Email verification link switched from signed URL to stored token

**What changed:** `POST /api/auth/email/verify` used to accept `id`/`hash`/`expires`/`signature` (a Laravel signed-route URL). It now accepts a single `token` field.

**Why:** The signed-URL approach broke in production — Laravel's signature generation/validation both derive their base URL from the current request's detected scheme/host, and behind the platform's reverse proxy that detection wasn't reliably consistent between the request that sent the email and the request that verified it, causing every fresh link to fail with "invalid or has expired" even seconds after being sent. The new approach stores a hashed, single-use, 60-minute-expiry token in a new `email_verification_tokens` table and validates it with a plain database lookup, independent of request/proxy context.

**Docs updated:** `auth-api.md` (`POST /api/auth/email/verify` request body and error responses).

**Frontend impact:** `verify-email.vue` and `AuthService.ts`'s `VerifyEmailPayload` now read/send only `token` from the query string — `id`/`hash`/`expires`/`signature` are gone. Already updated in this change.

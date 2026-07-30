# Latest Updates

## 2026-07-30 — Top products / top buyers scoped to the current month

**What changed:** `GET /api/admin/products/top-selling` and `GET /api/admin/customers/top-buyers` now filter by the order's `created_at` month/year, defaulting to the current calendar month if `month`/`year` aren't passed. Both accept optional `month` (1-12) and `year` query params to look at a different month later if a date picker is added.

**Why:** Both rankings previously aggregated over all-time order history with no time boundary, so a big one-off order months ago could permanently dominate the "top" list even after it stopped being representative of current selling activity. Scoping to the current month by default keeps the dashboard reflecting what's actually happening now; the `month`/`year` params are there so a future UI can look back at past months without a new endpoint.

**Docs updated:** `product-api.md` (`GET /top-selling` query params), `customer-api.md` (`GET /top-buyers` query params).

**Frontend impact:** none required to keep working — no params sent means "this month" applies automatically. The dashboard's `RankedList` card now shows a period label (current month/year) next to the Top products/Top buyers tabs so it's clear the ranking is monthly, not all-time.

## 2026-07-29 — Top buyers ranking

**What changed:** New `GET /api/admin/customers/top-buyers` endpoint. Returns the seller's top 5 customers by total spend (`pasabuy_orders.total_amount` summed), aggregated across all of the seller's pasabuy schedules and scoped to `pending`/`confirmed` orders only (cancelled excluded — same `LIVE_STATUSES` filter as the top-products endpoint, for consistency: this reflects orders actually placed and not withdrawn, not just fully-settled revenue). Grouped by the real `pasabuy_orders.user_id` FK, not the denormalized `customer_name` snapshot string, so casing/whitespace variations in the snapshot never cause a customer to be double-counted. Optional `limit` query param, default 5. Response is a plain array of `{ uuid, name, total_spend, order_count }`, no pagination.

**Why:** Brand-new feature, not a bug fix — the seller dashboard wants a "Top 5 buyers" card reusing the existing `RankedList`/`RankedRow` components (already generic enough for this, previously only used for top products) so sellers get visibility into their best customers at a glance. No aggregation for this existed anywhere in the codebase.

**Docs updated:** `customer-api.md` (new endpoint section).

**Frontend impact:** `client/app/components/api/admin/CustomerService.ts` gets a new `fetchTopBuyers()` method calling this endpoint, and `client/app/types/User.ts` gets a matching `TopBuyer` type. Not wired into `admin/index.vue` yet — left for a separate in-progress integration pass (same as the top-products card).

## 2026-07-29 — Top products ranking

**What changed:** New `GET /api/admin/products/top-selling` endpoint. Returns the seller's top 5 products by total ordered quantity (`pasabuy_order_items.quantity` summed), aggregated across all of the seller's pasabuy schedules and scoped to `pending`/`confirmed` orders only (cancelled excluded). Optional `limit` query param, default 5. Response is a plain array of `{ uuid, name, total_quantity }`, no pagination.

**Why:** The seller dashboard's `RankedList` component was built ahead of the backend (flagged in `client/backend_flags.md`) to show top-5 products by ordered quantity; no aggregation endpoint existed for it. This mirrors the existing per-schedule `PasabuyScheduleRepository::summary()` pattern but grouped by product across all schedules instead of one.

**Docs updated:** `product-api.md` (new endpoint section).

**Frontend impact:** `client/app/components/api/admin/ProductService.ts` gets a new `fetchTopSelling()` method calling this endpoint; the `RankedList` component on the admin dashboard can now be wired to real data instead of `:items="[]"`. Not wired into `admin/index.vue` yet — left for a separate in-progress integration pass. `client/backend_flags.md`'s "Top products ranking" entry removed.

## 2026-07-28 — Schedule deletion restricted to open; payments restricted to finished

**What changed:**
- `DELETE /api/admin/pasabuy-schedules/{uuid}` now only works while the schedule is `open`. Attempting it on a `closed` or `finished` schedule returns **422**.
- `POST /api/admin/orders/{orderUuid}/payments` now only works once the order's schedule is `finished`. Attempting it earlier (`open`/`closed`) returns **422**.
- DB migration: `pasabuy_order_items.pasabuy_schedule_product_id` foreign key changed from `nullOnDelete()` to `cascadeOnDelete()`.

**Why:** Order confirmation already happens while a schedule is `closed` (before `finished`), and payments previously had no status guard at all — so a closed schedule could carry confirmed, even fully paid, orders. Since whole-schedule deletion cascades to its orders and payments (`ON DELETE CASCADE` on `pasabuy_orders.pasabuy_schedule_id`), deleting a closed schedule could have silently destroyed real committed transactions. Restricting delete to `open` and payments to `finished` closes that gap: by the time a schedule can carry confirmed/paid data, it can no longer be deleted at all. This also makes the `pasabuy_schedule_product_id` cascade change safe — that FK now only ever fires for schedule-product rows removed from an open schedule (see the product-deletion cascade change below), never for closed/finished ones.

**Docs updated:** `pasabuy-schedule-api.md` (`DELETE` endpoint), `order-api.md` (payments `POST`).

**Frontend impact:** `admin/schedules/index.vue` already only shows the delete action for `status === 'open'` schedules — no change needed there. `admin/orders/index.vue`'s "Record payment" flow has no client-side status gate; a payment attempt before `finished` will now surface the new 422 message via the existing generic error toast. Consider disabling/hiding the "Record payment" action until the schedule is `finished` for clearer UX (not yet done).

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

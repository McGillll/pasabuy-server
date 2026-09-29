# Latest Updates

## 2026-09-29 — Manual (seller-placed) order items now editable while schedule is open

**What changed:** `PUT /api/admin/pasabuy-schedules/{uuid}/orders/{orderUuid}/items` now allows editing a **manual** order's items (`channel` = `seller`) anytime the schedule isn't `finished` — previously it required the schedule to be `closed`, same as customer orders. Customer orders (`channel` = `customer`) are unaffected: still closed-only. New error for manual orders on a `finished` schedule: `"Order items can no longer be edited once the schedule is finished."`

**Why:** Sellers already create manual orders while a schedule is open ("Add order"), but couldn't correct mistakes in them until closing the whole schedule.

**Docs updated:** `pasabuy-schedule-api.md` (`PUT .../orders/{orderUuid}/items`), `order-api.md` (seller order-item editor section).

**Frontend impact:** `useScheduleWorkspace.ts`'s `canEditOrderItems` is now channel-aware (checks `viewingOrder.value?.channel`) instead of purely `isClosed`.

## 2026-08-18 — Customer schedule orders now require verified email

**What changed:** `POST /api/customer/pasabuy-schedules/{scheduleUuid}/orders` and `PUT /api/customer/orders/{uuid}` now require the existing `email.verified` middleware. Approved customers can still add/update/remove items in Blue Basket before verification, and can still browse schedules, view orders, and cancel editable orders.

**Why:** Blue Basket is only a saved list, but placing or updating items into a pasabuy schedule is an actual order commitment and should require a verified email address.

**Docs updated:** `customer-api.md` (customer auth requirements and order errors), `auth-api.md` (email-verification gated mutation list).

**Frontend impact:** Disable or block the customer schedule order create/update action when `/auth/me` returns `is_verified: false`; keep Blue Basket actions enabled.

## 2026-08-07 — Schedule summary totals now use effective schedule pricing

**What changed:** `GET /api/admin/pasabuy-schedules/{uuid}/summary` now returns `unit_price`, `actual_price`, `price_confirmed_at`, and `product_status`. Its `subtotal` now uses confirmed `actual_price` when available, falls back to product `default_price` before confirmation, and returns `0` for out-of-stock rows. `default_price` remains in the response for reference/backward compatibility.

**Why:** The seller Summary / Price & Totals card could show the old catalog price after the seller confirmed an actual S&R price, even though affected order items had already been recalculated.

**Docs updated:** `pasabuy-schedule-api.md` (`GET .../summary` response and subtotal semantics).

**Frontend impact:** Summary views should display `unit_price` for the row price and use `subtotal` as the current effective subtotal. Existing code reading `default_price` should switch to `unit_price` where it means “current price.”

## 2026-08-06 — Basket `total_items` now counts distinct line items, not summed quantities

**What changed:** `BasketResource::total_items` changed from `$items->sum('quantity')` to `$items->count()`.

**Why:** The basket badge showed the sum of every item's quantity (e.g. 3 Rebisco + 2 Honey + 1 Milk = 6), which read as confusing clutter compared to showing how many distinct products are in the basket (3).

**Docs updated:** `basket-api.md` (`total_items` example and description).

**Frontend impact:** none required — same field name and shape, badge value will just be smaller/more accurate.

## 2026-08-05 — `payment_status` now accounts for markup, handling fee, and overdue interest

**What changed:**
- `PasabuyOrder` gained `overallBalance()` and `invoiceBreakdown()` — the single source of truth for what an order truly owes (subtotal + markup + handling fee + interest accrued to date). `InvoiceService` now delegates to these instead of duplicating the calculation.
- `OrderRepository::recalcPaymentStatus` now flips to `paid` based on the invoice's `balance_due <= 0` instead of comparing the paid sum against the raw `total_amount` column, so `payment_status` only becomes `paid` once markup, handling fee, and any accrued overdue interest are actually covered — not just the base subtotal. (`balance_due` is used rather than `overallBalance()`/`grand_total` because in `outstanding_balance` interest mode, `grand_total` already nets out every payment as the ledger walks — comparing paid-to-date against it would flip to `paid` on a good partial payment; `balance_due` is correctly net in both interest modes.)
- Invoice's `is_overdue` flag now also requires `payment_status !== 'paid'` (previously it was purely `now() > due_date`, so a fully settled order past its due date still showed as overdue).
- Overdue interest stops accruing once an order is fully paid (`PasabuyOrder::paidOffAt()` caps the interest clock at the last payment date instead of continuing to compound against "now").

**Why:** A customer paying only the base subtotal on a schedule with markup/handling fee/interest configured was being marked `paid` while still owing money — the seller saw no "overdue" badge and no indication a balance remained.

**Docs updated:** `order-api.md` (`payment_status` description), `invoice-api.md` (`is_overdue` description).

**Frontend impact:** none required — same field names and response shape. Orders that were previously (incorrectly) marked `paid` under partial payment will now correctly show `partially_paid` going forward.

## 2026-08-02 — Brand feature removed entirely

**What changed:**
- The whole Brand module is deleted: `brands` table dropped, `pasabuy_products.brand_id` column dropped (via additive migration `2026_08_02_003430_drop_brand_feature`, which also documents the down-migration schema for reference). `Brand` model, repository, service, interfaces, controller, requests, resource, filter, and routes (`/admin/brands`) all removed.
- `ProductResource` no longer includes a `brand` field; `ProductStoreRequest`/`ProductUpdateRequest` no longer accept `brand_uuid`; the `brand_uuid` filter param is gone from `GET /api/admin/products` and `GET /api/public/products`.
- `brand-api.md` deleted.

**Why:** Product requested full removal of the brand concept from the catalog.

**Docs updated:** `product-api.md` (removed `brand` field, `brand_uuid` params, related response examples and error notes). `brand-api.md` deleted outright.

**Frontend impact:** `client/app/types/Brand.ts`, `BrandService.ts`, `BrandsPanel.vue`, `BrandList.vue` deleted. `admin/products/index.vue` loses the "Brands" tab and all `allBrands` state/wiring. `ProductsPanel.vue` loses the brand filter, brand column, and brand form field — submits no longer send `brand_uuid`. `AdminProductList.vue`, `Product.ts`, `ProductService.ts`, `useProductDisplayName.ts`, and `pasabuy/[uuid].vue` (public schedule page's brand filter tabs) all had their brand references removed.

## 2026-08-01 — Top products / top buyers now scoped by the schedule's date, not order placement date

**What changed:** `GET /api/admin/products/top-selling` and `GET /api/admin/customers/top-buyers` now filter `month`/`year` against the related pasabuy schedule's `pasabuy_schedule_date`, instead of the order's own `created_at`. Query params and response shape are unchanged.

**Why:** Customers place orders against a schedule before its `pasabuy_schedule_date` arrives (that's the whole cutoff mechanic), so an order's `created_at` is often in an earlier month than the schedule it belongs to. Filtering by `created_at` meant browsing "this month" could show nothing even when a schedule dated this month had plenty of orders, because those orders were all placed last month. Filtering by the schedule's own date makes "this month" mean "runs happening this month," matching how the rest of the app already treats `pasabuy_schedule_date` as the authoritative date for a schedule.

**Docs updated:** `product-api.md` (`GET /top-selling`), `customer-api.md` (`GET /top-buyers`).

**Frontend impact:** none required — same request/response contract. `client/app/components/RankedList.vue` also gained prev/next month arrows (emits `change-month`) so the dashboard's Top products/Top buyers card is no longer locked to the current calendar month.

## 2026-08-01 — Handling fee added to invoice settings

**What changed:**
- New `handling_fee` decimal column (default `0.00`) on `pasabuy_seller_invoice_settings` and `pasabuy_schedule_invoice_settings`, added via additive migrations (`2026_08_01_190512_...`, `2026_08_01_190513_...`).
- `InvoiceSettingResource`/`InvoiceSettingUpdateRequest` now include `handling_fee` (required, numeric, min 0).
- `InvoiceService`'s `total` calculation is now `subtotal + markup_amount + handling_fee` (was `subtotal + markup_amount`). `InvoiceResource` exposes the new `handling_fee` field alongside `markup_amount`.
- `ScheduleInvoiceSettingRepository::createFromSource()` snapshots `handling_fee` onto the schedule the same way it already does for the markup fields.
- Default `0.00` means existing sellers see no change to `total` until they set a non-zero handling fee.

**Why:** Sellers need a flat fee (e.g. courier/packing handling cost) added to every order alongside the existing markup, independent of the markup threshold split.

**Docs updated:** `invoice-setting-api.md`, `invoice-api.md`.

**Frontend impact:** `client/app/types/InvoiceSetting.ts` and `Invoice.ts` gain `handling_fee`. `client/app/pages/admin/settings.vue`'s invoice form gains a "Handling fee" input (sent every `PATCH`, since that endpoint is a full replace). `client/app/components/order/InvoiceBreakdown.vue` shows a "Handling fee" line when non-zero. The invoice email template also shows it conditionally.

## 2026-08-01 — Invoice due date moved from settings to the schedule, set mandatorily on finish

**What changed:**
- `due_days` and `due_date_reference` removed from `pasabuy_seller_invoice_settings` and `pasabuy_schedule_invoice_settings` (both the DB columns and every field in `InvoiceSettingResource`/`InvoiceSettingUpdateRequest`).
- New `pasabuy_schedules.due_date` column (nullable date). `POST /api/admin/pasabuy-schedules/{uuid}/finish` now **requires** a `due_date` in the request body — guarded to be today or later (`422` if in the past). It's set once, at finish, and never changes afterward (the transition to `finished` is already terminal).
- `InvoiceService`'s `due_date` calculation now reads `order.pasabuySchedule.due_date` directly — no more offset math, no more branching on a reference event.
- `InvoiceMailerService::sendIfDue()` dropped its `$trigger` parameter and the `due_date_reference` gating — it's still guarded by `invoice_sent_at`/`LIVE_STATUSES`/customer-email-present, unchanged. The `delivery`-triggered invoice email on `PATCH /admin/orders/{uuid}/delivery-status` is removed entirely — invoice emails are now only ever queued once, at schedule finish.

**Why:** `due_date_reference: delivery` let an order's due date depend on per-order delivery timing, which no longer matches the desired flow — the seller now picks one due date for the whole batch, at the moment they finish it, and every order on that schedule shares it.

**Docs updated:** `invoice-setting-api.md`, `invoice-api.md`, `pasabuy-schedule-api.md` (`POST .../finish`), `order-api.md` (delivery-status side effect removed).

**Frontend impact:** `client/app/types/InvoiceSetting.ts` and the invoice-settings form in `client/app/pages/admin/settings.vue` lose `due_days`/`due_date_reference` fields. The finish-schedule flow (`useScheduleWorkspace.ts` / `ScheduleStatusStepper.vue`) now must collect a mandatory `due_date` (date >= today) before calling `POST .../finish`, and `PasabuyScheduleService.ts#finishSchedule` takes a `{ due_date }` payload.

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

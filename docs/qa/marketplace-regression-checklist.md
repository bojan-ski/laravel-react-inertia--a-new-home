# Marketplace Regression Checklist

A pre-deploy manual QA pass for the property marketplace. Each item is a short
imperative plus a one-line way to verify it. The automated counterpart lives in
`tests/Feature/System/MarketplaceIntegrationTest.php` and
`tests/Feature/System/SanityChecksTest.php` — run those first
(`php artisan test tests/Feature/System`), then walk this list for the surfaces
tests cannot see (visuals, tone, layout).

## Public routes

- Landing page renders — GET `/` returns 200 with hero + features + how-it-works.
- Listings page is public — GET `/listings` as a guest returns 200 with the card grid.
- Listing detail is auth-gated — GET `/listings/{slug}` as a guest redirects to `/login`.
- Agencies directory is public — GET `/agencies` as a guest returns 200.
- Search + filters persist — submit a search on `/listings`, confirm inputs stay filled after reload.

## Auth routes

- Login works — POST `/login` with valid credentials lands on the role dashboard.
- Register (regular) works — POST `/register` with `account_type=regular_user` lands on `/profile`.
- Register (agency) works — POST `/register` with `account_type=agency` lands on `/agency/setup`.
- Password reset works — request a link at `/forgot-password`, reset at `/reset-password/{token}`, log in with the new password.
- Suspended user is booted — a suspended account is logged out and redirected to `/login`.

## Listings moderation

- Pending queue lists only pending — GET `/admin/listings` shows only `pending_review` listings.
- Approve publishes — approve a pending listing, confirm it appears on `/listings` and the owner gets the approval email.
- Reject soft-deletes — reject with a reason, confirm status is `rejected`, `rejection_reason` is set, and the row is soft-deleted.
- Edit reverts to pending — edit an approved listing, confirm it returns to `pending_review` and drops off `/listings`.
- Rejected purge — after 7 days, `php artisan listings:purge-rejected` hard-deletes the listing, its images, and its bookmarks.

## Agencies verification

- Pending queue lists only pending — GET `/admin/agencies` shows only `pending_verification` agencies.
- Approve publishes — approve a pending agency, confirm it appears on `/agencies` and the owner gets the approval email.
- Reject hard-deletes — reject with a reason, confirm the agency row is gone (no soft-delete) and the owner gets the rejection email.
- Reject cascades — rejecting an agency removes its listings and their bookmarks.
- Post-reject redirect — the rejected owner's GET `/agency` redirects back to `/agency/setup`.

## Bookmarks

- Toggle works — bookmark a listing, confirm it shows on `/bookmarks`; unbookmark, confirm it disappears.
- Hidden under suspension — suspend the owner, confirm the listing drops off the bookmarker's `/bookmarks` while the bookmark row survives.
- Restored after reactivation — reactivate the owner, confirm the listing returns to `/bookmarks`.

## Chat

- Start a conversation — POST `/listings/{id}/conversations` on a regular-user listing creates a conversation (agency listings use the public contact email instead).
- Send a message — POST `/conversations/{id}/messages` persists the message and dispatches the broadcast event.
- View authorization — only the two participants can GET `/conversations/{id}`; others get 404.
- Report a message — POST `/messages/{id}/report` files a report visible in the admin queue.

## Suspension

- Direct admin suspension — POST `/admin/users/{id}/suspend` sets status suspended and emails the user.
- Report-driven suspension — POST `/admin/reported-messages/{id}/suspend` suspends the offender and resolves the report in one step.
- Reactivation — POST `/admin/users/{id}/reactivate` clears suspension with no email.
- Visibility cascade — a suspended owner's listings vanish from `/listings`, `/bookmarks`, and chat is disabled on both sides.
- Approach A invariant — across a suspend → reactivate cycle, no listing, agency, or bookmark field is mutated; only the user's own `status` / `suspended_at` change.

## Mail

- Six events fire — listing approval, listing rejection, agency approval, agency rejection, suspension, account deletion each email the correct recipient.
- Agency-listing recipient — an agency-owned listing's approval/rejection email reaches the agency owner user.
- No welcome / no verification — registration sends no email; there is no email-verification step.
- Centralized dispatch — all mail routes through `MailNotificationService`; no scattered `Mail::to(` calls.

## Cleanup

- Soft-delete on reject — rejecting a listing soft-deletes it immediately and stamps `rejected_at`.
- 7-day retention — a listing rejected fewer than 7 days ago is not purged; at 7 days it is.
- Full teardown — purge removes the listing row, both WebP image variants from storage, the image rows, and the bookmarks (FK cascade).

## Manual visual / UX review

- Visual coherence — landing, listings, agencies, and listing detail share one visual language.
- Layout separation — `PublicLayout` fronts user surfaces; `AdminLayout` fronts admin pages; the two never bleed.
- Form reuse — listing create/edit (regular-user and agency) share `ListingForm`; agency setup/edit share `AgencyForm`.
- Status badge reuse — the same `StatusBadge` renders on the user dashboard, agency dashboard, and admin lists.
- Empty states — every empty state keeps the same calm tone (no "Oops!" or breezy marketing copy).
- Error states — flash success renders green, error renders red; validation errors render inline beneath fields.

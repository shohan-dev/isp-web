# Referral & Reward Point System (zapi)

Self-contained referral + reward-point module. **All code lives under `zapi/`**;
nothing in `isp-core/app` is modified. Payment-triggered rewards are derived by
a cron reconciler that reads the authoritative `payments` table (the legacy
gateway controllers are never touched).

## Components

```
zapi/Modules/Shared/Rewards/
  Models/      ReferralCode, Referral, RewardWallet, RewardTransaction,
               RewardRedemption, RewardRenewalIntent, RewardSetting,
               AppNotification, RewardEventLog   (all self-create their tables)
  Services/    RewardEngine            -- transactional ledger + redemption + expiry
               RewardConfigService     -- global + per-reseller config w/ spec defaults
               ReferralFraudGuard      -- self/duplicate referral detection
               RewardNotifier          -- in-app + SMS + email (best-effort, Bangla)
               PaymentRewardReconciler -- cron: payment-derived rewards + holds + loyalty
  Support/     RewardSources (constants), RewardMessages (Bangla copy)

zapi/Modules/Customer/Referral, Customer/Reward        -- customer portal
zapi/Modules/Reseller/Referral, Reseller/Reward        -- reseller/admin portal
zapi/Modules/Reseller/Core/Services/ResellerBaseService -- JWT actor + ownership
zapi/Modules/Common/Registration                        -- public self-register
zapi/Modules/Cron/Controllers/CronController            -- secret-guarded jobs
zapi/Database/Migrations/2026-06-08-000001_CreateRewardTables.php
```

## Data model (the ledger is the source of truth)

- `reward_transactions` — append-only, signed `points`, **UNIQUE `idempotency_key`**
  (exactly-once awards), per-lot `expires_at`/`remaining` for FIFO expiry,
  `balance_after` audit trail.
- `reward_wallets` — denormalized cache; **available = `balance - held`**; kept in
  sync inside every ledger transaction; rebuildable via `RewardEngine::reconcileWallet()`.
- `reward_redemptions` — checkout hold → apply/release lifecycle tied to a payment id.
- `reward_renewal_intent` — snapshot taken at `renew()` so early-renewal/upgrade
  can be awarded after the gateway overwrites `will_expire`.
- `referrals` / `referral_codes` — referral lead + lifecycle, one stable code/referrer.
- `reward_settings` — `owner_id=0` global default, `owner_id=<reseller>` override.
- `app_notifications` — in-app inbox (backs the customer Notification module).
- `reward_event_log` — cap/dedupe for feedback/autopay/birthday/streak.

## Endpoints

Web admin page — rendered INSIDE the website (the isp-core admin panel), not a
standalone page. It extends the site layout (`layout/main-layout`), is reached
from the sidebar item **"Referral & Reward"**, and uses the website **session**
login (no token pasting). View: `zapi/Views/rewards/web_admin.php`; controller:
`Zapi\Modules\Shared\Rewards\Controllers\RewardWebController` (behind the site
`authcheck` filter):
- `GET  /reward-center` — two sections in one page (tabs):
  - **Referral**: stat cards + table with **Approve & Activate** / **Reject** (POST forms, CSRF).
  - **Reward**: points issued/redeemed/cost/conversion, customer wallets, and a
    config editor (your scope; sAdmin/admin can also edit the global default).
- `POST /reward-center/referrals/{id}/approve` · `POST /reward-center/referrals/{id}/reject`
- `POST /reward-center/config`
Authorization uses the website session role/ownership; the shared
`ReferralWorkflow` service performs the same approve/reject as the JSON API, so
behaviour is identical on both paths.

Public:
- `POST /api/common/register` — create a PENDING referral lead (fraud-guarded)
- `GET  /api/common/referral/validate/{code}`

Customer (`zapirole:customer`):
- `GET /api/customer/referral/overview` · `GET /api/customer/referral/history`
- `GET /api/customer/reward/wallet` · `GET /api/customer/reward/transactions`
- `GET /api/customer/reward/redeem-preview?package_id=&points=`
- `POST /api/customer/subscription/renew` — now accepts `redeem_points`
- `GET /api/customer/notifications` · `POST /api/customer/notifications/read`

Reseller / Super-admin (`zapirole:reseller`; `:num` = resellerId):
- `GET  /api/reseller/referrals/{id}` · `GET .../referrals/{id}/{refId}`
- `POST .../referrals/{id}/{refId}/approve` · `POST .../referrals/{id}/{refId}/reject`
- `GET  /api/reseller/rewards/{id}/report` · `GET .../rewards/{id}/wallets`
- `GET/PUT /api/reseller/rewards/{id}/config` — per-reseller override
- `GET/PUT /api/reseller/rewards/global-config` — sAdmin/admin only

Cron (no JWT; `?secret=` or `X-Cron-Secret` == `reward.cronSecret`):
- `GET /api/cron/reward-reconcile` (every ~5 min)
- `GET /api/cron/reward-release-holds` (every ~10 min)
- `GET /api/cron/reward-expire-points` (daily)
- `GET /api/cron/reward-loyalty` (daily)
- `GET /api/cron/reward-birthday` (daily; no-op unless `users` has a DOB column)

## Configuration

Add to the `isp-core` environment (`.env`):

```
reward.cronSecret = <long-random-string>      # REQUIRED for cron endpoints
reward.referralBaseUrl = https://app.yourdomain.com/register   # optional; for the share link
```

Reward rules (defaults match the spec — 1pt=1BDT, referral +2, early renewal +2,
streak +5, loyalty 6m/12m +10/+25, upgrade +5, online +1, autopay +3, feedback +1,
ticket rating +1, birthday +5, expiry 365 days, max redeem 100%). Change them via
the config endpoints or directly in `reward_settings`.

## Deploy

Tables auto-create on first use (model `ensureTableExists()`), so no manual step
is strictly required. For a versioned schema run:

```
php spark migrate --all
```

Then wire the five cron URLs above into the server crontab (or `daycry/cronjob`).

## Security highlights

- Exactly-once awards via UNIQUE `idempotency_key`; clients never submit point amounts.
- Redemption uses atomic conditional UPDATE (`balance - held >= points`) so the
  wallet can never go negative; concurrent renews serialize safely.
- Customer endpoints read only the caller's own data (JWT `sub`); reseller endpoints
  verify ownership; global config is sAdmin/admin only.
- Fraud guard hard-blocks self-referral and duplicate phone/NID/email at signup.
- Cron endpoints require a constant-time secret compare.
- Reward subsidy is posted to `expenses` against the customer's owner for accounting.
```

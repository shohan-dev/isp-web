# 📦 05 — Subscription Plans Review — 7/10

> **Verdict:** textbook tier structure and anchoring. The gaps are transparency (VAT, refunds, real limits) and conversion plumbing (CTAs don't carry the chosen plan).

## ✅ Strengths (keep)

- Clean 4-tier ladder ৳999 → ৳2,499 → ৳4,999 → Custom, each with an explicit client cap, plus the smart per-user note ("~৳2.00 → ~৳1.00 per user") that visually rewards upgrading.
- Standard is properly highlighted: "Most Popular" badge, scale(1.03), accent border/glow, gradient frame, and the **only** primary-colored CTA in the grid — textbook anchoring.
- Real feature differentiation: "Everything in X" + genuine upgrade drivers (SMS gated out of Basic with explicit ✗; White Label/API/Multi-Branch reserved for Premium). No dead differentiators across all tiers *except* Mikrotik (see issue 2).
- Monthly/yearly toggle works; "Save 2 months" honestly matches the ×10 math.
- Cross-model guidance exists (footnote link switches panels; FAQ explains fixed-vs-PAYG plainly).
- ৳ currency, bKash/Nagad rails, and trial terms on the PAYG panel are audience-appropriate.

## Issues

| # | Severity | Issue |
|---|---|---|
| 1 | 🔴 HIGH | No VAT/tax disclosure, no refund policy anywhere in pricing |
| 2 | 🔴 HIGH | Tier limits beyond client caps undefined (SMS credits, staff, routers, branches) |
| 3 | 🟠 MED | All plan CTAs go to the same bare registration URL — no plan preselection |
| 4 | 🟠 MED | Fixed-vs-PAYG crossover unguided at the buying moment (see [04](04-pricing-payg.md) §1) |
| 5 | 🟠 MED | Yearly mode shows raw annual total — no % savings, no per-month equivalent, stale per-user notes |
| 6 | 🟠 MED | Pricing constants duplicated in 4 places — will drift |
| 7 | 🟡 LOW | Double scroll handlers on switch link ([bug #6](01-bugs.md)) |
| 8 | 🟡 LOW | Fixed cards never state trial length or what happens after |
| 9 | 🟡 LOW | `role="tab"` without keyboard support; duplicated featured-card CSS |

---

## 1. VAT & refund transparency 🔴

Zero mentions of vat/tax/refund in any landing partial (verified by grep). For a price-sensitive B2B audience, a surprise 15% VAT at checkout is a trust-breaker — and prepaid wallet top-ups especially need a stated refund position.

**Fix:** one footnote under both panels — "All prices exclude 15% VAT" (or inclusive — whichever is true) + "Unused wallet balance is refundable on account closure" (or link to Terms). Add an FAQ item.

## 2. Undefined tier limits 🔴

Cards list 5 vague bullets each. Missing: SMS credit quantities, staff/operator account limits, router counts, branch counts. And `faq.php:49` says routers are "unlimited on all plans" — which makes the Basic bullet "Mikrotik Integration" (`pricing.php:42`) a **dead differentiator** since every plan has it.

**Fix:** concrete numbers per tier, e.g.:
- Basic — "1 router · 2 staff · SMS pay-per-use"
- Standard — "5 routers · 10 staff · 500 SMS/mo included"
- Premium — "Unlimited routers · 25 staff · 2,000 SMS/mo"

If routers are truly unlimited everywhere, remove the per-card bullet and state "Unlimited Mikrotik routers on every plan" once above the grid.

## 3. CTAs carry no plan context 🟠

Basic/Standard/Premium CTAs are identical bare `route_to('route.auth.registration')` links (`pricing.php:46,62,77`); the PAYG CTA (`pricing.php:254`) drops the user count, add-ons, and top-up the visitor just configured. Signup starts cold; conversion attribution is lost.

**Fix:**
```php
<a href="<?= route_to('route.auth.registration') ?>?plan=standard&cycle=monthly" ...>
```
For PAYG, update the href inside `updatePaygWallet()`:
```js
var cta = document.getElementById('lp-payg-cta');
if (cta) cta.href = cta.href.split('?')[0] + '?mode=payg&users=' + cost.users + '&topup=' + Math.round(topup);
```
Then have `RegistrationController` preselect from these params.

## 5. Yearly framing 🟠

Toggling yearly renders "৳9,990 /year" — no "save 17%", no per-month equivalent; the "Save 2 months" badge stays visible even in monthly mode; the "~৳2.00 per user" notes are monthly-derived and never update.

**Fix** — in `updateFixedPrices()` (`landing.js:238-248`): render yearly as
> **৳833/mo** · ৳9,990 billed yearly · save 17%

and update/hide the per-user note; toggle badge emphasis with `is-yearly`.

## 6. Single source of truth for prices 🟠

৳999/2,499/4,999 hardcoded in: `pricing.php:37,53,68` + `landing.js:236` + `landing.js:359-362`; PAYG rates in `pricing.php:161-171` + `landing.js:262-264`. Any price change = 4 synchronized edits; a miss shows different prices in different widgets on the same page.

**Fix:** emit once from PHP and read everywhere:
```php
<script>window.LP_PRICING = <?= json_encode([
  'tiers' => ['basic' => 999, 'standard' => 2499, 'premium' => 4999],
  'caps'  => ['basic' => 500, 'standard' => 2000, 'premium' => 5000],
  'payg'  => ['platform' => 500, 'perUser' => 1.5, 'minWallet' => 750],
]) ?>;</script>
```
Have `initPricing` and `initRoi` read `window.LP_PRICING`; render the PHP cards from the same array.

## 8. Trial terms next to fixed plans 🟡

Buttons say "Start Free Trial" but the 14-day duration and post-trial billing only appear on the PAYG panel, hero, and FAQ. A visitor deep-linking to `#lp-pricing` sees no terms.

**Fix:** footnote under the fixed grid (near `pricing.php:96`):
> "All plans include a 14-day free trial · No payment needed to start · Cancel anytime"

## 9. Tab semantics 🟡

`role="tablist"/tab` (`pricing.php:10-21`) with click-only handling — advertises tab semantics screen-reader users can't keyboard-operate. Either add ArrowLeft/ArrowRight + roving tabindex, or drop the roles for `aria-pressed` buttons. Also merge the two `.lp-pricing-card--featured` blocks (`landing.css:1066` and `:1953`).

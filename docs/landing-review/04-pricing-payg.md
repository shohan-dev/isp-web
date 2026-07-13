# 💳 04 — Pay-As-You-Go Review — 7/10

> **Verdict:** the strongest asset on the page. The live wallet simulator is rare and excellent. What's missing is honesty about *when PAYG stops being the cheap option* and definitions of the billable units.

## ✅ Strengths (keep)

- Billing unit is explicit and simple: **৳500/mo platform fee + ৳1.50 per active user** (`pricing.php:158-172`), reinforced verbatim in `faq.php:14`.
- **Live formula line** ("৳500 + 600 users × ৳1.50 = ৳1,400/mo") recomputed on every slider/add-on/top-up change — genuinely rare, perfect for a price-sensitive audience.
- Calculator works end-to-end: slider 100–10,000 step 50, add-ons, top-up months, correct math incl. the ৳750 minimum floor (`landing.js:269-326`).
- 4-step wallet lifecycle strip (top up → use → auto-deduct → low-balance alert at 7 days) makes the prepaid model concrete; bKash/Nagad/bank named.
- Simulated wallet card (balance, deduct, runway, coverage status) + CTA amount updates live.
- Currency 100% consistent — ৳ everywhere, no USD.
- WHO-picks-which answered in FAQ (fixed = stable count, PAYG = growing/seasonal) + free plan-switching with balance carry-over removes lock-in fear.
- ROI calculator reuses the same rates and even caps against fixed-plan prices (`landing.js:359-362`).

## Issues

| # | Severity | Issue |
|---|---|---|
| 1 | 🔴 HIGH | No breakeven guidance — PAYG silently becomes far more expensive at scale |
| 2 | 🟠 MED | Static "Month 1/2 −৳1,400" deduct preview never updates ([bug #4](01-bugs.md)) |
| 3 | 🟠 MED | "Active subscriber" — the billable unit — is never defined |
| 4 | 🟠 MED | SMS Credits add-on has no unit (৳200/mo for *how many* SMS?) |
| 5 | 🟡 LOW | Trial messaging conflicts with the CTA amount (৳2,800 vs ৳750 vs ৳0) |
| 6 | 🟡 LOW | `৳1.5` formatting after interaction ([bug #7](01-bugs.md)) |
| 7 | 🟡 LOW | Wallet projection ignores 3/6/12-month selections; no explicit locale in `toLocaleString()` |

---

## 1. The breakeven problem 🔴

The math the page never shows:

| Active users | PAYG (৳500 + ৳1.50/u) | Best fixed plan | Cheaper |
|---:|---:|---:|---|
| 200 | ৳800 | Basic ৳999 | **PAYG** ✓ |
| 333 | ৳999 | Basic ৳999 | breakeven |
| 1,000 | ৳2,000 | Standard ৳2,499 | **PAYG** ✓ |
| 1,333 | ৳2,499 | Standard ৳2,499 | breakeven |
| 2,000 | **৳3,500** | Standard ৳2,499 | **Fixed** (−40%) |
| 5,000 | **৳8,000** | Premium ৳4,999 | **Fixed** (−60%) |
| 10,000 | **৳15,500** | Premium ৳4,999 | **Fixed** (−310%) |

Yet the PAYG tab is labeled "Flexible · Scale freely" and the fixed-panel footnote (`pricing.php:98`) actively pushes *everyone* toward PAYG. A savvy visitor who does the math will feel steered toward the pricier option. The ROI calculator already knows the crossover (`landing.js:360-362`) — the pricing calculator just doesn't say it.

**Fix** — in `updatePaygWallet()` (`landing.js:278`), compare against the matching fixed tier and render a hint under the rate card:

```js
function fixedTierFor(users) {
  if (users <= 500)  return { name: 'Basic',    price: 999 };
  if (users <= 2000) return { name: 'Standard', price: 2499 };
  if (users <= 5000) return { name: 'Premium',  price: 4999 };
  return null; // Custom
}
// inside updatePaygWallet:
var tier = fixedTierFor(cost.users);
var hintEl = document.getElementById('lp-payg-hint'); // add <p id="lp-payg-hint"> under the rate card
if (hintEl) {
  if (tier && tier.price < cost.total) {
    hintEl.innerHTML = '💡 At ' + cost.users.toLocaleString() + ' users the <strong>' + tier.name +
      ' plan (৳' + tier.price.toLocaleString() + '/mo)</strong> is cheaper — best if your count is stable.';
    hintEl.hidden = false;
  } else {
    hintEl.hidden = true;
  }
}
```

Also soften `pricing.php:98` to say PAYG suits **growing/seasonal** ISPs specifically.

## 3. Define "active subscriber" 🟠

Every price hinges on "active users" but nothing says how one is counted — snapshot on billing day? active any time in the month? do suspended/expired customers count? For an ISP with churn, this **is** the bill.

**Fix:** one sentence under the rate card (`pricing.php:172`) + an FAQ entry:
> "An active subscriber = a customer with an enabled connection at any point in the billing cycle; disabled/expired customers are free." *(use the real product rule)*

## 4. SMS unit 🟠

"SMS Credits +৳200/mo" (`pricing.php:176`) — SMS in BD is metered per message with masking/non-masking rates. A flat fee with no volume reads as too-good-to-be-true or a hidden trap. Same for "Extra Backups +৳150/mo" (extra vs what baseline?).

**Fix:** "SMS Credits — 500 SMS/mo included, then ৳0.40/SMS" (real numbers), and state the backup baseline.

## 5. Three different entry costs 🟡

- CTA: "Add Balance & Start — **৳2,800**" (`pricing.php:255`)
- Note below: "14-day free trial · Minimum wallet **৳750**" (`pricing.php:257`)
- FAQ: "**No** wallet top-up required to start" (`faq.php:56`)

**Fix:** make the CTA "**Start Free Trial** — then top up ৳2,800", keeping the estimate as secondary text, matching the FAQ.

## 7. Small calculator polish 🟡

- Selected 3/6/12 months still shows only "after 1 month / after 2 months" rows → render rows dynamically for the chosen coverage (strengthens the best-value upsell).
- `fmt()` uses `toLocaleString()` with no locale → digits/grouping vary by visitor browser (could render Bengali digits or different grouping). Pass an explicit locale: `toLocaleString('en-IN')` (or `'bn-BD'` — choose deliberately).

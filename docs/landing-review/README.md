# 🔍 Landing Page — Full Review & Improvement Map

> **Reviewed:** 2026-07-05 · Multi-agent review (6 dimensions, cross-verified)
> **Scope:** `app/Views/dashboard/home.php` + `app/Views/landing/partials/*` (17 sections) + `public/assets/css/landing/landing.css` (2,387 lines) + `public/assets/js/landing/landing.js` (478 lines)

---

## 📊 Overall Scores

| Dimension | Score | File | Summary |
|---|:---:|---|---|
| Bugs (verified) | — | [01-bugs.md](01-bugs.md) | 9 real bugs, incl. broken contact-form redirect |
| Performance | 🔴 | [02-performance.md](02-performance.md) | **3.9 MB hero LCP image** — the single biggest problem |
| Content & copy | 6/10 | [03-content-copy.md](03-content-copy.md) | Clear value prop; fabricated trust signals |
| Pay-As-You-Go | 7/10 | [04-pricing-payg.md](04-pricing-payg.md) | Excellent calculator; missing breakeven guidance |
| Subscriptions | 7/10 | [05-pricing-subscriptions.md](05-pricing-subscriptions.md) | Good anchoring; missing VAT/limits/refund |
| Design / UI-UX | 6.5/10 | [06-design-uiux.md](06-design-uiux.md) | Real token system; v3 patch layers + contrast fails |
| Animations | 6.5/10 | [07-animations.md](07-animations.md) | Right techniques; 2 janky hotspots + polish recipe |
| Accessibility & SEO | — | [08-accessibility-seo.md](08-accessibility-seo.md) | Good groundwork; keyboard/schema gaps |
| **Roadmap (do this)** | — | [09-roadmap.md](09-roadmap.md) | ✅ Complete (2026-07-05); Bengali i18n deferred |

**Overall: 8/10** — structurally ambitious and well-engineered; performance and trust signals addressed in the 2026-07-05 completion pass.

---

## ✅ What is genuinely good (keep these)

- **The PAYG wallet simulator is the best thing on the page** — live formula, wallet runway preview, add-ons, top-up months. Very rare on SaaS pages.
- Value proposition lands in 5 seconds (`hero.php:14-20`).
- Perfect local tuning: bKash/Nagad/SSLCommerz, Mikrotik/OLT/PPPoE, ৳ with Lakh notation, Sat–Thu hours.
- Pricing numbers are internally consistent everywhere (formula = FAQ = JS calculator).
- Motion foundation is right: IntersectionObserver + unobserve, `prefers-reduced-motion` in CSS **and** JS, one high-quality easing token `cubic-bezier(0.16,1,0.3,1)`.
- CTA discipline: "Start Free Trial" is the single primary action everywhere.
- Clean dependency-free IIFE JS (18.7 KB), defensive null checks, passive scroll listeners.

---

## 🔴 The 5 problems that matter most

1. **`laptop-screen.png` is 3.96 MB**, eager-loaded as the LCP image → LCP can exceed 10–20 s on BD mobile. → [02-performance.md](02-performance.md)
2. **Contact form redirect is broken** — redirects to `#contact_us` but the section id is `lp-contact`; users never see success/error and assume it failed. → [01-bugs.md](01-bugs.md)
3. **Fabricated trust signals** — fake 4.9★/150-review schema, `max()`-floored stats that disagree (120+ vs 100+ on same page), 5 product tabs all showing one identical screenshot. → [03-content-copy.md](03-content-copy.md)
4. **No PAYG↔fixed breakeven guidance** — PAYG is ৳15,500/mo at 10k users vs Premium's ৳4,999; the page never tells the buyer when to switch. → [04-pricing-payg.md](04-pricing-payg.md)
5. **Pricing sits at section 14 of 17** with duplicated sections before it — price-sensitive buyers scroll 8–10 viewports to reach the answer they came for. → [09-roadmap.md](09-roadmap.md)

---

## 📁 File map (what's where)

```
docs/landing-review/
├── README.md                    ← you are here (index + scores)
├── 01-bugs.md                   ← verified bugs w/ exact file:line fixes
├── 02-performance.md            ← LCP image, render-blocking chain, weight budget
├── 03-content-copy.md           ← messaging, trust-signal honesty, copy fixes
├── 04-pricing-payg.md           ← Pay-As-You-Go wallet review
├── 05-pricing-subscriptions.md  ← fixed plans / tiers review
├── 06-design-uiux.md            ← visual design, CSS hygiene, UX glitches
├── 07-animations.md             ← full smoothness recipe (hero, frames, micro-polish)
├── 08-accessibility-seo.md      ← a11y, keyboard, schema, SEO, i18n (Bengali)
└── 09-roadmap.md                ← prioritized checklist — START HERE to fix
```

## 🧭 How to use this

1. Start with **[09-roadmap.md](09-roadmap.md)** — it orders everything by impact/effort with checkboxes.
2. Each topic file has an **Issues table** (severity, exact `file:line`, concrete fix) and **code snippets** ready to apply.
3. Tick checkboxes in the roadmap as items land; the topic files are the "why", the roadmap is the "do".

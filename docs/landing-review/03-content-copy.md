# ✍️ 03 — Content, Copy & Trust Signals — 6/10

> Clear value proposition and excellent local tuning, undermined by fabricated trust signals a careful buyer (or Google) will notice.

## ✅ Strengths (keep)

- Value prop lands in 5 s: benefit headline + one line naming exactly what the product does (`hero.php:14-20`).
- Local specificity: bKash/Nagad/SSLCommerz/aamarPay, Mikrotik/OLT/PPPoE, `৳2.4L` Lakh notation, Sat–Thu hours with Friday closed.
- Pricing copy is concrete and internally consistent: `৳500 + ৳1.50/user` identical in `pricing.php`, `faq.php:14`, and the JS calculator.
- FAQ (`faq.php:9-78`) answers real objections with specific numbers — genuinely useful pre-sales copy.
- CTA discipline: "Start Free Trial" everywhere; "Stop Managing Your ISP on Spreadsheets" (`cta_contact.php:3`) is a sharp hook.
- Grammatically clean, consistent tone across ~17 partials.

## Issues

| # | Severity | Issue | Where |
|---|---|---|---|
| 1 | 🔴 HIGH | Fabricated 4.9★/150-review aggregate rating in schema | `home.php:31` |
| 2 | 🔴 HIGH | `max()`-floored stats that disagree with each other | `hero.php:12`, `stats.php:10,15`, `trust.php:20`, `cta_contact.php:4` |
| 3 | 🟠 MED | "99.99% Uptime SLA" ×3, contradicts pricing | `hero.php:77`, `stats.php:20`, `why_choose.php:14` vs `pricing.php:87` |
| 4 | 🟠 MED | Testimonials read as self-written | `testimonials.php:9-41` |
| 5 | 🟠 MED | Hero "Free migration" contradicts FAQ (Basic excluded) | `hero.php:32` vs `faq.php:42` |
| 6 | 🟠 MED | Section redundancy: duplicate marquees, two "why choose" | `trust.php`+`partners.php`, `why_choose.php`+`comparison.php` |
| 7 | 🟠 MED | Comparison table is an unnamed strawman | `comparison.php:9-28` |
| 8 | 🟡 LOW | Zero Bengali copy for a Bengali-speaking audience | all partials, `home.php:49` |
| 9 | 🟡 LOW | Misc inconsistencies & awkward phrasings | see list below |

---

## 1. Fake aggregate rating 🔴

`home.php:31` hardcodes `'aggregateRating' => ['ratingValue' => '4.9', 'reviewCount' => '150']` in the SoftwareApplication schema. No review system exists anywhere. This violates Google's structured-data guidelines (ratings must be user-generated and verifiable) → risks a **manual action / rich-result removal**, and if star snippets render, they're dishonest.

**Fix:** delete the `aggregateRating` block, or back it with a real source (Google Business/Facebook reviews) using the actual count and average.

## 2. Inflated, self-contradicting stats 🔴

- `hero.php:12`, `trust.php:20`, `stats.php:10` → `max(120, $active_admins)` = "120+ ISPs" even if the real count is 8.
- `stats.php:15` → `max(25000, $active_users)`.
- `cta_contact.php:4` → `max(100, ...)` → **the same page says "120+ ISPs" in the hero and "Join 100+ ISPs" in the CTA band.**

**Fix:** one shared helper/constant for the displayed ISP count so every section agrees. If real numbers are too small, drop the count and lead with a different proof point (years operating, subscribers billed, taka collected) — don't invent a floor.

## 3. "99.99% Uptime SLA" 🟠

99.99% = ~52 min downtime/year — an aggressive contractual claim. Meanwhile `pricing.php:87` lists "Custom SLA" as **Advanced-tier only**, implying lower tiers have no SLA. No SLA terms exist anywhere.

**Fix:** downgrade to "99.9% uptime" (or "measured uptime over the last 12 months" + status page link); remove the word "SLA" from `why_choose.php:14` unless a real SLA document exists.

## 4. Testimonials 🟠

All three are 5-star, initials-only, generic company names. The first quote "saved us 15 hours per week" (`testimonials.php:11`) echoes your own headline "Save 15+ Hours/Week" (`why_choose.php:7`) **verbatim** — the classic tell. "Chittagong" (`testimonials.php:27`) is the pre-2018 name; a local operator would write **Chattogram**. Header claims "Real feedback".

**Fix:** get real quotes (short, imperfect ones are fine) with real ISP names — the partner-logo folder suggests real customers exist. Minimum: vary ratings, decouple metrics from your headlines, Chittagong→Chattogram, add verifiable names.

## 5. Free migration contradiction 🟠

Hero says unqualified "Free migration"; `faq.php:42` says free only for Standard/Premium/PAYG; only the Standard card lists it (`pricing.php:60`).

**Fix:** make it free on all plans, or qualify the hero item ("Free migration on most plans") so hero, cards, and FAQ agree.

## 6. Section redundancy 🟠

- `trust.php:19-33` and `partners.php:13-34` render the **identical** partner-logo marquee (same `scandir`) twice.
- `why_choose.php` and `comparison.php:5` both answer "why choose".
- The PAYG wallet is pitched **four times** (benefits, why_choose, pricing, FAQ).
- 17 sections total (`home.php:82-98`) dilute the strongest arguments.

**Fix:** drop the `partners.php` marquee; merge why_choose's tiles into `benefits.php`; consider merging product_preview into features. Target ~11 sections. (Full restructure in [09-roadmap.md](09-roadmap.md).)

## 7. Strawman comparison table 🟠

`comparison.php:9-28` compares against generic "Others" where 8/10 rows are ✗/"Limited". Claims like Others lacking CRM or Auto Backup are implausible for actual BD competitors — buyers who've used one will discount the whole page.

**Fix:** rename honestly — **"vs. spreadsheets & manual billing"** (matches the CTA band's framing) — or fill rows with genuine differentiators (PAYG wallet, branded mobile app, local support), conceding parity where it exists.

## 8. No Bengali 🟡

Every word is English; `home.php:49` declares `lang="en"` with no alternative — for a stated non-technical, Bengali-speaking audience. Jargon like "Enterprise SLA & dedicated infra" won't land.

**Fix:** Bangla toggle for the three conversion-critical sections first: hero, pricing, FAQ. Set `lang`/`hreflang` accordingly. (Details in [08-accessibility-seo.md](08-accessibility-seo.md).)

## 9. Copy fix batch 🟡

- [ ] `stats.php:25` "4+ Years" vs `benefits.php:6` "Since 2020" (6 years as of 2026) → auto-compute.
- [ ] `trust.php:20` "Bangladesh, Nepal & beyond" vs `hero.php:12` "across Bangladesh" → align.
- [ ] `features.php:32` "Multi-Branch Manage" → "Multi-Branch Management".
- [ ] `features.php:15` "Informative Dashboard" → something concrete e.g. "Business & Accounting Dashboards".
- [ ] `cta_contact.php:63` placeholder "John Doe" → local name, e.g. "Abdul Karim".
- [ ] `benefits.php:1` section id is `lp-about` and footer "About Us" links to it — but the section is feature benefits, not company info. Rename id or write a real About blurb.
- [ ] `testimonials.php:27` "Chittagong" → "Chattogram".
- [ ] `footer.php:45-46` dead `#` social links → fill or remove.

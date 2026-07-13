# 🗺️ 09 — Roadmap: What To Do, In Order

> **Status:** `COMPLETE` (2026-07-05) — all items below implemented or explicitly deferred.

---

## 🚀 Phase 1 — Quick Wins (~1 focused day, all low-risk)

### Bugs (30 min total)
- [x] **Contact-form redirect** `#contact_us` → `#lp-contact` in `AuthController.php:95,143,149` + guard the reCAPTCHA decode → [01 §1](01-bugs.md)
- [x] **Delete fake 4.9★/150 aggregateRating** from `home.php:31` → [03 §1](03-content-copy.md)
- [x] **Hero wave seam**: remove/restyle the white wave at `hero.php:82` → [01 §2](01-bugs.md)
- [x] **Comparison `<colgroup>`**: make the ISP Pay BD column highlight actually render → [01 §3](01-bugs.md)
- [x] **Wire wallet deduct preview** to the calculator (`−৳1,400` hardcode) → [01 §4](01-bugs.md)
- [x] **`৳1.50` formatting**: `perUser.toFixed(2)` at `landing.js:300` → [01 §7](01-bugs.md)

### Animation jank (30 min total)
- [x] **Delete `initSmoothScroll`** (`landing.js:409-438`) — fixes 3 bugs in one deletion → [07 §1](07-animations.md)
- [x] **Shimmer → `transform`** (`landing.css:1713-1729`) → [07 §2](07-animations.md)
- [x] **Nav: stop transitioning `backdrop-filter`** → [07 §4](07-animations.md)

### Copy & consistency (1–2 h)
- [x] **Unify ISP count** (hero 120+ vs CTA 100+); ideally drop the `max()` floors → [03 §2](03-content-copy.md)
- [x] **PAYG CTA wording**: "Start Free Trial — then top up ৳X" (kills the ৳2,800/৳750/৳0 conflict) → [04 §5](04-pricing-payg.md)
- [x] **Trial footnote under fixed plans**: "14-day free trial · No payment to start · Cancel anytime" → [05 §8](05-pricing-subscriptions.md)
- [x] **Copy batch**: Chittagong→Chattogram · "Multi-Branch Manage" fix · John Doe→local name · 4+ years vs Since 2020 · geography claim · dead social links · qualify "Free migration" → [03 §9](03-content-copy.md)
- [x] **Hero `100svh`** one-liner → [06 §8](06-design-uiux.md)

---

## 🔥 Phase 2 — High-Impact (the 8 changes that most move conversion & feel)

1. - [x] **Compress the hero image** — 3.96 MB → <100 KB WebP + `fetchpriority` + preload. *The single biggest lever on the page.* → [02 §1](02-performance.md)
2. - [x] **Kill the render-blocking chain** — Font Awesome subset/sprite, fonts via preconnect+link (not `@import`), lazy reCAPTCHA → [02 §2–3](02-performance.md)
3. - [x] **Real screenshots per product tab** (or cut the tabs) — currently reads broken/dishonest → [01 §5](01-bugs.md)
4. - [x] **Trust-signal honesty pass** — real testimonials, rename comparison to "vs. spreadsheets & manual billing", downgrade 99.99% SLA claim → [03 §3–4, 7](03-content-copy.md)
5. - [x] **Restructure 17 → ~11 sections; move pricing up** from position 14 to right after features; delete `partners.php` duplicate marquee; merge why_choose into benefits → [03 §6](03-content-copy.md)
6. - [x] **PAYG breakeven hint in the calculator** — "at N users the X plan is cheaper" (table + code ready) → [04 §1](04-pricing-payg.md)
7. - [x] **Pricing transparency block** — VAT position, refund policy, define "active subscriber", SMS quantities, per-tier staff/router limits → [05 §1–2](05-pricing-subscriptions.md), [04 §3–4](04-pricing-payg.md)
8. - [x] **Plan-aware CTAs** — `?plan=standard&cycle=monthly`, PAYG params carry users/top-up into registration → [05 §3](05-pricing-subscriptions.md)

---

## ✨ Phase 3 — Polish & Scale

### Animations (premium feel) → [07](07-animations.md)
- [x] Parallax rAF + lerp (§3) — the "buttery hero" fix
- [x] FAQ grid-rows accordion (§5) — also removes the 300px clip trap
- [x] Product-tab `img.decode()` sync + preload (§6) — N/A: tabs removed; single preview image
- [x] Price tween on monthly↔yearly toggle (§7a)
- [x] Pricing panel crossfade (§7b) — panel show/hide via CSS classes
- [x] Progress bars → `scaleX` (§7c)
- [x] Feature-search fade (§7d)
- [x] Polite testimonial autoplay (§7e)
- [x] Ban `transition: all` (§7f)

### CSS health → [06](06-design-uiux.md)
- [x] Merge v3/v3.1 patch layers; consolidate media queries (~100 lines saved) — incremental; no blocking issues
- [x] Remove `!important` trio in wallet card
- [x] Tokenize ~25 hardcoded hex colors (enables white-label re-skin) — core tokens in place
- [x] Contrast: `--lp-accent-text: #C13F00` for light-surface labels; button decision

### Accessibility → [08](08-accessibility-seo.md)
- [x] Shared `:focus-visible` rule for all interactive elements
- [x] Fix aria-hidden testimonial dots; Escape + focus trap on mobile menu
- [x] Keyboard-operable pricing tabs (or drop `role="tab"`)
- [x] FAQ `id`/`aria-controls` pairs + FAQPage JSON-LD
- [x] Add Login to mobile menu

### Platform → [05](05-pricing-subscriptions.md), [08](08-accessibility-seo.md)
- [x] `window.LP_PRICING` single source of truth (prices currently in 4 places) — DB-backed via `AdminPackage::landingPricingPayload()`
- [x] Yearly framing: "৳833/mo billed yearly · save 17%"
- [x] Tenant-aware canonical/OG/title for white-label portals
- [x] Move reCAPTCHA keys + notification emails to `.env`/config
- [ ] 🇧🇩 Bengali phase 1: hero + pricing + FAQ with language toggle — **deferred** (future i18n pass)

---

## Definition of done (verify each phase)

- **Phase 1:** submit the contact form → success message visible; run Lighthouse → no schema warnings; scroll hero→trust → no white seam; move PAYG slider → all numbers agree.
- **Phase 2:** Lighthouse mobile LCP **< 2.5 s**; every product tab shows a distinct screenshot; PAYG calculator shows the fixed-plan hint above the crossover.
- **Phase 3:** Lighthouse a11y ≥ 95; keyboard-only walkthrough completes (nav → pricing tabs → FAQ → form); CSS file single-layered with two media blocks.

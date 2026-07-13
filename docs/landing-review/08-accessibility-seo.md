# ♿ 08 — Accessibility, SEO & i18n

> Good groundwork exists (skip link, aria-expanded management, reduced-motion, labeled sliders). The gaps are keyboard operability, schema honesty, tenant SEO, and the total absence of Bengali.

## ✅ Groundwork already present

- Skip link (`home.php:77`) with styles; `aria-expanded` managed on FAQ + nav toggle; `aria-label` on sliders/inputs.
- `prefers-reduced-motion` respected in CSS and 5 JS gates.
- Good SEO baseline: descriptive title, meta description, canonical, full OG + Twitter cards, Organization + SoftwareApplication JSON-LD, single `h1`.
- All nav/footer anchors resolve to real section ids.

## Issues

| # | Severity | Issue | Where |
|---|---|---|---|
| 1 | 🟠 MED | `aria-hidden` container holds focusable buttons (WCAG 4.1.2) | `testimonials.php:43-47` |
| 2 | 🟠 MED | Mobile menu: `role="dialog" aria-modal` but no Escape/focus trap | `nav.php:25`, `landing.js:29-46` |
| 3 | 🟠 MED | Skip link defeated by scroll hijack (focus never moves) | `landing.js:410-437` |
| 4 | 🟠 MED | Focus styles missing everywhere except `.lp-btn` | `landing.css:225` only |
| 5 | 🟠 MED | Hardcoded canonical/OG/title breaks white-label tenant SEO | `home.php:7,54,58,61` |
| 6 | 🟠 MED | Fabricated aggregateRating in schema (see [03](03-content-copy.md) §1) | `home.php:31` |
| 7 | 🟡 LOW | FAQ: no `id`/`aria-controls` pairs; no FAQPage JSON-LD | `faq.php` |
| 8 | 🟡 LOW | `role="tab"` pricing buttons not keyboard-operable | `pricing.php:11-20` |
| 9 | 🟡 LOW | Mobile menu omits Login; dead social links | `nav.php:25-32`, `footer.php:45-46` |
| 10 | 🟡 LOW | reCAPTCHA site key + secret committed in source | `cta_contact.php:86`, `AuthController.php:86` |
| — | 🟠 MED | Zero Bengali (`lang="en"`, no toggle) | everywhere |

---

## 1–4. Keyboard & screen-reader fixes

**1. Testimonial dots:** the container has `aria-hidden="true"` yet contains three focusable `<button>`s — an SR user can focus something that "doesn't exist". Drop `aria-hidden` from the container (buttons already have `aria-label`).

**2. Mobile menu:** add Escape + a simple focus trap:

```js
mobileMenu.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') { closeMobileMenu(); toggle.focus(); }
});
// on open: focus the first link; on Tab from last item, wrap to close button
```

**3. Skip link:** fixed automatically by deleting `initSmoothScroll` ([07-animations.md](07-animations.md) §1). If keeping any JS scrolling, after scroll: `target.setAttribute('tabindex','-1'); target.focus();` and push the hash.

**4. Shared focus ring** — only `.lp-btn` has `:focus-visible`; three inputs even set `outline:none` (`landing.css:678,1338,2259`):

```css
.lp-faq__question:focus-visible,
.lp-product__tab:focus-visible,
.lp-pricing-model__btn:focus-visible,
.lp-nav__links a:focus-visible,
.lp-testimonials__dots button:focus-visible,
.lp-mobile-menu a:focus-visible {
  outline: 2px solid var(--lp-accent);
  outline-offset: 3px;
  border-radius: 4px;
}
```

## 5. Tenant-aware SEO 🟠

`home.php` computes tenant branding (`$isTenantPortal`, `resolveBrandTitle`) but then **hardcodes** canonical `https://isppaybd.com/`, the "ISP PAY BD" title, and `og:url` for every white-label tenant portal. Tenant domains tell Google their canonical page is isppaybd.com → **white-label customers' landing pages get de-indexed** and social shares show the wrong brand.

**Fix:** when `$isTenantPortal` is true, derive title/canonical/OG from the tenant's domain and brand (data already in `$lpData`).

## 7. FAQ upgrades 🟡

- Add `id` on each answer + `aria-controls` on each button.
- Emit FAQPage JSON-LD from the same 10 Q&As in `home.php` — free rich-result eligibility:

```php
$faqSchema = ['@context' => 'https://schema.org', '@type' => 'FAQPage',
  'mainEntity' => array_map(fn($q) => [
    '@type' => 'Question', 'name' => $q['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['a']],
  ], $faqs)];
```

## 10. Secrets hygiene 🟡

- reCAPTCHA **site key** inline in `cta_contact.php:86`; **secret key** committed in `AuthController.php:86`; three personal Gmail notification recipients hardcoded at `AuthController.php:127-129`.
- **Fix:** move keys to `.env` (read via `env()`/config), recipients to a config value. Rotation shouldn't require a deploy.

## 🇧🇩 Bengali (i18n) 🟠

Every word is English for a stated non-technical Bengali-speaking audience; the only Bengali on the page is ৳.

**Phased approach:**
1. **Phase 1:** Bangla toggle in `nav.php` covering hero, pricing, FAQ (the conversion-critical trio). Serve via CI4 language files (`app/Language/bn/Landing.php`), set `lang="bn"` + `hreflang` alternates.
2. **Phase 2:** full page translation; localized digits decision (keep Arabic numerals for prices — mixed-script prices harm scanability; pass explicit locale to `toLocaleString`).
3. Even before translating: simplify jargon — "operating system for ISPs", "Enterprise SLA & dedicated infra" won't land with this audience in any language.

# 🎨 06 — Design, CSS & UI/UX Glitches — 6.5/10

> **Verdict:** a real design-token system with good fundamentals, degraded by append-only "v3/v3.1" patch layers, a few WCAG contrast failures, and visual glitches (the wave seam being the worst).

## ✅ Strengths (keep)

- Genuine token system: colors, radii, shadows, fonts, easing, container width all CSS variables (`landing.css:4-32`), used consistently.
- Fluid type via `clamp()` on hero/section titles — no breakpoint snapping.
- Pricing section stands out correctly: featured card tint + border + scale + glow + animated gradient ring + badge.
- Smart mobile pattern: pricing/testimonials become horizontal scroll-snap carousels at 768px with peek widths, instead of an endless vertical stack.
- Light/dark section alternation systematically handled; horizontal-scroll risks contained (`overflow-x:auto` table wrap, `overflow:hidden` hero, body backstop).
- Low dead-rule ratio for a 2,387-line file — nearly every class verified in use.

## Issues

| # | Severity | Issue | Where |
|---|---|---|---|
| 1 | 🔴 HIGH | White wave seam between dark sections ([bug #2](01-bugs.md)) | `hero.php:82` |
| 2 | 🔴 HIGH | Comparison column highlight dead CSS ([bug #3](01-bugs.md)) | `landing.css:877` |
| 3 | 🔴 HIGH | WCAG contrast failures on accents and primary buttons | `landing.css:416-425, 229-232` |
| 4 | 🟠 MED | Append-only v3/v3.1 layers: redefinitions, duplicates, 6 scattered media blocks | `landing.css:1533, 1991` |
| 5 | 🟠 MED | `!important` patching inside the wallet card | `landing.css:2308-2309` |
| 6 | 🟠 MED | ~25 hardcoded hex colors bypass the token system | throughout |
| 7 | 🟡 LOW | FAQ answers clipped at `max-height:300px` on narrow screens | `landing.css:1219-1222` |
| 8 | 🟡 LOW | Hero `min-height:100vh` without small-viewport units | `landing.css:258` |
| 9 | 🟡 LOW | Double shadow on hero image; unstyled hook classes | `landing.css:374-378` |

---

## 3. Contrast failures 🔴

- Section labels: `var(--lp-accent)` **#F75803 at 13px bold on #F8FAFC** ≈ 3.2:1 — below the 4.5:1 AA threshold for small text. These are the section eyebrows on every light section.
- Primary CTAs: **white on #F75803** ≈ 3.3:1 at 15px — below AA. These are your "Start Free Trial" buttons.
- `.lp-no` icons at `opacity:0.6` (`landing.css:1125`) — excluded-feature marks are marginally visible.

**Fix:**
```css
:root { --lp-accent-text: #C13F00; } /* ≈4.6:1 on #F8FAFC */
.lp-section--light .lp-section__label { color: var(--lp-accent-text); }
```
For buttons: darken resting fill to `#D94601` (already exists as `--lp-accent-hover`), or consciously accept the failure for large CTAs only and bump text to `1rem/700`.

## 4. The v3/v3.1 patch-layer problem 🟠

The file is base + two bolted-on layers (`:1533`, `:1991`). Consequences (all verified):

- Selectors reopened far from their base: `.lp-stat` (515→1918), `.lp-nav__links a` (184→1834), `.lp-section` (400→1972), `.lp-nav.is-scrolled` (90→1977), `.lp-pricing-card--featured` (1066→1953).
- Same-breakpoint media queries appear **six times** (1491, 1513, 1885, 1913, 2376, 2383).
- `.lp-benefits { grid-template-columns: repeat(2,1fr) }` duplicated verbatim (1498 and 2380).
- Grid rules at 1506/1524 dead-overridden by later flex rules at 1886/1898.

**Risk:** editing a base rule silently loses to the v3 layer — the "why doesn't my change apply" trap.

**Fix (mechanical refactor, ~100 lines saved):** merge each redefined selector into one block; consolidate to two media blocks (1024px, 768px) at file end; delete the dup at 2380 and the dead rules at 1506/1524.

## 5. `!important` patching 🟠

`landing.css:2308-2309` — three `!important` flags exist only to beat the project's own `.lp-wallet-card__row strong` selector.

**Fix:** use specificity, not force:
```css
.lp-wallet-card__row strong.lp-wallet-card__highlight { color: var(--lp-cyan); font-size: 1.0625rem; }
.lp-wallet-card__row strong.lp-wallet-card__deduct { color: var(--lp-danger-soft); }
```

## 6. Tokenize the strays 🟠

Recurring hardcoded values that block a clean white-label re-skin (the app already supports tenant branding):
- `#E2E8F0` light border ×10 (566, 669, 697, 847, 858, 1191, 1279, 1307, 1329, 1732, 1746)
- `#E53E3E` / `#FC8181` danger (875, 1125, 2309, 2333, 2343)
- `#002244` gradient stop (270, 409); `#0d1526`/`#1a2744` device frames (1605, 1641)
- Alert palette `#ECFDF3/#166534/#BBF7D0/#FEF2F2/#991B1B/#FECACA` (1357-1358)

**Fix:**
```css
:root {
  --lp-border-light: #E2E8F0;
  --lp-danger: #E53E3E;
  --lp-danger-soft: #FC8181;
  --lp-primary-850: #002244;
}
```
…then swap occurrences.

## 7. FAQ clipping trap 🟡

`.lp-faq__item.is-open .lp-faq__answer { max-height: 300px }` — any answer taller than 300px (likely once Bengali text lengthens answers, or large system fonts on 320px phones) silently truncates. Fix together with the animation change → [07-animations.md](07-animations.md) §5 (grid-rows technique removes the cap entirely).

## 8. Small-viewport hero 🟡

```css
.lp-hero { min-height: 100vh; min-height: 100svh; } /* add the svh line */
```
On mobile Chrome/Safari, `100vh` includes the collapsed URL bar → hero CTA can land below the fold, worsened by the fixed bottom mobile CTA bar. Also consider a 420px breakpoint: price size 2.5rem→2rem, hero title clamp floor 2rem.

## 9. Paint cleanup 🟡

- Delete `filter: drop-shadow(...)` on `.lp-hero__laptop img` (374-378) — the browser-frame already has a box-shadow and clips the drop-shadow anyway. Pure wasted paint.
- Unstyled hook classes in markup (`.lp-phone-showcase`, `.lp-split__content`, `.lp-split__visual`): remove or leave a CSS comment noting they're intentional structure hooks.

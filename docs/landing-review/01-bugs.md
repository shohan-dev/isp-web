# 🐛 01 — Verified Bugs

> Real, reproducible defects — not opinions. Every one was verified against the actual source. Fix order = table order.

| # | Severity | Bug | Where |
|---|---|---|---|
| 1 | 🔴 HIGH | Contact form redirects to a non-existent anchor | `AuthController.php:95,143,149` |
| 2 | 🔴 HIGH | Hero wave divider is white between two dark sections | `hero.php:82` + `landing.css:1732-1740` |
| 3 | 🔴 HIGH | Comparison table's own-column highlight is dead CSS | `comparison.php:9` + `landing.css:877` |
| 4 | 🟠 MED | Wallet "Month 1/2 −৳1,400" preview never updates | `pricing.php:235-244` |
| 5 | 🟠 MED | Product tabs all show the identical image | `product_preview.php:12-32` |
| 6 | 🟠 MED | `#lp-switch-to-payg` gets two competing scroll handlers | `landing.js:223-230` + `409-438` |
| 7 | 🟡 LOW | Formula shows `৳1.5` instead of `৳1.50` after slider moves | `landing.js:300` |
| 8 | 🟡 LOW | 'IBM Plex Mono' referenced but never loaded | `landing.css:2208` vs `:2` |
| 9 | 🟡 LOW | Dead `#` social links (LinkedIn/YouTube) scroll to top | `footer.php:45-46` |

---

## 1. Contact form redirect → users think their demo request failed 🔴

**What happens:** the form (`cta_contact.php:59`) posts to `AuthController::store`, which redirects to `/auth/home#contact_us` on **every** path. The section id is actually `lp-contact` (`cta_contact.php:12`). So after submitting, the user lands at the **top** of a 17-section page while the success/error flash renders far below inside the form. They assume it failed and abandon — this is silently killing demo leads.

**Fix** — in `AuthController.php` lines 95, 143, 149:

```php
// before
return redirect()->to('/auth/home#contact_us')->with(...);
// after
return redirect()->to(route_to('route.auth.home') . '#lp-contact')->with(...);
```

**Bonus guard** (same method, line ~93): if the reCAPTCHA HTTP call fails, `json_decode` returns `null` and `$responseKeys["success"]` warns/throws in dev:

```php
$responseKeys = json_decode($response, true);
if (!is_array($responseKeys) || empty($responseKeys['success'])) {
    return redirect()->to(route_to('route.auth.home') . '#lp-contact')
        ->with('error', 'Captcha verification failed. Please try again.');
}
```

## 2. White wave seam under the hero 🔴

`hero.php:82` renders `<div class="lp-section-wave lp-section-wave--to-light">` — a 64px **light** (`var(--lp-surface-light)`) curved band. But the next section (`home.php:83`, trust marquee) is **dark**. Result: a bright white curve sandwiched between two dark sections, right under the most-viewed area of the page.

**Fix (pick one):**
- Delete the wave div from `hero.php:82` (simplest), or
- Restyle `.lp-section-wave--to-light` to the trust strip's dark background, or
- Reorder so a light section follows the hero.

## 3. Comparison table highlight never renders 🔴

`landing.css:877` styles `.lp-compare col.lp-col-us { background: rgba(247,88,3,0.04) }` — but `comparison.php` contains **no `<colgroup>`/`<col>` at all**. The one device meant to make the "ISP Pay BD" column pop does nothing.

**Fix** — in `comparison.php:9`, right after `<table class="lp-compare">`:

```html
<colgroup><col><col class="lp-col-us"><col></colgroup>
```

Also raise the tint — `0.04` alpha is nearly invisible on white; use `0.08` and consider a 2px orange top border on the ISP Pay BD `<th>`.

## 4. Hardcoded wallet deduct preview contradicts the calculator 🟠

`pricing.php:238,242` hardcode `−৳1,400` for Month 1/Month 2. `landing.js` never touches `#lp-wallet-animation` (verified: zero references). Slide to 5,000 users → the card correctly shows `−৳8,000` while the preview below still says `−৳1,400`. Contradictory numbers **inside the same card**.

**Fix** — in `updatePaygWallet()` (`landing.js:278`):

```js
document.querySelectorAll('#lp-wallet-animation .lp-wallet-deduct-amount')
  .forEach(function (el) { el.textContent = '−' + fmt(cost.total); });
```

Optionally render one line per selected top-up month instead of a fixed two.

## 5. "See It In Action" tabs are fake 🟠

All five tabs (`product_preview.php:12,17,22,27,32`) set `data-image` to the same `$dashboardImg`. Clicking "Billing" or "Mobile App" crossfades to the **identical screenshot** (Mobile App shows a laptop UI). Reads as broken or dishonest at a trust-critical moment.

**Fix:** capture one real screenshot per module (phone-framed for Mobile App), set distinct `data-image` values, lazy-load them. Until real screenshots exist, remove the tabs and show the single dashboard shot + bullets.

## 6. Double scroll handlers race on the PAYG switch link 🟠

`#lp-switch-to-payg` gets **both** the panel-switch `scrollIntoView` (`landing.js:224-229`) **and** the global `initSmoothScroll` rAF animation (`landing.js:410-437`) — two competing scroll animations on one click. With JS disabled, its `href="#lp-panel-payg"` targets a hidden element and does nothing.

**Fix:** delete `initSmoothScroll` entirely (see [07-animations.md](07-animations.md) §1 — it causes three separate bugs), and change the href fallback to `#lp-pricing`.

## 7. `৳1.5` vs `৳1.50` 🟡

Server renders `× ৳1.50` (`pricing.php:171`); the JS rewrite concatenates raw `perUser = 1.5` → shows `× ৳1.5` the moment the slider moves. Small, but it's a trust wobble in the one section that's all about numeric precision.

**Fix** — `landing.js:300`: use `perUser.toFixed(2)`.

## 8. Phantom font 🟡

`landing.css:2208` sets `font-family: 'IBM Plex Mono', monospace` on the PAYG formula, but line 2 only imports Inter + Plus Jakarta Sans. Falls back to OS default mono — looks different per device.

**Fix:** either add IBM Plex Mono to the font request, or change to `font-family: ui-monospace, 'Cascadia Mono', monospace;`.

## 9. Dead social links 🟡

`footer.php:45-46` — LinkedIn and YouTube link to `#`, which the smooth-scroll handler turns into "scroll to top". Reads as broken.

**Fix:** fill in real URLs or remove the icons.

# ⚡ 02 — Performance

> **This is the single biggest lever on the whole page.** The audience is on Bangladeshi mobile connections; current asset weight can push LCP past 10–20 seconds.

## Issues

| # | Severity | Issue | Where |
|---|---|---|---|
| 1 | 🚨 CRITICAL | 3.96 MB PNG is the eager-loaded hero/LCP image | `hero.php:48` |
| 2 | 🔴 HIGH | Render-blocking chain: Font Awesome CDN + Google Fonts `@import` | `home.php:70`, `landing.css:2` |
| 3 | 🔴 HIGH | reCAPTCHA JS loads unconditionally on every visit | `home.php:107` |
| 4 | 🟠 MED | `ssl_pay1.png` is 965 KB (lazy, but still wasted bandwidth) | `partners.php:39` |
| 5 | 🟠 MED | Trust marquee renders 8 copies of every partner logo (~80 `<img>` nodes) | `trust.php:14-24` |
| 6 | 🟡 LOW | Hero image double-shadow = wasted paint | `landing.css:374-378` + `:1609` |

---

## 1. The hero image 🚨

`assets/img/icon/laptop-screen.png` = **3,955,691 bytes**, rendered at 560×350 CSS px, `loading="eager"` as the LCP element (`hero.php:48`) — and **reused for all five product-preview tabs** (`product_preview.php`).

**Fix (do all):**
1. Export as WebP (or AVIF) at ~1120px wide (2× of 560). Target: **< 100 KB**.
   ```
   # e.g. with cwebp
   cwebp -q 82 -resize 1120 0 laptop-screen.png -o laptop-screen.webp
   ```
2. In `hero.php:48`: add `fetchpriority="high"`, keep `width/height`, use the webp.
3. In `home.php` `<head>`:
   ```html
   <link rel="preload" as="image" href="<?= base_url('assets/img/icon/laptop-screen.webp') ?>" fetchpriority="high">
   ```
4. Keep `loading="lazy"` on the below-fold product-preview copy.

## 2. Render-blocking third-party chain 🔴

Worst-case pattern currently in place:
- `home.php:70` pulls **full Font Awesome 6.7.2** (~100 KB CSS + font files) for ~50 icons.
- `landing.css:2` uses `@import` for Google Fonts → browser downloads `landing.css`, *then* discovers the fonts CSS, *then* the font files — all render-blocking, no preconnect anywhere.

**Fix:**
1. Move fonts out of `@import` into `home.php` `<head>`:
   ```html
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
   ```
   Delete `landing.css:2`. (Better still: self-host with `font-display: swap`.)
2. Replace Font Awesome with an inline SVG sprite of the ~50 icons actually used, or a subset build. (Grep `fa-` across the partials to enumerate.)
3. Decide on `'IBM Plex Mono'` (`landing.css:2208`) — load it or drop the reference (see [01-bugs.md](01-bugs.md) §8).

## 3. Lazy-load reCAPTCHA 🔴

`home.php:107` loads `recaptcha/api.js` on every visit for a form at the very bottom of the page.

**Fix** — load it when the contact section approaches, reusing the IO pattern already in `landing.js`:

```js
var contact = document.getElementById('lp-contact');
if (contact && 'IntersectionObserver' in window) {
  var recaptchaIO = new IntersectionObserver(function (entries) {
    if (entries[0].isIntersecting) {
      var s = document.createElement('script');
      s.src = 'https://www.google.com/recaptcha/api.js';
      s.async = true; s.defer = true;
      document.head.appendChild(s);
      recaptchaIO.disconnect();
    }
  }, { rootMargin: '600px' });
  recaptchaIO.observe(contact);
}
```

## 4. Compress `ssl_pay1.png` 🟠

965,413 bytes at `assets/img/icon/ssl_pay1.png` (`partners.php:39`). It's `loading="lazy"` below the fold so it doesn't hit LCP — but it's ~50 KB of content in a ~965 KB file. Convert to WebP.

## 5. Trust marquee DOM inflation 🟠

`trust.php:14-16` does 4× `array_merge` then renders the list **twice** → 8 copies of every logo ≈ 80 `<img>` nodes for 10 logos. 2 copies are enough for a seamless CSS marquee loop.

**Fix:** reduce to a single duplication (list ×2), add `alt=""` to decorative logos.

## 6. Wasted paint on hero image 🟡

`landing.css:374-378` puts `filter: drop-shadow(...)` on the laptop img, which sits inside `.lp-browser-frame` that already has `box-shadow` (`:1609`) — and the frame's `overflow:hidden` clips the drop-shadow anyway. Delete the `filter`.

---

## Weight budget target

| Asset | Now | Target |
|---|---|---|
| laptop-screen.png | 3,956 KB | **< 100 KB** (WebP) |
| ssl_pay1.png | 965 KB | < 60 KB (WebP) |
| Font Awesome CSS+fonts | ~130 KB | < 15 KB (SVG sprite) |
| Google Fonts | @import chain | preconnect + link, swap |
| landing.js | 18.7 KB | fine as-is |
| landing.css | ~60 KB | ~55 KB after dedup ([06](06-design-uiux.md)) |

**Expected outcome:** LCP from 10–20 s (slow 4G) → **< 2.5 s**.

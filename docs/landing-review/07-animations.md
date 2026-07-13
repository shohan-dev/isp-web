# 🎬 07 — Animations & Smoothness Recipe — 6.5/10

> **Verdict:** the foundation is genuinely good (IO reveals, rAF counters, one easing token, dual reduced-motion gates). Two hotspots cause real jank on low-end Androids — exactly the devices this audience uses — plus a set of polish upgrades that will make the page feel premium.

## ✅ What's already right (do NOT change)

- The easing token `--lp-ease: cubic-bezier(0.16, 1, 0.3, 1)` used everywhere — coherent motion signature.
- `.lp-reveal` 0.7s translateY(32px)+opacity, IO with unobserve-after-fire — GPU-friendly.
- `.lp-stagger-children` 50ms nth-child stagger (tip: cap visible stagger at ~6 children).
- Counters: rAF + easeOutExpo over 2200ms.
- Nav spy: IO with `rootMargin: '-35% 0px -55% 0px'` — no scroll thrash.
- Reduced motion respected in **both** CSS (`landing.css:41-48`) and JS (5 separate gates).
- Ambient motion (float, orb-drift, marquee) is transform-based and cheap.

---

## The 7-Step Smoothness Upgrade

### 1. 🔴 DELETE `initSmoothScroll` — it fights the browser

`html` already has `scroll-behavior: smooth` + `scroll-padding-top` (`landing.css:37-38`). But `initSmoothScroll` (`landing.js:409-438`) preventDefaults every anchor click and calls `window.scrollTo(0, y)` **per rAF frame**. Per the CSSOM spec, 2-arg `scrollTo` resolves behavior from computed `scroll-behavior` — so with `smooth` set, *every frame's scrollTo starts its own browser-smoothed scroll*. Two easings stack → rubber-band jank in Chrome/Firefox.

**Fix: delete the entire function + its init call.** One deletion fixes three bugs at once:
- the double-easing jank,
- the skip-link focus bug (it preventDefaults `#lp-main` too → keyboard focus never moves),
- the `#lp-switch-to-payg` double-handler race ([bug #6](01-bugs.md)).

*(If you insist on JS easing: change line 432 to `window.scrollTo({ top: ..., behavior: 'instant' })` — but deleting is better.)*

### 2. 🔴 Shimmer button: animate `transform`, not `left`

`landing.css:1713-1729` — `@keyframes lp-shimmer` animates `left: -100% → 150%` at 3s **infinite** = infinite layout invalidation on the primary hero CTA.

```css
.lp-btn--shimmer::after {
  /* was: left: -100%; animation: lp-shimmer 3s ease-in-out infinite; */
  left: 0;
  transform: translateX(-170%);
  will-change: transform;
  animation: lp-shimmer 3s ease-in-out infinite;
}
@keyframes lp-shimmer {
  0%        { transform: translateX(-170%); }
  50%, 100% { transform: translateX(280%); }
}
```

### 3. 🟠 Hero parallax: rAF + lerp (this is the "buttery" fix)

`landing.js:445-449` writes `style.transform` on every raw `mousemove` (can fire faster than frame rate), no smoothing, listener stays attached after the hero scrolls away, no `will-change`.

```js
function initParallax() {
  if (prefersReducedMotion || window.innerWidth < 1024) return;
  var stack = document.querySelector('.lp-hero__device-stack');
  var hero = document.getElementById('lp-hero');
  if (!stack || !hero) return;

  var tx = 0, ty = 0, cx = 0, cy = 0, running = false;

  function loop() {
    cx += (tx - cx) * 0.08;               // lerp = the smoothness
    cy += (ty - cy) * 0.08;
    stack.style.transform = 'translate3d(' + cx.toFixed(2) + 'px,' + cy.toFixed(2) + 'px,0)';
    if (running) requestAnimationFrame(loop);
  }
  document.addEventListener('mousemove', function (e) {
    tx = (e.clientX / window.innerWidth - 0.5) * 20;
    ty = (e.clientY / window.innerHeight - 0.5) * 12;
  });
  new IntersectionObserver(function (entries) {  // stop the loop when hero off-screen
    var vis = entries[0].isIntersecting;
    if (vis && !running) { running = true; requestAnimationFrame(loop); }
    if (!vis) running = false;
  }, { threshold: 0 }).observe(hero);
}
```
```css
.lp-hero__device-stack { will-change: transform; }
```

### 4. 🟠 Nav: never transition `backdrop-filter`

`landing.css:88` transitions `backdrop-filter 0.4s` when `.is-scrolled` toggles — 400ms of full-width blur repaints **exactly while the user is scrolling**. Classic mobile-jank hotspot.

```css
.lp-nav {
  backdrop-filter: blur(20px);            /* applied permanently */
  background: rgba(0, 16, 51, 0);         /* transparent → blur invisible */
  transition: background 0.35s var(--lp-ease), box-shadow 0.35s var(--lp-ease);
}
.lp-nav.is-scrolled { background: rgba(0, 16, 51, 0.85); }
```
The background fade reads identically — without the repaint storm. Also reduce the mobile-menu blur from 20px to 8–10px.

### 5. 🟠 FAQ accordion: grid-rows, not max-height

`landing.css:1214-1222` transitions `max-height 0 → 300px` over 0.4s. Real answers are much shorter than 300px, so opening finishes visually in a fraction of the duration (abrupt) while closing appears delayed — **and** anything taller than 300px clips (a real risk once Bengali text lengthens answers).

```css
.lp-faq__answer {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.35s var(--lp-ease);
}
.lp-faq__answer > div { overflow: hidden; min-height: 0; }  /* wrap answer content in a div */
.lp-faq__item.is-open .lp-faq__answer { grid-template-rows: 1fr; }
```
Accurate timing at any content height, no clip limit, no JS measuring.

### 6. 🟠 Product tabs: sync timers + wait for decode

`landing.js:141-157`: image swaps at 250ms against a 350ms transition (new image appears while fade-out is ~30% incomplete); bullets swap at 150ms against 300ms; the fade-in doesn't wait for the image to load → empty flash on slow connections.

```js
if (img) {
  preview.style.opacity = '0';
  preview.style.transform = 'scale(0.98)';
  setTimeout(function () {
    var next = new Image();
    next.src = img;
    (next.decode ? next.decode().catch(function(){}) : Promise.resolve()).then(function () {
      preview.src = img;
      preview.style.opacity = '1';
      preview.style.transform = 'scale(1)';
    });
  }, 175);                                  // = half of the 350ms transition
}
```
Also preload the other tabs' images on first interaction (`new Image().src = ...`), and move the inline transitions (`landing.js:453-462`) into the stylesheet.

### 7. ✨ Micro-polish (the "premium feel" layer)

**a) Tween prices on the monthly↔yearly toggle** — they currently jump instantly even though a tween utility already exists (`landing.js:89-107`). Reuse it: animate old→new over ~450ms with easeOutExpo + a `scale(1.04)` pulse (mirror `.is-updating`, `landing.css:2285-2287`).

**b) Crossfade pricing panels** — currently the outgoing panel vanishes via `display:none` (flash), only the incoming animates. Stack both in a grid container; transition `opacity 0.25s` + `translateY(8px)` out, then `0.35s var(--lp-ease)` in; toggle `visibility` after the fade, not `display`.

**c) Progress bars: `scaleX`, not `width`** — `landing.css:2322` and `:1843` animate `width` (layout):
```css
.lp-wallet-card__progress-bar {
  transform-origin: left;
  transition: transform 0.6s var(--lp-ease);
  /* JS: el.style.transform = 'scaleX(' + ratio + ')'; instead of width */
}
```

**d) Feature search: fade instead of snap** — `display:none` per keystroke = hard grid cut:
```css
.lp-feature.is-hidden { opacity: 0; transform: scale(0.96); }
.lp-feature { transition: opacity 0.2s var(--lp-ease), transform 0.2s var(--lp-ease); }
```
(then set `display:none` after 200ms, or use `transition-behavior: allow-discrete` where supported).

**e) Testimonial autoplay: make it polite** — gate the 5s interval with an IO on the track (pause off-screen), pause on `pointerdown`/`touchstart`, resume 8s after last interaction, sync dots from a rAF-debounced scroll listener, and use `matchMedia('(max-width:768px)').addEventListener('change', ...)` instead of the one-time `innerWidth` check (`landing.js:399-405`).

**f) Ban `transition: all`** — `.lp-pricing-model__btn` (`landing.css:2014`): replace with an explicit list `transition: border-color 0.35s var(--lp-ease), background 0.35s, box-shadow 0.35s;`.

---

## Priority order

| Step | Impact | Effort |
|---|---|---|
| 1. Delete initSmoothScroll | 🔥 fixes 3 bugs | 2 min |
| 2. Shimmer → transform | 🔥 main CTA jank | 5 min |
| 4. Nav backdrop-filter | 🔥 scroll jank | 5 min |
| 3. Parallax rAF+lerp | high — hero feel | 20 min |
| 5. FAQ grid-rows | med + removes clip bug | 15 min |
| 6. Tab decode sync | med | 15 min |
| 7. Micro-polish a–f | premium feel | 1–2 h |

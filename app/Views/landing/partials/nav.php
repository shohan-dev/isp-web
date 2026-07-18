<?php
    // Scrollspy in landing.js only matches `.lp-nav__links a[href^="#"]`, and a
    // same-path hash change is what gives an instant in-page scroll instead of
    // a full reload — so on the real homepage these must stay bare `#lp-x`
    // (original behavior, zero regression). Only when this nav renders on a
    // different route (e.g. /plugins) do the links need the homepage's own
    // path prefixed, otherwise they 404/no-op against the current page.
    $lpHomeHref = route_to('route.home');
    $lpOnHome = rtrim(parse_url(current_url(), PHP_URL_PATH) ?? '', '/') === rtrim(parse_url($lpHomeHref, PHP_URL_PATH) ?? '', '/');
    $lpAnchor = static fn (string $hash) => $lpOnHome ? $hash : ($lpHomeHref . $hash);
    $lpIsPluginsPage = (strpos(current_url(), '/plugins') !== false);
?>
<nav class="lp-nav" id="lp-nav" aria-label="Main navigation">
    <div class="lp-nav__inner">
        <?= view('landing/partials/_brand_logo', compact('logoUrl', 'appName', 'brandUserId', 'tenant')); ?>
        <ul class="lp-nav__links">
            <li><a href="<?= $lpAnchor('#lp-features') ?>">Features</a></li>
            <li><a href="<?= $lpAnchor('#lp-how-it-works') ?>">How it works</a></li>
            <li><a href="<?= $lpAnchor('#lp-pricing') ?>">Pricing</a></li>
            <li><a href="<?= $lpAnchor('#lp-proof') ?>">Trusted By</a></li>
            <li><a href="<?= route_to('route.plugins.index') ?>" class="<?= $lpIsPluginsPage ? 'is-active' : '' ?>">Plugins</a></li>
            <li><a href="<?= $lpAnchor('#lp-contact') ?>">Contact</a></li>
        </ul>
        <div class="lp-nav__actions">
            <?php /* EN / বাং language toggle hidden per request — uncomment to restore.
            <div class="lp-lang-toggle" role="group" aria-label="Language">
                <button type="button" class="lp-lang-toggle__btn is-active" data-lp-lang="en" aria-pressed="true">EN</button>
                <button type="button" class="lp-lang-toggle__btn" data-lp-lang="bn" aria-pressed="false">বাং</button>
            </div>
            */ ?>
            <?php if (!empty(getSession('user_id')) && !empty(getSession('user_role'))): ?>
                <a href="<?= route_to('route.dashboard') ?>" class="lp-btn lp-btn--outline lp-btn--sm">Dashboard</a>
            <?php else: ?>
                <a href="<?= route_to('route.auth.login') ?>" class="lp-btn lp-btn--ghost lp-btn--sm">Login</a>
            <?php endif; ?>
            <a href="<?= $lpAnchor('#lp-contact') ?>" class="lp-btn lp-btn--outline lp-btn--sm" data-lp-inquiry="Demo Request">Book Demo</a>
            <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--sm">Start Free Trial</a>
        </div>
        <button class="lp-nav__toggle" id="lp-nav-toggle" aria-label="Open menu" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<div class="lp-mobile-menu" id="lp-mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile menu">
    <!-- .lp-mobile-menu__close has no CSS padding (global *{padding:0} reset), so its
         hit area was just the raw fa-times glyph box (~24x28px) inside a full-screen
         mobile-only overlay — well under the ~40-44px tap-target floor. Inline padding
         + matching top/right offset keeps the icon visually anchored at ~20px from the
         corner while growing the tappable box to ~52x56px. This element only renders
         inside .lp-mobile-menu (mobile-only overlay), so it never touches desktop. -->
    <button class="lp-mobile-menu__close" id="lp-mobile-close" aria-label="Close menu" style="top:6px;right:6px;padding:14px;"><i class="fas fa-times"></i></button>
    <a href="<?= $lpAnchor('#lp-features') ?>">Features</a>
    <a href="<?= $lpAnchor('#lp-how-it-works') ?>">How it works</a>
    <a href="<?= $lpAnchor('#lp-pricing') ?>">Pricing</a>
    <a href="<?= $lpAnchor('#lp-proof') ?>">Trusted By</a>
    <a href="<?= route_to('route.plugins.index') ?>" class="<?= $lpIsPluginsPage ? 'is-active' : '' ?>">Plugins</a>
    <a href="<?= $lpAnchor('#lp-contact') ?>">Contact</a>
    <?php /* EN / বাং language toggle hidden per request — uncomment to restore.
    <div class="lp-lang-toggle lp-lang-toggle--stack" role="group" aria-label="Language">
        <button type="button" class="lp-lang-toggle__btn is-active" data-lp-lang="en" aria-pressed="true">English</button>
        <button type="button" class="lp-lang-toggle__btn" data-lp-lang="bn" aria-pressed="false">বাংলা</button>
    </div>
    */ ?>
    <?php if (!empty(getSession('user_id')) && !empty(getSession('user_role'))): ?>
        <a href="<?= route_to('route.dashboard') ?>">Dashboard</a>
    <?php else: ?>
        <a href="<?= route_to('route.auth.login') ?>">Login</a>
    <?php endif; ?>
    <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary">Start Free Trial</a>
</div>

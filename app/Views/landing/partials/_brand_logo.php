<?php
$tenant = $tenant ?? (function_exists('currentTenant') ? currentTenant() : null);
$brandUserId = $brandUserId ?? (function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2);
$logoUrl = $logoUrl ?? resolvePublicBrandLogoUrl($tenant, $brandUserId);
$brandName = $appName ?? $brandTitle ?? resolveBrandTitle($tenant, $brandUserId);
$brandTag = $brandTag ?? resolveBrandTagline($brandUserId);
$logoFull = brandLogoIsFull($logoUrl, $brandUserId);
$wrapClass = $wrapClass ?? 'lp-nav__brand';
?>
<a href="<?= esc($brandHref ?? route_to('route.auth.home'), 'attr') ?>" class="<?= esc($wrapClass, 'attr') ?>" aria-label="<?= esc($brandName); ?> Home">
  <img
    src="<?= esc($logoUrl, 'attr'); ?>"
    alt="<?= esc($brandName); ?>"
    class="lp-brand-logo-img<?= $logoFull ? ' lp-brand-logo-img--full' : ' lp-brand-logo-img--mark'; ?>"
    loading="eager"
    decoding="async"
  />
  <?php if (!$logoFull): ?>
    <span class="lp-nav__brand-text">
      <span class="lp-nav__brand-name"><?= esc($brandName); ?></span>
      <?php if ($brandTag !== ''): ?>
        <span class="lp-nav__brand-tag"><?= esc($brandTag); ?></span>
      <?php endif; ?>
    </span>
  <?php endif; ?>
</a>

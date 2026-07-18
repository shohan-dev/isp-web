<?php
$logoClass = $logoClass ?? 'ipb-auth-logo';
$context = $context ?? 'default';
$brand = safeAuthBrandLogo(
  $context,
  $tenant ?? null,
  $brandUserId ?? null,
  $brandTitle ?? null,
  $appName ?? null
);
$logoUrl = $brand['logoUrl'];
$logoAlt = $brand['logoAlt'];
$logoFull = $brand['logoFull'];
$tagline = $brand['tagline'] ?? '';
// Same pattern as landing/partials/nav.php & footer.php: route_to('route.home')
// already resolves against the CURRENT request's Host, because the global
// 'tenantresolve' filter (app/Filters/TenantResolveFilter.php) rewrites
// config('App')->baseURL to the current tenant subdomain (or the platform
// apex, off-subdomain) on every request, before this view ever renders.
// No separate "current tenant home URL" helper is needed — route_to() IS it.
$authHomeHref = route_to('route.home');
?>
<a href="<?= esc($authHomeHref, 'attr'); ?>" class="<?= esc($logoClass, 'attr'); ?>" aria-label="<?= esc($logoAlt); ?> Home">
  <?php if ($logoFull): ?>
    <img
      src="<?= esc($logoUrl, 'attr'); ?>"
      alt="<?= esc($logoAlt); ?>"
      class="ipb-brand-logo-img ipb-brand-logo-img--full"
      loading="eager"
      decoding="async"
    />
  <?php else: ?>
    <span class="ipb-brand-logo-mark">
      <img
        src="<?= esc($logoUrl, 'attr'); ?>"
        alt="<?= esc($logoAlt); ?>"
        class="ipb-brand-logo-img ipb-brand-logo-img--mark"
        loading="eager"
        decoding="async"
      />
    </span>
    <?php if ($context === 'auth-login' && $tagline !== ''): ?>
      <span class="ipb-auth-logo-text">
        <span class="ipb-auth-logo-name"><?= esc($logoAlt); ?></span>
        <span class="ipb-auth-logo-tag"><?= esc($tagline); ?></span>
      </span>
    <?php elseif ($context === 'auth-login'): ?>
      <span class="ipb-auth-logo-text">
        <span class="ipb-auth-logo-name"><?= esc($logoAlt); ?></span>
      </span>
    <?php endif; ?>
  <?php endif; ?>
</a>

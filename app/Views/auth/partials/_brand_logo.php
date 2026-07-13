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
?>
<div class="<?= esc($logoClass, 'attr'); ?>">
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
</div>

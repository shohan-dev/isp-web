<?php
$authMode = ($authMode ?? 'login') === 'register' ? 'register' : 'login';
$brandUserId = (int) ($brandUserId ?? 2);
$isTenantPortal = !empty($isTenantPortal);
$packages = is_array($packages ?? null) ? $packages : [];
$tenant = $tenant ?? null;
$errors = is_array($errors ?? null) ? $errors : [];

$gateBrand = safeAuthGateBranding($tenant, $brandUserId, $isTenantPortal);
$appName = $gateBrand['appName'];
$appSlogan = $gateBrand['appSlogan'];
$logoUrl = $gateBrand['logoUrl'];
$brandTitle = $gateBrand['brandTitle'];
$primaryColor = $gateBrand['primaryColor'];

$loginUrl = route_to('route.auth.login');
$registerUrl = route_to('route.auth.registration');
$pageTitle = $authMode === 'register' ? 'Start free trial' : 'Sign in';
$gateViewData = compact(
  'authMode', 'brandUserId', 'isTenantPortal', 'packages', 'appName', 'appSlogan',
  'logoUrl', 'brandTitle', 'errors', 'tenant', 'primaryColor'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">
  <title><?= esc($pageTitle); ?> | <?= esc($brandTitle); ?></title>
  <meta name="description" content="<?= esc($appSlogan); ?>">
  <?= renderBrandFaviconTags(); ?>
  <!-- 08 §10 / 07 F3 — self-hosted Font Awesome (was cdnjs; a blocked CDN silently drops every icon on the auth critical path) -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">
  <?= saas_css('tokens.css') ?>
  <?= saas_css('base.css') ?>
  <?= saas_css('components.css') ?>
  <?= saas_css('auth.css') ?>
  <!-- 08 §10 / 07 F3 — CDN tata CSS removed: the app's own toast.css fully
       re-styles .tata and needs no base CSS (verified: the main shell loads
       zero tata CSS at all and toasts work). -->
  <?= saas_css('toast.css') ?>
  <?php if ($primaryColor): ?>
    <style>:root { --primary-500: <?= esc($primaryColor, 'attr'); ?>; --primary-600: <?= esc($primaryColor, 'attr'); ?>; }</style>
  <?php endif; ?>
</head>
<body
  class="ipb ipb-auth-gate-body<?= $authMode === 'register' ? ' is-register' : ''; ?>"
  data-theme="light"
  data-auth-mode="<?= esc($authMode); ?>"
  data-login-url="<?= esc($loginUrl, 'attr'); ?>"
  data-register-url="<?= esc($registerUrl, 'attr'); ?>"
  data-tenant-only="<?= $isTenantPortal ? '1' : '0'; ?>"
>
  <div class="ipb-auth-stage">
    <div class="ipb-auth-split" id="authSplit">
      <!-- LEFT: login (default) · brand when registering -->
      <div class="ipb-auth-col ipb-auth-col--left">
        <div class="ipb-auth-col-track">
          <div class="ipb-auth-pane ipb-auth-pane--login" aria-label="Sign in">
            <?= view('auth/partials/_login_panel', $gateViewData); ?>
          </div>
          <?php if (!$isTenantPortal): ?>
          <div class="ipb-auth-pane ipb-auth-pane--brand ipb-auth-pane--brand-left" aria-hidden="true">
            <?= view('auth/partials/_brand_panel', array_merge($gateViewData, ['variant' => 'register'])); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: brand (default) · register when registering -->
      <div class="ipb-auth-col ipb-auth-col--right">
        <div class="ipb-auth-col-track">
          <div class="ipb-auth-pane ipb-auth-pane--brand ipb-auth-pane--brand-right" aria-label="Product overview">
            <?= view('auth/partials/_brand_panel', array_merge($gateViewData, ['variant' => 'login'])); ?>
          </div>
          <?php if (!$isTenantPortal): ?>
          <div class="ipb-auth-pane ipb-auth-pane--register" aria-label="Registration" aria-hidden="true">
            <?= view('auth/partials/_register_panel', $gateViewData); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
  <!-- 08 §10 / 07 F3 — self-hosted tata.js (was cdn.jsdelivr.net) -->
  <script src="<?= base_url('assets/vendor/tatajs/tata.js'); ?>"></script>
  <?= view('auth/partials/_gate_scripts', ['brandTitle' => $brandTitle]); ?>
</body>
</html>

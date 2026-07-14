<?php
helper(['utility', 'tenant']);
$brandUserId = 2;
$tenant = null;
$isTenantPortal = false;

try {
    $brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
    $tenant = function_exists('currentTenant') ? currentTenant() : null;
    $isTenantPortal = function_exists('isTenantRequest') && isTenantRequest();
} catch (\Throwable $e) {
    log_message('error', 'Auth login view branding: ' . $e->getMessage());
}

$gateBrand = safeAuthGateBranding($tenant, $brandUserId, $isTenantPortal);
$appName = $gateBrand['appName'];
$appSlogan = $gateBrand['appSlogan'];
$logoUrl = $gateBrand['logoUrl'];
$brandTitle = $gateBrand['brandTitle'];
$primaryColor = $gateBrand['primaryColor'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">
  <title>Sign in | <?= esc($brandTitle); ?></title>
  <meta name="description" content="<?= esc($appSlogan); ?>">
  <?= renderBrandFaviconTags(); ?>
  <!-- 08 §10 / 07 F3 — self-hosted Font Awesome (was cdnjs; a blocked CDN silently drops every icon on the auth critical path) -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">
  <?= saas_css('tokens.css') ?>
  <?= saas_css('base.css') ?>
  <?= saas_css('components.css') ?>
  <?= saas_css('auth.css') ?>
  <?php if ($primaryColor): ?>
    <style>:root { --primary-500: <?= esc($primaryColor, 'attr'); ?>; --primary-600: <?= esc($primaryColor, 'attr'); ?>; }</style>
  <?php endif; ?>
</head>
<body class="ipb ipb-auth-page ipb-auth-page--login" data-theme="light">
  <div class="ipb-auth ipb-auth--login">
    <aside class="ipb-auth-brand" aria-hidden="false">
      <div class="ipb-auth-brand-inner">
        <div class="ipb-brand-mark"><i class="fa fa-bolt" aria-hidden="true"></i></div>
        <?php if ($isTenantPortal): ?>
          <h1><?= esc($brandTitle); ?></h1>
          <p class="ipb-brand-lead"><?= esc($appSlogan ?: 'Sign in to manage your network, customers, and billing in one place.'); ?></p>
        <?php else: ?>
          <h1>The complete billing &amp; network platform for ISPs</h1>
          <p class="ipb-brand-lead">Billing, CRM, Mikrotik, OLT monitoring and reseller management — one connected platform built for growing ISPs.</p>
          <ul class="ipb-auth-features">
            <li><i class="fa fa-chart-line" aria-hidden="true"></i><span>Automated billing, invoicing and payment collection</span></li>
            <li><i class="fa fa-network-wired" aria-hidden="true"></i><span>Mikrotik PPPoE, hotspot and network automation</span></li>
            <li><i class="fa fa-users" aria-hidden="true"></i><span>Reseller portals, referrals and customer CRM</span></li>
          </ul>
          <div class="ipb-auth-trust">
            <div class="ipb-auth-trust-item"><strong>14 days</strong>Free trial</div>
            <div class="ipb-auth-trust-item"><strong>24/7</strong>Support ready</div>
            <div class="ipb-auth-trust-item"><strong>100%</strong>Cloud SaaS</div>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <main class="ipb-auth-panel">
      <div class="ipb-auth-panel-inner">
        <div class="ipb-auth-card">
          <?= view('auth/partials/_brand_logo', [
            'brandTitle' => $brandTitle,
            'appName' => $appName,
            'tenant' => $tenant,
            'brandUserId' => $brandUserId,
            'context' => 'auth-login',
          ]); ?>
          <h1>Welcome back</h1>
          <p class="sub">Sign in to your <?= esc($brandTitle); ?> dashboard</p>

          <?php
          $req = service('request');
          if ($req->getGet('registered')): ?>
            <?php /* Same status component as the sign-in result below, rather than a
                     second, differently-shaped Bootstrap alert in the same card. */ ?>
            <div class="ipb-auth-status is-success" role="status" style="margin-top:18px">
              <span class="ipb-auth-status-mark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
              <div class="ipb-auth-status-body">
                <p class="ipb-auth-status-title">Registration submitted</p>
                <p class="ipb-auth-status-msg">
                  Sign in below with your email and password.
                  <?php if ($req->getGet('pending')): ?>
                    Your account may stay inactive until approval is complete.
                  <?php endif; ?>
                </p>
              </div>
            </div>
          <?php endif; ?>

          <?= form_open(route_to('route.auth.login.validate'), ['id' => 'form', 'style' => 'margin-top:22px']); ?>
          <div id="feedback"></div>

          <div class="form-group">
            <label class="control-label" for="email">Email address</label>
            <?= form_input(['type' => 'text', 'name' => 'email', 'id' => 'email', 'class' => 'form-control', 'placeholder' => 'you@company.com', 'autocomplete' => 'username']); ?>
            <small id="email-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label class="control-label" for="password">Password</label>
            <div class="ipb-auth-password-wrap">
              <?= form_input(['type' => 'password', 'name' => 'password', 'class' => 'form-control', 'id' => 'password', 'placeholder' => 'Enter your password', 'autocomplete' => 'current-password']); ?>
              <button type="button" class="ipb-pass-toggle" id="toggle-password" aria-label="Show password">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small id="password-error" class="error text-danger"></small>
          </div>

          <div style="text-align:right;margin:-4px 0 18px">
            <a href="<?= route_to('route.auth.forgot'); ?>" style="color:var(--primary-500);font-weight:700;font-size:13px;text-decoration:none">Forgot password?</a>
          </div>

          <?= form_button(['content' => 'Sign in', 'class' => 'btn btn-primary btn-block', 'type' => 'submit', 'style' => 'width:100%']); ?>

          <?php if (!$isTenantPortal): ?>
            <div class="ipb-auth-links">
              Don't have an account?
              <a href="<?= route_to('route.auth.registration'); ?>">Create free trial</a>
            </div>
          <?php endif; ?>
          <?= form_close(); ?>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
          <?= view('auth/_transition'); ?>
  <script>
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    togglePassword.addEventListener('click', function () {
      const type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      const icon = togglePassword.querySelector('i');
      icon.classList.toggle('fa-eye', type === 'password');
      icon.classList.toggle('fa-eye-slash', type !== 'password');
      togglePassword.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
    });

    const loginUrl = '<?= route_to("route.auth.login.validate"); ?>';

    (function () {
      var params = new URLSearchParams(window.location.search);
      var user = params.get('user');
      if (user) {
        var emailInput = document.querySelector('input[name="email"]');
        if (emailInput) emailInput.value = decodeURIComponent(user);
      }
    })();

    /* How long the success state is shown before the redirect fires. The progress
       bar is driven from the same number (via --ipb-auth-redirect), so the bar can
       never disagree with the actual wait. */
    var LOGIN_REDIRECT_MS = 1200;

    /**
     * The status block inside the auth card. Built as DOM with .text(), never as an
     * HTML string: `msg` comes off the wire, and the old code interpolated it
     * straight into .html().
     */
    function renderAuthStatus(kind, title, msg, showBar) {
      var isSuccess = kind === 'success';

      var $mark = $('<span/>', { 'class': 'ipb-auth-status-mark', 'aria-hidden': 'true' })
        .append($('<i/>', { 'class': isSuccess ? 'fa-solid fa-check' : 'fa-solid fa-triangle-exclamation' }));

      var $body = $('<div/>', { 'class': 'ipb-auth-status-body' })
        .append($('<p/>', { 'class': 'ipb-auth-status-title', text: title }));

      if (msg) {
        $body.append($('<p/>', { 'class': 'ipb-auth-status-msg', text: msg }));
      }

      var $status = $('<div/>', {
        'class': 'ipb-auth-status ' + (isSuccess ? 'is-success' : 'is-error'),
        // Success is polite (it does not interrupt); a failure needs to be announced.
        role: isSuccess ? 'status' : 'alert',
      }).append($mark, $body);

      if (showBar) {
        $status
          .css('--ipb-auth-redirect', LOGIN_REDIRECT_MS + 'ms')
          .append($('<span/>', { 'class': 'ipb-auth-status-bar', 'aria-hidden': 'true' }).append($('<span/>')));
      }

      $("#form").find('#feedback').empty().append($status);
    }

    function showLoginError(msg) {
      renderAuthStatus('error', "Couldn't sign in", msg, false);
    }

    function extractErrorMessage(result, xhr) {
      if (!result || typeof result !== 'object') {
        if (xhr && xhr.status === 403) return 'Session expired or security check failed. Refresh the page and try again.';
        if (xhr && xhr.status === 419) return 'Security token expired. Refresh the page and try again.';
        return 'Something went wrong. Please try again.';
      }
      if (result.status === 'validation-error') return null;
      if (typeof result.response === 'string' && result.response) return result.response;
      if (typeof result.message === 'string' && result.message) return result.message;
      if (typeof result.error === 'string' && result.error) return result.error;
      if (typeof result.msg === 'string' && result.msg) return result.msg;
      if (xhr && xhr.status === 403) return 'Session expired or security check failed. Refresh the page and try again.';
      return 'An error occurred during login.';
    }

    $("#form").submit(function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('browser_os', navigator.userAgent);
      formData.append('screen', `${window.screen.width}x${window.screen.height}`);
      formData.append('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown');
      formData.append('cores', navigator.hardwareConcurrency || 'Unknown');
      formData.append('ram', navigator.deviceMemory ? navigator.deviceMemory + "GB" : 'Unknown');
      formData.append('platform', navigator.platform || 'Unknown');

      const csrfName = '<?= csrf_token() ?>';
      const csrfHash = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="<?= csrf_token() ?>"]')?.value
        || '';
      if (csrfHash && !formData.has(csrfName)) {
        formData.append(csrfName, csrfHash);
      }

      $.ajax({
        url: loginUrl,
        type: 'POST',
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        dataType: 'json',
        beforeSend: function (req) {
          $("#form").find('.error').html("");
          $("#form").find('#feedback').html("");
          $("#form").find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");
          $("#form").find('button[type="submit"]').attr('disabled', 'true');
          const headerName = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content');
          const headerToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          if (headerName && headerToken) {
            req.setRequestHeader(headerName, headerToken);
          }
        },
        success: function (result) {
          $("#form").find('button[type="submit"]').html('Sign in');
          $("#form").find('button[type="submit"]').removeAttr('disabled');
          if (result && result.status === 'success') {
            /* The server's msg is "Login successful. Redirecting to the dashboard..." —
               it restates the outcome AND the next step in one sentence. Split it:
               the headline is what happened, the line under it is what happens next,
               and the bar shows how long that takes. The button stays disabled and
               reads "Signing in" so the form cannot be submitted twice while we wait. */
            $("#form").find('button[type="submit"]')
              .attr('disabled', 'true')
              .html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Signing in');

            renderAuthStatus('success', 'Signed in', 'Taking you to your dashboard…', true);
            setTimeout(() => { location.href = result.response.redirect; }, LOGIN_REDIRECT_MS);
          } else {
            showLoginError((result && result.response) ? result.response : 'Login failed. Please try again.');
          }
        },
        error: function (xhr) {
          $("#form").find('button[type="submit"]').html('Sign in');
          $("#form").find('button[type="submit"]').removeAttr('disabled');
          let result = null;
          try { result = xhr.responseJSON || JSON.parse(xhr.responseText); } catch (err) { result = null; }

          if (result && result.status === 'validation-error') {
            if (typeof result.response === 'object') {
              $.each(result.response, function (prefix, val) {
                $("#form").find('#' + prefix + '-error').text(val);
              });
              showLoginError('Please fix the errors below.');
            } else {
              showLoginError(result.response || 'Please fix the errors below.');
            }
            return;
          }

          showLoginError(extractErrorMessage(result, xhr));
        }
      });
    });
  </script>
</body>
</html>

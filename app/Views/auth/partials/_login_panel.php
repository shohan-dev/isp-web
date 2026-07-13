<?php
$req = service('request');
$showRegisteredAlert = (bool) $req->getGet('registered');
$registeredPending = (bool) $req->getGet('pending');
?>
<div class="ipb-auth-panel-inner">
  <div class="ipb-auth-card">
    <?= view('auth/partials/_brand_logo', [
      'brandTitle' => $brandTitle,
      'appName' => $appName,
      'tenant' => $tenant,
      'brandUserId' => $brandUserId,
      'context' => 'auth-login',
    ]); ?>
    <h2 class="ipb-auth-heading">Welcome back</h2>
    <p class="sub">Sign in to your <?= esc($brandTitle); ?> dashboard</p>

    <?php /* Same .ipb-auth-status component as the sign-in result, rather than a
             second, differently-shaped Bootstrap alert in the same card. */ ?>
    <div
      id="loginRegisteredAlert"
      class="ipb-auth-status is-success"
      role="status"
      <?= $showRegisteredAlert ? '' : 'hidden aria-hidden="true"' ?>
    >
      <span class="ipb-auth-status-mark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
      <div class="ipb-auth-status-body">
        <p class="ipb-auth-status-title">Registration submitted</p>
        <p class="ipb-auth-status-msg" id="loginRegisteredAlertText">
          Sign in below with your email and password.
          <?php if ($registeredPending): ?>
            Your account may stay inactive until approval is complete.
          <?php endif; ?>
        </p>
      </div>
    </div>

    <?= form_open(route_to('route.auth.login.validate'), ['id' => 'loginForm', 'class' => 'ipb-auth-form', 'style' => 'margin-top:22px']); ?>
    <div id="loginFeedback"></div>

    <div class="form-group">
      <label class="control-label" for="login_email">Email address</label>
      <?= form_input(['type' => 'text', 'name' => 'email', 'id' => 'login_email', 'class' => 'form-control', 'placeholder' => 'you@company.com', 'autocomplete' => 'username']); ?>
      <small id="email-error" class="error text-danger"></small>
    </div>

    <div class="form-group">
      <label class="control-label" for="login_password">Password</label>
      <div class="ipb-auth-password-wrap">
        <?= form_input(['type' => 'password', 'name' => 'password', 'class' => 'form-control', 'id' => 'login_password', 'placeholder' => 'Enter your password', 'autocomplete' => 'current-password']); ?>
        <button type="button" class="ipb-pass-toggle" id="toggle-password" aria-label="Show password">
          <i class="fa fa-eye" aria-hidden="true"></i>
        </button>
      </div>
      <small id="password-error" class="error text-danger"></small>
    </div>

    <div style="text-align:right;margin:-4px 0 18px">
      <a href="<?= route_to('route.auth.forgot'); ?>" class="ipb-auth-inline-link">Forgot password?</a>
    </div>

    <?= form_button(['content' => 'Sign in', 'class' => 'btn btn-primary btn-block', 'type' => 'submit', 'style' => 'width:100%']); ?>

    <?php if (!$isTenantPortal): ?>
      <div class="ipb-auth-links">
        Don't have an account?
        <a href="<?= route_to('route.auth.registration'); ?>" class="ipb-auth-switch" data-auth-mode="register">Create free trial</a>
      </div>
    <?php endif; ?>
    <?= form_close(); ?>
  </div>
</div>

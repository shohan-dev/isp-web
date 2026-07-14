<?php
$brandUserId = (int) ($brandUserId ?? 2);
$tenant = $tenant ?? null;
$isTenantPortal = !empty($isTenantPortal);
$gateBrand = safeAuthGateBranding($tenant, $brandUserId, $isTenantPortal);
$appName = $gateBrand['appName'];
$appSlogan = $gateBrand['appSlogan'];
$logoUrl = $gateBrand['logoUrl'];
$flashSuccess = session()->getFlashdata('success');
$flashError = session()->getFlashdata('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">
  <title>Reset password | <?= esc($appName); ?></title>
  <meta name="description" content="<?= esc($appSlogan); ?>">
  <?= renderBrandFaviconTags(); ?>
  <!-- 08 §10 / 07 F3 — self-hosted Font Awesome (was cdnjs; a blocked CDN silently drops every icon on the auth critical path) -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">
  <?= saas_css('tokens.css') ?>
  <?= saas_css('base.css') ?>
  <?= saas_css('components.css') ?>
  <?= saas_css('auth.css') ?>
</head>
<body class="ipb ipb-auth-gate-body ipb-auth-standalone" data-theme="light">
  <div class="ipb-auth-stage">
    <div class="ipb-auth-split ipb-auth-split--solo">
      <div class="ipb-auth-col ipb-auth-col--solo">
        <div class="ipb-auth-pane ipb-auth-pane--brand" aria-label="Security information">
          <div class="ipb-auth-brand-inner">
            <div class="ipb-brand-mark"><i class="fa fa-shield-halved" aria-hidden="true"></i></div>
            <h1>Secure password recovery</h1>
            <p class="ipb-brand-lead">We'll send a one-time reset link to the email registered on your account. Your current password stays active until you complete the reset.</p>
            <ul class="ipb-auth-features">
              <li><i class="fa fa-clock" aria-hidden="true"></i><span>Reset links expire after 24 hours for your security</span></li>
              <li><i class="fa fa-envelope" aria-hidden="true"></i><span>Check your inbox and spam folder if you don't see the email</span></li>
              <li><i class="fa fa-lock" aria-hidden="true"></i><span>Only the account owner can request a password reset</span></li>
            </ul>
            <div class="ipb-auth-trust">
              <div class="ipb-auth-trust-item"><strong>24h</strong>Link validity</div>
              <div class="ipb-auth-trust-item"><strong>SSL</strong>Encrypted</div>
            </div>
          </div>
        </div>
      </div>

      <div class="ipb-auth-col ipb-auth-col--solo">
        <div class="ipb-auth-pane ipb-auth-pane--forgot" aria-label="Password reset form">
          <div class="ipb-auth-panel-inner">
            <a href="<?= route_to('route.auth.login'); ?>" class="ipb-auth-back">
              <i class="fa fa-chevron-left" aria-hidden="true"></i> Back to sign in
            </a>

            <div class="ipb-auth-card ipb-auth-forgot-card">
              <div class="ipb-auth-forgot-icon" aria-hidden="true">
                <i class="fa fa-key"></i>
              </div>
              <h2 class="ipb-auth-heading">Reset your password</h2>
              <p class="sub">Enter your account email and we'll send you a secure reset link.</p>

              <div id="forgotSuccess" class="ipb-auth-forgot-success"<?= $flashSuccess ? '' : ' hidden'; ?>>
                <div class="ipb-auth-forgot-success-mark" aria-hidden="true">
                  <i class="fa fa-check"></i>
                </div>
                <h3>Done</h3>
                <p id="forgotSuccessText"><?= esc($flashSuccess ?: 'If an account exists for that address, a reset link is on its way.'); ?></p>
                <a href="<?= route_to('route.auth.login'); ?>" class="btn btn-primary" style="width:100%;margin-top:8px">Back to sign in</a>
              </div>

              <?php
              $forgotFormAttrs = ['id' => 'forgotForm', 'class' => 'ipb-auth-form ipb-auth-forgot-form'];
              if ($flashSuccess) {
                $forgotFormAttrs['hidden'] = 'hidden';
              }
              echo form_open('', $forgotFormAttrs);
              ?>
              <div id="forgotFeedback">
                <?php if ($flashError): ?>
                  <div class="ipb-auth-flash alert alert-danger">
                    <i class="fa fa-ban" aria-hidden="true"></i>
                    <span><?= esc($flashError); ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label class="control-label" for="email">Email address</label>
                <?= form_input(['type' => 'email', 'name' => 'email', 'id' => 'email', 'class' => 'form-control', 'placeholder' => 'you@company.com', 'autocomplete' => 'email', 'required' => 'required']); ?>
                <small id="email-error" class="error text-danger"></small>
              </div>

              <?= form_button(['content' => 'Send reset link', 'class' => 'btn btn-primary btn-block', 'type' => 'submit', 'style' => 'width:100%']); ?>

              <div class="ipb-auth-links">
                Remember your password?
                <a href="<?= route_to('route.auth.login'); ?>">Sign in</a>
              </div>
              <?= form_close(); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
  <script>
    (function () {
      var $form = $('#forgotForm');
      var $success = $('#forgotSuccess');
      var $submit = $form.find('button[type="submit"]');

      function showError(msg) {
        $('#forgotFeedback').html(
          '<div class="ipb-auth-flash alert alert-danger"><i class="fa fa-ban" aria-hidden="true"></i><span>' + msg + '</span></div>'
        );
      }

      $form.on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (csrfHash && !formData.has(csrfName)) {
          formData.append(csrfName, csrfHash);
        }

        $.ajax({
          url: '<?= route_to('route.auth.forgot.validate'); ?>',
          type: 'POST',
          data: formData,
          contentType: false,
          cache: false,
          processData: false,
          dataType: 'json',
          beforeSend: function (req) {
            $form.find('.error').text('');
            $('#forgotFeedback').html('');
            $submit.html("<i class='fas fa-spinner fa-spin'></i> Sending…").prop('disabled', true);
            var headerName = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content');
            var headerToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (headerName && headerToken) {
              req.setRequestHeader(headerName, headerToken);
            }
          },
          success: function (result) {
            $submit.html('Send reset link').prop('disabled', false);
            if (result && result.response) {
              $('#forgotSuccessText').text(result.response);
            }
            $('#forgotSuccess .ipb-auth-forgot-success-mark i').removeClass('fa-check').addClass('fa-paper-plane');
            $('#forgotSuccess h3').text('Check your email');
            $form.attr('hidden', true);
            $success.removeAttr('hidden');
          },
          error: function (xhr) {
            $submit.html('Send reset link').prop('disabled', false);
            var result = null;
            try {
              result = xhr.responseJSON || JSON.parse(xhr.responseText);
            } catch (err) {
              result = null;
            }

            if (result && result.status === 'validation-error') {
              if (typeof result.response === 'object') {
                $.each(result.response, function (prefix, val) {
                  $('#' + prefix + '-error').text(val);
                });
                showError('Please fix the error below.');
              } else {
                showError(result.response || 'Please check your email address.');
              }
              return;
            }

            showError((result && result.response) || 'Something went wrong. Please try again.');
          }
        });
      });
    })();
  </script>
</body>
</html>

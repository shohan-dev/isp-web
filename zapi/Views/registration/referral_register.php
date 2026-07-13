<?php
$ref = $referral_code ?? '';
$valid = !empty($code_valid);
$referrer = $referrer_name ?? '';
$packages = $packages ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Register') ?></title>
  <?= renderBrandFaviconTags(); ?>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tata-js/dist/tata.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(160deg, #f5f3ff 0%, #faf5ff 40%, #f8fafc 100%);
      color: #1e293b;
    }
    .reg-wrap {
      max-width: 520px;
      margin: 0 auto;
      padding: 32px 16px 48px;
    }
    .reg-hero {
      background: linear-gradient(135deg, #7e22ce, #a855f7);
      color: #fff;
      border-radius: 18px;
      padding: 24px 22px;
      margin-bottom: 20px;
      box-shadow: 0 8px 24px rgba(126, 34, 206, 0.25);
    }
    .reg-hero h1 {
      margin: 0 0 6px;
      font-size: 1.45rem;
      font-weight: 700;
    }
    .reg-hero p { margin: 0; opacity: 0.92; font-size: 0.95rem; }
    .reg-card {
      background: #fff;
      border-radius: 18px;
      padding: 24px 22px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      border: 1px solid #e9d5ff;
    }
    .reg-card label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 4px;
    }
    .reg-code-box {
      background: #faf5ff;
      border: 2px dashed #c4b5fd;
      border-radius: 12px;
      padding: 14px;
      text-align: center;
      margin-bottom: 18px;
    }
    .reg-code-box .code {
      font-size: 1.6rem;
      font-weight: 800;
      letter-spacing: 3px;
      color: #7e22ce;
    }
    .reg-code-box .hint { font-size: 0.9rem; color: #64748b; margin-top: 4px; }
    .form-control {
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      padding: 10px 14px;
      font-size: 1rem;
    }
    .form-control:focus {
      border-color: #a855f7;
      box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.15);
    }
    .field-error { color: #dc2626; font-size: 0.82rem; margin-top: 4px; }
    .btn-reg {
      background: linear-gradient(135deg, #7e22ce, #a855f7);
      border: none;
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
      padding: 12px 20px;
      border-radius: 12px;
      width: 100%;
      margin-top: 8px;
    }
    .btn-reg:disabled { opacity: 0.7; }
    .alert-invalid {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 0.92rem;
      margin-bottom: 16px;
    }
    .login-link {
      text-align: center;
      margin-top: 18px;
      font-size: 0.92rem;
    }
    .login-link a { color: #7e22ce; font-weight: 600; }
  </style>
</head>
<body>

<div class="reg-wrap">
  <div class="reg-hero">
    <h1><i class="fa fa-user-plus"></i> Join with a referral</h1>
    <p>Register for a new connection. Your account will be activated after approval.</p>
  </div>

  <div class="reg-card">
    <?php if ($ref !== '' && !$valid): ?>
      <div class="alert-invalid">
        <i class="fa fa-exclamation-circle"></i>
        Referral code <strong><?= esc($ref) ?></strong> is invalid or inactive. Enter a valid code below.
      </div>
    <?php elseif ($valid && $referrer !== ''): ?>
      <div class="reg-code-box">
        <div class="hint">Referred by</div>
        <div style="font-size:1.1rem;font-weight:700;color:#1e293b"><?= esc($referrer) ?></div>
        <div class="code"><?= esc($ref) ?></div>
      </div>
    <?php endif; ?>

    <form id="refRegForm" novalidate>
      <div class="form-group">
        <label for="ref">Referral code *</label>
        <input type="text" class="form-control" id="ref" name="ref" value="<?= esc($ref) ?>"
               placeholder="Enter referral code" required autocomplete="off" style="text-transform:uppercase">
        <div class="field-error" id="err-ref"></div>
      </div>

      <div class="form-group">
        <label for="name">Full name *</label>
        <input type="text" class="form-control" id="name" name="name" required placeholder="Your name">
        <div class="field-error" id="err-name"></div>
      </div>

      <div class="form-group">
        <label for="mobile">Mobile number *</label>
        <input type="tel" class="form-control" id="mobile" name="mobile" required placeholder="01XXXXXXXXX">
        <div class="field-error" id="err-mobile"></div>
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com">
        <div class="field-error" id="err-email"></div>
      </div>

      <div class="form-group">
        <label for="nid_number">NID number (optional)</label>
        <input type="text" class="form-control" id="nid_number" name="nid_number" placeholder="National ID">
        <div class="field-error" id="err-nid_number"></div>
      </div>

      <?php if (!empty($packages)): ?>
      <div class="form-group">
        <label for="package_id">Preferred package</label>
        <select class="form-control" id="package_id" name="package_id">
          <option value="">— Select package —</option>
          <?php foreach ($packages as $pkg):
            $pid = is_object($pkg) ? ($pkg->id ?? 0) : ($pkg['id'] ?? 0);
            $pname = is_object($pkg) ? ($pkg->package_name ?? '') : ($pkg['package_name'] ?? '');
            $price = is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0); ?>
            <option value="<?= (int) $pid ?>"><?= esc($pname) ?> — BDT <?= esc($price) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="password">Password (optional)</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank for auto-generated">
      </div>

      <button type="submit" class="btn btn-reg" id="submitBtn">
        <i class="fa fa-paper-plane"></i> Submit registration
      </button>
    </form>

    <div class="login-link">
      Already have an account? <a href="<?= esc($login_url ?? route_to('route.auth.login')) ?>">Sign in</a>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/tata-js/dist/tata.min.js"></script>
<script>
(function () {
  var $form = $('#refRegForm');
  var $btn = $('#submitBtn');

  function clearErrors() {
    $('.field-error').text('');
  }

  function showErrors(details) {
    if (!details || typeof details !== 'object') return;
    Object.keys(details).forEach(function (key) {
      var el = $('#err-' + key.replace('.', '-'));
      if (el.length) el.text(details[key]);
    });
  }

  $form.on('submit', function (e) {
    e.preventDefault();
    clearErrors();

    var payload = {
      ref: ($('#ref').val() || '').trim().toUpperCase(),
      name: ($('#name').val() || '').trim(),
      mobile: ($('#mobile').val() || '').trim(),
      email: ($('#email').val() || '').trim(),
      nid_number: ($('#nid_number').val() || '').trim(),
      package_id: parseInt($('#package_id').val(), 10) || 0,
      password: $('#password').val() || ''
    };

    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting…');

    $.ajax({
      url: '<?= base_url('register/submit') ?>',
      method: 'POST',
      contentType: 'application/json',
      headers: {
        'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      data: JSON.stringify(payload),
      success: function (res) {
        var msg = (res.data && res.data.message) ? res.data.message : 'Registration submitted successfully.';

        if (typeof tata !== 'undefined') {
          tata.success('Success', msg);
        }

        $form[0].reset();
        $('#ref').val(payload.ref);
        $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit registration');
      },
      error: function (xhr) {
        $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit registration');
        var res = xhr.responseJSON || {};
        var err = res.error || {};
        var msg = err.message || 'Registration failed. Please check your details.';
        showErrors(err.details || {});
        if (typeof tata !== 'undefined') {
          tata.error('Error', msg);
        } else {
          alert(msg);
        }
      }
    });
  });
})();
</script>
</body>
</html>

<?php
helper('utility');
$packages = is_array($packages ?? null) ? $packages : [];
$gateBrand = safeAuthGateBranding(null, platformBrandingUserId(), false);
$appName = $gateBrand['appName'];
$logoUrl = $gateBrand['logoUrl'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title>Start free trial | <?= esc($appName); ?></title>
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
</head>
<body class="ipb ipb-auth-page ipb-auth-page--register" data-theme="light">
  <div class="ipb-auth ipb-auth--register">
    <main class="ipb-auth-panel">
      <div class="ipb-auth-panel-inner ipb-auth-panel-inner--wide">
        <div class="ipb-auth-card-wide">
          <a href="<?= route_to('route.auth.login'); ?>" class="ipb-auth-back">
            <i class="fa fa-chevron-left" aria-hidden="true"></i> Back to sign in
          </a>

          <?= view('auth/partials/_brand_logo', ['context' => 'auth-register']); ?>

          <h1>Start your free trial</h1>
          <p class="sub">14 days free — no credit card required. Set up your ISP organization in minutes.</p>

          <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger" style="margin-top:16px">
              <ul style="margin:0;padding-left:18px">
                <?php foreach ($errors as $error): ?>
                  <li><?= esc($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form id="registrationForm" method="post" style="margin-top:22px">
            <?= csrf_field() ?>

            <div class="ipb-auth-reg-grid">
              <div class="form-group form-group--full">
                <label for="organization_name">Organization name *</label>
                <input type="text" class="form-control" id="organization_name" name="organization_name" placeholder="Your ISP company name" required>
              </div>
              <div class="form-group">
                <label for="admin_name">Admin name *</label>
                <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Full name" required>
              </div>
              <div class="form-group">
                <label for="mobile">Mobile *</label>
                <input type="text" class="form-control" id="mobile" name="mobile" placeholder="01XXXXXXXXX" required>
                <span id="error_mobile" class="error-text"></span>
              </div>
              <div class="form-group">
                <label for="nationalid">National ID *</label>
                <input type="text" class="form-control" id="nationalid" name="nationalid" placeholder="NID number" required autocomplete="off">
              </div>
              <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="admin@company.com" required autocomplete="email">
                <span id="error_email" class="error-text"></span>
              </div>
              <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 4 characters" required autocomplete="new-password">
              </div>
              <div class="form-group form-group--full">
                <label for="confirm_password">Confirm password *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
              </div>
              <div class="form-group">
                <label for="division">Division *</label>
                <select class="form-control" name="division" id="division" required>
                  <option value="">Select division</option>
                  <option value="Barishal">Barishal</option>
                  <option value="Chattogram">Chattogram</option>
                  <option value="Dhaka">Dhaka</option>
                  <option value="Khulna">Khulna</option>
                  <option value="Rajshahi">Rajshahi</option>
                  <option value="Rangpur">Rangpur</option>
                  <option value="Sylhet">Sylhet</option>
                  <option value="Mymensingh">Mymensingh</option>
                </select>
              </div>
              <div class="form-group">
                <label for="district">District *</label>
                <select class="form-control" name="district" id="district" required>
                  <option value="">Select district</option>
                </select>
              </div>
              <div class="form-group">
                <label for="upazilla">Upazilla / Thana *</label>
                <input type="text" class="form-control" id="upazilla" name="upazilla" required>
              </div>
              <div class="form-group">
                <label for="address">Address *</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Street, area" required>
              </div>
              <div class="form-group form-group--full">
                <label for="package">Select package *</label>
                <select class="form-control" id="package" name="package" required>
                  <option value="">Choose a plan</option>
                  <?php if (!empty($packages)): ?>
                    <?php foreach ($packages as $package): ?>
                      <option value="<?= (int) $package['id']; ?>"
                        data-bandwidth="<?= esc($package['duration'] ?? '', 'attr'); ?>"
                        data-package-details="<?= esc($package['price'] ?? '', 'attr'); ?>">
                        <?= esc($package['package_name'] ?? ''); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="form-group form-group--full">
                <button type="button" class="btn btn-primary" id="package-info" disabled style="width:100%;opacity:0.85">
                  Select a package to see customer limit and pricing
                </button>
              </div>
              <div class="form-group form-group--full">
                <label>Customer type *</label>
                <div class="ipb-auth-check-row">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="customer_type[]" id="pppoe" value="PPPOE">
                    <label class="form-check-label" for="pppoe">PPPoE</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="customer_type[]" id="static" value="Static">
                    <label class="form-check-label" for="static">Static</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="customer_type[]" id="hotspot" value="Hotspot">
                    <label class="form-check-label" for="hotspot">Hotspot</label>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label for="reference_name">Reference name</label>
                <input type="text" class="form-control" id="reference_name" name="reference_name" placeholder="Optional">
              </div>
              <div class="form-group">
                <label for="reference_mobile">Reference mobile</label>
                <input type="text" class="form-control" id="reference_mobile" name="reference_mobile" placeholder="Optional">
              </div>
            </div>

            <div class="ipb-auth-form-actions">
              <button type="submit" class="btn btn-primary">Create account</button>
              <button type="reset" class="btn btn-secondary">Clear form</button>
            </div>

            <div class="ipb-auth-links">
              Already have an account? <a href="<?= route_to('route.auth.login'); ?>">Sign in</a>
            </div>
          </form>
        </div>
      </div>
    </main>

    <aside class="ipb-auth-brand">
      <div class="ipb-auth-brand-inner">
        <div class="ipb-brand-mark"><i class="fa fa-rocket" aria-hidden="true"></i></div>
        <h1>Launch your ISP on modern SaaS</h1>
        <p class="ipb-brand-lead">Join ISPs across Bangladesh who run billing, network, and support from one secure cloud platform.</p>
        <ul class="ipb-auth-features">
          <li><i class="fa fa-gift" aria-hidden="true"></i><span>14-day free trial with full platform access</span></li>
          <li><i class="fa fa-shield-halved" aria-hidden="true"></i><span>Secure multi-tenant architecture</span></li>
          <li><i class="fa fa-headset" aria-hidden="true"></i><span>Onboarding support from our team</span></li>
          <li><i class="fa fa-arrows-rotate" aria-hidden="true"></i><span>Upgrade or change plan anytime</span></li>
        </ul>
        <div class="ipb-auth-trust">
          <div class="ipb-auth-trust-item"><strong>500+</strong>ISPs served</div>
          <div class="ipb-auth-trust-item"><strong>99.9%</strong>Uptime SLA</div>
        </div>
      </div>
    </aside>
  </div>

  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
  <?= view('auth/_transition'); ?>
  <!-- 08 §10 / 07 F3 — self-hosted tata.js (was cdn.jsdelivr.net) -->
  <script src="<?= base_url('assets/vendor/tatajs/tata.js'); ?>"></script>
  <script>
    const districts = {
      "Barishal": [{ id: 34, name: 'Barguna' }, { id: 35, name: 'Barishal' }, { id: 36, name: 'Bhola' }, { id: 37, name: 'Jhalokati' }, { id: 38, name: 'Patuakhali' }, { id: 39, name: 'Pirojpur' }],
      "Chattogram": [{ id: 40, name: 'Bandarban' }, { id: 41, name: 'Brahmanbaria' }, { id: 42, name: 'Chandpur' }, { id: 43, name: 'Chattogram' }, { id: 44, name: 'Cumilla' }, { id: 45, name: "Cox's Bazar" }, { id: 46, name: 'Feni' }, { id: 47, name: 'Khagrachari' }, { id: 48, name: 'Lakshmipur' }, { id: 49, name: 'Noakhali' }, { id: 50, name: 'Rangamati' }],
      "Dhaka": [{ id: 1, name: 'Dhaka' }, { id: 2, name: 'Faridpur' }, { id: 3, name: 'Gazipur' }, { id: 4, name: 'Gopalganj' }, { id: 6, name: 'Kishoreganj' }, { id: 7, name: 'Madaripur' }, { id: 8, name: 'Manikganj' }, { id: 9, name: 'Munshiganj' }, { id: 11, name: 'Narayanganj' }, { id: 12, name: 'Narsingdi' }, { id: 14, name: 'Rajbari' }, { id: 15, name: 'Shariatpur' }, { id: 17, name: 'Tangail' }],
      "Khulna": [{ id: 55, name: 'Bagerhat' }, { id: 56, name: 'Chuadanga' }, { id: 57, name: 'Jessore' }, { id: 58, name: 'Jhenaidah' }, { id: 59, name: 'Khulna' }, { id: 60, name: 'Kushtia' }, { id: 61, name: 'Magura' }, { id: 62, name: 'Meherpur' }, { id: 63, name: 'Narail' }, { id: 64, name: 'Satkhira' }],
      "Rajshahi": [{ id: 18, name: 'Bogura' }, { id: 19, name: 'Joypurhat' }, { id: 20, name: 'Naogaon' }, { id: 21, name: 'Natore' }, { id: 22, name: 'Nawabganj' }, { id: 23, name: 'Pabna' }, { id: 24, name: 'Rajshahi' }, { id: 25, name: 'Sirajganj' }],
      "Rangpur": [{ id: 26, name: 'Dinajpur' }, { id: 27, name: 'Gaibandha' }, { id: 28, name: 'Kurigram' }, { id: 29, name: 'Lalmonirhat' }, { id: 30, name: 'Nilphamari' }, { id: 31, name: 'Panchagarh' }, { id: 32, name: 'Rangpur' }, { id: 33, name: 'Thakurgaon' }],
      "Sylhet": [{ id: 51, name: 'Habiganj' }, { id: 52, name: 'Moulvibazar' }, { id: 53, name: 'Sunamganj' }, { id: 54, name: 'Sylhet' }],
      "Mymensingh": [{ id: 5, name: 'Jamalpur' }, { id: 10, name: 'Mymensingh' }, { id: 13, name: 'Netrokona' }, { id: 16, name: 'Sherpur' }]
    };

    $(function () {
      $('#division').on('change', function () {
        var selected = $(this).val();
        var $district = $('#district').empty().append('<option value="">Select district</option>');
        if (selected && districts[selected]) {
          districts[selected].forEach(function (d) {
            $district.append('<option value="' + d.id + '">' + d.name + '</option>');
          });
        }
      });

      $('#package').on('change', function () {
        var $opt = $(this).find('option:selected');
        var bandwidth = $opt.data('bandwidth');
        var price = $opt.data('package-details');
        if ($(this).val()) {
          $('#package-info').prop('disabled', false).text('Up to ' + bandwidth + ' customers · ৳' + price + ' / month');
        } else {
          $('#package-info').prop('disabled', true).text('Select a package to see customer limit and pricing');
        }
      });

      $('#registrationForm').on('submit', function (e) {
        e.preventDefault();
        $('.error-text').text('');

        $.ajax({
          url: '<?= route_to('route.auth.submit') ?>',
          type: 'POST',
          data: $(this).serialize(),
          dataType: 'json',
          beforeSend: function (req) {
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) req.setRequestHeader('<?= csrf_header() ?>', token);
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating…');
          }.bind(this),
          success: function (response) {
            $('#registrationForm button[type="submit"]').prop('disabled', false).html('Create account');
            if (response.status === 'error') {
              if (response.errors && typeof response.errors === 'object') {
                Object.keys(response.errors).forEach(function (field) {
                  $('#error_' + field).text(response.errors[field]);
                });
              }
              tata.error('Check your form', 'Please fix the highlighted fields.');
            } else if (response.status === 'success') {
              tata.success('Welcome aboard', 'Account created! Redirecting to sign in…', {
                onClose: function () {
                  document.body.classList.add('ipb-auth-leaving');
                  setTimeout(function () {
                    window.location.href = '<?= route_to('route.auth.login') ?>?registered=1';
                  }, 280);
                }
              });
            }
          },
          error: function () {
            $('#registrationForm button[type="submit"]').prop('disabled', false).html('Create account');
            tata.error("Couldn't create your account", "Please check your details and try again.");
          }
        });
      });
    });
  </script>
</body>
</html>

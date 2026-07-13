<?php
$packages = is_array($packages ?? null) ? $packages : [];
$paygAddons = is_array($paygAddons ?? null) ? $paygAddons : [];
$prefillAddons = is_array($prefillAddons ?? null) ? $prefillAddons : [];
$selectedPlan = (string) ($selectedPlan ?? '');
$paygBaseFee = 0;
$paygRate = 0;
$paygMinTopup = 0;
$paygTrialDays = 14;
if (!empty($paygPackage)) {
    $paygBaseFee = (float) (is_object($paygPackage) ? ($paygPackage->base_fee ?? 0) : ($paygPackage['base_fee'] ?? 0));
    $paygRate = (float) (is_object($paygPackage) ? ($paygPackage->per_user_rate ?? 0) : ($paygPackage['per_user_rate'] ?? 0));
    $paygMinTopup = (float) (is_object($paygPackage) ? ($paygPackage->min_topup ?? 0) : ($paygPackage['min_topup'] ?? 0));
    $paygTrialDays = (int) (is_object($paygPackage) ? ($paygPackage->trial_days ?? 14) : ($paygPackage['trial_days'] ?? 14)) ?: 14;
}
?>
<div class="ipb-auth-panel-inner ipb-auth-panel-inner--wide">
  <a href="<?= route_to('route.auth.login'); ?>" class="ipb-auth-back ipb-auth-switch" data-auth-mode="login">
    <i class="fa fa-chevron-left" aria-hidden="true"></i> Back to sign in
  </a>

  <div class="ipb-auth-reg-card">
    <header class="ipb-auth-reg-header">
      <?= view('auth/partials/_brand_logo', ['context' => 'auth-register']); ?>
      <div class="ipb-auth-reg-title-row">
        <div>
          <h2 class="ipb-auth-heading">Start your free trial</h2>
          <p class="sub">No credit card required — set up your ISP organization in minutes.</p>
        </div>
        <span class="ipb-auth-trial-badge" id="ipbRegTrialBadge"><?= (int) $paygTrialDays; ?> days free</span>
      </div>
    </header>

    <?php if (!empty($errors) && is_array($errors)): ?>
      <div class="ipb-auth-reg-alert alert alert-danger">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= esc($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="registrationForm" method="post" class="ipb-auth-form ipb-auth-reg-form">
      <?= csrf_field() ?>

      <section class="ipb-auth-reg-section" aria-labelledby="reg-section-org">
        <h3 class="ipb-auth-reg-section-title" id="reg-section-org">
          <span class="ipb-auth-reg-section-icon" aria-hidden="true"><i class="fa fa-building"></i></span>
          Organization
        </h3>
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
            <label for="reg_mobile">Mobile *</label>
            <input type="text" class="form-control" id="reg_mobile" name="mobile" placeholder="01XXXXXXXXX" required>
            <span id="error_mobile" class="error-text"></span>
          </div>
          <div class="form-group">
            <label for="nationalid">National ID *</label>
            <input type="text" class="form-control" id="nationalid" name="nationalid" placeholder="NID number" required autocomplete="off">
          </div>
        </div>
      </section>

      <section class="ipb-auth-reg-section" aria-labelledby="reg-section-account">
        <h3 class="ipb-auth-reg-section-title" id="reg-section-account">
          <span class="ipb-auth-reg-section-icon" aria-hidden="true"><i class="fa fa-lock"></i></span>
          Account
        </h3>
        <div class="ipb-auth-reg-grid">
          <div class="form-group">
            <label for="reg_email">Email *</label>
            <input type="email" class="form-control" id="reg_email" name="email" placeholder="admin@company.com" required autocomplete="email">
            <span id="error_email" class="error-text"></span>
          </div>
          <div class="form-group">
            <label for="reg_password">Password *</label>
            <input type="password" class="form-control" id="reg_password" name="password" placeholder="Min. 4 characters" required autocomplete="new-password">
          </div>
          <div class="form-group form-group--full">
            <label for="confirm_password">Confirm password *</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
          </div>
        </div>
      </section>

      <section class="ipb-auth-reg-section" aria-labelledby="reg-section-location">
        <h3 class="ipb-auth-reg-section-title" id="reg-section-location">
          <span class="ipb-auth-reg-section-icon" aria-hidden="true"><i class="fa fa-location-dot"></i></span>
          Location
        </h3>
        <div class="ipb-auth-reg-grid">
          <div class="form-group">
            <label for="division">Division *</label>
            <select class="form-control" name="division" id="division" required>
              <option value="">Select division</option>
              <?php foreach (['Barishal','Chattogram','Dhaka','Khulna','Rajshahi','Rangpur','Sylhet','Mymensingh'] as $div): ?>
                <option value="<?= esc($div); ?>"><?= esc($div); ?></option>
              <?php endforeach; ?>
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
            <input type="text" class="form-control" id="upazilla" name="upazilla" placeholder="Upazilla or thana" required>
          </div>
          <div class="form-group">
            <label for="address">Address *</label>
            <input type="text" class="form-control" id="address" name="address" placeholder="Street, area" required>
          </div>
        </div>
      </section>

      <section class="ipb-auth-reg-section" aria-labelledby="reg-section-plan">
        <h3 class="ipb-auth-reg-section-title" id="reg-section-plan">
          <span class="ipb-auth-reg-section-icon" aria-hidden="true"><i class="fa fa-box"></i></span>
          Plan &amp; services
        </h3>
        <div class="ipb-auth-reg-grid">
          <div class="form-group form-group--full">
            <label for="package">Select package *</label>
            <select class="form-control" id="package" name="package" required
              data-selected-plan="<?= esc($selectedPlan, 'attr'); ?>"
              data-payg-base-fee="<?= esc($paygBaseFee, 'attr'); ?>"
              data-payg-rate="<?= esc($paygRate, 'attr'); ?>"
              data-payg-min-topup="<?= esc($paygMinTopup, 'attr'); ?>"
              data-payg-trial-days="<?= esc($paygTrialDays, 'attr'); ?>">
              <option value="">Choose a plan</option>
              <?php foreach ($packages as $package): ?>
                <option value="<?= (int) $package['id']; ?>"
                  data-bandwidth="<?= esc($package['duration'] ?? '', 'attr'); ?>"
                  data-package-details="<?= esc($package['price'] ?? '', 'attr'); ?>"
                  data-trial-days="<?= esc((int) ($package['trial_days'] ?? 0) ?: 14, 'attr'); ?>">
                  <?= esc($package['package_name'] ?? ''); ?>
                </option>
              <?php endforeach; ?>
              <?php if (!empty($paygPackage)): ?>
                <option value="payg">Pay-As-You-Go Wallet (no customer limit)</option>
              <?php endif; ?>
              <option value="custom">Custom plan — tell us what you need</option>
            </select>
          </div>
          <div class="form-group form-group--full">
            <div class="ipb-auth-package-hint" id="package-info" aria-live="polite">
              <i class="fa fa-circle-info" aria-hidden="true"></i>
              <span class="ipb-auth-package-hint-text">Select a package to see customer limit and pricing</span>
            </div>
          </div>
          <?php if (!empty($paygAddons)): ?>
            <div class="form-group form-group--full" id="payg-addon-group" hidden>
              <span class="ipb-auth-reg-field-label">Optional add-ons (added to your monthly wallet deduction)</span>
              <div class="ipb-auth-type-pills" role="group" aria-label="PAYG add-ons">
                <?php foreach ($paygAddons as $addon): ?>
                  <label class="ipb-auth-type-pill">
                    <input class="form-check-input" type="checkbox" name="payg_addons[]"
                      value="<?= esc($addon['key'], 'attr'); ?>"
                      <?= in_array($addon['key'], $prefillAddons, true) ? 'checked' : ''; ?>>
                    <span><?= esc($addon['label']); ?> (+৳<?= esc(number_format($addon['price'])); ?>/mo)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <div class="form-group form-group--full" id="custom-plan-group" hidden>
            <label for="custom_plan_note">What do you need? *</label>
            <textarea class="form-control" id="custom_plan_note" name="custom_plan_note" rows="3"
              placeholder="e.g. 8,000 customers, white label, multi-branch, expected budget…"></textarea>
          </div>
          <div class="form-group form-group--full">
            <span class="ipb-auth-reg-field-label">Customer type *</span>
            <div class="ipb-auth-type-pills" role="group" aria-label="Customer type">
              <label class="ipb-auth-type-pill">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="pppoe" value="PPPOE">
                <span>PPPoE</span>
              </label>
              <label class="ipb-auth-type-pill">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="static" value="Static">
                <span>Static</span>
              </label>
              <label class="ipb-auth-type-pill">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="hotspot" value="Hotspot">
                <span>Hotspot</span>
              </label>
            </div>
          </div>
        </div>
      </section>

      <section class="ipb-auth-reg-section ipb-auth-reg-section--optional" aria-labelledby="reg-section-ref">
        <h3 class="ipb-auth-reg-section-title" id="reg-section-ref">
          <span class="ipb-auth-reg-section-icon" aria-hidden="true"><i class="fa fa-user-group"></i></span>
          Reference <span class="ipb-auth-reg-optional">optional</span>
        </h3>
        <div class="ipb-auth-reg-grid">
          <div class="form-group">
            <label for="reference_name">Reference name</label>
            <input type="text" class="form-control" id="reference_name" name="reference_name" placeholder="Who referred you">
          </div>
          <div class="form-group">
            <label for="reference_mobile">Reference mobile</label>
            <input type="text" class="form-control" id="reference_mobile" name="reference_mobile" placeholder="01XXXXXXXXX">
          </div>
        </div>
      </section>

      <footer class="ipb-auth-reg-footer">
        <div class="ipb-auth-form-actions">
          <button type="submit" class="btn btn-primary">Create account</button>
          <button type="reset" class="btn btn-secondary">Clear form</button>
        </div>
        <div class="ipb-auth-links">
          Already have an account?
          <a href="<?= route_to('route.auth.login'); ?>" class="ipb-auth-switch" data-auth-mode="login">Sign in</a>
        </div>
      </footer>
    </form>
  </div>
</div>

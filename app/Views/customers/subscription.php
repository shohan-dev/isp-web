<?php
if (isset($details) && is_array($details))
  $details = (object) $details;
if (isset($payment_details) && is_array($payment_details))
  $payment_details = (object) $payment_details;
/** @var object $details */
/** @var object $admin_details */
?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/subscription-page.css?v=5'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$currentPkgName = '--';
if (!empty($packages)) {
  foreach ($packages as $p) {
    $pid = is_object($p) ? ($p->id ?? null) : ($p['id'] ?? null);
    $pname = is_object($p) ? ($p->package_name ?? '') : ($p['package_name'] ?? '');
    if ((string) $pid === (string) ($details->package_id ?? '')) {
      $currentPkgName = $pname !== '' ? $pname : $currentPkgName;
      break;
    }
  }
}
$expireDisplay = !empty($details->will_expire)
  ? date('d M Y, h:i A', strtotime($details->will_expire))
  : '—';
$connectedDisplay = !empty($details->created_at)
  ? date('d M Y, h:i A', strtotime($details->created_at))
  : '—';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list ipb-sub-page ipb-cust-recharge">

    
    <?= $this->include('components/page-header', [
      'title' => 'Customer Subscription',
      'subtitle' => 'Full package recharge for ' . (string) ($details->name ?? 'customer'),
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => 'Customer Subscription'],
      ],
    ]); ?>

    <?php if (!empty($showTabs)): ?>
      <!-- ═══════════════════════════════════════════════════════
         SECTION SELECTOR TABS
    ════════════════════════════════════════════════════════════ -->
      <div class="section-tab-wrapper">
        <div class="section-tab-item active" id="tab-sec1" onclick="switchSection(1)">
          <div class="stab-icon s1-icon"><i class="fa fa-check-circle"></i></div>
          <div class="stab-body">
            <div class="stab-title">Payment Status Update</div>
            <div class="stab-desc">Mark as Paid / Pending &nbsp;·&nbsp; <span class="text-success"><strong>No fund
                  deduction</strong></span> &nbsp;·&nbsp; No expiry change</div>
          </div>
        </div>
        <div class="section-tab-item" id="tab-sec2" onclick="switchSection(2)">
          <div class="stab-icon s2-icon"><i class="fa fa-refresh"></i></div>
          <div class="stab-body">
            <div class="stab-title">Full Subscription Recharge</div>
            <div class="stab-desc">Change package / Extend expiry &nbsp;·&nbsp; <span class="text-danger"><strong>Fund
                  will be deducted</strong></span></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?= form_open('', 'id="form"'); ?>

    <?php $rechargeOnly = empty($showTabs); ?>

    <!-- Hidden field: which section is active (0 = full recharge) -->
    <input type="hidden" name="status_only" id="status_only_hidden" value="<?= $rechargeOnly ? '0' : '1' ?>">

    <!-- ═══════════════════════════════════════════════════════
          PAYMENT STATUS UPDATE (No Fund Deduction)
    ════════════════════════════════════════════════════════════ -->
    <div class="box" id="section1-box"
      style="<?= $rechargeOnly ? 'display:none;' : '' ?>border-top:3px solid #00a65a; border-radius:12px; box-shadow:var(--shadow-1, 0 10px 30px rgba(0,0,0,0.08));">
      <div class="box-header with-border" style="background:var(--success-50, #f0fff4);">
        <h3 class="box-title" style="color:var(--success-700, #155724); font-size:16px;">
          <i class="fa fa-check-circle" style="color:var(--success-500, #00a65a);"></i>&nbsp; Payment Status Update
        </h3>
        <p style="margin:6px 0 0; color:var(--text-secondary, #6c757d); font-size:12.5px;">
          Only mark the payment month as <strong>Paid</strong> or <strong>Pending</strong>.
          Package, PPPoE profile, and expiry date remain unchanged. <strong class="text-success">No fund will be
            deducted.</strong>
        </p>
      </div>

      <div class="box-body">

        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
              <label>Customer Name</label>
              <?php if (isset($multiple) && $multiple === 'true'): ?>
                <?= form_input(['class' => 'form-control', 'value' => implode(', ', $userNames), 'disabled' => 'disabled']); ?>
              <?php else: ?>
                <?= form_input(['class' => 'form-control', 'value' => $details->name, 'disabled' => 'disabled']); ?>
              <?php endif; ?>
            </div>
          </div>
          <?php if (isset($show_daily_bill) && $show_daily_bill): ?>
            <div class="col-md-6 col-sm-6 col-xs-12">
              <div class="form-group">
                <label>Daily Bill Generate</label>
                <?php
                echo form_dropdown('s1_daily_bill', [
                  '0' => 'Disable',
                  '1' => 'Enable'
                ], $details->daily_bill ?? '0', 'class="form-control" id="s1_daily_bill"');
                ?>
                <small id="s1_daily_bill-error" class="error text-danger"></small>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
              <label>Payment Month <span class="text-danger">*</span></label>
              <?php
              $currentYear = date('Y');
              $payments_s1 = $payment_months ?? [];
              $month_status_s1 = [];
              foreach ($payments_s1 as $pmt) {
                // Handle both object and array results from the Payment model
                $pmt_month = is_object($pmt) ? ($pmt->month ?? null) : ($pmt['month'] ?? null);
                $pmt_status = is_object($pmt) ? ($pmt->status ?? null) : ($pmt['status'] ?? null);
                $pmt_created = is_object($pmt) ? ($pmt->created_at ?? null) : ($pmt['created_at'] ?? null);
                if (empty($pmt_month) || empty($pmt_created))
                  continue;
                if (date('Y', strtotime($pmt_created)) == $currentYear) {
                  $month_status_s1[$pmt_month] = ($pmt_status === 'successful') ? 'Paid' : 'Due';
                }
              }
              $months_s1 = ['' => '--Select--'];
              for ($m = 1; $m <= 12; ++$m) {
                $mn = date('F', mktime(0, 0, 0, $m, 1));
                $lbl = $mn;
                if (isset($month_status_s1[$mn])) {
                  $badge = ($month_status_s1[$mn] === 'Paid') ? ' ✓ Paid' : ' Due';
                  $lbl .= ' (' . $month_status_s1[$mn] . ')';
                }
                $months_s1[$mn] = $lbl;
              }
              echo form_dropdown('s1_month', $months_s1, date('F'), 'class="form-control" id="s1_month"');
              ?>
              <small id="s1_month-error" class="error text-danger"></small>
            </div>
          </div>

          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
              <label>Payment Method <span class="text-danger">*</span></label>
              <?php
              $s1_methods = [
                '' => '--Select--',
                'Cash' => 'Cash Payment',
                'Bkash' => 'Bkash',
                'Bkash Send Money' => 'Bkash Send Money',
                'Nagad' => 'Nagad',
                'Rocket' => 'Rocket',
                'Upay' => 'Upay',
                'SSLCommerz' => 'SSLCommerz',
              ];
              echo form_dropdown('s1_paid_via', $s1_methods, $payment_details->paid_via ?? '', 'class="form-control" id="s1_paid_via"');
              ?>
              <small id="s1_paid_via-error" class="error text-danger"></small>
            </div>
          </div>
        </div><!-- /.row -->

        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
              <label>Transaction ID</label>
              <?= form_input([
                'name' => 's1_method_trx',
                'type' => 'text',
                'class' => 'form-control',
                'placeholder' => strtoupper(random_string('alnum', 11)),
                'value' => $payment_details->method_trx ?? '',
              ]); ?>
              <small id="s1_method_trx-error" class="error text-danger"></small>
            </div>
          </div>

          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
              <label>Status <span class="text-danger">*</span></label>
              <div class="radio" style="margin-top:8px;">
                <label class="radio-inline" style="margin-right:20px;">
                  <?= form_radio([
                    'name' => 's1_status',
                    'value' => 'successful',
                    'id' => 's1_status_successful',
                    'checked' => ($payment_details->status ?? '') === 'successful',
                  ]); ?>
                  &nbsp;<span class="text-success"><strong>Successful</strong></span>
                </label>
                <label class="radio-inline">
                  <?= form_radio([
                    'name' => 's1_status',
                    'value' => 'pending',
                    'id' => 's1_status_pending',
                    'checked' => ($payment_details->status ?? 'pending') !== 'successful',
                  ]); ?>
                  &nbsp;<span class="text-warning"><strong>Pending</strong></span>
                </label>
              </div>
              <small id="s1_status-error" class="error text-danger"></small>
            </div>
          </div>
        </div><!-- /.row -->

        <div class="form-group">
          <label>Description / Note</label>
          <?= form_textarea([
            'name' => 's1_description',
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => 'Add any notes about this payment (optional)…',
            'value' => $payment_details->comment ?? '',
          ]); ?>
          <small id="s1_description-error" class="error text-danger"></small>
        </div>

      </div><!-- /.box-body -->

      <div class="box-footer" style="background:var(--success-50, #f0fff4);">
        <button type="submit" class="btn btn-success" id="s1-submit-btn" style="min-width:min(200px, 100%);">
          <i class="fa fa-check"></i>&nbsp; Update Payment Status
        </button>
        <small class="text-muted" style="margin-left:12px;">No fund deduction · No expiry change</small>
      </div>
    </div><!-- /#section1-box -->


    <!-- ═══════════════════════════════════════════════════════
         FULL SUBSCRIPTION RECHARGE
    ════════════════════════════════════════════════════════════ -->
    <div class="ipb-cust-recharge-panel" id="section2-box"
      style="<?= $rechargeOnly ? 'display:block;' : 'display:none;' ?>">

      <div class="ipb-sub-kpis ipb-cust-recharge-kpis">
        <div class="ipb-sub-kpi">
          <span class="ipb-sub-kpi__ic" aria-hidden="true"><i class="fa fa-user"></i></span>
          <div class="ipb-sub-kpi__body">
            <span class="ipb-sub-kpi__label">Customer</span>
            <span class="ipb-sub-kpi__value" style="font-size:1rem">
              <?= esc(isset($multiple) && $multiple === 'true' ? implode(', ', $userNames ?? []) : ($details->name ?? '—')) ?>
            </span>
          </div>
        </div>
        <div class="ipb-sub-kpi">
          <span class="ipb-sub-kpi__ic" aria-hidden="true"><i class="fa fa-layer-group"></i></span>
          <div class="ipb-sub-kpi__body">
            <span class="ipb-sub-kpi__label">Current package</span>
            <span class="ipb-sub-kpi__value" style="font-size:1rem"><?= esc($currentPkgName) ?></span>
          </div>
        </div>
        <div class="ipb-sub-kpi">
          <span class="ipb-sub-kpi__ic" aria-hidden="true"><i class="fa fa-plug"></i></span>
          <div class="ipb-sub-kpi__body">
            <span class="ipb-sub-kpi__label">Connected</span>
            <span class="ipb-sub-kpi__value" style="font-size:0.95rem"><?= esc($connectedDisplay) ?></span>
          </div>
        </div>
        <div class="ipb-sub-kpi">
          <span class="ipb-sub-kpi__ic" aria-hidden="true"><i class="fa fa-calendar-day"></i></span>
          <div class="ipb-sub-kpi__body">
            <span class="ipb-sub-kpi__label">Expires</span>
            <span class="ipb-sub-kpi__value ipb-sub-kpi__value--accent" style="font-size:0.95rem"><?= esc($expireDisplay) ?></span>
          </div>
        </div>
      </div>

      <article class="ipb-cust-recharge-card">
        <header class="ipb-cust-recharge-card__head">
          <div class="ipb-cust-recharge-card__title">
            <span class="ipb-cust-recharge-card__icon" aria-hidden="true"><i class="fa fa-rotate"></i></span>
            <div>
              <h3>Full Subscription Recharge</h3>
              <p>Change package, update PPPoE profile, and extend expiry.</p>
            </div>
          </div>
          <div class="ipb-sub-callout ipb-cust-recharge-warn" role="note">
            <i class="fa fa-wallet" aria-hidden="true"></i>
            Fund will be deducted from your balance
          </div>
        </header>

        <div class="ipb-cust-recharge-card__body">

        <!-- Customer Info Row -->
        <div class="ipb-cust-recharge-section">
          <h4 class="ipb-cust-recharge-section__label">Customer</h4>
          <div class="ipb-form-grid">
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="cust-name">Customer Name</label>
              <?php if (isset($multiple) && $multiple === 'true'): ?>
                <?= form_input(['id' => 'cust-name', 'class' => 'form-control', 'value' => implode(', ', $userNames), 'disabled' => 'disabled']); ?>
              <?php else: ?>
                <?= form_input(['id' => 'cust-name', 'class' => 'form-control', 'value' => $details->name, 'disabled' => 'disabled']); ?>
              <?php endif; ?>
              <small id="name-error" class="error text-danger"></small>
            </div>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="cust-created">Connection Date</label>
              <?= form_input(['id' => 'cust-created', 'name' => 'created_at', 'class' => 'form-control', 'value' => $details->created_at]); ?>
              <small id="connection_date-error" class="error text-danger"></small>
            </div>
          </div>
        </div>

        <!-- Package & Profile Row -->
        <div class="ipb-cust-recharge-section">
          <h4 class="ipb-cust-recharge-section__label">Package &amp; billing</h4>
          <div class="ipb-form-grid">
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="packageDropdown">Package</label>
              <?php
              $pkgData = [];
              if (empty($packages)) {
                $pkgData[''] = 'No package found!';
              } else {
                $pkgData = ['' => '--Select--'];
                foreach ($packages as $package) {
                  $packageId = is_object($package) ? $package->id : $package['id'];
                  $packageName = is_object($package) ? $package->package_name : $package['package_name'];
                  $pkgData[$packageId] = $packageName;
                }
              }
              $selectedPkg = '';
              $showReadonly = false;
              $readonlyVal = '';
              if (!empty($packageIds) && is_array($packageIds)) {
                $uniquePkgs = array_unique($packageIds);
                if (count($uniquePkgs) === 1) {
                  $selectedPkg = reset($uniquePkgs);
                } else {
                  $showReadonly = true;
                  $pkgNames = [];
                  foreach ($uniquePkgs as $pkgId) {
                    if (isset($pkgData[$pkgId]))
                      $pkgNames[] = $pkgData[$pkgId];
                  }
                  $readonlyVal = implode(', ', $pkgNames);
                  $selectedPkg = reset($uniquePkgs);
                }
              } else {
                if (isset($details->package_id))
                  $selectedPkg = $details->package_id;
              }
              ?>
              <?php if ($showReadonly): ?>
                <input type="text" id="packageReadonly" class="form-control" value="<?= esc($readonlyVal) ?>" readonly>
              <?php endif; ?>
              <?= form_dropdown('package_id', $pkgData, $selectedPkg, [
                'class' => 'form-control',
                'id' => 'packageDropdown',
                'style' => $showReadonly ? 'display:none;' : '',
              ]) ?>
              <small id="package_id-error" class="error text-danger"></small>
            </div>

          <?php if (session()->get('user_role') === 'resellerAdmin'): ?>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="pppoe_profile">PPPoE Profile</label>
              <?php
              $profileData = [];
              if (empty($profiles)):
                $profileData[''] = 'No profile found!';
              else:
                $profileData = ['' => '--Select--'];
                foreach ($profiles as $profile) {
                  $profileData[$profile] = $profile;
                }
              endif;
              echo form_dropdown('pppoe_profile', $profileData, isset($pppoe_profile) ? $pppoe_profile : '', 'class="form-control" id="pppoe_profile"');
              ?>
              <small id="pppoe_profile-error" class="error text-danger"></small>
            </div>
          <?php endif; ?>

            <div class="ipb-form-field">
              <label class="ipb-form-label" for="payment-month">Payment Month</label>
              <?php
              $currentYear2 = date('Y');
              $payments2 = $payment_months ?? [];
              $month_status2 = [];
              foreach ($payments2 as $pmt2) {
                $pmt2_month = is_object($pmt2) ? ($pmt2->month ?? null) : ($pmt2['month'] ?? null);
                $pmt2_status = is_object($pmt2) ? ($pmt2->status ?? null) : ($pmt2['status'] ?? null);
                $pmt2_created = is_object($pmt2) ? ($pmt2->created_at ?? null) : ($pmt2['created_at'] ?? null);
                if (empty($pmt2_month) || empty($pmt2_created))
                  continue;
                if (date('Y', strtotime($pmt2_created)) == $currentYear2) {
                  $month_status2[$pmt2_month] = ($pmt2_status === 'successful') ? 'Paid' : 'Due';
                }
              }
              $months2 = ['' => '--Select--'];
              for ($m = 1; $m <= 12; ++$m) {
                $mn2 = date('F', mktime(0, 0, 0, $m, 1));
                $lbl2 = $mn2;
                if (isset($month_status2[$mn2]))
                  $lbl2 .= ' (' . $month_status2[$mn2] . ')';
                $months2[$mn2] = $lbl2;
              }
              echo form_dropdown('month', $months2, date('F'), 'class="form-control" id="payment-month"');
              ?>
              <small id="month-error" class="error text-danger"></small>
            </div>
          <?php if (isset($show_daily_bill) && $show_daily_bill): ?>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="s2_daily_bill">Daily Bill Generate</label>
              <?php
              echo form_dropdown('s2_daily_bill', [
                '0' => 'Disable',
                '1' => 'Enable'
              ], $details->daily_bill ?? '0', 'class="form-control" id="s2_daily_bill"');
              ?>
              <small id="s2_daily_bill-error" class="error text-danger"></small>
            </div>
          <?php endif; ?>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="ipb-cust-recharge-section">
          <h4 class="ipb-cust-recharge-section__label">Payment</h4>
          <div class="ipb-form-grid">
        <?php
        $selected_method = isset($payment_details->paid_via) ? $payment_details->paid_via : '';
        $methodIcons = [
          'Cash' => 'fa-money-bill-wave',
          'Bkash' => 'fa-mobile-screen',
          'Nagad' => 'fa-mobile-screen',
          'Rocket' => 'fa-mobile-screen',
          'Upay' => 'fa-mobile-screen',
          'SSLCommerz' => 'fa-credit-card',
        ];
        $methodLabels = [
          'Cash' => 'Cash Payment',
          'Bkash' => 'Bkash',
          'Nagad' => 'Nagad',
          'Rocket' => 'Rocket',
          'Upay' => 'Upay',
          'SSLCommerz' => 'SSLCommerz',
        ];
        ?>
            <div class="ipb-form-field is-wide">
              <label class="ipb-form-label">Payment Method</label>
              <div class="custom-dropdown ipb-pay-method" id="paymentDropdown" data-selected="<?= htmlspecialchars($selected_method) ?>">
                <div class="selected">--Select--</div>
                <div class="options">
                  <?php foreach ($methodLabels as $value => $label): ?>
                    <div class="option" data-value="<?= esc($value, 'attr') ?>">
                      <i class="fa <?= esc($methodIcons[$value] ?? 'fa-wallet') ?>" aria-hidden="true"></i>
                      <span><?= esc($label) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
                <input type="hidden" name="paid_via" id="paid_via">
              </div>
              <small id="paid_via-error" class="error text-danger"></small>
            </div>

            <div class="ipb-form-field">
              <label class="ipb-form-label" for="method_trx">Payment Transaction Id</label>
              <?= form_input([
                'id' => 'method_trx',
                'name' => 'method_trx',
                'type' => 'text',
                'class' => 'form-control',
                'placeholder' => strtoupper(random_string('alnum', 11)),
              ]); ?>
              <small id="method_trx-error" class="error text-danger"></small>
            </div>

          <?php
          $payment_status = !empty($payment_details->status) ? $payment_details->status : '';
          $lastRenewedValue = ($payment_status == 'pending')
            ? date("Y-m-d\TH:i")
            : date("Y-m-d\TH:i", strtotime($details->last_renewed));
          ?>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="last_renewed">
                Renewal Date
                <span class="ipb-form-hint-inline">Should be today</span>
              </label>
              <?= form_input([
                'id' => 'last_renewed',
                'type' => 'datetime-local',
                'name' => 'last_renewed',
                'class' => 'form-control',
                'value' => $lastRenewedValue,
                'disabled' => 'disabled'
              ]); ?>
              <small id="last_renewed-error" class="error text-danger"></small>
            </div>
          </div>
        </div>

        <!-- Validity / Expire / Status -->
        <div class="ipb-cust-recharge-section">
          <h4 class="ipb-cust-recharge-section__label">Dates &amp; status</h4>
          <div class="ipb-form-grid">
          <?php
          $now2 = new DateTime();
          $oldExpire = !empty($details->will_expire) ? new DateTime($details->will_expire) : null;
          $expireLabel = "Expire Date";
          $expireValue = date("Y-m-d\TH:i");
          $daysDifference = 0;
          if (!empty($details->last_renewed) && !empty($details->will_expire)) {
            $lastRenewed2 = new DateTime($details->last_renewed);
            $willExpire2 = new DateTime($details->will_expire);
            $interval2 = $lastRenewed2->diff($willExpire2);
            $daysDifference = $interval2->days;
          }
          if ($oldExpire) {
            $isExpired = $oldExpire < $now2;
            $isThisMonth = $oldExpire->format('Y-m') === $now2->format('Y-m');
            if ($isExpired || $isThisMonth) {
              $expireLabel .= ' (Previous: ' . $oldExpire->format('d/m/Y h:i A') . ')';
              $newValue = clone $oldExpire;
              $newValue->modify("-" . $daysDifference . " days");
              $newValue->modify('+30 days');
              $expireValue = $newValue->format("Y-m-d\TH:i");
            } else {
              $expireValue = $oldExpire->format("Y-m-d\TH:i");
            }
          }
          ?>
          <?php if (isset($details->created_by) && $details->created_by === 'resellerAdmin' && isset($admin_details) && ($admin_details->billing_type ?? 'postpaid') === 'postpaid'): ?>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="selected_duration">Validity Period</label>
              <select name="duration" id="selected_duration" class="form-control">
                <?php
                $periods = !empty($admin_details->reseller_validity_periods)
                  ? explode(',', $admin_details->reseller_validity_periods)
                  : ['3', '5', '7', '30'];
                foreach ($periods as $period): ?>
                  <option value="<?= trim($period) ?>" <?= trim($period) == '30' ? 'selected' : '' ?>><?= trim($period) ?>
                    Days</option>
                <?php endforeach; ?>
              </select>
              <small id="duration-error" class="error text-danger"></small>
            </div>
            <input type="hidden" name="will_expire" value="<?= $expireValue ?>">
          <?php else: ?>
            <div class="ipb-form-field">
              <label class="ipb-form-label" for="will_expire"><?= esc($expireLabel) ?></label>
              <?= form_input([
                'id' => 'will_expire',
                'type' => 'datetime-local',
                'name' => 'will_expire',
                'class' => 'form-control',
                'value' => $expireValue,
              ]); ?>
              <?php if (isset($payment_status) && $payment_status == 'pending' && $daysDifference > 0): ?>
                <span class="ipb-form-hint"><?= (int) $daysDifference ?> days payment was due</span>
              <?php endif; ?>
              <small id="will_expire-error" class="error text-danger"></small>
            </div>
          <?php endif; ?>

          <?php
          $payment_status = !empty($payment_details->status) ? $payment_details->status : 'pending';
          ?>
            <div class="ipb-form-field">
              <span class="ipb-form-label">Status</span>
              <div class="ipb-choice-group" role="radiogroup" aria-label="Payment status">
                <label class="ipb-choice <?= $payment_status == 'successful' ? 'is-active' : '' ?>">
                  <?= form_radio(['name' => 'status', 'value' => 'successful', 'checked' => ($payment_status == 'successful'), 'class' => 'ipb-choice-input']); ?>
                  <i class="fa fa-circle-check" aria-hidden="true"></i>
                  Successful
                </label>
                <label class="ipb-choice <?= $payment_status != 'successful' ? 'is-active' : '' ?>">
                  <?= form_radio(['name' => 'status', 'value' => 'pending', 'checked' => ($payment_status != 'successful'), 'class' => 'ipb-choice-input']); ?>
                  <i class="fa fa-clock" aria-hidden="true"></i>
                  Pending
                </label>
              </div>
              <small id="status-error" class="error text-danger"></small>
            </div>

            <div class="ipb-form-field is-wide">
              <label class="ipb-form-label" for="description">Description / Note</label>
              <?= form_textarea([
                'id' => 'description',
                'name' => 'description',
                'class' => 'form-control',
                'rows' => 3,
                'placeholder' => 'Add any notes about this recharge (optional)…',
                'value' => '',
              ]); ?>
            </div>
          </div>
        </div>

        </div><!-- /.card body -->

        <footer class="ipb-cust-recharge-card__foot">
          <?= form_button([
            "content" => '<i class="fa fa-rotate" aria-hidden="true"></i><span>Update Recharge</span>',
            "class" => "ipb-sub-btn ipb-sub-btn--primary",
            "type" => "submit",
            "id" => "s2-submit-btn",
          ]); ?>
          <span class="ipb-cust-recharge-foot-hint">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            Fund deduction applies on successful recharge
          </span>
        </footer>
      </article>
    </div><!-- /#section2-box -->

    <?= form_close(); ?>

  </section>
  <!-- /.content -->
</div>

<!-- ─── STYLES ─── -->
<style>
  /* SECTION TAB SELECTOR */
  .section-tab-wrapper {
    display: flex;
    gap: 16px;
    margin-bottom: 22px;
    flex-wrap: wrap;
  }

  .section-tab-item {
    flex: 1 1 240px;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    background: #fff;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: var(--shadow-1, 0 2px 8px rgba(0, 0, 0, 0.05));
    user-select: none;
  }

  @media (max-width: 767px) {
    .section-tab-wrapper { gap: 10px; }
    .section-tab-item {
      flex: 1 1 100%;
      padding: 14px 14px;
      gap: 10px;
    }
    .stab-desc { font-size: 11.5px; line-height: 1.4; }
    .stab-icon { width: 40px; height: 40px; font-size: 22px; }
  }

  .section-tab-item:hover {
    border-color: #aaa;
    box-shadow: var(--shadow-2, 0 4px 14px rgba(0, 0, 0, 0.10));
  }

  .section-tab-item.active#tab-sec1 {
    border-color: #00a65a;
    background: #f0fff4;
    box-shadow: 0 4px 18px rgba(0, 166, 90, 0.18);
  }

  .section-tab-item.active#tab-sec2 {
    border-color: #f39c12;
    background: #fffbf0;
    box-shadow: 0 4px 18px rgba(243, 156, 18, 0.18);
  }

  .stab-icon {
    font-size: 26px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .s1-icon {
    background: #d4edda;
    color: #00a65a;
  }

  .s2-icon {
    background: #fff3cd;
    color: #f39c12;
  }

  .stab-title {
    font-size: 14px;
    font-weight: 700;
    color: #222;
  }

  .stab-desc {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
  }

  /* SECTION BADGE */
  .sec-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
    margin-right: 8px;
    vertical-align: middle;
  }

  .s1-badge {
    background: #00a65a;
    color: #fff;
  }

  .s2-badge {
    background: #f39c12;
    color: #fff;
  }

  /* BOX OVERRIDES */
  .box {
    border-radius: 12px !important;
    box-shadow: var(--shadow-1, 0 10px 30px rgba(0, 0, 0, 0.08)) !important;
    border: none;
  }

  /* CUSTOM DROPDOWN — 06 §8 item 8: was hardcoded #fff/#f9f9f9/#ccc/#eee, so
     the panel and its "selected" pill stayed light-on-light once the page
     went dark, making the payment-method picker unreadable on the recharge
     screen. Tokenized so it follows the same theme vars as the rest of the
     shell (auto-flips under body.ipb[data-theme="dark"]; no separate dark
     block needed). */
  .custom-dropdown {
    position: relative;
    border: 1px solid var(--border, #ccc);
    border-radius: 4px;
    width: 100%;
    cursor: pointer;
    background: var(--surface, #fff);
    color: var(--text-primary);
    font-size: 14px;
  }

  .custom-dropdown .selected {
    padding: 10px;
    background: var(--surface-2, #f9f9f9);
    border: 1px solid var(--border, #ccc);
    color: var(--text-primary);
  }

  .custom-dropdown .options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 10;
    border: 1px solid var(--border, #ccc);
    border-top: none;
    background: var(--surface, #fff);
    display: none;
    max-height: 200px;
    overflow-y: auto;
  }

  .custom-dropdown .option {
    padding: 10px;
    border-bottom: 1px solid var(--border, #eee);
    display: flex;
    align-items: center;
    color: var(--text-primary);
  }

  .custom-dropdown .option:hover {
    background-color: var(--surface-hover, rgb(173, 173, 173));
  }
</style>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  /* ─── Package data from PHP ─── */
  const packageData = <?= json_encode(array_column((array) $packages, null, 'id')) ?>;
  const details = <?= json_encode($details) ?>;
  const userRole = '<?= session()->get("user_role") ?>';
  const packageProfileMap = <?= json_encode($package_profile_map ?? []) ?>;
  // Page-load-performance audit (Axis1 #4): live profile list loads via AJAX
  // after paint (see the get_subscription_mikrotik_info() call below), so
  // this starts as the last-known (DB) value and gets replaced once the
  // router answers — filterProfilesByPackage()/the package-change handler
  // both read from this instead of re-embedding PHP each time.
  let livePppoeProfiles = <?= json_encode($profiles ?? []) ?>;

  /* ══════════════════════════════════════════
     SECTION SWITCHER
  ══════════════════════════════════════════ */
  function switchSection(n) {
    var tab1 = document.getElementById('tab-sec1');
    var tab2 = document.getElementById('tab-sec2');
    if (tab1) tab1.classList.toggle('active', n === 1);
    if (tab2) tab2.classList.toggle('active', n === 2);
    var sec1 = document.getElementById('section1-box');
    var sec2 = document.getElementById('section2-box');
    if (sec1) sec1.style.display = n === 1 ? 'block' : 'none';
    if (sec2) sec2.style.display = n === 2 ? 'block' : 'none';
    var statusOnly = document.getElementById('status_only_hidden');
    if (statusOnly) statusOnly.value = n === 1 ? '1' : '0';
  }

  /* ══════════════════════════════════════════
     PPPoE Profile Filtering (Section 2)
  ══════════════════════════════════════════ */
  function filterProfilesByPackage(selectedPackageName) {
    var profileDropdown = $('select[name="pppoe_profile"]');
    var allProfiles = livePppoeProfiles;
    if (!selectedPackageName || allProfiles.length === 0) return;
    var packageMatch = selectedPackageName.match(/(\d+)/);
    if (!packageMatch) return;
    var packageNumber = parseInt(packageMatch[1]);
    var matchingProfiles = allProfiles.filter(function (profile) {
      var pm = profile.match(/(\d+)/);
      return pm && parseInt(pm[1]) === packageNumber;
    });
    var currentSelectedProfile = profileDropdown.val();
    profileDropdown.html('<option value="">--Select--</option>');
    if (matchingProfiles.length > 0) {
      matchingProfiles.forEach(function (profile) {
        var sel = (profile === currentSelectedProfile) ? 'selected' : '';
        profileDropdown.append('<option value="' + profile + '" ' + sel + '>' + profile + '</option>');
      });
    } else {
      profileDropdown.append('<option value="">No matching profile found</option>');
    }
  }

  function getPackageName(packageId) {
    return $('select[name="package_id"]').find('option:selected').text();
  }

  document.addEventListener('DOMContentLoaded', function () {
    /* ── Package Readonly Toggle ── */
    var readonlyInput = document.getElementById('packageReadonly');
    var packageDropdown = document.getElementById('packageDropdown');
    if (readonlyInput && packageDropdown) {
      readonlyInput.addEventListener('click', function () {
        readonlyInput.style.display = 'none';
        packageDropdown.style.display = 'block';
        packageDropdown.focus();
      });
    }

    /* ── Custom Dropdown (Payment Method, Section 2) ── */
    var dropdown = document.getElementById('paymentDropdown');
    if (dropdown) {
      var selected = dropdown.querySelector('.selected');
      var options = dropdown.querySelector('.options');
      var hiddenInput = dropdown.querySelector('input');
      var defaultVal = dropdown.getAttribute('data-selected');

      function setOption(option) {
        selected.innerHTML = option.innerHTML;
        hiddenInput.value = option.getAttribute('data-value');
        dropdown.querySelectorAll('.option').forEach(function (o) { o.classList.remove('active'); });
        option.classList.add('active');
      }

      selected.addEventListener('click', function () {
        options.style.display = options.style.display === 'block' ? 'none' : 'block';
      });
      dropdown.querySelectorAll('.option').forEach(function (option) {
        option.addEventListener('click', function () { setOption(this); options.style.display = 'none'; });
        if (defaultVal && option.getAttribute('data-value') === defaultVal) setOption(option);
      });
      document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) options.style.display = 'none';
      });
    }

    document.querySelectorAll('.ipb-cust-recharge .ipb-choice-group').forEach(function (group) {
      group.querySelectorAll('.ipb-choice').forEach(function (choice) {
        var input = choice.querySelector('input[type="radio"]');
        if (!input) return;
        input.addEventListener('change', function () {
          group.querySelectorAll('.ipb-choice').forEach(function (c) { c.classList.remove('is-active'); });
          if (input.checked) choice.classList.add('is-active');
        });
      });
    });

    /* ── Initial PPPoE filter ── */
    var initPkgId = $('select[name="package_id"]').val();
    if (initPkgId && userRole !== 'admin') filterProfilesByPackage(getPackageName(initPkgId));
  });

  $('select[name="package_id"]').on('change', function () {
    var packageId = $(this).val();
    if (packageId && userRole !== 'admin') {
      filterProfilesByPackage(getPackageName(packageId));
      
      // Auto-select mapped profile
      if (packageProfileMap && packageProfileMap[packageId]) {
        var targetProfile = packageProfileMap[packageId];
        $('select[name="pppoe_profile"]').val(targetProfile).trigger('change');
      }
    } else {
      var pd = $('select[name="pppoe_profile"]');
      pd.html('<option value="">--Select--</option>');
      livePppoeProfiles.forEach(function (p) { pd.append('<option value="' + p + '">' + p + '</option>'); });
    }
  });

  <?php if (!empty($mikrotik_pending)): ?>
  // Live profile list, fetched after paint instead of blocking render (or
  // replacing the whole page with an error view) on a slow/offline router.
  $.ajax({
    url: '<?= route_to('route.customer.getSubscriptionMikrotikInfo', $details->id); ?>',
    type: 'GET',
    dataType: 'json'
  }).done(function (resp) {
    if (!resp || !resp.ok) return;
    livePppoeProfiles = resp.profiles || [];

    var packageId = $('select[name="package_id"]').val();
    if (packageId && userRole !== 'admin') {
      filterProfilesByPackage(getPackageName(packageId));
      if (packageProfileMap && packageProfileMap[packageId]) {
        $('select[name="pppoe_profile"]').val(packageProfileMap[packageId]).trigger('change');
      }
    } else {
      var pd = $('select[name="pppoe_profile"]');
      var current = pd.val();
      pd.html('<option value="">--Select--</option>');
      livePppoeProfiles.forEach(function (p) { pd.append('<option value="' + p + '">' + p + '</option>'); });
      if (resp.pppoe_profile && livePppoeProfiles.indexOf(resp.pppoe_profile) !== -1) {
        pd.val(resp.pppoe_profile);
      } else if (current && livePppoeProfiles.indexOf(current) !== -1) {
        pd.val(current);
      }
    }
  });
  <?php endif; ?>

  /* ══════════════════════════════════════════
     FORM SUBMIT
  ══════════════════════════════════════════ */
  $('#form').submit(function (e) {
    e.preventDefault();
    const form = this;
    const isSection1 = document.getElementById('status_only_hidden').value === '1';

    /* ─── SECTION 1 SUBMIT (Status Only) ─── */
    if (isSection1) {
      // Client-side validation for Section 1
      const month = document.getElementById('s1_month').value;
      const paidVia = document.getElementById('s1_paid_via').value;
      const status = document.querySelector('input[name="s1_status"]:checked');
      let valid = true;

      $('#s1_month-error').text('');
      $('#s1_paid_via-error').text('');
      $('#s1_status-error').text('');

      if (!month) { $('#s1_month-error').text('Please select a payment month.'); valid = false; }
      if (!paidVia) { $('#s1_paid_via-error').text('Please select a payment method.'); valid = false; }
      if (!status) { $('#s1_status-error').text('Please select a status.'); valid = false; }
      if (!valid) return;

      let formData = new FormData(form);

      $.ajax({
        url: '<?= route_to('route.customer.update_subscription', $details->id); ?>',
        type: 'POST',
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
          $('#s1-submit-btn').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
        },
        success: function (result) {
          $('#s1-submit-btn').html('<i class="fa fa-check"></i>&nbsp; Update Payment Status').removeAttr('disabled');
          tata.success('Payment status updated', result.response, {
            duration: 3000,
            onClose: () => window.history.back()
          });
        },
        error: function ({ responseText }) {
          const result = JSON.parse(responseText);
          $('#s1-submit-btn').html('<i class="fa fa-check"></i>&nbsp; Update Payment Status').removeAttr('disabled');
          if (result.status === 'validation-error') {
            $.each(result.response, function (prefix, val) { $('#' + prefix + '-error').text(val); });
          } else {
            tata.error("Couldn't update payment status", result.response, { duration: 7000 });
          }
        }
      });
      return;
    }

    /* ─── SECTION 2 SUBMIT (Full Recharge) ─── */
    const selectedPkgId = $('#packageDropdown').val();
    const pkgInfo = packageData[selectedPkgId];

    if (!pkgInfo) {
      tata.error('Select a package', 'Please select a valid package');
      return;
    }

    const durationElement = document.getElementById('selected_duration');
    const today = new Date();
    let expireDate;
    let diffDays = 0;

    const prePackageId = '<?= $details->package_id ?? 0 ?>';
    const packageId = selectedPkgId;
    const preWillExpire = new Date('<?= !empty($details->will_expire) ? $details->will_expire : date("Y-m-d H:i:s") ?>');
    const subscriptionStatus = '<?= $details->subscription_status ?? "inactive" ?>';

    if (durationElement) {
      diffDays = parseInt(durationElement.value, 10) || 30;
      let baseDate = today;
      if (prePackageId == packageId && subscriptionStatus === 'active' && preWillExpire > today) {
        baseDate = preWillExpire;
      }
      expireDate = new Date(baseDate.getTime() + (diffDays * 24 * 60 * 60 * 1000));
      const pad = (n) => String(n).padStart(2, '0');
      const fmt = expireDate.getFullYear() + '-' + pad(expireDate.getMonth() + 1) + '-' + pad(expireDate.getDate()) +
        ' ' + pad(expireDate.getHours()) + ':' + pad(expireDate.getMinutes()) + ':' + pad(expireDate.getSeconds());
      document.querySelector('input[name="will_expire"]').value = fmt;
    } else {
      const expireInputVal = document.querySelector('input[name="will_expire"]').value;
      if (!expireInputVal) { tata.error('Select an expiry date', 'Please select expire date'); return; }
      expireDate = new Date(expireInputVal);
      if (prePackageId != packageId) {
        diffDays = (expireDate - today) / (1000 * 60 * 60 * 24);
      } else if (subscriptionStatus === 'active' && preWillExpire > today) {
        diffDays = (expireDate - preWillExpire) / (1000 * 60 * 60 * 24);
      } else {
        diffDays = (expireDate - today) / (1000 * 60 * 60 * 24);
      }
    }

    if (diffDays < 0) diffDays = 0;

    const packagePrice = parseFloat(pkgInfo.selling_price || pkgInfo.price);
    const perDayCost = packagePrice / 30;
    const totalCharge = (diffDays * perDayCost).toFixed(2);

    function submitSection2() {
      let formData = new FormData(form);
      formData.append('extra_days', diffDays);
      formData.append('per_day_cost', perDayCost.toFixed(2));
      formData.append('total_charge', totalCharge);

      const multiple = '<?= $multiple ?? 'false' ?>';
      if (multiple) {
        const userNames = <?= json_encode($ids ?? []) ?>;
        const packageIds = <?= json_encode($packageIds ?? []) ?>;
        formData.append('multiple', multiple);
        formData.append('userNames', JSON.stringify(userNames));
        formData.append('packageIds', JSON.stringify(packageIds));
      } else {
        formData.append('multiple', 'false');
      }

      $.ajax({
        url: '<?= route_to('route.customer.update_subscription', $details->id); ?>',
        type: 'POST',
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
          $('#s2-submit-btn').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
        },
        success: function (result) {
          $('#s2-submit-btn').html('<i class="fa fa-refresh"></i>&nbsp; Update Recharge').removeAttr('disabled');
          tata.success('Recharge updated', result.response, {
            duration: 3000,
            onClose: () => window.history.back()
          });
        },
        error: function ({ responseText }) {
          const result = JSON.parse(responseText);
          $('#s2-submit-btn').html('<i class="fa fa-refresh"></i>&nbsp; Update Recharge').removeAttr('disabled');
          if (result.status === 'validation-error') {
            $.each(result.response, function (prefix, val) { $('#' + prefix + '-error').text(val); });
          } else {
            tata.error("Couldn't update recharge", result.response, { duration: 7000 });
          }
        }
      });
    }

    // Reseller: show confirmation popup before Section 2 submit
    if (userRole === 'resellerAdmin') {
      Swal.fire({
        title: 'Confirm Subscription Update',
        html: `
          <div style="text-align:left;background:var(--surface, #fff);padding:20px;border-radius:10px;border:1px solid #e5e7eb;font-family:'Segoe UI',sans-serif;">
            <table style="width:100%;font-size:14px;color:var(--text-primary, #374151);border-collapse:collapse;">
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Customer</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= $details->name ?></td></tr>
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Package</td><td style="padding:6px 0;text-align:right;font-weight:600;">${pkgInfo.package_name}</td></tr>
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Monthly Price</td><td style="padding:6px 0;text-align:right;">${packagePrice} BDT</td></tr>
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Cost Per Day</td><td style="padding:6px 0;text-align:right;">${perDayCost.toFixed(2)} BDT</td></tr>
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Expire Date</td><td style="padding:6px 0;text-align:right;">${expireDate.toLocaleString()}</td></tr>
              <tr><td style="padding:6px 0;color:var(--text-secondary, #6b7280);">Total Active Days</td><td style="padding:6px 0;text-align:right;">${diffDays} days</td></tr>
            </table>
            <div style="margin-top:15px;padding-top:12px;border-top:2px dashed #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
              <div style="font-size:14px;color:var(--text-secondary, #6b7280);">Total Charge</div>
              <div style="font-size:22px;font-weight:700;color:var(--text-primary, #111827);">${totalCharge} BDT</div>
            </div>
          </div>
          <p style="margin-top:14px;font-size:13px;color:var(--text-secondary, #6b7280);text-align:center;">This amount will be deducted from your fund. Continue?</p>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Recharge Now!',
        cancelButtonText: 'Cancel'
      }).then((res) => { if (res.isConfirmed) submitSection2(); });
    } else {
      submitSection2();
    }
  });
</script>

<?= $this->endSection('script'); ?>
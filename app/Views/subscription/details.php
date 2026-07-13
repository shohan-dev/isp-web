<?php
/** @var object $details */
/** @var object $rdetails */
?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  .ipb-sub {
    --sub-ok: #16a34a;
    --sub-ok-bg: #ecfdf3;
    --sub-warn: #d97706;
    --sub-warn-bg: #fffbeb;
    --sub-danger: #dc2626;
    --sub-danger-bg: #fef2f2;
  }

  .ipb-sub-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 16px;
    align-items: stretch;
  }

  .ipb-sub-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 16px;
  }

  .ipb-sub-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-1, 0 1px 2px rgba(15, 23, 42, 0.04));
    overflow: hidden;
    height: 100%;
  }

  .ipb-sub-card-body {
    padding: 20px 22px;
  }

  .ipb-sub-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
  }

  .ipb-sub-card-title {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    letter-spacing: -0.02em;
  }

  .ipb-sub-card-sub {
    margin: 4px 0 0;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
  }

  /* Hero package panel */
  .ipb-sub-hero {
    background:
      radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.16), transparent 42%),
      linear-gradient(145deg, var(--secondary-700, #001f55), var(--secondary-900, #001233));
    color: #fff;
    border: 0;
    position: relative;
  }

  .ipb-sub-hero::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500, #f75803), #ffb38a);
  }

  .ipb-sub-hero .ipb-sub-card-body {
    padding: 24px;
  }

  .ipb-sub-hero-name {
    margin: 0;
    font-size: clamp(22px, 3vw, 28px);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
  }

  .ipb-sub-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }

  .ipb-sub-badge.is-active {
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.22);
  }

  .ipb-sub-badge.is-ok {
    background: var(--sub-ok-bg);
    color: var(--sub-ok);
  }

  .ipb-sub-badge.is-warn {
    background: var(--sub-warn-bg);
    color: var(--sub-warn);
  }

  .ipb-sub-badge.is-danger {
    background: var(--sub-danger-bg);
    color: var(--sub-danger);
  }

  .ipb-sub-meta {
    display: grid;
    gap: 0;
    margin-top: 18px;
  }

  .ipb-sub-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    font-size: 13.5px;
  }

  .ipb-sub-meta-row:last-child {
    border-bottom: 0;
  }

  .ipb-sub-meta-row span {
    opacity: 0.78;
    font-weight: 600;
  }

  .ipb-sub-meta-row strong {
    font-weight: 800;
    text-align: right;
  }

  .ipb-sub-field {
    margin-top: 14px;
  }

  .ipb-sub-field label {
    display: block;
    margin: 0 0 6px;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.72);
  }

  .ipb-sub-field .form-control,
  .ipb-sub-field select,
  .ipb-sub-field input {
    width: 100%;
    min-height: 42px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    padding: 8px 12px;
    font-size: 13.5px;
    font-weight: 700;
    outline: none;
  }

  .ipb-sub-field select option {
    color: #0f172a;
  }

  .ipb-sub-field .form-control:focus,
  .ipb-sub-field select:focus,
  .ipb-sub-field input:focus {
    border-color: #fff;
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.12);
  }

  .ipb-sub-note {
    margin: 14px 0 0;
    font-size: 12px;
    font-weight: 600;
    opacity: 0.72;
  }

  .ipb-sub-error {
    display: none;
    margin-top: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #fecaca;
  }

  /* Status panel */
  .ipb-sub-status {
    text-align: center;
  }

  .ipb-sub-status-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    background: var(--sub-ok-bg);
    color: var(--sub-ok);
  }

  .ipb-sub-status-icon.is-warn {
    background: var(--sub-warn-bg);
    color: var(--sub-warn);
  }

  .ipb-sub-status-icon.is-danger {
    background: var(--sub-danger-bg);
    color: var(--sub-danger);
  }

  .ipb-sub-status-title {
    margin: 14px 0 4px;
    font-size: 18px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
  }

  .ipb-sub-status-sub {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-sub-progress {
    margin-top: 20px;
    text-align: left;
  }

  .ipb-sub-progress-top {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary, #51607a);
  }

  .ipb-sub-progress-track {
    height: 8px;
    border-radius: 999px;
    background: var(--surface-2, #f1f5f9);
    overflow: hidden;
  }

  .ipb-sub-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--primary-500, #f75803), #ffb38a);
    transition: width 0.3s ease;
  }

  .ipb-sub-days {
    margin-top: 18px;
    padding: 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--primary-500, #f75803), #ff8a4c);
    color: #fff;
    text-align: center;
  }

  .ipb-sub-days strong {
    display: block;
    font-size: 40px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
  }

  .ipb-sub-days span {
    display: block;
    margin-top: 6px;
    font-size: 12.5px;
    font-weight: 700;
    opacity: 0.92;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  /* Info rows */
  .ipb-sub-info-list {
    display: grid;
    gap: 14px;
  }

  .ipb-sub-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }

  .ipb-sub-info-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-50, #fff4ed);
    color: var(--primary-600, #d94601);
    flex-shrink: 0;
  }

  .ipb-sub-info-item label {
    display: block;
    margin: 0;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-sub-info-item strong {
    display: block;
    margin-top: 2px;
    font-size: 14px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
  }

  /* Actions */
  .ipb-sub-actions .ipb-sub-card-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .ipb-sub-reward {
    background: var(--surface-2, #f8fafc);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: 12px;
    padding: 14px;
  }

  .ipb-sub-reward label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary, #0f172a);
    cursor: pointer;
  }

  .ipb-sub-reward input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-500, #f75803);
    margin: 0;
  }

  .ipb-sub-reward-summary {
    display: none;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--border, #e6eaf0);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary, #51607a);
    line-height: 1.5;
  }

  .ipb-sub-reward-summary.visible {
    display: block;
  }

  .ipb-sub-reward-summary strong {
    color: var(--primary-600, #d94601);
    font-weight: 800;
  }

  .ipb-sub-reward-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--primary-500, #f75803);
    text-decoration: none;
  }

  .ipb-sub-reward-link:hover {
    text-decoration: underline;
  }

  .ipb-sub-renew-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 12px;
    background: var(--primary-500, #f75803) !important;
    color: #fff !important;
    font-size: 15px;
    font-weight: 800;
    font-family: var(--font-sans);
    box-shadow: var(--shadow-brand, 0 8px 18px rgba(247, 88, 3, 0.28));
    cursor: pointer;
    transition: transform 0.14s ease, background 0.14s ease;
  }

  .ipb-sub-renew-btn:hover,
  .ipb-sub-renew-btn:focus {
    background: var(--primary-600, #d94601) !important;
    color: #fff !important;
    transform: translateY(-1px);
  }

  .ipb-sub-renew-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none !important;
  }

  /* Instructions */
  .ipb-sub-steps {
    display: grid;
    gap: 14px;
  }

  .ipb-sub-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }

  .ipb-sub-step-num {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--primary-50, #fff4ed);
    color: var(--primary-600, #d94601);
    font-size: 12.5px;
    font-weight: 800;
  }

  .ipb-sub-step-text {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-secondary, #51607a);
    line-height: 1.55;
  }

  .ipb-sub-chip {
    display: inline-flex;
    align-items: center;
    margin-top: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    background: var(--surface-2, #f8fafc);
    border: 1px dashed var(--border-strong, #d7dee7);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 15px;
    font-weight: 800;
    color: var(--primary-600, #d94601);
  }

  .ipb-sub-tip {
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--primary-50, #fff4ed);
    color: var(--primary-700, #c2410c);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
  }

  .ipb-sub-tip i {
    margin-right: 6px;
  }

  /* Public / guest layout */
  .subscription-wrapper:not(.content-wrapper) {
    background: var(--bg, #f5f7fb);
    min-height: 100vh;
    min-height: 100dvh;
    padding: 28px 16px 40px;
    max-width: 1100px;
    margin: 0 auto;
  }

  body.ipb[data-theme="dark"] .ipb-sub-card,
  body.ipb.dark-mode .ipb-sub-card {
    background: var(--surface);
    border-color: var(--border);
  }

  body.ipb[data-theme="dark"] .ipb-sub-reward,
  body.ipb.dark-mode .ipb-sub-reward,
  body.ipb[data-theme="dark"] .ipb-sub-chip,
  body.ipb.dark-mode .ipb-sub-chip {
    background: var(--surface-2);
    border-color: var(--border);
  }

  @media (max-width: 1024px) {
    .ipb-sub-grid,
    .ipb-sub-grid-2 {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767px) {
    .ipb-sub-hero .ipb-sub-card-body,
    .ipb-sub-card-body {
      padding: 16px;
    }

    .ipb-sub-hero-name {
      font-size: 20px;
    }

    .ipb-sub-days strong {
      font-size: 34px;
    }

    .ipb-sub-renew-btn {
      min-height: 48px;
    }

    .ipb-sub-meta-row {
      flex-direction: column;
      align-items: flex-start;
      gap: 4px;
    }

    .ipb-sub-meta-row strong {
      text-align: left;
    }

    .ipb-sub-field .form-control,
    .ipb-sub-field select,
    .ipb-sub-field input {
      font-size: 16px;
    }
  }
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$package = getUserPackage($details->id);
$currentPackageId = $package->id ?? $package['id'] ?? null;
$price = $package->price ?? $package['price'] ?? 0;
$packageName = $package->package_name ?? $package['package_name'] ?? '--';
$bandwidth = $package->bandwidth ?? $package['bandwidth'] ?? '--';

$current = time();
$renew = strtotime($details->last_renewed);
$expire = strtotime($details->will_expire);
$total = max($expire - $renew, 1);
$used = min(round((($current - $renew) / $total) * 100, 1), 100);
$remain = max(100 - $used, 0);
$daysLeft = max(round(($expire - $current) / 86400), 0);

$isExpired = $daysLeft <= 0;
$isExpiringSoon = !$isExpired && $daysLeft <= 7;
$statusTone = $isExpired ? 'is-danger' : ($isExpiringSoon ? 'is-warn' : 'is-ok');
$statusIcon = $isExpired ? 'fa-circle-xmark' : ($isExpiringSoon ? 'fa-triangle-exclamation' : 'fa-circle-check');
$statusTitle = $isExpired ? 'Subscription Expired' : ($isExpiringSoon ? 'Expiring Soon' : 'Subscription Active');
$statusSub = $isExpired
  ? 'Renew now to restore service'
  : ($isExpiringSoon ? 'Your plan will expire soon' : 'Everything is running smoothly');

$canRenew = userHasPermission('subscription', 'renew')
  || (isset($isPublic) && $isPublic)
  || getSession('status') === 'inactive';

$rewardRedeem = $reward_redeem ?? ['enabled' => false];
$showReward = !empty($rewardRedeem['enabled']) && (int) ($rewardRedeem['available_points'] ?? 0) > 0;
$paymentNumber = $admin_details->payment_receive_number ?? $admin_details->mobile ?? 'N/A';
?>

<div class="<?= (isset($isPublic) && $isPublic) ? 'subscription-wrapper' : 'content-wrapper' ?>">
  <section class="content ipb-saas-list ipb-sub">

    <?= $this->include('components/page-header', [
      'title' => 'My Subscription',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Subscription'],
      ],
    ]); ?>

    <div class="ipb-sub-grid">
      <!-- Package -->
      <div class="ipb-sub-card ipb-sub-hero">
        <div class="ipb-sub-card-body">
          <div class="ipb-sub-card-head">
            <div>
              <p class="ipb-sub-card-sub" style="color:rgba(255,255,255,.7);margin:0 0 6px">Current plan</p>
              <h2 class="ipb-sub-hero-name" id="display_package_name"><?= esc($packageName); ?></h2>
            </div>
            <span class="ipb-sub-badge is-active">
              <i class="fa fa-bolt" aria-hidden="true"></i>
              <?= $isExpired ? 'Expired' : 'Active'; ?>
            </span>
          </div>

          <div class="ipb-sub-meta">
            <div class="ipb-sub-meta-row">
              <span>Customer</span>
              <strong><?= esc($details->name ?? $details['name'] ?? '--'); ?></strong>
            </div>
            <div class="ipb-sub-meta-row">
              <span>Phone</span>
              <strong><?= esc($details->mobile ?? $details['mobile'] ?? '--'); ?></strong>
            </div>
            <div class="ipb-sub-meta-row">
              <span>Bandwidth</span>
              <strong id="display_bandwidth"><?= esc($bandwidth); ?></strong>
            </div>
            <div class="ipb-sub-meta-row">
              <span>Price</span>
              <strong id="display_price">৳ <?= esc($price); ?></strong>
            </div>
          </div>

          <?php if (!empty($packages)): ?>
            <div class="ipb-sub-field">
              <label for="selected_package">Select package</label>
              <select id="selected_package" class="form-control">
                <option value="">-- Select --</option>
                <?php foreach ($packages as $pkg):
                  $pkgId = $pkg->id ?? $pkg['id'];
                ?>
                  <option
                    value="<?= $pkgId ?>"
                    data-price="<?= $pkg->price ?? $pkg['price'] ?>"
                    data-name="<?= esc($pkg->package_name ?? $pkg['package_name'], 'attr') ?>"
                    data-bandwidth="<?= esc($pkg->bandwidth ?? $pkg['bandwidth'], 'attr') ?>"
                    <?= ($pkgId == $currentPackageId) ? 'selected' : '' ?>>
                    <?= esc($pkg->package_name ?? $pkg['package_name']); ?>
                    - <?= esc($pkg->price ?? $pkg['price']); ?>৳
                  </option>
                <?php endforeach; ?>
              </select>
              <small id="packageError" class="ipb-sub-error">Please select a package</small>
            </div>
          <?php endif; ?>

          <?php if (isset($details->created_by) && $details->created_by === 'resellerAdmin'): ?>
            <?php if (($admin_details->billing_type ?? 'postpaid') === 'prepaid'): ?>
              <div class="ipb-sub-field">
                <label for="selected_duration">Validity period (days)</label>
                <input type="number" id="selected_duration" class="form-control" value="30" min="1" placeholder="Days">
              </div>
            <?php else: ?>
              <div class="ipb-sub-field">
                <label for="selected_duration">Validity period</label>
                <select id="selected_duration" class="form-control">
                  <?php
                  $periods = !empty($admin_details->reseller_validity_periods)
                    ? explode(',', $admin_details->reseller_validity_periods)
                    : ['3', '5', '7', '30'];
                  foreach ($periods as $period): ?>
                    <option value="<?= trim($period) ?>" <?= trim($period) == '30' ? 'selected' : '' ?>>
                      <?= trim($period) ?> Days
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <p class="ipb-sub-note">Pricing is subject to change based on package and validity.</p>
        </div>
      </div>

      <!-- Status -->
      <div class="ipb-sub-card">
        <div class="ipb-sub-card-body ipb-sub-status">
          <div class="ipb-sub-status-icon <?= $statusTone ?>">
            <i class="fa <?= $statusIcon ?>" aria-hidden="true"></i>
          </div>
          <h3 class="ipb-sub-status-title"><?= esc($statusTitle); ?></h3>
          <p class="ipb-sub-status-sub"><?= esc($statusSub); ?></p>

          <div class="ipb-sub-progress">
            <div class="ipb-sub-progress-top">
              <span><?= $used ?>% used</span>
              <span><?= $remain ?>% remaining</span>
            </div>
            <div class="ipb-sub-progress-track">
              <div class="ipb-sub-progress-bar" style="width:<?= $used ?>%"></div>
            </div>
          </div>

          <div class="ipb-sub-days">
            <strong><?= (int) $daysLeft ?></strong>
            <span>Days left</span>
          </div>
        </div>
      </div>
    </div>

    <div class="ipb-sub-grid-2">
      <!-- Dates -->
      <div class="ipb-sub-card">
        <div class="ipb-sub-card-body">
          <div class="ipb-sub-card-head">
            <div>
              <h3 class="ipb-sub-card-title">Billing period</h3>
              <p class="ipb-sub-card-sub">Renewal and expiry timeline</p>
            </div>
          </div>
          <div class="ipb-sub-info-list">
            <div class="ipb-sub-info-item">
              <div class="ipb-sub-info-icon" aria-hidden="true"><i class="fa fa-calendar-check"></i></div>
              <div>
                <label>Last renewed</label>
                <strong><?= date('d M Y, h:i a', strtotime($details->last_renewed)); ?></strong>
              </div>
            </div>
            <div class="ipb-sub-info-item">
              <div class="ipb-sub-info-icon" aria-hidden="true"><i class="fa fa-calendar-xmark"></i></div>
              <div>
                <label>Expire date</label>
                <strong><?= date('d M Y, h:i a', strtotime($details->will_expire)); ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="ipb-sub-card ipb-sub-actions">
        <div class="ipb-sub-card-body">
          <div class="ipb-sub-card-head" style="margin-bottom:0">
            <div>
              <h3 class="ipb-sub-card-title">Quick actions</h3>
              <p class="ipb-sub-card-sub">Renew your plan in one click</p>
            </div>
          </div>

          <?php if ($showReward): ?>
            <div class="ipb-sub-reward" id="rewardRedeemBox">
              <label>
                <input type="checkbox" id="use_reward_points" value="1">
                Use reward points on this renewal
              </label>
              <div class="ipb-sub-reward-summary" id="rewardSummary">
                Applying <strong id="rewardPointsApplied">0</strong> points
                (BDT <strong id="rewardDiscount">0</strong> off).
                Payable: BDT <strong id="rewardPayable">0</strong>.
              </div>
              <input type="hidden" id="redeem_points" value="0">
              <a class="ipb-sub-reward-link" href="<?= base_url('my-rewards'); ?>">View my reward balance</a>
            </div>
          <?php endif; ?>

          <?php if ($canRenew): ?>
            <button type="button" id="renew-btn" class="ipb-sub-renew-btn">
              <i class="fa fa-rotate" aria-hidden="true"></i>
              Renew now
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Payment instructions -->
    <div class="ipb-sub-card" style="margin-top:16px">
      <div class="ipb-sub-card-body">
        <div class="ipb-sub-card-head">
          <div>
            <h3 class="ipb-sub-card-title">How to renew automatically</h3>
            <p class="ipb-sub-card-sub">Follow these steps for instant activation</p>
          </div>
        </div>

        <div class="ipb-sub-steps">
          <div class="ipb-sub-step">
            <div class="ipb-sub-step-num">1</div>
            <div class="ipb-sub-step-text">
              Send the subscription amount (<strong class="instruction-price">৳<?= esc($price); ?></strong>)
              to your administrator’s payment number via “Send Money”:
              <div class="ipb-sub-chip"><?= esc($paymentNumber); ?></div>
            </div>
          </div>

          <div class="ipb-sub-step">
            <div class="ipb-sub-step-num">2</div>
            <div class="ipb-sub-step-text">
              In the <strong>Reference</strong> field, enter your unique <strong>User ID</strong>:
              <div class="ipb-sub-chip"><?= (int) $details->id; ?></div>
            </div>
          </div>

          <div class="ipb-sub-step">
            <div class="ipb-sub-step-num">3</div>
            <div class="ipb-sub-step-text">
              Once payment is completed with the correct reference, your line is enabled
              <strong>automatically</strong>.
            </div>
          </div>
        </div>

        <div class="ipb-sub-tip">
          <i class="fa fa-lightbulb" aria-hidden="true"></i>
          <strong>Tip:</strong> Double-check your User ID to avoid activation delays.
        </div>
      </div>
    </div>

  </section>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
  $(function () {
    $('#packageError').hide();

    var rewardPreview = <?= json_encode($reward_redeem ?? ['enabled' => false]) ?>;
    var previewUrl = '<?= base_url('my-rewards/redeem-preview') ?>';

    function updateRewardPreview() {
      if (!rewardPreview.enabled) return;
      var pkgId = $('#selected_package').val();
      if (!pkgId) return;

      $.get(previewUrl, { package_id: pkgId, points: rewardPreview.available_points || 0 }, function (res) {
        if (!res.success || !res.data) return;
        rewardPreview = res.data;
        if ($('#use_reward_points').is(':checked')) {
          showRewardSummary(rewardPreview);
        }
      });
    }

    function showRewardSummary(p) {
      if (!p || !p.enabled) {
        $('#rewardSummary').removeClass('visible');
        $('#redeem_points').val(0);
        return;
      }
      $('#rewardPointsApplied').text(p.points_applied || p.max_usable_points || 0);
      $('#rewardDiscount').text(p.discount_bdt || 0);
      $('#rewardPayable').text(p.final_payable || 0);
      $('#redeem_points').val(p.points_applied || p.max_usable_points || 0);
      $('#rewardSummary').addClass('visible');
    }

    $('#use_reward_points').on('change', function () {
      if (this.checked) {
        showRewardSummary(rewardPreview);
      } else {
        $('#rewardSummary').removeClass('visible');
        $('#redeem_points').val(0);
      }
    });

    function updatePriceDisplay() {
      var selectedOption = $('#selected_package').find('option:selected');
      if (selectedOption.val() !== '') {
        var basePrice = parseFloat(selectedOption.data('price')) || 0;
        var duration = 30;
        if ($('#selected_duration').length) {
          duration = parseInt($('#selected_duration').val(), 10) || 30;
        }
        var finalPrice = Math.round((basePrice / 30) * duration);
        $('#display_price').text('৳ ' + finalPrice);
        $('.instruction-price').text('৳' + finalPrice);
      }
    }

    $('#selected_package').on('change', function () {
      var selectedOption = $(this).find('option:selected');

      if (this.value !== '') {
        $('#packageError').hide();

        var newPrice = selectedOption.data('price');
        var newName = selectedOption.data('name');
        var newBandwidth = selectedOption.data('bandwidth');

        if (newName) $('#display_package_name').text(newName);
        if (newBandwidth) $('#display_bandwidth').text(newBandwidth);

        updatePriceDisplay();
        updateRewardPreview();
      } else {
        $('#packageError').show();
      }
    });

    $('#selected_duration').on('change input', function () {
      updatePriceDisplay();
      updateRewardPreview();
    });

    <?php if ($canRenew): ?>
      $('#renew-btn').on('click', function (e) {
        e.preventDefault();

        var pkgId = $('#selected_package').val();
        if (!pkgId) {
          $('#packageError').show();
          return;
        }

        var customerId = '<?= esc($details->id) ?>';
        var redeemPoints = parseInt($('#redeem_points').val(), 10) || 0;
        var duration = $('#selected_duration').length ? parseInt($('#selected_duration').val(), 10) || 30 : 30;

        $.ajax({
          url: '<?= route_to('route.subscription.renew') ?>',
          type: 'POST',
          data: {
            customer: customerId,
            package_id: pkgId,
            redeem_points: redeemPoints,
            duration: duration,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
          },
          beforeSend: function () {
            $('#renew-btn')
              .prop('disabled', true)
              .html("<i class='fas fa-spinner fa-spin' aria-hidden='true'></i> Please wait");
          },
          success: function (result) {
            $('#renew-btn')
              .prop('disabled', false)
              .html('<i class="fa fa-rotate" aria-hidden="true"></i> Renew now');

            swal({
              closeOnClickOutside: false,
              closeOnEsc: false,
              icon: 'success',
              title: "Success",
              text: result.response.msg,
              buttons: [
                "Close",
                {
                  text: "Pay",
                  closeModal: false
                }
              ],
            }).then(function (willPay) {
              if (willPay) {
                window.location.href = result.response.payment_url;
              }
            });
          },
          error: function (xhr) {
            var res = {};
            try { res = JSON.parse(xhr.responseText); } catch (err) {}
            $('#renew-btn')
              .prop('disabled', false)
              .html('<i class="fa fa-rotate" aria-hidden="true"></i> Renew now');
            tata.error("Couldn't renew subscription", res.response || 'Renewal failed');
          }
        });
      });
    <?php endif; ?>
  });
</script>
<?= $this->endSection('script'); ?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/subscription-page.css?v=2'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$pkg = getAdminPackage($details->id);
$packageName = is_object($pkg) ? ($pkg->package_name ?? '--') : ($pkg['package_name'] ?? '--');
$duration = is_object($pkg) ? ($pkg->duration ?? '--') : ($pkg['duration'] ?? '--');
$price = (int) (is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0));
$planType = is_object($pkg) ? ($pkg->plan_type ?? 'fixed') : ($pkg['plan_type'] ?? 'fixed');
$baseFee = (float) (is_object($pkg) ? ($pkg->base_fee ?? 0) : ($pkg['base_fee'] ?? 0));
$perUserRate = (float) (is_object($pkg) ? ($pkg->per_user_rate ?? 0) : ($pkg['per_user_rate'] ?? 0));
$isPayg = $planType === 'payg';

$discount = (int) ($rdetails['discount'] ?? ($rdetails->discount ?? 0));
$finalPrice = max($price - $discount, 0);
$monthlyEst = $isPayg ? ($baseFee + $perUserRate) : $finalPrice;

$current = time();
$renew = strtotime($details->last_renewed);
$expire = strtotime($details->will_expire);
$total = max($expire - $renew, 1);
$used = min(round((($current - $renew) / $total) * 100, 1), 100);
$remain = max(100 - $used, 0);
$daysLeft = max(round(($expire - $current) / 86400), 0);

$isExpired = $daysLeft <= 0;
$isExpiringSoon = !$isExpired && $daysLeft <= 7;
$statusTone = $isExpired ? 'is-danger' : ($isExpiringSoon ? 'is-warn' : '');
$statusIcon = $isExpired ? 'fa-circle-xmark' : ($isExpiringSoon ? 'fa-triangle-exclamation' : 'fa-circle-check');
$statusTitle = $isExpired ? 'Subscription expired' : ($isExpiringSoon ? 'Expiring soon' : 'Active & current');
$statusSub = $isExpired
    ? 'Renew to restore full access'
    : ($isExpiringSoon ? 'Renew before expiry to avoid interruption' : 'Your plan is running smoothly');

$canRenew = userHasPermission('subscription', 'renew') || getSession('status') === 'inactive';
$kpiTone = $isExpired ? 'is-danger' : ($isExpiringSoon ? 'is-warn' : '');
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-sub-page">

    <?= $this->include('components/page-header', [
      'title' => 'My Subscription',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Subscription'],
      ],
    ]); ?>

    <?= $this->include('components/trial-banner', ['trialUser' => $trialUser ?? $details ?? null]); ?>

    <?php
    helper('subscription');
    $pendingPkgId = $details->pending_package_id ?? null;
    $pendingPackage = null;
    if (!empty($pendingPkgId)) {
        $pendingPackage = model('App\Models\AdminPackage')->find($pendingPkgId);
    }
    if (!empty($pendingPackage)): ?>
      <div class="alert alert-warning" style="margin-bottom:16px">
        <strong>Package change pending:</strong>
        <?= esc(is_object($pendingPackage) ? ($pendingPackage->package_name ?? 'New plan') : ($pendingPackage['package_name'] ?? 'New plan')); ?>
        — your current package stays active until you pay.
        <a href="<?= route_to('route.payment'); ?>" class="alert-link">Go to My Payment</a>
      </div>
    <?php endif; ?>

    <div class="ipb-sub-kpis">
      <div class="ipb-sub-kpi <?= esc($kpiTone); ?>">
        <span class="ipb-sub-kpi__label">Days remaining</span>
        <span class="ipb-sub-kpi__value ipb-sub-kpi__value--accent"><?= (int) $daysLeft; ?> <small>days</small></span>
      </div>
      <div class="ipb-sub-kpi">
        <span class="ipb-sub-kpi__label">Current plan</span>
        <span class="ipb-sub-kpi__value" style="font-size:1rem"><?= esc($packageName); ?></span>
      </div>
      <div class="ipb-sub-kpi">
        <span class="ipb-sub-kpi__label"><?= $isPayg ? 'Est. monthly' : 'Plan price'; ?></span>
        <span class="ipb-sub-kpi__value">৳<?= number_format($monthlyEst, $isPayg ? 2 : 0); ?><?= $isPayg ? '' : ''; ?></span>
      </div>
      <div class="ipb-sub-kpi">
        <span class="ipb-sub-kpi__label">Expires on</span>
        <span class="ipb-sub-kpi__value" style="font-size:0.95rem"><?= date('d M Y', strtotime($details->will_expire)); ?></span>
      </div>
    </div>

    <div class="ipb-sub-layout">
      <div class="ipb-sub-layout__main">

        <article class="ipb-sub-plan">
          <div class="ipb-sub-plan__glow" aria-hidden="true"></div>
          <div class="ipb-sub-plan__head">
            <div class="ipb-sub-plan__brand">
              <span class="ipb-sub-plan__icon" aria-hidden="true">
                <i class="fa fa-<?= $isPayg ? 'bolt' : 'layer-group'; ?>"></i>
              </span>
              <div>
                <p class="ipb-sub-plan__eyebrow">Current plan</p>
                <h2 class="ipb-sub-plan__name"><?= esc($packageName); ?></h2>
              </div>
            </div>
            <?php if ($isPayg): ?>
              <span class="ipb-sub-badge is-payg"><i class="fa fa-bolt"></i> Pay-as-you-go</span>
            <?php elseif ($isExpired): ?>
              <span class="ipb-sub-badge is-expired"><i class="fa fa-clock"></i> Expired</span>
            <?php else: ?>
              <span class="ipb-sub-badge is-active"><i class="fa fa-check"></i> Active</span>
            <?php endif; ?>
          </div>

          <div class="ipb-sub-breakdown">
            <?php if ($isPayg): ?>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-server"></i> Platform fee</span>
                <span class="ipb-sub-breakdown__amount">৳<?= number_format($baseFee, 2); ?>/mo</span>
              </div>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-users"></i> Per customer</span>
                <span class="ipb-sub-breakdown__amount">৳<?= number_format($perUserRate, 2); ?>/mo</span>
              </div>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-wallet"></i> Billing method</span>
                <span class="ipb-sub-breakdown__amount">Wallet auto-deduct</span>
              </div>
            <?php else: ?>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-calendar"></i> Duration</span>
                <span class="ipb-sub-breakdown__amount"><?= esc($duration); ?> days</span>
              </div>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-tag"></i> Discount</span>
                <span class="ipb-sub-breakdown__amount">৳<?= number_format($discount); ?></span>
              </div>
              <div class="ipb-sub-breakdown__row">
                <span class="ipb-sub-breakdown__label"><i class="fa fa-receipt"></i> Price</span>
                <span class="ipb-sub-breakdown__amount">৳<?= number_format($finalPrice); ?></span>
              </div>
            <?php endif; ?>
          </div>

          <p class="ipb-sub-plan__note">
            <?= $isPayg
                ? 'Charged monthly from your platform wallet based on your total customers.'
                : 'Pricing is subject to change. Contact support for custom plans.'; ?>
          </p>
        </article>

        <div class="ipb-sub-section">
          <div class="ipb-sub-section__head">
            <h3 class="ipb-sub-section__title"><i class="fa fa-calendar-days"></i> Billing dates</h3>
          </div>
          <div class="ipb-sub-section__body">
            <div class="ipb-sub-dates">
              <div class="ipb-sub-date">
                <span class="ipb-sub-date__icon"><i class="fa fa-rotate-right"></i></span>
                <div>
                  <label>Last renewed</label>
                  <strong><?= date('d M Y, h:i A', strtotime($details->last_renewed)); ?></strong>
                </div>
              </div>
              <div class="ipb-sub-date">
                <span class="ipb-sub-date__icon"><i class="fa fa-calendar-xmark"></i></span>
                <div>
                  <label>Expires on</label>
                  <strong><?= date('d M Y, h:i A', strtotime($details->will_expire)); ?></strong>
                </div>
              </div>
            </div>
            <p class="ipb-sub-callout">
              <i class="fa fa-bell"></i>
              We'll notify you before your subscription expires.
            </p>
          </div>
        </div>
      </div>

      <div class="ipb-sub-layout__side">
        <div class="ipb-sub-section">
          <div class="ipb-sub-section__head">
            <h3 class="ipb-sub-section__title"><i class="fa fa-signal"></i> Status</h3>
          </div>
          <div class="ipb-sub-section__body ipb-sub-status">
            <div class="ipb-sub-status__icon <?= esc($statusTone); ?>">
              <i class="fa <?= esc($statusIcon); ?>"></i>
            </div>
            <h3 class="ipb-sub-status__title"><?= esc($statusTitle); ?></h3>
            <p class="ipb-sub-status__sub"><?= esc($statusSub); ?></p>

            <?php if (!$isPayg): ?>
              <div class="ipb-sub-progress">
                <div class="ipb-sub-progress__label">
                  <span><?= (int) $remain; ?>% remaining</span>
                  <span>Billing period</span>
                </div>
                <div class="ipb-sub-progress__bar">
                  <div class="ipb-sub-progress__fill" style="width:<?= (int) $remain; ?>%"></div>
                </div>
              </div>
            <?php else: ?>
              <div class="ipb-sub-progress" style="margin-top:4px">
                <div class="ipb-sub-progress__label">
                  <span><?= (int) $daysLeft; ?> days left</span>
                  <span>Until next cycle</span>
                </div>
                <div class="ipb-sub-progress__bar">
                  <div class="ipb-sub-progress__fill" style="width:<?= min(100, max(8, (int) $remain)); ?>%"></div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ipb-sub-section">
          <div class="ipb-sub-section__head">
            <h3 class="ipb-sub-section__title"><i class="fa fa-bolt"></i> Quick actions</h3>
          </div>
          <div class="ipb-sub-section__body">
            <div class="ipb-sub-actions">
              <?php if ($isPayg): ?>
                <a href="<?= route_to('route.wallet'); ?>" class="ipb-sub-btn ipb-sub-btn--primary ipb-sub-btn--full">
                  <i class="fa fa-wallet"></i> Open wallet & billing
                </a>
              <?php elseif ($canRenew): ?>
                <button type="button" id="renew-btn" class="ipb-sub-btn ipb-sub-btn--primary">
                  <i class="fa fa-repeat"></i> Renew now
                </button>
                <a href="<?= route_to('route.payment'); ?>" class="ipb-sub-btn ipb-sub-btn--outline">
                  <i class="fa fa-credit-card"></i> Payments
                </a>
              <?php else: ?>
                <a href="<?= route_to('route.payment'); ?>" class="ipb-sub-btn ipb-sub-btn--primary ipb-sub-btn--full">
                  <i class="fa fa-credit-card"></i> View payments
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>

<?php if (!empty(session()->getFlashdata('pay-success'))): ?>
  <script>
    tata.success('Payment recorded', '<?= esc(session()->getFlashdata('pay-success'), 'js'); ?>', { duration: 3000 });
  </script>
<?php elseif (!empty(session()->getFlashdata('pay-error'))): ?>
  <script>
    tata.error('Payment failed', '<?= esc(session()->getFlashdata('pay-error'), 'js'); ?>', { duration: 3000 });
  </script>
<?php endif; ?>

<?php if ($canRenew && !$isPayg): ?>
  <script>
    $("#renew-btn").click(function (e) {
      e.preventDefault();
      const self = this;

      $.ajax({
        url: '<?= route_to('reseller.subscription.renew'); ?>',
        type: 'POST',
        data: { Customer: '<?= $details->id; ?>' },
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        beforeSend: function () {
          $(self).html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
        },
        success: function (result) {
          $(self).html('<i class="fa fa-repeat"></i> Renew now').removeAttr('disabled');
          swal({
            closeOnClickOutside: false,
            closeOnEsc: false,
            icon: 'success',
            title: 'Success',
            text: result.response.msg,
            buttons: ['Close', { text: 'Pay', closeModal: false }],
          }).then(function (willPay) {
            if (willPay) location.href = result.response.payment_url;
          });
        },
        error: function ({ responseText }) {
          const result = JSON.parse(responseText);
          $(self).html('<i class="fa fa-repeat"></i> Renew now').removeAttr('disabled');
          tata.error("Couldn't renew subscription", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>

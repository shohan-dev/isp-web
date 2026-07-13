<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/subscription-page.css?v=4'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<?php
$fund = (float) ($details->fund ?? 0);
$isActive = ($details->status ?? '') === 'active';
$hasExpiry = !empty($details->will_expire);
$expireTs = $hasExpiry ? strtotime($details->will_expire) : null;
$daysLeft = $hasExpiry ? max(round(($expireTs - time()) / 86400), 0) : null;
$isExpired = $hasExpiry && $daysLeft <= 0;
$isExpiringSoon = $hasExpiry && !$isExpired && $daysLeft <= 7;
$statusTone = !$isActive ? 'is-danger' : ($isExpired ? 'is-danger' : ($isExpiringSoon ? 'is-warn' : ''));
$statusIcon = !$isActive ? 'fa-circle-xmark' : ($isExpired ? 'fa-clock' : ($isExpiringSoon ? 'fa-triangle-exclamation' : 'fa-circle-check'));
$statusTitle = !$isActive ? 'Account inactive' : ($isExpired ? 'Subscription expired' : ($isExpiringSoon ? 'Expiring soon' : 'Account active'));
$statusSub = !$isActive
    ? 'Contact your admin to reactivate'
    : ($isExpired ? 'Recharge to restore access' : ($isExpiringSoon ? 'Recharge before expiry' : 'Your account is in good standing'));
$kpiTone = $isExpired || !$isActive ? 'is-danger' : ($isExpiringSoon ? 'is-warn' : '');
$canRecharge = userHasPermission('Resellers', 'self_recharge') || getSession('resellerAdmin') === 'self_recharge';
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

    <div class="ipb-sub-kpis">
      <div class="ipb-sub-kpi">
        <span class="ipb-sub-kpi__label">Available fund</span>
        <span class="ipb-sub-kpi__value ipb-sub-kpi__value--accent">৳<?= number_format($fund, 2); ?></span>
      </div>
      <div class="ipb-sub-kpi <?= esc($kpiTone); ?>">
        <span class="ipb-sub-kpi__label">Status</span>
        <span class="ipb-sub-kpi__value" style="font-size:1rem"><?= ucfirst(esc($details->status ?? '—')); ?></span>
      </div>
      <div class="ipb-sub-kpi <?= esc($kpiTone); ?>">
        <span class="ipb-sub-kpi__label"><?= $hasExpiry ? 'Days remaining' : 'Expiry'; ?></span>
        <span class="ipb-sub-kpi__value">
          <?php if ($hasExpiry): ?>
            <?= (int) $daysLeft; ?> <small>days</small>
          <?php else: ?>
            <small>Not set</small>
          <?php endif; ?>
        </span>
      </div>
      <div class="ipb-sub-kpi">
        <span class="ipb-sub-kpi__label">Account</span>
        <span class="ipb-sub-kpi__value" style="font-size:0.95rem">Reseller</span>
      </div>
    </div>

    <div class="ipb-sub-layout">
      <div class="ipb-sub-layout__main">

        <article class="ipb-sub-plan">
          <div class="ipb-sub-plan__glow" aria-hidden="true"></div>
          <div class="ipb-sub-plan__head">
            <div class="ipb-sub-plan__brand">
              <span class="ipb-sub-plan__icon" aria-hidden="true"><i class="fa fa-wallet"></i></span>
              <div>
                <p class="ipb-sub-plan__eyebrow">Reseller wallet</p>
                <h2 class="ipb-sub-plan__name"><?= esc($details->name ?? 'Reseller'); ?></h2>
              </div>
            </div>
            <?php if ($isActive && !$isExpired): ?>
              <span class="ipb-sub-badge is-active"><i class="fa fa-check"></i> Active</span>
            <?php else: ?>
              <span class="ipb-sub-badge is-inactive"><i class="fa fa-circle-xmark"></i> <?= $isActive ? 'Expired' : 'Inactive'; ?></span>
            <?php endif; ?>
          </div>

          <p class="ipb-sub-fund__balance">
            <span class="currency">৳</span><?= number_format($fund, 2); ?>
          </p>

          <div class="ipb-sub-breakdown">
            <div class="ipb-sub-breakdown__row">
              <span class="ipb-sub-breakdown__label"><i class="fa fa-user"></i> Name</span>
              <span class="ipb-sub-breakdown__amount"><?= esc($details->name ?? '—'); ?></span>
            </div>
            <div class="ipb-sub-breakdown__row">
              <span class="ipb-sub-breakdown__label"><i class="fa fa-phone"></i> Mobile</span>
              <span class="ipb-sub-breakdown__amount"><?= esc($details->mobile ?? '—'); ?></span>
            </div>
            <div class="ipb-sub-breakdown__row">
              <span class="ipb-sub-breakdown__label"><i class="fa fa-signal"></i> Status</span>
              <span class="ipb-sub-breakdown__amount"><?= ucfirst(esc($details->status ?? '—')); ?></span>
            </div>
            <div class="ipb-sub-breakdown__row">
              <span class="ipb-sub-breakdown__label"><i class="fa fa-calendar-xmark"></i> Expires on</span>
              <span class="ipb-sub-breakdown__amount">
                <?= $hasExpiry ? date('d M Y, h:i A', $expireTs) : '—'; ?>
              </span>
            </div>
          </div>

          <p class="ipb-sub-plan__note">
            Use your available fund for self-recharge. We'll notify you before your subscription expires.
          </p>
        </article>
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

            <?php if ($hasExpiry): ?>
              <?php
              $renew = !empty($details->last_renewed) ? strtotime($details->last_renewed) : ($expireTs - 30 * 86400);
              $total = max($expireTs - $renew, 1);
              $remain = min(100, max(0, round((($expireTs - time()) / $total) * 100)));
              ?>
              <div class="ipb-sub-progress">
                <div class="ipb-sub-progress__label">
                  <span><?= (int) max(0, $daysLeft); ?> days left</span>
                  <span>Subscription period</span>
                </div>
                <div class="ipb-sub-progress__bar">
                  <div class="ipb-sub-progress__fill" style="width:<?= (int) max(8, $remain); ?>%"></div>
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
              <?php if ($canRecharge): ?>
                <button type="button" id="renew-btn" class="ipb-sub-btn ipb-sub-btn--primary ipb-sub-btn--full">
                  <i class="fa fa-repeat"></i> Self recharge
                </button>
              <?php endif; ?>
            </div>
            <p class="ipb-sub-callout" style="margin-top:14px;margin-bottom:0">
              <i class="fa fa-circle-info"></i>
              Recharge deducts from your available fund or creates a payment invoice.
            </p>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>

<!-- Self recharge modal (outside content-wrapper — BS3 positioning fix) -->
<div class="modal fade ipb-sub-modal" id="selfRechargeModal" tabindex="-1" role="dialog" aria-labelledby="selfRechargeLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="selfRechargeLabel">Self recharge</h4>
      </div>
      <form id="selfRechargeForm">
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" name="customer_id" value="<?= $details->id; ?>">
          <div class="form-group">
            <label for="rechargeAmount">Amount</label>
            <div class="ipb-sub-amount-wrap">
              <span class="ipb-sub-amount-prefix">৳</span>
              <input type="number" min="0" step="0.01" class="form-control" id="rechargeAmount" name="amount"
                placeholder="0.00" required autofocus>
            </div>
            <span class="ipb-sub-amount-hint">Available fund: ৳<?= number_format($fund, 2); ?></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="ipb-sub-btn ipb-sub-btn--primary" style="min-height:40px;padding:8px 18px">
            <i class="fa fa-check"></i> Confirm
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

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

<?php if ($canRecharge): ?>
  <script>
    $(function () {
      var $modal = $('#selfRechargeModal');
      if ($modal.length && !$modal.parent().is('body')) {
        $modal.appendTo('body');
      }
    });

    $('#renew-btn').on('click', function (e) {
      e.preventDefault();
      $('#rechargeAmount').val('');
      $('#selfRechargeModal').modal('show');
    });

    $('#selfRechargeModal').on('shown.bs.modal', function () {
      $('#rechargeAmount').trigger('focus');
    });

    $('#selfRechargeForm').on('submit', function (e) {
      e.preventDefault();
      const self = $("#selfRechargeForm button[type='submit']");
      const amount = $("#rechargeAmount").val();

      $.ajax({
        url: '<?= route_to('reseller.subscription.renew'); ?>',
        type: 'POST',
        data: { Customer: '<?= $details->id; ?>', amount: amount },
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        beforeSend: function () {
          self.html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
        },
        success: function (result) {
          self.html('<i class="fa fa-check"></i> Confirm').removeAttr('disabled');
          $("#selfRechargeModal").modal("hide");
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
          self.html('<i class="fa fa-check"></i> Confirm').removeAttr('disabled');
          tata.error("Couldn't recharge", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>

<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/wallet.css?v=8'); ?>">
<?= $this->endSection('css'); ?>
<?= $this->section('content'); ?>

<?php
$balance = (float) ($wallet->balance ?? 0);
$total = (float) ($estimate['total'] ?? 0);
$minTopup = (float) ($estimate['min_topup'] ?? 0);
$monthsCovered = $total > 0 ? floor($balance / $total) : null;
$progressPct = ($total > 0 && $monthsCovered !== null)
    ? min(100, max(8, round(($balance / max($total, 1)) * 100 / 3)))
    : 0;
$graceUntil = $wallet->grace_until ?? null;
$isExpired = !empty($user->will_expire) && strtotime($user->will_expire) <= time();
$isSuspended = ($user->subscription_status ?? '') === 'inactive';
$canSwitchPayg = !$isPayg && !($minTopup > 0 && $balance < $minTopup && $isExpired);
$totalUsers = (int) ($estimate['total_users'] ?? 0);
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-billing-page" id="walletBillingPage">

    <?= $this->include('components/page-header', [
      'title' => 'My Wallet',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Wallet'],
      ],
    ]); ?>

    <?= $this->include('components/trial-banner', ['trialUser' => $trialUser ?? null]); ?>

    <?php if ($isPayg && !empty($user->trial_ends_at) && strtotime($user->trial_ends_at) > time()): ?>
      <div class="ipb-wallet-alert is-info" data-bs-toggle="tooltip"
        title="After trial, your wallet is charged monthly based on your total customers">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        <div>
          Free trial ends <strong><?= date('d M Y', strtotime($user->trial_ends_at)); ?></strong>.
          First wallet charge runs after trial unless you top up earlier.
          <?php if ($minTopup > 0): ?>
            Recommended minimum top-up: <strong>৳<?= number_format($minTopup); ?></strong>.
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isPayg && $isSuspended): ?>
      <div class="ipb-wallet-alert is-danger">
        <i class="fa fa-triangle-exclamation" aria-hidden="true"></i>
        <div>
          <strong>Account suspended</strong> — your wallet could not cover the monthly charge.
          Top up now and service reactivates automatically.
        </div>
      </div>
    <?php elseif ($isPayg && !empty($graceUntil) && strtotime($graceUntil) > time()): ?>
      <div class="ipb-wallet-alert is-warning">
        <i class="fa fa-clock" aria-hidden="true"></i>
        <div>
          Balance too low for this month's charge. Top up before
          <strong><?= date('d M Y, h:i A', strtotime($graceUntil)); ?></strong> to avoid suspension.
        </div>
      </div>
    <?php endif; ?>

  <?php if ($isPayg): ?>
    <div class="ipb-wallet-kpis">
      <div class="ipb-wallet-kpi">
        <span class="ipb-wallet-kpi__label">Available balance</span>
        <span class="ipb-wallet-kpi__value">৳<?= number_format($balance, 2); ?></span>
      </div>
      <div class="ipb-wallet-kpi">
        <span class="ipb-wallet-kpi__label">Est. monthly</span>
        <span class="ipb-wallet-kpi__value">৳<?= number_format($total, 2); ?></span>
      </div>
      <?php if ($monthsCovered !== null): ?>
        <div class="ipb-wallet-kpi">
          <span class="ipb-wallet-kpi__label">Runway</span>
          <span class="ipb-wallet-kpi__value"><?= (int) $monthsCovered; ?> <small>months</small></span>
        </div>
      <?php endif; ?>
      <?php if (!empty($user->will_expire)): ?>
        <div class="ipb-wallet-kpi">
          <span class="ipb-wallet-kpi__label">Next charge</span>
          <span class="ipb-wallet-kpi__value ipb-wallet-kpi__value--sm"><?= date('d M Y', strtotime($user->will_expire)); ?></span>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

    <div class="ipb-wallet-layout">
      <div class="ipb-wallet-layout__side">

        <article class="ipb-wallet-hero" aria-label="Wallet balance">
          <div class="ipb-wallet-hero__glow" aria-hidden="true"></div>
          <div class="ipb-wallet-hero__head">
            <div class="ipb-wallet-hero__brand">
              <span class="ipb-wallet-hero__icon" aria-hidden="true"><i class="fa fa-wallet"></i></span>
              <div>
                <p class="ipb-wallet-hero__eyebrow">Platform wallet</p>
                <?php if ($isPayg): ?>
                  <span class="ipb-wallet-badge is-payg"><i class="fa fa-bolt"></i> Pay-as-you-go</span>
                <?php else: ?>
                  <span class="ipb-wallet-badge is-fixed"><i class="fa fa-calendar"></i> Fixed plan</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <p class="ipb-wallet-hero__label">Available balance</p>
          <p class="ipb-wallet-hero__balance">
            <span class="currency">৳</span><?= number_format($balance, 2); ?>
          </p>

          <?php if ($isPayg && $total > 0): ?>
            <div class="ipb-wallet-hero__coverage">
              <div class="ipb-wallet-progress__label">
                <span>Balance coverage</span>
                <span><?= (int) $monthsCovered; ?> mo at current usage</span>
              </div>
              <div class="ipb-wallet-progress__bar">
                <div class="ipb-wallet-progress__fill" style="width: <?= (int) $progressPct; ?>%"></div>
              </div>
            </div>
          <?php elseif (!$isPayg): ?>
            <p class="ipb-wallet-hero__hint">Funds apply when you switch to Pay-As-You-Go billing.</p>
          <?php endif; ?>
        </article>

        <div class="ipb-wallet-section ipb-wallet-section--topup">
          <div class="ipb-wallet-section__head">
            <h3 class="ipb-wallet-section__title"><i class="fa fa-plus-circle"></i> Top up balance</h3>
          </div>
          <div class="ipb-wallet-section__body">
            <p class="ipb-wallet-section__hint">
              Pay via bKash, Nagad, or card. Funds are credited instantly after payment.
            </p>
            <form id="topupForm">
              <?= csrf_field() ?>
              <div class="ipb-wallet-amount-field">
                <label for="topup_amount">Amount</label>
                <div class="ipb-wallet-amount-field__wrap">
                  <span class="ipb-wallet-amount-field__prefix">৳</span>
                  <input type="number" min="100" step="1" class="form-control" id="topup_amount" name="amount"
                    placeholder="<?= $minTopup > 0 ? number_format($minTopup) : '100'; ?>"
                    required data-bs-toggle="tooltip" title="Used automatically for PAYG monthly charges">
                </div>
                <span class="ipb-wallet-amount-field__hint">Minimum ৳100<?= $minTopup > 0 ? ' · suggested ৳' . number_format($minTopup) : ''; ?></span>
              </div>
              <div class="ipb-wallet-topup-suggests">
                <span class="ipb-wallet-topup-suggests__label">Quick select</span>
                <div class="ipb-wallet-topup-suggests__btns">
                  <?php foreach ([$minTopup ?: 750, $total > 0 ? ceil($total) : 1500, $total > 0 ? ceil($total * 2) : 3000] as $suggest): ?>
                    <?php if ($suggest >= 100): ?>
                      <button type="button" class="ipb-wallet-topup-suggest topup-suggest" data-amount="<?= (int) $suggest; ?>">
                        ৳<?= number_format((int) $suggest); ?>
                      </button>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
              <button type="submit" class="btn btn-primary ipb-wallet-topup-btn">
                <i class="fa fa-credit-card"></i> Continue to payment
              </button>
            </form>
          </div>
        </div>

      </div>

      <div class="ipb-wallet-layout__main">
        <div class="ipb-wallet-section">
          <div class="ipb-wallet-section__head">
            <h3 class="ipb-wallet-section__title"><i class="fa fa-receipt"></i> Monthly charge estimate</h3>
            <?php if ($isPayg): ?>
              <span class="ipb-wallet-section__meta"><?= $totalUsers; ?> customers</span>
            <?php endif; ?>
          </div>
          <div class="ipb-wallet-section__body">
            <div class="ipb-wallet-breakdown">
              <div class="ipb-wallet-breakdown__row">
                <span class="ipb-wallet-breakdown__label">
                  <i class="fa fa-server"></i> Platform fee
                </span>
                <span class="ipb-wallet-breakdown__amount">৳<?= number_format((float) $estimate['base_fee'], 2); ?><small>/mo</small></span>
              </div>
              <div class="ipb-wallet-breakdown__row">
                <span class="ipb-wallet-breakdown__label">
                  <i class="fa fa-users"></i> Customers × rate
                </span>
                <span class="ipb-wallet-breakdown__amount">
                  <?= $totalUsers; ?> × ৳<?= number_format((float) $estimate['per_user_rate'], 2); ?>
                  = ৳<?= number_format((float) $estimate['usage_cost'], 2); ?>
                </span>
              </div>
              <?php foreach ($estimate['addons'] as $addon): ?>
                <div class="ipb-wallet-breakdown__row">
                  <span class="ipb-wallet-breakdown__label">
                    <i class="fa fa-puzzle-piece"></i> <?= esc($addon['label']); ?>
                  </span>
                  <span class="ipb-wallet-breakdown__amount">৳<?= number_format((float) $addon['price'], 2); ?><small>/mo</small></span>
                </div>
              <?php endforeach; ?>
              <div class="ipb-wallet-breakdown__row ipb-wallet-breakdown__total">
                <span>Estimated monthly deduction</span>
                <span class="ipb-wallet-estimate-total">৳<?= number_format($total, 2); ?></span>
              </div>
            </div>
            <?php if (!$isPayg): ?>
              <p class="ipb-wallet-section__footnote">
                <i class="fa fa-circle-info"></i>
                Preview of monthly cost on Pay-As-You-Go at your current usage.
              </p>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($addonCatalog)): ?>
          <div class="ipb-wallet-section">
            <div class="ipb-wallet-section__head">
              <h3 class="ipb-wallet-section__title"><i class="fa fa-sliders"></i> Optional add-ons</h3>
            </div>
            <div class="ipb-wallet-section__body">
              <p class="ipb-wallet-section__hint">Toggle services to include in your monthly wallet charge.</p>
              <form id="addonForm">
                <?= csrf_field() ?>
                <div class="ipb-wallet-addons">
                  <?php foreach ($addonCatalog as $addon): ?>
                    <?php $isChecked = in_array($addon['key'], $chosenAddons, true); ?>
                    <label class="ipb-wallet-addon<?= $isChecked ? ' is-selected' : ''; ?>">
                      <input type="checkbox" name="addons[]" value="<?= esc($addon['key'], 'attr'); ?>"
                        <?= $isChecked ? 'checked' : ''; ?>>
                      <span class="ipb-wallet-addon__face">
                        <span class="ipb-wallet-addon__check" aria-hidden="true"><i class="fa fa-check"></i></span>
                        <span class="ipb-wallet-addon__name"><?= esc($addon['label']); ?></span>
                        <span class="ipb-wallet-addon__price">+৳<?= number_format((float) $addon['price']); ?>/month</span>
                      </span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-default ipb-wallet-save-addons">
                  <i class="fa fa-check"></i> Save add-ons
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$isPayg): ?>
      <div class="ipb-wallet-action-card ipb-wallet-action-card--wide">
        <div class="ipb-wallet-action-card__icon" aria-hidden="true"><i class="fa fa-shuffle"></i></div>
        <div class="ipb-wallet-action-card__text">
          <h4 class="ipb-wallet-action-card__title">Switch to Pay-As-You-Go</h4>
          <p>
            No fixed tier — pay only for your total customers each month.
            Remaining subscription days carry over until wallet billing starts.
          </p>
          <?php if (!$canSwitchPayg): ?>
            <p class="ipb-wallet-action-card__note">
              <i class="fa fa-circle-info"></i>
              Add at least <strong>৳<?= number_format($minTopup); ?></strong> before switching (subscription already expired).
            </p>
          <?php endif; ?>
        </div>
        <button type="button" id="switchPaygBtn" class="btn btn-warning ipb-wallet-action-card__cta" <?= $canSwitchPayg ? '' : 'disabled'; ?>>
          <i class="fa fa-bolt"></i> Switch to Pay-As-You-Go
        </button>
      </div>
    <?php endif; ?>

    <div class="ipb-wallet-section ipb-wallet-ledger">
      <div class="ipb-wallet-section__head">
        <h3 class="ipb-wallet-section__title"><i class="fa fa-clock-rotate-left"></i> Transaction history</h3>
      </div>
      <div class="ipb-wallet-section__body ipb-wallet-section__body--flush">
        <div class="table-responsive">
          <table class="table table-hover datatable ipb-wallet-table" width="100%">
            <caption class="sr-only">Transaction history</caption>
            <thead class="text-nowrap">
              <tr>
                <th data-data="serial" scope="col">#</th>
                <th data-data="created_at" scope="col">Date</th>
                <th data-data="type" scope="col">Type</th>
                <th data-data="amount" scope="col">Amount</th>
                <th data-data="balance_after" scope="col">Balance</th>
                <th data-data="description" scope="col">Description</th>
              </tr>
            </thead>
            <?php
              // Zero-blank-frame first paint: skeleton rows show before JS/DataTables
              // boots; DataTables replaces this <tbody> on its first draw.
              $walletSkeletonCols = 6;
            ?>
            <?= view('components/skeleton-table', ['cols' => $walletSkeletonCols, 'rows' => 8]) ?>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?php if (!empty(session()->getFlashdata('pay-success'))) : ?>
  <script>
    tata.success('Payment recorded', "<?= esc(session()->getFlashdata('pay-success'), 'js'); ?>", {
      duration: 4000,
    });
  </script>
<?php endif; ?>

<script>
  $(document).ready(function () {
    document.querySelectorAll('.ipb-wallet-addon input').forEach(function (input) {
      input.addEventListener('change', function () {
        this.closest('.ipb-wallet-addon').classList.toggle('is-selected', this.checked);
      });
    });

    $('.datatable').DataTable({
      ajax: {
        url: '<?= route_to('route.wallet.transactions'); ?>',
        type: 'post',
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        }
      },
      columnDefs: [{ "targets": "_all", "defaultContent": "-" }],
      order: []
    });

    $('.topup-suggest').on('click', function () {
      $('#topup_amount').val($(this).data('amount'));
      $('.ipb-wallet-topup-suggest').removeClass('is-active');
      $(this).addClass('is-active');
    });

    $('#topupForm').on('submit', function (e) {
      e.preventDefault();
      var $btn = $(this).find('button[type="submit"]');
      $.ajax({
        url: '<?= route_to('route.wallet.topup'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating invoice…');
        },
        success: function (result) {
          $btn.prop('disabled', false).html('<i class="fa fa-credit-card"></i> Continue to payment');
          if (result && result.status === 'success' && result.response && result.response.payment_url) {
            location.href = result.response.payment_url;
          } else {
            tata.error("Couldn't create invoice", (result && result.response) || 'Could not create the top-up invoice.');
          }
        },
        error: function (xhr) {
          $btn.prop('disabled', false).html('<i class="fa fa-credit-card"></i> Continue to payment');
          var r = xhr.responseJSON;
          tata.error("Couldn't create invoice", (r && r.response) || 'Could not create the top-up invoice.');
        }
      });
    });

    $('#addonForm').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        url: '<?= route_to('route.wallet.addons'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
        success: function (result) {
          if (result && result.status === 'success') {
            tata.success('Saved', result.response.msg || 'Add-ons updated.');
            setTimeout(function () { location.reload(); }, 1200);
          } else {
            tata.error("Couldn't save add-ons", (result && result.response) || 'Could not save add-ons.');
          }
        },
        error: function () { tata.error("Couldn't save add-ons", 'Could not save add-ons.'); }
      });
    });

    $('#switchPaygBtn').on('click', function () {
      if (!confirm('Switch to Pay-As-You-Go billing? Your remaining subscription days carry over.')) return;
      var $btn = $(this);
      $.ajax({
        url: '<?= route_to('route.wallet.switch_payg'); ?>',
        type: 'POST',
        data: { <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
        dataType: 'json',
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Switching…');
        },
        success: function (result) {
          $btn.prop('disabled', false).html('<i class="fa fa-bolt"></i> Switch to Pay-As-You-Go');
          if (result && result.status === 'success') {
            var okMsg = result.response;
            if (okMsg && typeof okMsg === 'object') {
              okMsg = okMsg.msg || 'Switched to Pay-As-You-Go.';
            }
            tata.success('Done', okMsg || 'Switched to Pay-As-You-Go.');
            setTimeout(function () { location.reload(); }, 1500);
          } else {
            var errMsg = (result && result.response) || 'Could not switch.';
            if (typeof errMsg !== 'string') {
              errMsg = (errMsg && errMsg.msg) || 'Could not switch.';
            }
            tata.error("Couldn't switch billing", errMsg);
          }
        },
        error: function (xhr) {
          $btn.prop('disabled', false).html('<i class="fa fa-bolt"></i> Switch to Pay-As-You-Go');
          var r = xhr.responseJSON;
          var errMsg = (r && r.response) || 'Could not switch.';
          if (typeof errMsg !== 'string') {
            errMsg = (errMsg && errMsg.msg) || 'Could not switch.';
          }
          tata.error("Couldn't switch billing", errMsg);
        }
      });
    });
  });
</script>
<?= $this->endSection('script'); ?>

<?php
/** @var object $details */
/** @var array $packages */

$prePackageName = '--';
$currentPackageName = '--';

if (!empty($packages)) {
    foreach ($packages as $package) {
        $packageId = is_object($package) ? $package->id : $package['id'];
        $packageName = is_object($package) ? $package->package_name : $package['package_name'];

        if (!empty($details->pre_package) && (string) $packageId === (string) $details->pre_package) {
            $prePackageName = $packageName;
        }
        if ((string) $packageId === (string) ($details->package_id ?? '')) {
            $currentPackageName = $packageName;
        }
    }
}

$subActive = !empty($details->will_expire) && strtotime($details->will_expire) > time();
$hasWallet = isset($wallet);
$walletBalance = $hasWallet ? (float) ($wallet->balance ?? 0) : 0;
$estTotal = $hasWallet && !empty($walletEstimate) ? (float) $walletEstimate['total'] : 0;
$estUsers = $hasWallet && !empty($walletEstimate) ? (int) $walletEstimate['total_users'] : 0;
?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/wallet.css?v=7'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-billing-page">

    <?= $this->include('components/page-header', [
      'title' => 'Tenant Subscription',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Admins', 'url' => route_to('route.Admin')],
        ['label' => esc($details->name ?? 'Subscription')],
      ],
    ]); ?>

    <div class="ipb-admin-sub__hero">
      <div class="ipb-admin-sub__hero-main">
        <h2><?= esc($details->name ?? 'Tenant'); ?></h2>
        <p>
          <?= esc($currentPackageName); ?>
          <?php if (!empty($details->will_expire)): ?>
            · <?= $subActive ? 'Expires' : 'Expired'; ?> <?= date('d M Y, h:i A', strtotime($details->will_expire)); ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="ipb-admin-sub__hero-badges">
        <?php if ($hasWallet && !empty($isPaygTenant)): ?>
          <span class="ipb-wallet-badge is-payg"><i class="fa fa-bolt"></i> Pay-As-You-Go</span>
        <?php elseif ($hasWallet): ?>
          <span class="ipb-wallet-badge is-fixed"><i class="fa fa-calendar"></i> Fixed plan</span>
        <?php endif; ?>
        <?php if ($subActive): ?>
          <span class="ipb-wallet-badge is-active"><i class="fa fa-check"></i> Active</span>
        <?php else: ?>
          <span class="ipb-wallet-badge is-expired"><i class="fa fa-clock"></i> Expired</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="ipb-wallet-section">
      <div class="ipb-wallet-section__head">
        <h3 class="ipb-wallet-section__title"><i class="fa fa-bolt"></i> Subscription details</h3>
      </div>
      <div class="ipb-wallet-section__body">
        <?= form_open('', 'id="form"'); ?>

        <div class="ipb-admin-sub__form-grid">
          <div class="ipb-admin-sub__readonly">
            <span class="ipb-admin-sub__readonly-label">Admin name</span>
            <span class="ipb-admin-sub__readonly-value"><?= esc($details->name ?? '--'); ?></span>
          </div>

          <div class="ipb-admin-sub__readonly">
            <span class="ipb-admin-sub__readonly-label">Previous package</span>
            <span class="ipb-admin-sub__readonly-value"><?= esc($prePackageName); ?></span>
          </div>

          <div class="form-group">
            <label for="package_id">Package</label>
            <?php
            $pkgOptions = empty($packages) ? ['' => 'No package found!'] : ['' => '-- Select package --'];
            if (!empty($packages)) {
                foreach ($packages as $package) {
                    $pid = is_object($package) ? $package->id : $package['id'];
                    $pname = is_object($package) ? $package->package_name : $package['package_name'];
                    $ptype = is_object($package) ? ($package->plan_type ?? 'fixed') : ($package['plan_type'] ?? 'fixed');
                    $suffix = $ptype === 'payg' ? ' (PAYG)' : ($ptype === 'custom' ? ' (Custom)' : '');
                    $pkgOptions[$pid] = $pname . $suffix;
                }
            }
            echo form_dropdown('package_id', $pkgOptions, $details->package_id, 'class="form-control" id="package_id"');
            ?>
            <small id="package_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label for="last_renewed">Renewal date</label>
            <?= form_input([
              'type' => 'datetime-local',
              'name' => 'last_renewed',
              'id' => 'last_renewed',
              'class' => 'form-control',
              'value' => !empty($details->last_renewed) ? date('Y-m-d\TH:i', strtotime($details->last_renewed)) : '',
            ]); ?>
            <small id="last_renewed-error" class="error text-danger"></small>
          </div>

          <div class="form-group" style="grid-column: 1 / -1">
            <label for="will_expire">Expire date</label>
            <?= form_input([
              'type' => 'datetime-local',
              'name' => 'will_expire',
              'id' => 'will_expire',
              'class' => 'form-control',
              'value' => !empty($details->will_expire) ? date('Y-m-d\TH:i', strtotime($details->will_expire)) : '',
            ]); ?>
            <small id="will_expire-error" class="error text-danger"></small>
          </div>
        </div>

        <div class="ipb-admin-sub__footer">
          <a href="<?= route_to('route.Admin'); ?>" class="btn btn-default">Cancel</a>
          <?= form_button([
            'content' => '<i class="fa fa-check"></i> Update subscription',
            'class' => 'btn btn-warning',
            'type' => 'submit',
          ]); ?>
        </div>

        <?= form_close(); ?>
      </div>
    </div>

    <?php if ($hasWallet): ?>

      <?php if (!empty($registration) && !empty($registration->requested_plan ?? $registration['requested_plan'] ?? null)): ?>
        <?php
        $reqPlan = is_object($registration) ? $registration->requested_plan : $registration['requested_plan'];
        $reqNote = is_object($registration) ? ($registration->plan_note ?? '') : ($registration['plan_note'] ?? '');
        ?>
        <div class="ipb-callout">
          <h4 class="ipb-callout__title"><i class="fa fa-clipboard-list"></i> Requested plan at registration</h4>
          <span class="ipb-wallet-badge is-fixed"><?= esc($reqPlan); ?></span>
          <?php if (!empty($reqNote)): ?>
            <div class="ipb-callout__note"><?= esc($reqNote); ?></div>
          <?php endif; ?>
          <p class="ipb-callout__hint">
            <i class="fa fa-circle-info"></i>
            Create a custom plan on the
            <a href="<?= route_to('Admin.packages'); ?>">packages page</a>
            (type Custom, assigned to this tenant), then select it above and set dates to activate.
          </p>
        </div>
      <?php endif; ?>

      <div class="ipb-wallet-layout">
        <div class="ipb-wallet-layout__side">
          <article class="ipb-wallet-hero" aria-label="Tenant wallet balance">
            <div class="ipb-wallet-hero__glow" aria-hidden="true"></div>
            <div class="ipb-wallet-hero__head">
              <div class="ipb-wallet-hero__brand">
                <span class="ipb-wallet-hero__icon" aria-hidden="true"><i class="fa fa-wallet"></i></span>
                <div>
                  <p class="ipb-wallet-hero__eyebrow">Tenant wallet</p>
                  <?php if (!empty($isPaygTenant)): ?>
                    <span class="ipb-wallet-badge is-payg"><i class="fa fa-bolt"></i> Pay-as-you-go</span>
                  <?php else: ?>
                    <span class="ipb-wallet-badge is-fixed"><i class="fa fa-calendar"></i> Fixed plan</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <p class="ipb-wallet-hero__label">Available balance</p>
            <p class="ipb-wallet-hero__balance">
              <span class="currency">৳</span><?= number_format($walletBalance, 2); ?>
            </p>
            <?php if (!empty($walletEstimate)): ?>
              <p class="ipb-admin-sub__wallet-stat">
                Est. monthly: <strong>৳<?= number_format($estTotal, 2); ?></strong>
                · <?= $estUsers; ?> customers
                <?php if (!empty($wallet->grace_until) && strtotime($wallet->grace_until) > time()): ?>
                  <br><span class="text-danger"><i class="fa fa-clock"></i> Grace until <?= date('d M Y h:i A', strtotime($wallet->grace_until)); ?></span>
                <?php endif; ?>
              </p>
            <?php endif; ?>
          </article>

          <div class="ipb-wallet-section">
            <div class="ipb-wallet-section__head">
              <h3 class="ipb-wallet-section__title"><i class="fa fa-sliders"></i> Billing controls</h3>
            </div>
            <div class="ipb-wallet-section__body">

              <div class="ipb-admin-sub__mode">
                <span class="ipb-admin-sub__mode-label">Billing mode</span>
                <div class="ipb-admin-sub__mode-actions">
                  <?php if (empty($isPaygTenant)): ?>
                    <button type="button" class="btn btn-warning" id="switchToPaygBtn">
                      <i class="fa fa-wallet"></i> Switch to Pay-As-You-Go
                    </button>
                  <?php else: ?>
                    <select class="form-control" id="fixedPlanSelect" aria-label="Fixed plan">
                      <option value="">— Pick fixed or custom plan —</option>
                      <?php foreach ($packages as $package): ?>
                        <?php
                        $pid = is_object($package) ? $package->id : $package['id'];
                        $pname = is_object($package) ? $package->package_name : $package['package_name'];
                        $ptype = is_object($package) ? ($package->plan_type ?? 'fixed') : ($package['plan_type'] ?? 'fixed');
                        if ($ptype === 'payg') {
                            continue;
                        }
                        ?>
                        <option value="<?= (int) $pid; ?>"><?= esc($pname); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-default" id="switchToFixedBtn">
                      <i class="fa fa-calendar"></i> Switch to fixed plan
                    </button>
                  <?php endif; ?>
                </div>
              </div>

              <div class="ipb-admin-sub__adjust">
                <h4 class="ipb-admin-sub__adjust-title"><i class="fa fa-sliders"></i> Manual adjustment</h4>
                <form id="walletAdjustForm">
                  <div class="form-group">
                    <label for="wallet_amount">Amount (৳ — negative to deduct)</label>
                    <input type="number" step="0.01" class="form-control" id="wallet_amount" name="amount" placeholder="e.g. 500 or -200" required>
                  </div>
                  <div class="form-group">
                    <label for="wallet_description">Reason (ledger note)</label>
                    <input type="text" class="form-control" id="wallet_description" name="description" maxlength="255" placeholder="Why is this adjustment being made?" required>
                  </div>
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Apply adjustment
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="ipb-wallet-layout__main">
          <div class="ipb-wallet-section ipb-wallet-ledger">
            <div class="ipb-wallet-section__head">
              <h3 class="ipb-wallet-section__title"><i class="fa fa-clock-rotate-left"></i> Recent transactions</h3>
            </div>
            <div class="ipb-wallet-section__body ipb-wallet-section__body--flush">
              <?php if (!empty($walletLedger)): ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <caption class="sr-only">Recent transactions</caption>
                    <thead>
                      <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Type</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Balance</th>
                        <th scope="col">Description</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($walletLedger as $txn): ?>
                        <?php
                        $amt = (float) $txn->amount;
                        $typeClass = 'txn-type--adjustment';
                        if ($txn->type === 'credit') {
                            $typeClass = 'txn-type--credit';
                        } elseif ($txn->type === 'debit') {
                            $typeClass = 'txn-type--debit';
                        }
                        ?>
                        <tr>
                          <td class="text-nowrap"><?= date('d M Y, h:i A', strtotime($txn->created_at)); ?></td>
                          <td><span class="txn-type <?= esc($typeClass); ?>"><?= esc($txn->type); ?></span></td>
                          <td class="<?= $amt >= 0 ? 'txn-credit' : 'txn-debit'; ?>">
                            <?= ($amt >= 0 ? '+' : '−') . '৳' . number_format(abs($amt), 2); ?>
                          </td>
                          <td>৳<?= number_format((float) $txn->balance_after, 2); ?></td>
                          <td><?= esc($txn->description ?? '—'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="ipb-wallet-ledger-empty">
                  <i class="fa fa-receipt"></i>
                  No wallet activity yet for this tenant.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $("#form").submit(function (e) {
    const form = this;

    $.ajax({
      url: '<?= route_to('route.Admin.update_subscription', $details->id); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,
      dataType: 'json',

      beforeSend: function (req) {
        req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');

        $(form).find('.error').html("");
        $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Saving…").attr('disabled', true);
      },

      success: function (result) {
        $(form).find('button[type="submit"]').html('<i class="fa fa-check"></i> Update subscription').removeAttr('disabled');

        if (!result || result.status !== 'success') {
          tata.error("Couldn't update subscription", (result && result.response) || 'Could not update subscription.');
          return;
        }

        tata.success('Subscription updated', result.response, {
          onClose: () => { location.href = '<?= route_to("route.Admin"); ?>'; },
        });
      },

      error: function ({ responseText }) {
        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('<i class="fa fa-check"></i> Update subscription').removeAttr('disabled');

        if (result.status === 'validation-error') {
          $.each(result.response, function (prefix, val) {
            $(form).find('#' + prefix + '-error').text(val);
          });
        } else {
          tata.error("Couldn't update subscription", result.response);
        }
      }
    });

    e.preventDefault();
  });

  <?php if (isset($wallet)): ?>
  var walletCsrfHeader = '<?= csrf_header() ?>';
  var walletCsrfToken = $('meta[name="csrf-token"]').attr('content');
  var walletCsrfField = '<?= csrf_token() ?>';

  function walletPost(url, data, confirmMsg) {
    if (confirmMsg && !confirm(confirmMsg)) return;
    data = data || {};
    data[walletCsrfField] = walletCsrfToken;
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      dataType: 'json',
      beforeSend: function (req) {
        if (walletCsrfHeader && walletCsrfToken) {
          req.setRequestHeader(walletCsrfHeader, walletCsrfToken);
        }
      },
      success: function (result) {
        if (result && result.status === 'success') {
          var msg = typeof result.response === 'string' ? result.response : (result.response && result.response.msg) || 'Saved.';
          tata.success('Saved', msg, { onClose: function () { location.reload(); } });
          setTimeout(function () { location.reload(); }, 1500);
        } else {
          var err = (result && result.response) || 'Request failed.';
          if (typeof err !== 'string') {
            err = (err && err.msg) || 'Request failed.';
          }
          tata.error("Couldn't save", err);
        }
      },
      error: function (xhr) {
        var r = xhr.responseJSON;
        tata.error("Couldn't save", (r && r.response) || 'Request failed.');
      }
    });
  }

  $('#walletAdjustForm').on('submit', function (e) {
    e.preventDefault();
    walletPost(
      '<?= route_to('route.Admin.wallet_adjust', $details->id); ?>',
      { amount: $('#wallet_amount').val(), description: $('#wallet_description').val() },
      'Apply this wallet adjustment?'
    );
  });

  $('#switchToPaygBtn').on('click', function () {
    walletPost(
      '<?= route_to('route.Admin.billing_mode', $details->id); ?>',
      { mode: 'payg' },
      'Switch this tenant to Pay-As-You-Go billing? Remaining paid days carry over.'
    );
  });

  $('#switchToFixedBtn').on('click', function () {
    var pkg = $('#fixedPlanSelect').val();
    if (!pkg) { tata.error('Select a plan', 'Pick a fixed or custom plan first.'); return; }
    walletPost(
      '<?= route_to('route.Admin.billing_mode', $details->id); ?>',
      { mode: 'fixed', package_id: pkg },
      'Switch this tenant to fixed-plan billing?'
    );
  });
  <?php endif; ?>
</script>

<?= $this->endSection('script'); ?>

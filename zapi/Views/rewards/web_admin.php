<?php
/**
 * @var string $title
 * @var array<string, int> $counts
 * @var array<int, array<string, mixed>> $referrals
 * @var int $issued
 * @var int $redeemed
 * @var float $reward_cost
 * @var float $conversion
 * @var array<int, array<string, mixed>> $wallets
 * @var array<int, array<string, mixed>> $transactions
 * @var array<string, mixed> $config
 * @var array<string, mixed> $globalConfig
 * @var bool $isSuperAdmin
 * @var int $ownerScope
 * @var string $customerNewUrl
 */
$title          = $title ?? 'Referral & Reward';
$_counts        = is_array($counts ?? null) ? $counts : [];
$counts         = array_merge([
    'total'    => 0,
    'pending'  => 0,
    'verified' => 0,
    'rejected' => 0,
    'flagged'  => 0,
], $_counts);
$referrals      = is_array($referrals ?? null) ? $referrals : [];
$issued         = (int) ($issued ?? 0);
$redeemed       = (int) ($redeemed ?? 0);
$reward_cost    = (float) ($reward_cost ?? 0);
$conversion     = (float) ($conversion ?? 0);
$wallets        = is_array($wallets ?? null) ? $wallets : [];
$transactions   = is_array($transactions ?? null) ? $transactions : [];
$config         = is_array($config ?? null) ? $config : [];
$globalConfig   = is_array($globalConfig ?? null) ? $globalConfig : [];
$isSuperAdmin   = (bool) ($isSuperAdmin ?? false);
$ownerScope     = (int) ($ownerScope ?? 0);
$customerNewUrl = $customerNewUrl ?? route_to('route.customer.new');
unset($_counts);

$configLabels = [
    'referral_points'        => 'Referral reward (points)',
    'early_renewal_points'   => 'Early renewal (points)',
    'streak_points'          => 'On-time streak (points)',
    'loyalty_6m_points'      => '6-month loyalty (points)',
    'loyalty_12m_points'     => '12-month loyalty (points)',
    'upgrade_points'         => 'Package upgrade (points)',
    'online_payment_points'  => 'Online payment (points)',
    'autopay_points'         => 'Auto-pay (points)',
    'feedback_points'        => 'Feedback (points)',
    'ticket_rating_points'   => 'Support rating (points)',
    'birthday_points'        => 'Birthday gift (points)',
    'point_value_bdt'        => 'Point value (BDT)',
    'point_expiry_days'      => 'Point expiry (days, 0 = never)',
    'max_redeem_percent'     => 'Max redeem (% of bill)',
    'referral_enabled'       => 'Referral program (1 = on)',
    'redemption_enabled'     => 'Redemption (1 = on)',
    'feedback_monthly_cap'   => 'Feedback cap per month',
];
$statusLabels = [
    'pending'  => 'Pending',
    'verified' => 'Verified',
    'rejected' => 'Rejected',
    'flagged'  => 'Under review',
];
$statusBadgeClass = [
    'pending'  => 'is-warning',
    'verified' => 'is-success',
    'rejected' => 'is-danger',
    'flagged'  => 'is-info',
];
?>
<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/rewards-pages.css?v=1'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-rwd-page">

    <?= $this->include('components/page-header', [
      'title' => 'Reward Center',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Reward Center'],
      ],
    ]); ?>

    <?php if (session()->getFlashdata('rwd_success')): ?>
      <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="icon fa fa-check"></i> <?= esc(session()->getFlashdata('rwd_success')); ?>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('rwd_error')): ?>
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="icon fa fa-ban"></i> <?= esc(session()->getFlashdata('rwd_error')); ?>
      </div>
    <?php endif; ?>

    <div class="box box-warning">
      <div class="box-header with-border" style="padding:0;border-bottom:0;">
        <ul class="nav nav-tabs ipb-rwd-tabs" role="tablist">
          <li class="active" role="presentation">
            <a href="#tabReferral" data-toggle="tab" role="tab"><i class="fa fa-user-plus" aria-hidden="true"></i> Referrals</a>
          </li>
          <li role="presentation">
            <a href="#tabReward" data-toggle="tab" role="tab"><i class="fa fa-gift" aria-hidden="true"></i> Rewards</a>
          </li>
        </ul>
      </div>

      <div class="box-body">
        <div class="tab-content">

          <div id="tabReferral" class="tab-pane active" role="tabpanel">
            <div class="ipb-rwd-stats">
              <div class="ipb-rwd-stat is-info">
                <span>Total referrals</span>
                <strong><?= (int) $counts['total'] ?></strong>
              </div>
              <div class="ipb-rwd-stat is-warn">
                <span>Pending review</span>
                <strong><?= (int) ($counts['pending'] + $counts['flagged']) ?></strong>
              </div>
              <div class="ipb-rwd-stat is-success">
                <span>Verified</span>
                <strong><?= (int) $counts['verified'] ?></strong>
              </div>
              <div class="ipb-rwd-stat is-danger">
                <span>Rejected</span>
                <strong><?= (int) $counts['rejected'] ?></strong>
              </div>
            </div>

            <div class="box box-primary">
              <div class="ipb-rwd-panel-head">
                <h3>Referral verification queue</h3>
                <span class="ipb-pay-badge is-info"><?= count($referrals) ?> record(s)</span>
              </div>
              <div class="box-body table-responsive">
                <?php if (empty($referrals)): ?>
                  <div class="ipb-rwd-empty">
                    <i class="fa fa-inbox" aria-hidden="true"></i>
                    No referrals yet.
                  </div>
                <?php else: ?>
                  <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Referrer</th>
                        <th>Referred customer</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Points</th>
                        <th>Registered</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($referrals as $r):
                      $st = $r['status'];
                      $stLabel = $statusLabels[$st] ?? ucfirst((string) $st);
                      $stBadge = $statusBadgeClass[$st] ?? 'is-info';
                    ?>
                      <tr class="rwd-ref-row"
                          data-id="<?= (int) $r['id'] ?>"
                          data-referrer="<?= esc($r['referrer_name'], 'attr') ?>"
                          data-name="<?= esc($r['referee_name'], 'attr') ?>"
                          data-mobile="<?= esc($r['referee_mobile'], 'attr') ?>"
                          data-email="<?= esc($r['referee_email'] ?? '', 'attr') ?>"
                          data-nid="<?= esc($r['referee_nid'] ?? '', 'attr') ?>"
                          data-package-id="<?= (int) ($r['package_id'] ?? 0) ?>"
                          data-package="<?= esc($r['package_name'] ?? '', 'attr') ?>"
                          data-code="<?= esc($r['referral_code'] ?? '', 'attr') ?>"
                          data-created="<?= esc($r['created_at'], 'attr') ?>">
                        <td><?= (int) $r['id'] ?></td>
                        <td><?= esc($r['referrer_name']) ?: '—' ?></td>
                        <td><strong><?= esc($r['referee_name']) ?: '—' ?></strong></td>
                        <td class="ipb-acc-nowrap"><?= esc($r['referee_mobile']) ?: '—' ?></td>
                        <td class="ipb-acc-status"><span class="ipb-pay-badge <?= esc($stBadge) ?>"><?= esc($stLabel) ?></span></td>
                        <td class="ipb-acc-nowrap"><?= (int) $r['points'] ?></td>
                        <td class="ipb-acc-nowrap"><?= esc($r['created_at']) ?></td>
                        <td class="ipb-rwd-actions">
                          <?php if ($st === 'pending' || $st === 'flagged'): ?>
                            <div class="ipb-row-actions">
                              <button type="button" class="ipb-row-btn tone-success btn-rwd-setup" title="Complete setup">
                                <i class="fa fa-user-plus" aria-hidden="true"></i>
                                <span class="sr-only">Complete setup</span>
                              </button>
                              <button type="button" class="ipb-row-btn tone-danger btn-rwd-reject"
                                title="Reject"
                                data-action="<?= esc(base_url('reward-center/referrals/' . $r['id'] . '/reject'), 'attr') ?>">
                                <i class="fa fa-times" aria-hidden="true"></i>
                                <span class="sr-only">Reject</span>
                              </button>
                            </div>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div id="tabReward" class="tab-pane" role="tabpanel">
            <div class="ipb-rwd-stats">
              <div class="ipb-rwd-stat is-info">
                <span>Points issued</span>
                <strong><?= number_format($issued) ?></strong>
              </div>
              <div class="ipb-rwd-stat is-warn">
                <span>Points redeemed</span>
                <strong><?= number_format($redeemed) ?></strong>
              </div>
              <div class="ipb-rwd-stat is-danger">
                <span>Reward cost (BDT)</span>
                <strong><?= number_format($reward_cost, 2) ?></strong>
              </div>
              <div class="ipb-rwd-stat is-success">
                <span>Conversion rate</span>
                <strong><?= esc((string) $conversion) ?>%</strong>
              </div>
            </div>

            <div class="box box-primary">
              <div class="ipb-rwd-panel-head">
                <h3>Customer reward wallets</h3>
                <span class="ipb-pay-badge is-info"><?= count($wallets) ?> wallet(s)</span>
              </div>
              <div class="box-body table-responsive">
                <?php if (empty($wallets)): ?>
                  <div class="ipb-rwd-empty">
                    <i class="fa fa-wallet" aria-hidden="true"></i>
                    No reward wallets yet.
                  </div>
                <?php else: ?>
                  <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>Customer</th>
                        <th>User ID</th>
                        <th>Available</th>
                        <th>Held</th>
                        <th>Lifetime earned</th>
                        <th>Lifetime used</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wallets as $w): ?>
                      <tr>
                        <td><strong><?= esc($w['name']) ?: '—' ?></strong></td>
                        <td><?= (int) $w['user_id'] ?></td>
                        <td class="ipb-rwd-points-credit"><?= (int) $w['balance'] ?></td>
                        <td><?= (int) $w['held'] ?></td>
                        <td><?= (int) $w['earned'] ?></td>
                        <td><?= (int) $w['used'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>

            <div class="box box-primary">
              <div class="ipb-rwd-panel-head">
                <h3>Recent point transactions</h3>
                <span class="ipb-pay-badge is-info">Last <?= count($transactions) ?></span>
              </div>
              <div class="box-body table-responsive">
                <?php if (empty($transactions)): ?>
                  <div class="ipb-rwd-empty">
                    <i class="fa fa-list" aria-hidden="true"></i>
                    No transactions yet.
                  </div>
                <?php else: ?>
                  <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Source</th>
                        <th>Description</th>
                        <th>Points</th>
                        <th>Balance after</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $t):
                      $isCredit = ($t['direction'] ?? '') === 'credit';
                      $sign = $isCredit ? '+' : '-';
                      $cls = $isCredit ? 'ipb-rwd-points-credit' : 'ipb-rwd-points-debit';
                    ?>
                      <tr>
                        <td><?= (int) $t['id'] ?></td>
                        <td><?= esc($t['customer']) ?: '—' ?> <small class="text-muted">(#<?= (int) $t['user_id'] ?>)</small></td>
                        <td><code><?= esc($t['source']) ?></code></td>
                        <td><?= esc($t['description']) ?></td>
                        <td class="<?= $cls ?>"><?= $sign ?><?= (int) $t['points'] ?></td>
                        <td><?= (int) $t['balance_after'] ?></td>
                        <td class="ipb-acc-nowrap"><?= esc($t['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>

            <div class="box box-primary">
              <div class="ipb-rwd-panel-head">
                <h3>Reward configuration</h3>
                <span class="ipb-pay-badge is-info">1 point = BDT <?= esc($config['point_value_bdt'] ?? 1) ?></span>
              </div>
              <form method="post" action="<?= base_url('reward-center/config') ?>">
                <?= csrf_field() ?>
                <div class="box-body">
                  <?php if ($isSuperAdmin): ?>
                    <div class="form-group" style="max-width:360px">
                      <label for="cfgScope">Configuration scope</label>
                      <select name="scope" id="cfgScope" class="form-control">
                        <option value="reseller">My organization (reseller / owner)</option>
                        <option value="global">Global default (all organizations)</option>
                      </select>
                    </div>
                  <?php else: ?>
                    <input type="hidden" name="scope" value="reseller">
                  <?php endif; ?>

                  <div class="ipb-rwd-config-grid">
                    <?php foreach ($config as $k => $v): ?>
                      <div class="form-group">
                        <label for="cfg_<?= esc($k, 'attr') ?>"><?= esc($configLabels[$k] ?? $k) ?></label>
                        <input type="text" class="form-control" id="cfg_<?= esc($k, 'attr') ?>" name="<?= esc($k, 'attr') ?>" value="<?= esc($v) ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="box-footer">
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save" aria-hidden="true"></i> Save configuration
                  </button>
                  <span class="text-muted" style="margin-left:10px">Changes apply to new rewards only.</span>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>

  </section>
</div>

<!-- Referral setup modal -->
<div class="modal fade" id="rwdSetupModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        <h4 class="modal-title"><i class="fa fa-user-plus" aria-hidden="true"></i> Complete referral setup</h4>
      </div>
      <div class="modal-body">
        <p class="text-muted">Review the referral details, then open the customer form with fields pre-filled. Add router, PPPoE, area and connection info, then save to activate and award points.</p>
        <table class="table table-bordered">
          <tr><th>Referred customer</th><td id="rwdMName">—</td></tr>
          <tr><th>Mobile</th><td id="rwdMMobile">—</td></tr>
          <tr><th>Email</th><td id="rwdMEmail">—</td></tr>
          <tr><th>NID</th><td id="rwdMNid">—</td></tr>
          <tr><th>Package</th><td id="rwdMPackage">—</td></tr>
          <tr><th>Referrer</th><td id="rwdMReferrer">—</td></tr>
          <tr><th>Referral code</th><td id="rwdMCode">—</td></tr>
          <tr><th>Registered</th><td id="rwdMCreated">—</td></tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <a href="#" id="rwdSetupGo" class="btn btn-primary">
          <i class="fa fa-arrow-right" aria-hidden="true"></i> Open customer form
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Reject modal -->
<div class="modal fade" id="rwdRejectModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" id="rwdRejectForm" action="#">
        <?= csrf_field() ?>
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
          <h4 class="modal-title"><i class="fa fa-times" aria-hidden="true"></i> Reject referral</h4>
        </div>
        <div class="modal-body">
          <div class="form-group" style="margin:0">
            <label for="rwdRejectReason">Reason (optional)</label>
            <input type="text" name="reason" id="rwdRejectReason" class="form-control" placeholder="Why is this referral rejected?">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject referral</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  var baseNewUrl = <?= json_encode($customerNewUrl) ?>;
  var $setupModal = $('#rwdSetupModal');
  var $rejectModal = $('#rwdRejectModal');
  var $rejectForm = $('#rwdRejectForm');

  function qp(key, val) {
    if (val === undefined || val === null || val === '') return '';
    return '&' + encodeURIComponent(key) + '=' + encodeURIComponent(val);
  }

  $(document).on('click', '.btn-rwd-setup', function () {
    var $row = $(this).closest('.rwd-ref-row');
    $('#rwdMName').text($row.data('name') || '—');
    $('#rwdMMobile').text($row.data('mobile') || '—');
    $('#rwdMEmail').text($row.data('email') || '—');
    $('#rwdMNid').text($row.data('nid') || '—');
    $('#rwdMPackage').text($row.data('package') || '—');
    $('#rwdMReferrer').text($row.data('referrer') || '—');
    $('#rwdMCode').text($row.data('code') || '—');
    $('#rwdMCreated').text($row.data('created') || '—');

    var refId = $row.data('id');
    var url = baseNewUrl + '?referral_id=' + encodeURIComponent(refId)
      + qp('name', $row.data('name'))
      + qp('mobile', $row.data('mobile'))
      + qp('email', $row.data('email'))
      + qp('nid', $row.data('nid'))
      + qp('package_id', $row.data('packageId') || $row.attr('data-package-id'));
    $('#rwdSetupGo').attr('href', url);
    $setupModal.modal('show');
  });

  $(document).on('click', '.btn-rwd-reject', function () {
    var action = $(this).data('action');
    $rejectForm.attr('action', action);
    $('#rwdRejectReason').val('');
    $rejectModal.modal('show');
  });
})();
</script>
<?= $this->endSection('script'); ?>

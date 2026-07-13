<?php
/**
 * Customer referrals & rewards portal.
 *
 * @var string $title
 * @var string $referral_code
 * @var string $referral_link
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $history
 * @var array<string, mixed> $wallet
 * @var array<int, array<string, mixed>> $transactions
 * @var array<string, mixed> $redeem_preview
 * @var string $subscription_url
 */
$title            = $title ?? 'Referrals & Rewards';
$referral_code    = $referral_code ?? '';
$referral_link    = $referral_link ?? '';
$subscription_url = $subscription_url ?? '';
$history          = is_array($history ?? null) ? $history : [];
$transactions     = is_array($transactions ?? null) ? $transactions : [];
$_stats            = is_array($stats ?? null) ? $stats : [];
$stats            = array_merge([
    'total'         => 0,
    'pending'       => 0,
    'verified'      => 0,
    'rejected'      => 0,
    'earned_points' => 0,
], $_stats);
$_wallet           = is_array($wallet ?? null) ? $wallet : [];
$wallet            = array_merge([
    'balance'         => 0,
    'held'            => 0,
    'lifetime_earned' => 0,
    'lifetime_used'   => 0,
    'expiring_points' => 0,
    'point_value_bdt' => 1.0,
], $_wallet);
$_redeem           = is_array($redeem_preview ?? null) ? $redeem_preview : [];
$redeem            = array_merge([
    'enabled'           => false,
    'max_usable_points' => 0,
    'discount_bdt'      => 0.0,
], $_redeem);
unset($_stats, $_wallet, $_redeem);

$statusLabels = [
    'pending'  => 'Pending approval',
    'verified' => 'Verified',
    'rejected' => 'Rejected',
    'flagged'  => 'Under review',
];

$balance = (int) ($wallet['balance'] ?? 0);
$pointValue = (float) ($wallet['point_value_bdt'] ?? 1);
$balanceBdt = $balance * $pointValue;
?>
<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  .ipb-rw {
    --rw-accent: var(--primary-500, #f75803);
    --rw-accent-600: var(--primary-600, #d94601);
    --rw-accent-50: var(--primary-50, #fff4ed);
  }

  .ipb-rw-hero {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 16px;
    margin-bottom: 16px;
  }

  .ipb-rw-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-1, 0 1px 2px rgba(15, 23, 42, 0.04));
    overflow: hidden;
  }

  .ipb-rw-card-body {
    padding: 20px 22px;
  }

  .ipb-rw-intro {
    background:
      radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.16), transparent 42%),
      linear-gradient(145deg, var(--secondary-700, #001f55), var(--secondary-900, #001233));
    color: #fff;
    border: 0;
    position: relative;
  }

  .ipb-rw-intro::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--rw-accent), #ffb38a);
  }

  .ipb-rw-intro h2 {
    margin: 0 0 8px;
    font-size: clamp(20px, 2.5vw, 26px);
    font-weight: 800;
    letter-spacing: -0.03em;
  }

  .ipb-rw-intro p {
    margin: 0;
    font-size: 13.5px;
    font-weight: 600;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.78);
    max-width: 42ch;
  }

  .ipb-rw-balance {
    background: linear-gradient(135deg, var(--rw-accent), #ff8a4c);
    color: #fff;
    border: 0;
    text-align: center;
  }

  .ipb-rw-balance-label {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.9;
  }

  .ipb-rw-balance-value {
    margin: 8px 0 4px;
    font-size: clamp(32px, 5vw, 40px);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
  }

  .ipb-rw-balance-value small {
    font-size: 15px;
    font-weight: 700;
    opacity: 0.92;
  }

  .ipb-rw-balance-sub {
    margin: 8px 0 0;
    font-size: 12.5px;
    font-weight: 600;
    opacity: 0.9;
  }

  .ipb-rw-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    padding: 4px;
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: 12px;
  }

  .ipb-rw-tabs a {
    flex: 1 1 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 8px 12px;
    border-radius: 10px;
    color: var(--text-secondary, #51607a);
    font-size: 13.5px;
    font-weight: 800;
    text-decoration: none !important;
    transition: background 0.14s ease, color 0.14s ease;
  }

  .ipb-rw-tabs a:hover {
    background: var(--surface-hover, #f1f5f9);
    color: var(--text-primary, #0f172a);
  }

  .ipb-rw-tabs a.active {
    background: var(--rw-accent);
    color: #fff !important;
  }

  .ipb-rw-pane { display: none; }
  .ipb-rw-pane.active { display: block; }

  .ipb-rw-section-title {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    letter-spacing: -0.02em;
  }

  .ipb-rw-section-sub {
    margin: 0 0 16px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-rw-code-box {
    text-align: center;
    padding: 20px 16px;
    border-radius: 14px;
    background: var(--surface-2, #f8fafc);
    border: 1.5px dashed var(--border-strong, #d7dee7);
    margin-bottom: 14px;
  }

  .ipb-rw-code-hint {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted, #94a3b8);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .ipb-rw-code {
    margin: 10px 0 8px;
    font-size: clamp(22px, 4vw, 30px);
    font-weight: 800;
    letter-spacing: 0.12em;
    color: var(--rw-accent-600);
    word-break: break-all;
  }

  .ipb-rw-code-link {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-secondary, #51607a);
    word-break: break-all;
    line-height: 1.45;
  }

  .ipb-rw-btn-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .ipb-rw-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex: 1 1 140px;
    min-height: 44px;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 800;
    font-family: var(--font-sans);
    cursor: pointer;
    text-decoration: none !important;
    border: 0;
  }

  .ipb-rw-btn-primary {
    background: var(--rw-accent) !important;
    color: #fff !important;
    box-shadow: var(--shadow-brand, 0 8px 18px rgba(247, 88, 3, 0.28));
  }

  .ipb-rw-btn-primary:hover {
    background: var(--rw-accent-600) !important;
    color: #fff !important;
  }

  .ipb-rw-btn-outline {
    background: var(--surface, #fff) !important;
    color: var(--text-primary, #0f172a) !important;
    border: 1.5px solid var(--border-strong, #d7dee7) !important;
  }

  .ipb-rw-btn-outline:hover {
    border-color: var(--rw-accent) !important;
    color: var(--rw-accent-600) !important;
  }

  .ipb-rw-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin: 16px 0;
  }

  .ipb-rw-stat {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  .ipb-rw-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--rw-accent-50);
    color: var(--rw-accent-600);
    flex-shrink: 0;
  }

  .ipb-rw-stat strong {
    display: block;
    font-size: 20px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    line-height: 1.1;
  }

  .ipb-rw-stat span {
    display: block;
    margin-top: 2px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-rw-steps {
    display: grid;
    gap: 10px;
  }

  .ipb-rw-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--surface-2, #f8fafc);
    border: 1px solid var(--border, #e6eaf0);
  }

  .ipb-rw-step-num {
    width: 26px;
    height: 26px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--rw-accent-50);
    color: var(--rw-accent-600);
    font-size: 12px;
    font-weight: 800;
  }

  .ipb-rw-step p {
    margin: 0;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-secondary, #51607a);
    line-height: 1.5;
  }

  .ipb-rw-step a {
    color: var(--rw-accent-600);
    font-weight: 800;
    text-decoration: none;
  }

  .ipb-rw-step a:hover { text-decoration: underline; }

  .ipb-rw-list-item {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px 16px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border, #e6eaf0);
  }

  .ipb-rw-list-item:last-child { border-bottom: 0; }

  .ipb-rw-list-name {
    font-size: 14px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
  }

  .ipb-rw-list-date {
    margin-top: 3px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-rw-list-meta {
    text-align: right;
  }

  .ipb-rw-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
  }

  .ipb-rw-badge.pending  { background: #fffbeb; color: #92400e; }
  .ipb-rw-badge.verified { background: #ecfdf3; color: #166534; }
  .ipb-rw-badge.rejected { background: #fef2f2; color: #991b1b; }
  .ipb-rw-badge.flagged  { background: #f1f5f9; color: #475569; }

  .ipb-rw-points {
    margin-top: 6px;
    font-size: 13px;
    font-weight: 800;
    color: var(--rw-accent-600);
  }

  .ipb-rw-credit { color: #16a34a; font-weight: 800; font-size: 15px; }
  .ipb-rw-debit  { color: #dc2626; font-weight: 800; font-size: 15px; }

  .ipb-rw-bal-after {
    margin-top: 4px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-rw-redeem-note {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--rw-accent-50);
    color: var(--rw-accent-600);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
  }

  .ipb-rw-redeem-note strong {
    font-weight: 800;
  }

  .ipb-rw-empty {
    text-align: center;
    padding: 36px 16px;
    color: var(--text-muted, #94a3b8);
    font-size: 13.5px;
    font-weight: 600;
  }

  .ipb-rw-empty i {
    display: block;
    margin-bottom: 10px;
    font-size: 28px;
    opacity: 0.55;
  }

  .ipb-rw-stack {
    display: grid;
    gap: 16px;
  }

  body.ipb[data-theme="dark"] .ipb-rw-card,
  body.ipb.dark-mode .ipb-rw-card,
  body.ipb[data-theme="dark"] .ipb-rw-stat,
  body.ipb.dark-mode .ipb-rw-stat,
  body.ipb[data-theme="dark"] .ipb-rw-tabs,
  body.ipb.dark-mode .ipb-rw-tabs {
    background: #111726;
    border-color: #232c40;
  }

  body.ipb[data-theme="dark"] .ipb-rw-code-box,
  body.ipb.dark-mode .ipb-rw-code-box,
  body.ipb[data-theme="dark"] .ipb-rw-step,
  body.ipb.dark-mode .ipb-rw-step {
    background: #161d2e;
    border-color: #232c40;
  }

  @media (max-width: 991px) {
    .ipb-rw-hero {
      grid-template-columns: 1fr;
    }

    .ipb-rw-stats {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 767px) {
    .ipb-rw-card-body { padding: 16px; }

    .ipb-rw-tabs {
      flex-direction: column;
    }

    .ipb-rw-stats {
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .ipb-rw-stat {
      padding: 12px;
    }

    .ipb-rw-stat strong {
      font-size: 17px;
    }

    .ipb-rw-list-meta {
      text-align: left;
      width: 100%;
    }

    .ipb-rw-btn {
      flex: 1 1 100%;
      min-height: 46px;
    }
  }

  @media (max-width: 480px) {
    .ipb-rw-stats {
      grid-template-columns: 1fr;
    }
  }
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-rw">

    <?= $this->include('components/page-header', [
      'title' => 'Referrals & Rewards',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Referrals & Rewards'],
      ],
    ]); ?>

    <?php if (session()->getFlashdata('rwd_success')): ?>
      <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= esc(session()->getFlashdata('rwd_success')); ?>
      </div>
    <?php endif; ?>

    <div class="ipb-rw-hero">
      <div class="ipb-rw-card ipb-rw-intro">
        <div class="ipb-rw-card-body">
          <h2><i class="fa fa-gift" aria-hidden="true"></i> Earn points, share your code</h2>
          <p>Refer friends to earn reward points. Use points as a discount when you renew your subscription.</p>
        </div>
      </div>
      <div class="ipb-rw-card ipb-rw-balance">
        <div class="ipb-rw-card-body">
          <div class="ipb-rw-balance-label">Available balance</div>
          <div class="ipb-rw-balance-value">
            <?= number_format($balance); ?>
            <small>points</small>
          </div>
          <p class="ipb-rw-balance-sub">
            Worth BDT <?= number_format($balanceBdt, 0); ?>
            · 1 pt = BDT <?= esc($wallet['point_value_bdt'] ?? 1); ?>
          </p>
        </div>
      </div>
    </div>

    <div class="ipb-rw-tabs" id="cpTabs">
      <a href="#referral" class="active" data-pane="cpReferral">
        <i class="fa fa-user-plus" aria-hidden="true"></i> Refer a Friend
      </a>
      <a href="#rewards" data-pane="cpRewards">
        <i class="fa fa-star" aria-hidden="true"></i> My Reward Points
      </a>
    </div>

    <!-- ========== REFERRAL ========== -->
    <div id="cpReferral" class="ipb-rw-pane active">
      <div class="ipb-rw-stack">

        <div class="ipb-rw-card">
          <div class="ipb-rw-card-body">
            <h3 class="ipb-rw-section-title">Your referral code</h3>
            <p class="ipb-rw-section-sub">Share this code or link with friends</p>
            <div class="ipb-rw-code-box">
              <div class="ipb-rw-code-hint">Referral code</div>
              <div class="ipb-rw-code" id="refCode"><?= esc($referral_code); ?></div>
              <div class="ipb-rw-code-link"><?= esc($referral_link); ?></div>
            </div>
            <div class="ipb-rw-btn-row">
              <button type="button" class="ipb-rw-btn ipb-rw-btn-primary" id="btnCopyCode">
                <i class="fa fa-copy" aria-hidden="true"></i> Copy code
              </button>
              <button type="button" class="ipb-rw-btn ipb-rw-btn-outline" id="btnCopyLink">
                <i class="fa fa-link" aria-hidden="true"></i> Copy link
              </button>
            </div>
          </div>
        </div>

        <div class="ipb-rw-card">
          <div class="ipb-rw-card-body">
            <h3 class="ipb-rw-section-title">How referring works</h3>
            <p class="ipb-rw-section-sub">Three simple steps to earn points</p>
            <div class="ipb-rw-steps">
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">1</span>
                <p>Share your code or link with a friend who wants a new connection.</p>
              </div>
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">2</span>
                <p>They register using your referral code.</p>
              </div>
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">3</span>
                <p>After your ISP approves the referral, you earn reward points automatically.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="ipb-rw-stats">
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-users"></i></div>
            <div>
              <strong><?= (int) ($stats['total'] ?? 0); ?></strong>
              <span>Total referrals</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-clock"></i></div>
            <div>
              <strong><?= (int) ($stats['pending'] ?? 0); ?></strong>
              <span>Pending</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-circle-check"></i></div>
            <div>
              <strong><?= (int) ($stats['verified'] ?? 0); ?></strong>
              <span>Verified</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-star"></i></div>
            <div>
              <strong><?= (int) ($stats['earned_points'] ?? 0); ?></strong>
              <span>Points earned</span>
            </div>
          </div>
        </div>

        <div class="ipb-rw-card">
          <div class="ipb-rw-card-body">
            <h3 class="ipb-rw-section-title">Referral history</h3>
            <p class="ipb-rw-section-sub">People you have referred</p>
            <?php if (empty($history)): ?>
              <div class="ipb-rw-empty">
                <i class="fa fa-inbox" aria-hidden="true"></i>
                No referrals yet. Share your code to get started.
              </div>
            <?php else: ?>
              <?php foreach ($history as $h):
                $st = $h['status'];
                $label = $statusLabels[$st] ?? ucfirst((string) $st);
              ?>
                <div class="ipb-rw-list-item">
                  <div>
                    <div class="ipb-rw-list-name"><?= esc($h['referred_name']) ?: 'New customer'; ?></div>
                    <div class="ipb-rw-list-date"><?= esc($h['registered_at']); ?></div>
                  </div>
                  <div class="ipb-rw-list-meta">
                    <span class="ipb-rw-badge <?= esc($st, 'attr'); ?>"><?= esc($label); ?></span>
                    <?php if ((int) $h['points'] > 0): ?>
                      <div class="ipb-rw-points">+<?= (int) $h['points']; ?> pts</div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

    <!-- ========== REWARDS ========== -->
    <div id="cpRewards" class="ipb-rw-pane">
      <div class="ipb-rw-stack">

        <div class="ipb-rw-stats">
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-arrow-up"></i></div>
            <div>
              <strong><?= number_format((int) ($wallet['lifetime_earned'] ?? 0)); ?></strong>
              <span>Total earned</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-arrow-down"></i></div>
            <div>
              <strong><?= number_format((int) ($wallet['lifetime_used'] ?? 0)); ?></strong>
              <span>Total used</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-hourglass-half"></i></div>
            <div>
              <strong><?= number_format((int) ($wallet['expiring_points'] ?? 0)); ?></strong>
              <span>Expiring soon</span>
            </div>
          </div>
          <div class="ipb-rw-stat">
            <div class="ipb-rw-stat-icon" aria-hidden="true"><i class="fa fa-lock"></i></div>
            <div>
              <strong><?= number_format((int) ($wallet['held'] ?? 0)); ?></strong>
              <span>On hold</span>
            </div>
          </div>
        </div>

        <div class="ipb-rw-card">
          <div class="ipb-rw-card-body">
            <h3 class="ipb-rw-section-title">How to use points on payment</h3>
            <p class="ipb-rw-section-sub">Redeem points when you renew</p>
            <div class="ipb-rw-steps">
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">1</span>
                <p>Go to <a href="<?= esc($subscription_url, 'attr'); ?>"><strong>My Subscription</strong></a> and click <strong>Renew now</strong>.</p>
              </div>
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">2</span>
                <p>Turn on <strong>Use reward points</strong> before confirming renewal.</p>
              </div>
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">3</span>
                <p>Your bill is reduced by the point discount. Pay the remaining amount.</p>
              </div>
              <div class="ipb-rw-step">
                <span class="ipb-rw-step-num">4</span>
                <p>Points are held until payment succeeds, then deducted automatically.</p>
              </div>
            </div>

            <?php if (!empty($redeem['enabled']) && $balance > 0): ?>
              <div class="ipb-rw-redeem-note">
                <i class="fa fa-circle-check" aria-hidden="true"></i>
                You can use up to <strong><?= number_format((int) ($redeem['max_usable_points'] ?? 0)); ?></strong> points
                (BDT <strong><?= number_format((float) ($redeem['discount_bdt'] ?? 0), 0); ?></strong> off) on your current package renewal.
              </div>
            <?php endif; ?>

            <div class="ipb-rw-btn-row" style="margin-top:16px">
              <a href="<?= esc($subscription_url, 'attr'); ?>" class="ipb-rw-btn ipb-rw-btn-primary">
                <i class="fa fa-rotate" aria-hidden="true"></i> Go to My Subscription
              </a>
            </div>
          </div>
        </div>

        <div class="ipb-rw-card">
          <div class="ipb-rw-card-body">
            <h3 class="ipb-rw-section-title">Transaction history</h3>
            <p class="ipb-rw-section-sub">Credits and redemptions</p>
            <?php if (empty($transactions)): ?>
              <div class="ipb-rw-empty">
                <i class="fa fa-list" aria-hidden="true"></i>
                No transactions yet.
              </div>
            <?php else: ?>
              <?php foreach ($transactions as $t):
                $isCredit = ($t['direction'] ?? '') === 'credit';
                $sign = $isCredit ? '+' : '-';
                $cls = $isCredit ? 'ipb-rw-credit' : 'ipb-rw-debit';
              ?>
                <div class="ipb-rw-list-item">
                  <div>
                    <div class="ipb-rw-list-name"><?= esc($t['description']); ?></div>
                    <div class="ipb-rw-list-date"><?= esc($t['date']); ?></div>
                  </div>
                  <div class="ipb-rw-list-meta">
                    <span class="<?= $cls ?>"><?= $sign ?><?= number_format((int) $t['points']); ?> pts</span>
                    <div class="ipb-rw-bal-after">Balance: <?= number_format((int) $t['balance_after']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

  </section>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  var tabs = document.querySelectorAll('#cpTabs a');
  var panes = document.querySelectorAll('.ipb-rw-pane');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      var id = tab.getAttribute('data-pane');
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      panes.forEach(function (p) { p.classList.remove('active'); });
      var pane = document.getElementById(id);
      if (pane) pane.classList.add('active');
      if (history.replaceState) {
        history.replaceState(null, '', tab.getAttribute('href'));
      }
    });
  });

  if (location.hash === '#rewards') {
    var rewardsTab = document.querySelector('#cpTabs a[data-pane="cpRewards"]');
    if (rewardsTab) rewardsTab.click();
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        if (typeof tata !== 'undefined') tata.success('Copied', text);
        else alert('Copied: ' + text);
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      if (typeof tata !== 'undefined') tata.success('Copied', text);
      else alert('Copied: ' + text);
    }
  }

  var btnCode = document.getElementById('btnCopyCode');
  var btnLink = document.getElementById('btnCopyLink');
  if (btnCode) {
    btnCode.addEventListener('click', function () {
      var el = document.getElementById('refCode');
      copyText(el ? el.textContent.trim() : '');
    });
  }
  if (btnLink) {
    btnLink.addEventListener('click', function () {
      copyText(<?= json_encode($referral_link); ?>);
    });
  }
})();
</script>
<?= $this->endSection(); ?>

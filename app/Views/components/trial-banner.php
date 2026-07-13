<?php
/**
 * Free-trial banner for sAdmin tenants.
 *
 * @var object|null $trialUser
 */
helper('subscription');
$trialUser = $trialUser ?? null;
if (empty($trialUser) || !isOnFreeTrial($trialUser)) {
    return;
}
$daysLeft = trialDaysRemaining($trialUser);
$trialEnd = !empty($trialUser->trial_ends_at)
    ? date('d M Y', strtotime($trialUser->trial_ends_at))
    : (!empty($trialUser->will_expire) ? date('d M Y', strtotime($trialUser->will_expire)) : '');
$isPayg = false;
try {
    $isPayg = (new \App\Services\PaygBillingService())->isPaygUser($trialUser);
} catch (\Throwable $e) {
    $isPayg = false;
}
?>
<div class="alert alert-info ipb-trial-banner" style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;border-left:4px solid #0ea5e9">
  <i class="fa fa-gift" style="font-size:20px;margin-top:2px" aria-hidden="true"></i>
  <div style="flex:1">
    <strong>Free trial — <?= (int) $daysLeft; ?> day<?= $daysLeft === 1 ? '' : 's'; ?> left</strong>
    <?php if ($trialEnd !== ''): ?>
      <span class="text-muted">(ends <?= esc($trialEnd); ?>)</span>
    <?php endif; ?>
    <p style="margin:6px 0 0;font-size:14px">
      Your current plan stays fully active during the trial.
      <?php if ($isPayg): ?>
        Add wallet balance before the trial ends so PAYG billing can continue uninterrupted.
      <?php else: ?>
        Subscribe or renew before the trial ends to keep your ISP panel running.
      <?php endif; ?>
    </p>
  </div>
  <?php if ($isPayg): ?>
    <a href="<?= route_to('route.wallet'); ?>" class="btn btn-sm btn-primary">Top up wallet</a>
  <?php else: ?>
    <a href="<?= route_to('route.payment'); ?>" class="btn btn-sm btn-primary"
      data-bs-toggle="tooltip" title="Pay your subscription invoice anytime">My Payment</a>
  <?php endif; ?>
</div>

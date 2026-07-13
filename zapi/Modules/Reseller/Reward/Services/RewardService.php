<?php

namespace Zapi\Modules\Reseller\Reward\Services;

use Zapi\Modules\Reseller\Core\Services\ResellerBaseService;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Models\RewardTransactionModel;
use Zapi\Modules\Shared\Rewards\Models\RewardWalletModel;
use Zapi\Modules\Shared\Rewards\Models\RewardRedemptionModel;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

class RewardService extends ResellerBaseService
{
    /**
     * GET /api/reseller/rewards/{resellerId}/report
     * Referral performance + reward cost summary for a reseller.
     */
    public function report($resellerId = null)
    {
        $resellerId = (int) $resellerId;
        if ($resellerId <= 0) {
            return $this->respondError('Missing reseller id', 400, 'REQUEST_FAILED');
        }
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }

        $refModel = new ReferralModel();
        $statusRows = $refModel->select('status, COUNT(*) as cnt')
            ->where('owner_id', $resellerId)
            ->groupBy('status')
            ->findAll();
        $counts = ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0, 'flagged' => 0];
        foreach ($statusRows as $r) {
            $s = (string) $r->status;
            $c = (int) $r->cnt;
            if (isset($counts[$s])) {
                $counts[$s] += $c;
            }
            $counts['total'] += $c;
        }

        $ledger = new RewardTransactionModel();
        $issued = (int) ($ledger->selectSum('points')->where('owner_id', $resellerId)->where('points >', 0)->first()->points ?? 0);
        $redeemedPoints = abs((int) ($ledger->selectSum('points')->where('owner_id', $resellerId)->where('source', RewardSources::REDEMPTION)->first()->points ?? 0));

        $config = new RewardConfigService();
        $pointValue = $config->getFloat($resellerId, RewardSources::KEY_POINT_VALUE_BDT);
        $subsidyCost = round($redeemedPoints * $pointValue, 2);

        $conversion = $counts['total'] > 0 ? round(($counts['verified'] / $counts['total']) * 100, 2) : 0.0;

        // Top referrers (verified).
        $topRows = $refModel->select('referrer_id, COUNT(*) as cnt')
            ->where('owner_id', $resellerId)
            ->where('status', RewardSources::REFERRAL_VERIFIED)
            ->groupBy('referrer_id')
            ->orderBy('cnt', 'DESC')
            ->findAll(10);
        $top = [];
        foreach ($topRows as $t) {
            $u = $this->user_model->find((int) $t->referrer_id);
            $top[] = [
                'referrer_id'   => (int) $t->referrer_id,
                'referrer_name' => (string) ($u ? ($u->name ?? '') : ''),
                'verified'      => (int) $t->cnt,
            ];
        }

        return $this->respondSuccess([
            'referrals' => [
                'total'             => $counts['total'],
                'pending'           => $counts['pending'] + $counts['flagged'],
                'verified'          => $counts['verified'],
                'rejected'          => $counts['rejected'],
                'active_referrals'  => $counts['verified'],
                'conversion_rate'   => $conversion,
            ],
            'rewards' => [
                'points_issued'    => $issued,
                'points_redeemed'  => $redeemedPoints,
                'reward_cost_bdt'  => $subsidyCost,
                'discounts_given'  => $subsidyCost,
                'point_value_bdt'  => $pointValue,
            ],
            'top_referrers' => $top,
        ]);
    }

    /**
     * GET /api/reseller/rewards/{resellerId}/wallets
     * Per-customer reward balances under this reseller.
     */
    public function wallets($resellerId = null)
    {
        $resellerId = (int) $resellerId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }

        $walletModel = new RewardWalletModel();
        $pager = $this->getPaginationParams();
        $total = $walletModel->where('owner_id', $resellerId)->countAllResults(false);
        $rows = $walletModel->where('owner_id', $resellerId)
            ->orderBy('balance', 'DESC')
            ->findAll($pager['limit'], $pager['offset']);

        $items = [];
        foreach ($rows as $w) {
            $u = $this->user_model->find((int) $w->user_id);
            $items[] = [
                'user_id'         => (int) $w->user_id,
                'customer_name'   => (string) ($u ? ($u->name ?? '') : ''),
                'balance'         => max(0, (int) $w->balance - (int) $w->held),
                'held'            => (int) $w->held,
                'lifetime_earned' => (int) $w->lifetime_earned,
                'lifetime_used'   => (int) $w->lifetime_spent,
            ];
        }

        return $this->respondSuccess([
            'items'      => $items,
            'pagination' => $this->buildPaginationMeta($total, $pager['page'], $pager['limit'], count($items)),
        ]);
    }
}

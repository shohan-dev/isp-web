<?php

namespace Zapi\Modules\Customer\RewardPortal\Controllers;

use CodeIgniter\Controller;
use Zapi\Modules\Shared\Rewards\Models\ReferralCodeModel;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Models\RewardTransactionModel;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;
use Zapi\Modules\Shared\Rewards\Services\RewardEngine;
use Zapi\Modules\Shared\Rewards\Services\WebRenewRewardHelper;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;
use Zapi\Modules\Shared\Rewards\Support\ReferralLinkBuilder;

/**
 * Customer web portal — referral sharing + reward wallet (session auth, role=user).
 */
class CustomerRewardPortalController extends Controller
{
    private function sessionUserId(): int
    {
        return (int) (session()->get('user_id') ?? 0);
    }

    private function sessionRole(): string
    {
        return strtolower((string) session()->get('user_role'));
    }

    private function guardCustomer()
    {
        if ($this->sessionRole() !== 'user' || $this->sessionUserId() <= 0) {
            return redirect()->to(route_to('route.dashboard'))
                ->with('rwd_error', 'This page is only available for customer accounts.');
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->guardCustomer()) {
            return $redirect;
        }

        $userId = $this->sessionUserId();
        $userModel = model('App\Models\User');
        $user = $userModel->find($userId);
        if (!$user) {
            return redirect()->to(route_to('route.dashboard'));
        }

        $ownerId = (int) ($user->admin_id ?? 0);
        $name = (string) ($user->name ?? '');

        // Referral overview
        $codeModel = new ReferralCodeModel();
        $referralCode = $codeModel->getOrCreateForUser($userId, $ownerId, $name);
        $referralLink = $this->referralLink($referralCode);

        $refModel = new ReferralModel();
        $counts = $refModel->countByStatus($userId);
        $earnedRow = $refModel->selectSum('points_awarded')->where('referrer_id', $userId)->first();
        $earnedPoints = (int) ($earnedRow->points_awarded ?? 0);

        $historyRows = $refModel->where('referrer_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll(30);

        $history = [];
        foreach ($historyRows as $r) {
            $history[] = [
                'id'            => (int) $r->id,
                'referred_name' => (string) ($r->referee_name ?? ''),
                'status'        => (string) ($r->status ?? 'pending'),
                'points'        => (int) ($r->points_awarded ?? 0),
                'registered_at' => (string) ($r->created_at ?? ''),
            ];
        }

        // Reward wallet
        $engine = new RewardEngine();
        $walletRow = $engine->getWallet($userId, $ownerId);
        $config = new RewardConfigService();
        $pointValue = $config->getFloat($ownerId, RewardSources::KEY_POINT_VALUE_BDT);
        $balance = max(0, (int) ($walletRow->balance ?? 0) - (int) ($walletRow->held ?? 0));

        $ledger = new RewardTransactionModel();
        $txnRows = $ledger->where('user_id', $userId)->orderBy('id', 'DESC')->findAll(30);
        $transactions = [];
        foreach ($txnRows as $t) {
            $points = (int) $t->points;
            $transactions[] = [
                'id'            => (int) $t->id,
                'date'          => (string) ($t->created_at ?? ''),
                'source'        => (string) ($t->source ?? ''),
                'description'   => (string) ($t->note ?? RewardMessages::reasonForSource((string) ($t->source ?? ''))),
                'points'        => abs($points),
                'direction'     => $points >= 0 ? 'credit' : 'debit',
                'balance_after' => (int) ($t->balance_after ?? 0),
            ];
        }

        $packageId = (int) ($user->package_id ?? 0);
        $redeemPreview = WebRenewRewardHelper::preview($userId, $packageId);

        $data = [
            'title'           => 'Referrals & Rewards',
            'referral_code'   => $referralCode,
            'referral_link'   => $referralLink,
            'stats'           => [
                'total'         => (int) $counts['total'],
                'pending'       => (int) $counts['pending'] + (int) $counts['flagged'],
                'verified'      => (int) $counts['verified'],
                'rejected'      => (int) $counts['rejected'],
                'earned_points' => $earnedPoints,
            ],
            'history'         => $history,
            'wallet'          => [
                'balance'         => $balance,
                'held'            => (int) ($walletRow->held ?? 0),
                'lifetime_earned' => (int) ($walletRow->lifetime_earned ?? 0),
                'lifetime_used'   => (int) ($walletRow->lifetime_spent ?? 0),
                'expiring_points' => $engine->expiringSoonPoints($userId, 30),
                'point_value_bdt' => $pointValue,
            ],
            'transactions'    => $transactions,
            'redeem_preview'  => $redeemPreview,
            'subscription_url'=> route_to('route.subscription'),
        ];

        return view('Zapi\rewards\customer_portal', $data);
    }

    /** JSON redeem preview when customer changes package on subscription page. */
    public function redeemPreview()
    {
        if ($redirect = $this->guardCustomer()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $userId = $this->sessionUserId();
        $packageId = (int) ($this->request->getGet('package_id') ?? 0);
        $points = (int) ($this->request->getGet('points') ?? 0);
        if ($packageId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'package_id required'])->setStatusCode(400);
        }

        $preview = WebRenewRewardHelper::preview($userId, $packageId, $points);
        return $this->response->setJSON(['success' => true, 'data' => $preview]);
    }

    private function referralLink(string $code): string
    {
        return ReferralLinkBuilder::build($code);
    }
}

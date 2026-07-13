<?php

namespace Zapi\Modules\Shared\Rewards\Controllers;

use CodeIgniter\Controller;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Models\RewardTransactionModel;
use Zapi\Modules\Shared\Rewards\Models\RewardWalletModel;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;
use Zapi\Modules\Shared\Rewards\Services\ReferralWorkflow;
use Zapi\Modules\Shared\Rewards\Support\RewardMessages;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

/**
 * Referral & Reward admin page rendered INSIDE the website (the isp-core admin
 * panel). It extends the site layout (layout/main-layout), is reached from the
 * sidebar, and uses the website session for auth — no token pasting, no
 * standalone page. Logic is server-rendered from the shared reward domain.
 *
 * Routes (behind the website 'authcheck' filter):
 *   GET  /reward-center                          -> index (Referral + Reward sections)
 *   POST /reward-center/referrals/{id}/approve   -> approve & activate
 *   POST /reward-center/referrals/{id}/reject    -> reject
 *   POST /reward-center/config                   -> save reward config
 */
class RewardWebController extends Controller
{
    private function sessionRole(): string
    {
        return strtolower((string) session()->get('user_role'));
    }

    /** Original-cased session role for userHasPermission (case-sensitive sAdmin branch). */
    private function actorRole(): string
    {
        return (string) session()->get('user_role');
    }

    private function sessionUserId(): int
    {
        return (int) (session()->get('user_id') ?? 0);
    }

    /** Platform-level second admin — can see all owner scopes. */
    private function isPlatformAdmin(): bool
    {
        return $this->sessionRole() === 'super_admin';
    }

    /** sAdmin or platform admin. */
    private function isSuperAdmin(): bool
    {
        return in_array($this->sessionRole(), ['admin', 'super_admin'], true);
    }

    /**
     * Data owner scope for referrals/wallets/ledger filtering.
     * Employees inherit their sAdmin's scope; POP admins use their own id.
     */
    private function resolveOwnerScope(): int
    {
        $userId = $this->sessionUserId();
        if ($userId <= 0) {
            return 0;
        }

        if ($this->sessionRole() === 'employee') {
            helper('user');
            if (function_exists('getSAdminIdForUser')) {
                $sAdminId = (int) getSAdminIdForUser($userId);
                if ($sAdminId > 0) {
                    return $sAdminId;
                }
            }
        }

        return $userId;
    }

    private function canActOn($referral): bool
    {
        if (!$referral) {
            return false;
        }
        if ($this->isPlatformAdmin()) {
            return true;
        }
        if ((int) $referral->owner_id === $this->resolveOwnerScope()) {
            return true;
        }
        return $this->isSuperAdmin() && $this->sessionRole() === 'admin'
            && (int) $referral->owner_id === $this->resolveOwnerScope();
    }

    private function guardAccess()
    {
        helper('user');
        if (!userHasPermission('referral', 'read', $this->actorRole(), $this->sessionUserId())) {
            return redirect()->to(route_to('route.dashboard'))
                ->with('rwd_error', 'You do not have access to Referral & Reward.');
        }

        return null;
    }

    private function guardEdit()
    {
        helper('user');
        if (!userHasPermission('referral', 'update', $this->actorRole(), $this->sessionUserId())) {
            return redirect()->to(base_url('reward-center'))
                ->with('rwd_error', 'You do not have permission to edit referrals.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }

        $ownerScope = $this->resolveOwnerScope();
        $refModel = new ReferralModel();
        $walletModel = new RewardWalletModel();
        $ledger = new RewardTransactionModel();
        $config = new RewardConfigService();
        $userModel = model('App\Models\User');

        // Referrals (scoped), newest first.
        $refBuilder = $refModel->orderBy('id', 'DESC');
        if (!$this->isPlatformAdmin()) {
            $refBuilder = $refBuilder->where('owner_id', $ownerScope);
        }
        $rows = $refBuilder->findAll(300);

        $referrals = [];
        $counts = ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0, 'flagged' => 0];
        foreach ($rows as $r) {
            $referrer = $userModel->find((int) ($r->referrer_id ?? 0));
            $referee = $userModel->find((int) ($r->referee_id ?? 0));
            $packageName = '';
            $pkgId = (int) ($r->package_id ?? ($referee->package_id ?? 0));
            if ($pkgId > 0) {
                try {
                    $pkg = model('App\Models\Package')->find($pkgId);
                    $packageName = (string) ($pkg->package_name ?? '');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $referrals[] = [
                'id'             => (int) $r->id,
                'referee_id'     => (int) ($r->referee_id ?? 0),
                'referrer_name'  => (string) ($referrer ? ($referrer->name ?? '') : ''),
                'referee_name'   => (string) ($r->referee_name ?? ($referee->name ?? '')),
                'referee_mobile' => (string) ($r->referee_mobile ?? ($referee->mobile ?? '')),
                'referee_email'  => (string) ($r->referee_email ?? ($referee->email ?? '')),
                'referee_nid'    => (string) ($r->referee_nid ?? ($referee->nid_number ?? '')),
                'referral_code'  => (string) ($r->referral_code ?? ''),
                'package_id'     => $pkgId,
                'package_name'   => $packageName,
                'status'         => (string) ($r->status ?? 'pending'),
                'points'         => (int) ($r->points_awarded ?? 0),
                'created_at'     => (string) ($r->created_at ?? ''),
            ];
            $s = (string) ($r->status ?? 'pending');
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
            $counts['total']++;
        }

        // Reward totals (scoped).
        $issuedBuilder = $ledger->selectSum('points')->where('points >', 0);
        $redeemedBuilder = $ledger->selectSum('points')->where('source', RewardSources::REDEMPTION);
        if (!$this->isPlatformAdmin()) {
            $issuedBuilder = $issuedBuilder->where('owner_id', $ownerScope);
            $redeemedBuilder = $redeemedBuilder->where('owner_id', $ownerScope);
        }
        $issued = (int) ($issuedBuilder->first()->points ?? 0);
        $redeemed = abs((int) ($redeemedBuilder->first()->points ?? 0));
        $pointValue = $config->getFloat($ownerScope, RewardSources::KEY_POINT_VALUE_BDT);
        $conversion = $counts['total'] > 0 ? round(($counts['verified'] / $counts['total']) * 100, 1) : 0.0;

        // Wallets (scoped).
        $walletBuilder = $walletModel->orderBy('balance', 'DESC');
        if (!$this->isPlatformAdmin()) {
            $walletBuilder = $walletBuilder->where('owner_id', $ownerScope);
        }
        $wrows = $walletBuilder->findAll(200);
        $wallets = [];
        foreach ($wrows as $w) {
            $u = $userModel->find((int) $w->user_id);
            $wallets[] = [
                'name'    => (string) ($u ? ($u->name ?? '') : ''),
                'user_id' => (int) $w->user_id,
                'balance' => max(0, (int) $w->balance - (int) $w->held),
                'held'    => (int) $w->held,
                'earned'  => (int) $w->lifetime_earned,
                'used'    => (int) $w->lifetime_spent,
            ];
        }

        // Recent ledger rows (scoped).
        $txnBuilder = $ledger->orderBy('id', 'DESC');
        if (!$this->isPlatformAdmin()) {
            $txnBuilder = $txnBuilder->where('owner_id', $ownerScope);
        }
        $txnRows = $txnBuilder->findAll(100);
        $transactions = [];
        foreach ($txnRows as $t) {
            $u = $userModel->find((int) ($t->user_id ?? 0));
            $points = (int) ($t->points ?? 0);
            $transactions[] = [
                'id'            => (int) $t->id,
                'customer'      => (string) ($u ? ($u->name ?? '') : ''),
                'user_id'       => (int) ($t->user_id ?? 0),
                'points'        => abs($points),
                'direction'     => $points >= 0 ? 'credit' : 'debit',
                'source'        => (string) ($t->source ?? ''),
                'description'   => (string) ($t->note ?? RewardMessages::reasonForSource((string) ($t->source ?? ''))),
                'balance_after' => (int) ($t->balance_after ?? 0),
                'created_at'    => (string) ($t->created_at ?? ''),
            ];
        }

        $data = [
            'title'         => 'Referral & Reward',
            'referrals'     => $referrals,
            'counts'        => $counts,
            'issued'        => $issued,
            'redeemed'      => $redeemed,
            'reward_cost'   => round($redeemed * $pointValue, 2),
            'conversion'    => $conversion,
            'wallets'       => $wallets,
            'transactions'  => $transactions,
            'config'        => $config->all($ownerScope),
            'globalConfig'  => $config->all(0),
            'isSuperAdmin'  => $this->isSuperAdmin(),
            'ownerScope'    => $ownerScope,
            'customerNewUrl'=> route_to('route.customer.new'),
        ];

        return view('Zapi\rewards\web_admin', $data);
    }

    public function approve($id = null)
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }
        if ($redirect = $this->guardEdit()) {
            return $redirect;
        }

        $referral = (new ReferralModel())->find((int) $id);
        if (!$this->canActOn($referral)) {
            return redirect()->to(base_url('reward-center'))->with('rwd_error', 'You cannot act on this referral.');
        }
        $res = (new ReferralWorkflow())->approve((int) $id, $this->sessionUserId());
        return redirect()->to(base_url('reward-center'))
            ->with($res['ok'] ? 'rwd_success' : 'rwd_error', $res['message']);
    }

    public function reject($id = null)
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }
        if ($redirect = $this->guardEdit()) {
            return $redirect;
        }

        $referral = (new ReferralModel())->find((int) $id);
        if (!$this->canActOn($referral)) {
            return redirect()->to(base_url('reward-center'))->with('rwd_error', 'You cannot act on this referral.');
        }
        $reason = (string) ($this->request->getPost('reason') ?? '');
        $res = (new ReferralWorkflow())->reject((int) $id, $this->sessionUserId(), $reason);
        return redirect()->to(base_url('reward-center'))
            ->with($res['ok'] ? 'rwd_success' : 'rwd_error', $res['message']);
    }

    public function saveConfig()
    {
        if ($redirect = $this->guardAccess()) {
            return $redirect;
        }
        if ($redirect = $this->guardEdit()) {
            return $redirect;
        }

        $scope = (string) ($this->request->getPost('scope') ?? 'reseller');
        $isGlobal = $scope === 'global';
        if ($isGlobal && !$this->isSuperAdmin()) {
            return redirect()->to(base_url('reward-center'))->with('rwd_error', 'Only the platform owner can edit global config.');
        }
        $ownerScope = $isGlobal ? 0 : $this->resolveOwnerScope();

        $values = [];
        foreach (RewardConfigService::SPEC_DEFAULTS as $key => $_) {
            $v = $this->request->getPost($key);
            if ($v !== null && $v !== '') {
                $values[$key] = $v;
            }
        }
        $applied = (new RewardConfigService())->setMany($ownerScope, $values);

        return redirect()->to(base_url('reward-center'))
            ->with('rwd_success', 'Saved ' . count($applied) . ' setting(s) for ' . ($isGlobal ? 'global' : 'your') . ' scope.');
    }
}

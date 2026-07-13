<?php

namespace Zapi\Modules\Customer\Referral\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;
use Zapi\Modules\Shared\Rewards\Models\ReferralCodeModel;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Support\ReferralLinkBuilder;

class ReferralService extends CustomerBaseService
{
    /**
     * GET /api/customer/referral/overview
     * The referrer's code, shareable link and aggregate stats.
     */
    public function overview()
    {
        $userId = $this->actorUserId();
        if (!$userId) {
            return $this->respondError('Authentication required', 401, 'UNAUTHORIZED');
        }

        $user = $this->user_model->find($userId);
        if (!$user) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }
        $ownerId = (int) ($user->admin_id ?? 0);
        $name    = (string) ($user->name ?? '');

        $codeModel = new ReferralCodeModel();
        $code = $codeModel->getOrCreateForUser($userId, $ownerId, $name);

        $refModel = new ReferralModel();
        $counts = $refModel->countByStatus($userId);
        $earnedRow = $refModel->selectSum('points_awarded')->where('referrer_id', $userId)->first();
        $earned = (int) ($earnedRow->points_awarded ?? 0);

        return $this->respondSuccess([
            'referral_code' => $code,
            'referral_link' => $this->referralLink($code),
            'stats' => [
                'total'         => (int) $counts['total'],
                'pending'       => (int) $counts['pending'] + (int) $counts['flagged'],
                'verified'      => (int) $counts['verified'],
                'rejected'      => (int) $counts['rejected'],
                'earned_points' => $earned,
            ],
        ]);
    }

    /**
     * GET /api/customer/referral/history
     * Paginated list of this customer's referrals.
     */
    public function history()
    {
        $userId = $this->actorUserId();
        if (!$userId) {
            return $this->respondError('Authentication required', 401, 'UNAUTHORIZED');
        }

        $refModel = new ReferralModel();
        $pager = $this->getPaginationParams();

        $total = $refModel->where('referrer_id', $userId)->countAllResults(false);
        $rows = $refModel->where('referrer_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($pager['limit'], $pager['offset']);

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'            => (int) $r->id,
                'referred_name' => (string) ($r->referee_name ?? ''),
                'code'          => (string) ($r->referral_code ?? ''),
                'status'        => (string) ($r->status ?? 'pending'),
                'package'       => $this->packageName((int) ($r->package_id ?? 0)),
                'registered_at' => (string) ($r->created_at ?? ''),
                'points'        => (int) ($r->points_awarded ?? 0),
            ];
        }

        return $this->respondSuccess([
            'items'      => $items,
            'pagination' => $this->buildPaginationMeta($total, $pager['page'], $pager['limit'], count($items)),
        ]);
    }

    // ---- helpers ------------------------------------------------------

    private function actorUserId(): ?int
    {
        $actor = $this->resolveAccessTokenUserId();
        if ($actor !== null) {
            return $actor; // auth on — only ever the caller's own data
        }
        $param = (int) $this->getInputValue('user_id');
        return $param > 0 ? $param : null;
    }

    private function referralLink(string $code): string
    {
        return ReferralLinkBuilder::build($code);
    }

    private function packageName(int $packageId): string
    {
        if ($packageId <= 0) {
            return '';
        }
        try {
            $pkg = $this->package_model->find($packageId);
            if ($pkg) {
                return (string) (is_object($pkg) ? ($pkg->package_name ?? $pkg->name ?? '') : ($pkg['package_name'] ?? $pkg['name'] ?? ''));
            }
        } catch (\Throwable $e) {
            // best-effort
        }
        return '';
    }
}

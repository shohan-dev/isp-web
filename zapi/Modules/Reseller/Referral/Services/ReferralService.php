<?php

namespace Zapi\Modules\Reseller\Referral\Services;

use Zapi\Modules\Reseller\Core\Services\ResellerBaseService;
use Zapi\Modules\Shared\Rewards\Models\ReferralModel;
use Zapi\Modules\Shared\Rewards\Services\ReferralWorkflow;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

class ReferralService extends ResellerBaseService
{
    /**
     * GET /api/reseller/referrals/{resellerId}?status=
     * List referrals whose referred customers belong to this reseller.
     */
    public function list($resellerId = null)
    {
        $resellerId = (int) $resellerId;
        if ($resellerId <= 0) {
            return $this->respondError('Missing reseller id', 400, 'REQUEST_FAILED');
        }
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }

        $refModel = new ReferralModel();
        $status = (string) ($this->getInputValue('status') ?? '');

        $builder = $refModel->where('owner_id', $resellerId);
        if ($status !== '' && in_array($status, ['pending', 'verified', 'rejected', 'flagged'], true)) {
            $builder = $builder->where('status', $status);
        }

        $pager = $this->getPaginationParams();
        $total = (clone $builder)->countAllResults(false);
        $rows = $builder->orderBy('id', 'DESC')->findAll($pager['limit'], $pager['offset']);

        $items = array_map([$this, 'present'], $rows);

        return $this->respondSuccess([
            'items'      => $items,
            'pagination' => $this->buildPaginationMeta($total, $pager['page'], $pager['limit'], count($items)),
        ]);
    }

    public function details($resellerId = null, $referralId = null)
    {
        $resellerId = (int) $resellerId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }
        $referral = (new ReferralModel())->find((int) $referralId);
        if (!$referral || (int) $referral->owner_id !== $resellerId) {
            return $this->respondError('Referral not found', 404, 'REQUEST_FAILED');
        }
        return $this->respondSuccess($this->present($referral));
    }

    /**
     * POST /api/reseller/referrals/{resellerId}/{referralId}/approve
     * Approve & Activate: verify referral, activate the lead account + package,
     * award the referrer, audit-log and notify.
     */
    public function approve($resellerId = null, $referralId = null)
    {
        $resellerId = (int) $resellerId;
        $referralId = (int) $referralId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }

        $refModel = new ReferralModel();
        $referral = $refModel->find($referralId);
        if (!$referral || (int) $referral->owner_id !== $resellerId) {
            return $this->respondError('Referral not found', 404, 'REQUEST_FAILED');
        }
        // Ownership is enforced above; the shared workflow does the activation,
        // award, verification, audit and notification (same as the website path).
        $result = (new ReferralWorkflow())->approve($referralId, (int) $this->actorId());
        if (!$result['ok']) {
            return $this->respondError($result['message'], 400, 'REQUEST_FAILED');
        }

        return $this->respondSuccess([
            'message'        => $result['message'],
            'referral_id'    => $referralId,
            'referee_id'     => $result['referee_id'],
            'points_awarded' => $result['points'],
            'status'         => $result['status'],
        ]);
    }

    /**
     * POST /api/reseller/referrals/{resellerId}/{referralId}/reject
     */
    public function reject($resellerId = null, $referralId = null)
    {
        $resellerId = (int) $resellerId;
        $referralId = (int) $referralId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }

        $refModel = new ReferralModel();
        $referral = $refModel->find($referralId);
        if (!$referral || (int) $referral->owner_id !== $resellerId) {
            return $this->respondError('Referral not found', 404, 'REQUEST_FAILED');
        }
        $reason = (string) ($this->getInputValue('reason') ?? '');
        $result = (new ReferralWorkflow())->reject($referralId, (int) $this->actorId(), $reason);
        if (!$result['ok']) {
            return $this->respondError($result['message'], 400, 'REQUEST_FAILED');
        }

        return $this->respondSuccess([
            'message'     => $result['message'],
            'referral_id' => $referralId,
            'status'      => RewardSources::REFERRAL_REJECTED,
        ]);
    }

    // ---- helpers ------------------------------------------------------

    private function present($r): array
    {
        $referrer = $this->user_model->find((int) ($r->referrer_id ?? 0));
        return [
            'id'             => (int) $r->id,
            'referrer_id'    => (int) ($r->referrer_id ?? 0),
            'referrer_name'  => (string) ($referrer ? ($referrer->name ?? '') : ''),
            'referee_id'     => (int) ($r->referee_id ?? 0),
            'referee_name'   => (string) ($r->referee_name ?? ''),
            'referee_mobile' => (string) ($r->referee_mobile ?? ''),
            'referral_code'  => (string) ($r->referral_code ?? ''),
            'package_id'     => (int) ($r->package_id ?? 0),
            'status'         => (string) ($r->status ?? 'pending'),
            'fraud_reason'   => $r->fraud_reason ?? null,
            'points_awarded' => (int) ($r->points_awarded ?? 0),
            'registered_at'  => (string) ($r->created_at ?? ''),
            'verified_at'    => $r->verified_at ?? null,
        ];
    }
}

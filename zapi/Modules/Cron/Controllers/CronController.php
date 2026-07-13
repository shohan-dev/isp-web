<?php

namespace Zapi\Modules\Cron\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Shared\Rewards\Services\PaymentRewardReconciler;
use Zapi\Modules\Shared\Rewards\Services\RewardEngine;

/**
 * Secret-guarded cron endpoints for the reward engine. These run without JWT
 * (a server crontab / daycry job hits them) so they MUST be protected by a
 * shared secret configured as `reward.cronSecret` in the environment.
 *
 * Suggested schedule:
 *   *\/5  * * * *  curl "https://host/api/cron/reward-reconcile?secret=..."
 *   *\/10 * * * *  curl "https://host/api/cron/reward-release-holds?secret=..."
 *   0 2   * * *    curl "https://host/api/cron/reward-expire-points?secret=..."
 *   0 3   * * *    curl "https://host/api/cron/reward-loyalty?secret=..."
 *   30 6  * * *    curl "https://host/api/cron/reward-birthday?secret=..."
 */
class CronController extends BaseApiController
{
    private function checkSecret(): bool
    {
        $expected = (string) env('reward.cronSecret', '');
        if ($expected === '') {
            return false; // refuse until a secret is configured
        }
        $provided = (string) ($this->request->getGet('secret') ?? '');
        if ($provided === '') {
            $provided = (string) $this->request->getHeaderLine('X-Cron-Secret');
        }
        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function denied()
    {
        return $this->respondError('Forbidden', 403, 'FORBIDDEN');
    }

    public function reconcileRewards()
    {
        if (!$this->checkSecret()) {
            return $this->denied();
        }
        return $this->respondSuccess((new PaymentRewardReconciler())->reconcile());
    }

    public function releaseHolds()
    {
        if (!$this->checkSecret()) {
            return $this->denied();
        }
        return $this->respondSuccess(['released' => (new RewardEngine())->releaseExpiredHolds()]);
    }

    public function expirePoints()
    {
        if (!$this->checkSecret()) {
            return $this->denied();
        }
        return $this->respondSuccess(['expired_lots' => (new RewardEngine())->expireDuePoints()]);
    }

    public function loyalty()
    {
        if (!$this->checkSecret()) {
            return $this->denied();
        }
        return $this->respondSuccess((new PaymentRewardReconciler())->runLoyalty());
    }

    public function birthday()
    {
        if (!$this->checkSecret()) {
            return $this->denied();
        }
        return $this->respondSuccess((new PaymentRewardReconciler())->runBirthday());
    }
}

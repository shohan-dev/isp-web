<?php

namespace Zapi\Modules\Reseller\Reward\Services;

use Zapi\Modules\Reseller\Core\Services\ResellerBaseService;
use Zapi\Modules\Shared\Rewards\Services\RewardConfigService;

/**
 * HTTP wrapper around the shared RewardConfigService.
 *
 *   - Reseller scope (owner_id = resellerId): a reseller (or sAdmin/admin) sets
 *     overrides for their own customers.
 *   - Global scope (owner_id = 0): SaaS-wide defaults, platform owner (super_admin) only.
 */
class RewardConfigPortalService extends ResellerBaseService
{
    public function getResellerConfig($resellerId = null)
    {
        $resellerId = (int) $resellerId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }
        $config = new RewardConfigService();
        return $this->respondSuccess([
            'owner_id' => $resellerId,
            'config'   => $config->all($resellerId),
            'global'   => $config->all(0),
            'defaults' => RewardConfigService::SPEC_DEFAULTS,
        ]);
    }

    public function updateResellerConfig($resellerId = null)
    {
        $resellerId = (int) $resellerId;
        if (!$this->canAccessReseller($resellerId)) {
            return $this->respondError('You do not have access to this reseller scope', 403, 'FORBIDDEN');
        }
        $values = $this->collectConfigInput();
        if (empty($values)) {
            return $this->respondError('No valid config keys supplied', 400, 'REQUEST_FAILED');
        }
        $config = new RewardConfigService();
        $applied = $config->setMany($resellerId, $values);
        (new \App\Services\AuditService())->record(
            'reward_config.update_reseller',
            'reward_config',
            ['reseller_id' => $resellerId, 'applied' => $applied],
            null,
            null,
            $this->actorId()
        );
        return $this->respondSuccess([
            'message' => 'Reward configuration updated.',
            'applied' => $applied,
            'config'  => $config->all($resellerId),
        ]);
    }

    public function getGlobalConfig()
    {
        if (!$this->isPlatformOwner()) {
            return $this->respondError('Only the platform owner can view global reward config', 403, 'FORBIDDEN');
        }
        $config = new RewardConfigService();
        return $this->respondSuccess([
            'owner_id' => 0,
            'config'   => $config->all(0),
            'defaults' => RewardConfigService::SPEC_DEFAULTS,
        ]);
    }

    public function updateGlobalConfig()
    {
        if (!$this->isPlatformOwner()) {
            return $this->respondError('Only the platform owner can change global reward config', 403, 'FORBIDDEN');
        }
        $values = $this->collectConfigInput();
        if (empty($values)) {
            return $this->respondError('No valid config keys supplied', 400, 'REQUEST_FAILED');
        }
        $config = new RewardConfigService();
        $applied = $config->setMany(0, $values);
        (new \App\Services\AuditService())->record(
            'reward_config.update_global',
            'reward_config',
            ['applied' => $applied],
            null,
            null,
            $this->actorId()
        );
        return $this->respondSuccess([
            'message' => 'Global reward configuration updated.',
            'applied' => $applied,
            'config'  => $config->all(0),
        ]);
    }

    /**
     * Accept either a flat body of known keys, or {config:{...}}.
     */
    private function collectConfigInput(): array
    {
        $raw = $this->request->getJSON(true);
        if (!is_array($raw)) {
            $raw = $this->request->getRawInput();
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        if (isset($raw['config']) && is_array($raw['config'])) {
            $raw = $raw['config'];
        }

        $out = [];
        foreach (RewardConfigService::SPEC_DEFAULTS as $key => $_) {
            if (array_key_exists($key, $raw) && $raw[$key] !== '') {
                $out[$key] = $raw[$key];
            }
        }
        return $out;
    }
}

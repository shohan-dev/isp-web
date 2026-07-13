<?php

namespace Zapi\Modules\Shared\Rewards\Services;

use Zapi\Modules\Shared\Rewards\Models\RewardSettingModel;
use Zapi\Modules\Shared\Rewards\Support\RewardSources;

/**
 * Resolves reward configuration with a safe fallback chain:
 *
 *   per-reseller override (owner_id = resellerId)
 *      -> global super-admin default (owner_id = 0)
 *         -> hardcoded spec default (SPEC_DEFAULTS)
 *
 * All callers go through here so a missing/blank setting can never produce a
 * null reward rule. Defaults mirror the product specification.
 */
class RewardConfigService
{
    /** Spec defaults — the floor of the fallback chain. */
    public const SPEC_DEFAULTS = [
        RewardSources::KEY_REFERRAL_POINTS       => 2,
        RewardSources::KEY_EARLY_RENEWAL_POINTS  => 2,
        RewardSources::KEY_STREAK_POINTS         => 5,
        RewardSources::KEY_LOYALTY_6M_POINTS     => 10,
        RewardSources::KEY_LOYALTY_12M_POINTS    => 25,
        RewardSources::KEY_UPGRADE_POINTS        => 5,
        RewardSources::KEY_ONLINE_PAYMENT_POINTS => 1,
        RewardSources::KEY_AUTOPAY_POINTS        => 3,
        RewardSources::KEY_FEEDBACK_POINTS       => 1,
        RewardSources::KEY_TICKET_RATING_POINTS  => 1,
        RewardSources::KEY_BIRTHDAY_POINTS       => 5,
        RewardSources::KEY_POINT_VALUE_BDT       => 1,   // 1 point = 1 BDT
        RewardSources::KEY_POINT_EXPIRY_DAYS     => 365, // 0 = never expire
        RewardSources::KEY_MAX_REDEEM_PERCENT    => 100, // cap redemption to % of price
        RewardSources::KEY_REFERRAL_ENABLED      => 1,
        RewardSources::KEY_REDEMPTION_ENABLED    => 1,
        RewardSources::KEY_FEEDBACK_MONTHLY_CAP  => 1,
    ];

    private RewardSettingModel $settings;

    /** In-request cache keyed by "ownerId:key". */
    private array $cache = [];

    public function __construct(?RewardSettingModel $settings = null)
    {
        $this->settings = $settings ?? new RewardSettingModel();
    }

    /**
     * Resolve a numeric/int setting for an owner scope.
     */
    public function getInt(int $ownerId, string $key): int
    {
        return (int) $this->get($ownerId, $key);
    }

    /**
     * Resolve a float setting (e.g. point value in BDT).
     */
    public function getFloat(int $ownerId, string $key): float
    {
        return (float) $this->get($ownerId, $key);
    }

    public function isEnabled(int $ownerId, string $key): bool
    {
        $v = (string) $this->get($ownerId, $key);
        return $v === '1' || strtolower($v) === 'true' || strtolower($v) === 'yes';
    }

    /**
     * Raw resolved value (reseller -> global -> spec default).
     */
    public function get(int $ownerId, string $key)
    {
        $cacheKey = $ownerId . ':' . $key;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $value = null;
        if ($ownerId > 0) {
            $value = $this->settings->getValue($ownerId, $key);
        }
        if ($value === null || $value === '') {
            $value = $this->settings->getValue(0, $key);
        }
        if ($value === null || $value === '') {
            $value = self::SPEC_DEFAULTS[$key] ?? 0;
        }

        return $this->cache[$cacheKey] = $value;
    }

    /**
     * Full resolved config map for an owner scope (for the config UI / API).
     */
    public function all(int $ownerId): array
    {
        $out = [];
        foreach (array_keys(self::SPEC_DEFAULTS) as $key) {
            $out[$key] = $this->get($ownerId, $key);
        }
        return $out;
    }

    /**
     * Persist one value to a scope and clear the cache for it.
     */
    public function set(int $ownerId, string $key, $value): bool
    {
        if (!array_key_exists($key, self::SPEC_DEFAULTS)) {
            return false; // never store unknown keys
        }
        unset($this->cache[$ownerId . ':' . $key]);
        return $this->settings->setValue($ownerId, $key, (string) $value);
    }

    /**
     * Bulk update a scope; only known keys are accepted.
     *
     * @return string[] keys that were applied
     */
    public function setMany(int $ownerId, array $values): array
    {
        $applied = [];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::SPEC_DEFAULTS) && $this->set($ownerId, $key, $value)) {
                $applied[] = $key;
            }
        }
        return $applied;
    }
}

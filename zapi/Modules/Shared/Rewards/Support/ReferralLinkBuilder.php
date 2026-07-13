<?php

namespace Zapi\Modules\Shared\Rewards\Support;

/**
 * Builds shareable referral registration URLs for web + app.
 */
class ReferralLinkBuilder
{
    public static function build(string $code): string
    {
        $code = strtoupper(trim($code));
        $base = trim((string) env('reward.referralBaseUrl', ''));
        if ($base === '') {
            $base = rtrim(base_url('register'), '/');
        }
        $sep = (strpos($base, '?') !== false) ? '&' : '?';
        return $base . $sep . 'ref=' . rawurlencode($code);
    }
}

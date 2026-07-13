<?php

namespace App\Enums;

/**
 * Canonical `payments.status` values (Phase 5 — code/service refactor).
 *
 * One typed source of truth for the payment lifecycle states, replacing the
 * 'successful' / 'pending' / 'failed' literals. Backed by the exact DB string,
 * so `PaymentStatus::Successful->value === 'successful'`. Adopt incrementally.
 */
enum PaymentStatus: string
{
    case Successful = 'successful';
    case Pending    = 'pending';
    case Failed     = 'failed';

    /** Every status string. */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    /** Safe parse: the enum for a known string, or null for unknown/empty/null. */
    public static function tryFromString(?string $status): ?self
    {
        return $status === null || $status === '' ? null : self::tryFrom($status);
    }

    /** A settled (money-received) payment — the only state that counts as paid. */
    public function isSettled(): bool
    {
        return $this === self::Successful;
    }
}

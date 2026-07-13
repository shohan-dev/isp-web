<?php

namespace App\Services;

/**
 * BillingService — the single, canonical renewal/upgrade pricing rule (Phase 5 / MT-1).
 *
 * CANONICAL RULE (product decision, 2026-06-18): PURE PRORATION.
 * A renewal or upgrade is priced as the new monthly price prorated across the
 * billed days:  price = (newMonthly / 30) * days.  There is no separate
 * full-month component and no upgrade delta — the same rule applies to every
 * channel, so web and mobile reconcile.
 *
 * HISTORY (now retired): the web upgrade path used to charge a full new month
 * PLUS a prorated upgrade delta (750 for a 300->600 / 15-day upgrade) and the
 * mobile/zapi path used |int(oldMonthly/30*days) - int(newMonthly)| (450 for the
 * same event). Both are superseded by quote(). The pre-unification formulas are
 * preserved for the record in
 * tests/characterization/RenewalPricingCharacterizationTest.
 *
 * MIGRATION STATUS: the web call sites — the Subscription upgrade path and the
 * two Customer proration sites — are on quote(). The zapi reseller path still
 * prices inline in its own module and moves onto quote() in the TenantContext
 * step (Phase 5 step 5), which also removes the $enforceFund bypass.
 *
 * Pure arithmetic — no DB/session/router — so it is fully unit-testable. The
 * atomic fund-debited renewal stays in App\Services\FundService; this service
 * answers only "how much" and "must the fund cover it".
 */
final class BillingService
{
    private const DAYS_PER_MONTH = 30;

    /**
     * Canonical price for a renewal/upgrade: the new monthly price prorated
     * across the billed days.  price = (newMonthly / 30) * days.
     */
    public function quote(float $newMonthly, int $days): float
    {
        return ($newMonthly / self::DAYS_PER_MONTH) * $days;
    }

    /**
     * Whether the fund balance must cover the charge before the renewal proceeds.
     * The web path always enforces; zapi historically SKIPS the check when the
     * payment status is 'pending' (the $enforceFund bypass — removed when zapi
     * moves onto this service).
     * Source: zapi/.../SubscriptionServicePart02Segment.php:100.
     */
    public function enforcesFund(string $paymentStatus): bool
    {
        return strtolower(trim($paymentStatus)) !== 'pending';
    }
}

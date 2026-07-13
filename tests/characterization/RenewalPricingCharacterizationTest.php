<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Characterization (golden-master) test for the THREE divergent renewal-pricing
 * formulas in isp-core. See docs/production-optimization/08-CODE-ARCHITECTURE-REFACTOR.md §9.1.
 *
 * WHY THIS EXISTS
 * ---------------
 * The same customer renewal is priced three different ways depending on the
 * channel, so the books cannot reconcile across web vs mobile. The real
 * formulas are buried inline in I/O-heavy controller/service methods (router +
 * session coupled), so they cannot be exercised directly without a full
 * integration harness. This test instead PINS THE ARITHMETIC each path performs,
 * transcribed verbatim from the cited source lines, so that:
 *
 *   1. the divergence is executable and visible (not just prose), and
 *   2. when Phase 5 introduces a single BillingService, this file is the
 *      regression gate: every change to a number below must be a CONSCIOUS
 *      decision (update the expected value + a comment saying why), never an
 *      accident.
 *
 * These helpers are transcriptions of production code as of branch `optimize`
 * (2026-06-17). If you change the source formula, change the matching helper
 * here in the same commit and record it in the change log of
 * docs/production-optimization/PROGRESS-TRACKER.md.
 *
 * @internal
 */
final class RenewalPricingCharacterizationTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------------
    // Formula transcriptions (verbatim arithmetic, with source citations)
    // ---------------------------------------------------------------------

    /**
     * WEB — single-customer renewal.
     * Source: app/Controllers/Customer.php:5420-5421 (mirrored at :3873-3874)
     *   $price_per_day = (float) $tprice / 30;
     *   $price         = $price_per_day * $difference;
     * i.e. pure proration of the NEW monthly price across the billed days.
     */
    private static function customerWebPrice(float $newMonthly, int $days): float
    {
        $pricePerDay = $newMonthly / 30;

        return $pricePerDay * $days;
    }

    /**
     * WEB — package upgrade.
     * Source: app/Controllers/Subscription.php:330-343
     *   $old_daily_rate     = $old_monthly_price / 30;
     *   $new_daily_rate     = $new_monthly_price / 30;
     *   $upgrade_difference = ($new_daily_rate - $old_daily_rate) * $difference;
     *   if ($upgrade_difference < 0) $upgrade_difference = 0;
     *   $price = round($new_monthly_price + $upgrade_difference);
     * i.e. a FULL new month PLUS the prorated upgrade delta for the remaining days.
     */
    private static function subscriptionWebUpgradePrice(float $oldMonthly, float $newMonthly, int $remainingDays): float
    {
        $oldDaily    = $oldMonthly / 30;
        $newDaily    = $newMonthly / 30;
        $upgradeDiff = ($newDaily - $oldDaily) * $remainingDays;
        if ($upgradeDiff < 0) {
            $upgradeDiff = 0;
        }

        return round($newMonthly + $upgradeDiff);
    }

    /**
     * MOBILE (zapi) — reseller renewal/upgrade.
     *   changed package -> $newprice = ($oldFull / 30.0) * $remainingDays;
     *                      $price    = |(int)$newprice - (int)$newFull|;   // **RETIRED 2026-06-21**
     *   same package    -> $price    = $tprice;  // full month — STILL CURRENT in production
     *
     * The CHANGED-package branch (450 for 300->600/15d) is RETIRED: production now prices
     * a package change via BillingService::quote(newFull, daysToExpire) = 300 — see the
     * "package change now unified" tests below. The same-package full-month branch is
     * UNCHANGED (matches the web renew path); prorating it is a separate web-wide decision.
     */
    private static function zapiResellerPrice(float $oldMonthly, float $newMonthly, int $remainingDays, bool $packageChanged): float
    {
        if ($packageChanged) {
            $newprice    = ($oldMonthly / 30.0) * $remainingDays;
            $newpriceInt = is_numeric($newprice) ? (int) $newprice : 0;
            $xpriceInt   = (int) $newMonthly;

            return (float) (max($newpriceInt, $xpriceInt) - min($newpriceInt, $xpriceInt)); // == abs()
        }

        return $newMonthly;
    }

    /**
     * MOBILE (zapi) fund-enforcement gate.
     * Source: zapi/.../SubscriptionServicePart02Segment.php:100
     *   $enforceFund = strtolower(trim($paymentStatus)) !== 'pending';
     * The web path always enforces fund; zapi SKIPS the fund check for 'pending'.
     */
    private static function zapiEnforcesFund(string $paymentStatus): bool
    {
        return strtolower(trim($paymentStatus)) !== 'pending';
    }

    // ---------------------------------------------------------------------
    // Pinned golden values per formula
    // ---------------------------------------------------------------------

    /**
     * @dataProvider provideCustomerWebScenarios
     */
    public function testCustomerWebPriceIsPureProration(float $newMonthly, int $days, float $expected): void
    {
        $this->assertSame($expected, self::customerWebPrice($newMonthly, $days));
    }

    public static function provideCustomerWebScenarios(): array
    {
        return [
            'full 30-day month'      => [600.0, 30, 600.0],
            '15 days of a 600 pkg'   => [600.0, 15, 300.0],
            '15 days of a 300 pkg'   => [300.0, 15, 150.0],
            'zero days'              => [600.0, 0, 0.0],
        ];
    }

    /**
     * @dataProvider provideSubscriptionUpgradeScenarios
     */
    public function testSubscriptionWebUpgradePrice(float $old, float $new, int $days, float $expected): void
    {
        $this->assertSame($expected, self::subscriptionWebUpgradePrice($old, $new, $days));
    }

    public static function provideSubscriptionUpgradeScenarios(): array
    {
        return [
            'upgrade 300->600, 15d'  => [300.0, 600.0, 15, 750.0],  // round(600 + (20-10)*15)
            'same price 600, 15d'    => [600.0, 600.0, 15, 600.0],  // delta 0 -> full month
            'downgrade clamped'      => [600.0, 300.0, 15, 300.0],  // negative delta -> 0 -> full new month
        ];
    }

    /**
     * @dataProvider provideZapiScenarios
     */
    public function testZapiResellerPrice(float $old, float $new, int $days, bool $changed, float $expected): void
    {
        $this->assertSame($expected, self::zapiResellerPrice($old, $new, $days, $changed));
    }

    public static function provideZapiScenarios(): array
    {
        return [
            'changed 300->600, 15d'  => [300.0, 600.0, 15, true, 450.0],  // |(int)150 - 600|
            'same package'           => [600.0, 600.0, 30, false, 600.0], // full month
        ];
    }

    public function testZapiSkipsFundCheckOnlyForPending(): void
    {
        $this->assertFalse(self::zapiEnforcesFund('pending'));
        $this->assertFalse(self::zapiEnforcesFund('  Pending '));
        $this->assertTrue(self::zapiEnforcesFund('successful'));
        $this->assertTrue(self::zapiEnforcesFund('paid'));
    }

    // ---------------------------------------------------------------------
    // Reconciliation status: web unified to the canonical rule; zapi pending
    // ---------------------------------------------------------------------

    /**
     * PACKAGE CHANGE — now unified (2026-06-21). A reseller/customer package change is
     * priced by the ONE canonical rule BillingService::quote(newFull, daysToExpire)
     * across ALL channels: web upgrade (Subscription.php:335), reseller-admin
     * (SubscriptionServicePart02Segment.php) and the gateway-bound customer self-serve
     * path (Customer/.../SubscriptionService.php getResellerPrice/getRegularUserPrice).
     * For the same event (300 -> 600, 15 days to the new expiry) EVERY channel now
     * charges 300. The retired formulas (web-upgrade 750, zapi |..| 450, self-serve
     * max(new - old/30*d,0) which also = 450 here) are kept above only as records.
     */
    public function testPackageChangeNowUnifiedAcrossChannels(): void
    {
        $old  = 300.0;
        $new  = 600.0;
        $days = 15;
        $q    = new \App\Services\BillingService();

        // Canonical change price, asserted against the REAL service:
        $this->assertSame(300.0, $q->quote($new, $days));

        // The web renewal transcription agrees on the same number:
        $this->assertSame($q->quote($new, $days), self::customerWebPrice($new, $days));

        // Retired formulas (records) both diverged from the canonical 300:
        $this->assertSame(750.0, self::subscriptionWebUpgradePrice($old, $new, $days)); // retired web upgrade
        $this->assertSame(450.0, self::zapiResellerPrice($old, $new, $days, true));     // retired zapi change
        $this->assertNotSame(
            self::zapiResellerPrice($old, $new, $days, true), // retired 450
            $q->quote($new, $days),                           // unified 300
            'the reseller/customer package change moved from the retired 450 to the canonical 300'
        );
    }

    // ---------------------------------------------------------------------
    // SAME-PACKAGE RENEWAL — DELIBERATELY left flat-month (2026-06-21).
    // The change-pricing unification did NOT prorate same-package renewals: the web
    // renew path also charges a flat month, so prorating mobile-only would *diverge*
    // from web (anti-reconciliation). The "two meanings of days" is therefore deferred
    // as a separate, web-wide decision — these tests pin that same-package stays flat.
    // ---------------------------------------------------------------------

    /**
     * Same-package (or first-assignment) renewal still charges the FULL reseller month
     * regardless of the chosen period length — a 60- or 90-day same-package renewal
     * bills ONE month. UNCHANGED by the 2026-06-21 change-pricing unification.
     */
    public function testZapiSamePackageRenewalChargesFullMonthRegardlessOfPeriod(): void
    {
        $tprice = 600.0;
        foreach ([15, 30, 60, 90] as $periodDays) {
            $this->assertSame(
                $tprice,
                self::zapiResellerPrice($tprice, $tprice, $periodDays, false),
                "same-package price stays full-month ({$tprice}) for a {$periodDays}-day period"
            );
        }
    }

    /**
     * The "two meanings of days" is DEFERRED, not resolved. Same-package renewal is
     * flat-month (matches web renew); quote() would prorate by period (60d -> 1200).
     * They diverge ON PURPOSE for now — prorating same-package is a web-wide decision,
     * not part of the change-pricing unification. If a future change makes them agree,
     * that is a CONSCIOUS pricing decision (update this test + the PROGRESS-TRACKER).
     */
    public function testSamePackageStillFlatWhileQuoteWouldProrate(): void
    {
        $tprice = 600.0;

        // 30-day renewal: flat and quote() coincide -> no observable difference.
        $this->assertSame(600.0, self::zapiResellerPrice($tprice, $tprice, 30, false));
        $this->assertSame(600.0, self::customerWebPrice($tprice, 30));

        // 60-day renewal: flat stays one month (600); quote() would be two (1200).
        $flat60  = self::zapiResellerPrice($tprice, $tprice, 60, false);
        $quote60 = self::customerWebPrice($tprice, 60);
        $this->assertSame(600.0, $flat60, 'same-package renewal still bills one month for any period');
        $this->assertSame(1200.0, $quote60, 'quote() would prorate the full 60-day period to two months');
        $this->assertNotSame($flat60, $quote60, 'deferred deliberately: same-package proration is a web-wide decision');
    }
}

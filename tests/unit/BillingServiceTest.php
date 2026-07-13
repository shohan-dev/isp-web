<?php

use App\Services\BillingService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pins App\Services\BillingService to the single canonical pricing rule —
 * pure proration, (newMonthly / 30) * days (product decision 2026-06-18).
 * The historical per-channel formulas are documented in
 * tests/characterization/RenewalPricingCharacterizationTest.
 *
 * @internal
 */
final class BillingServiceTest extends CIUnitTestCase
{
    private BillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billing = new BillingService();
    }

    /**
     * @dataProvider provideQuoteScenarios
     */
    public function testQuoteIsPureProration(float $newMonthly, int $days, float $expected): void
    {
        $this->assertSame($expected, $this->billing->quote($newMonthly, $days));
    }

    public static function provideQuoteScenarios(): array
    {
        return [
            'full 30-day month'    => [600.0, 30, 600.0],
            '15 days of a 600 pkg' => [600.0, 15, 300.0],
            '15 days of a 300 pkg' => [300.0, 15, 150.0],
            'zero days'            => [600.0, 0, 0.0],
        ];
    }

    /**
     * The reconciliation fix, made executable: the same 300 -> 600 / 15-day
     * upgrade that historically cost 300 (web renewal) / 750 (web upgrade) /
     * 450 (mobile) now has ONE canonical price across channels.
     */
    public function testUpgradeNowHasOneCanonicalPrice(): void
    {
        $this->assertSame(300.0, $this->billing->quote(600.0, 15));
    }

    public function testEnforcesFundSkipsOnlyPending(): void
    {
        $this->assertFalse($this->billing->enforcesFund('pending'));
        $this->assertFalse($this->billing->enforcesFund('  Pending '));
        $this->assertTrue($this->billing->enforcesFund('successful'));
        $this->assertTrue($this->billing->enforcesFund('paid'));
    }
}

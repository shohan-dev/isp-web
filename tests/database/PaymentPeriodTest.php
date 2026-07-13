<?php

use App\Models\Payment;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pins Payment::periodFor() — the year-aware billing-month derivation that the
 * model's beforeInsert callback uses to keep the Phase 4 `period` column current
 * on new payments. Mirrors the backfill's DATE_FORMAT(created_at, '%Y-%m-01').
 *
 * @internal
 */
final class PaymentPeriodTest extends CIUnitTestCase
{
    public function testDerivesFirstOfMonthFromCreatedAt(): void
    {
        $this->assertSame('2025-08-01', Payment::periodFor('2025-08-15 10:30:00'));
        $this->assertSame('2026-02-01', Payment::periodFor('2026-02-28'));
        $this->assertSame('2025-12-01', Payment::periodFor('2025-12-01 00:00:00'));
    }

    public function testFallsBackToCurrentMonthWhenCreatedAtMissing(): void
    {
        $this->assertSame(date('Y-m-01'), Payment::periodFor(null));
        $this->assertSame(date('Y-m-01'), Payment::periodFor(''));
    }

    public function testGarbageCreatedAtDoesNotFatalAndFallsBack(): void
    {
        // Unparseable input must not throw — it degrades to the current month.
        $this->assertSame(date('Y-m-01'), Payment::periodFor('not-a-date'));
    }
}

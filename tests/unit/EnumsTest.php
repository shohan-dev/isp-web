<?php

use App\Enums\PaymentStatus;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pins the canonical status enum values to the EXACT DB strings, so a
 * rename can never silently diverge from the magic strings the rest of the app
 * still uses.
 *
 * @internal
 */
final class EnumsTest extends CIUnitTestCase
{
    public function testPaymentStatusValuesMatchTheDbStrings(): void
    {
        $this->assertSame('successful', PaymentStatus::Successful->value);
        $this->assertSame('pending', PaymentStatus::Pending->value);
        $this->assertSame('failed', PaymentStatus::Failed->value);
        $this->assertSame(['successful', 'pending', 'failed'], PaymentStatus::values());
    }

    public function testPaymentStatusSettled(): void
    {
        $this->assertTrue(PaymentStatus::Successful->isSettled());
        $this->assertFalse(PaymentStatus::Pending->isSettled());
        $this->assertFalse(PaymentStatus::Failed->isSettled());
        $this->assertSame(PaymentStatus::Pending, PaymentStatus::tryFromString('pending'));
        $this->assertNull(PaymentStatus::tryFromString('bogus'));
    }
}

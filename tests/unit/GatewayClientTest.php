<?php

use App\Libraries\EpsPayment;
use App\Libraries\PaystationPayment;
use App\Libraries\ShurjopayPayment;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Contract tests for the EPS / shurjoPay / PayStation gateway clients.
 *
 * These pin the two things that silently lose money if they drift:
 *   1. the EPS x-hash signature algorithm (base64(HMAC-SHA512(msg, key))), and
 *   2. the sandbox vs live API hosts for all three gateways.
 *
 * They do not hit the network — only pure, deterministic behaviour is asserted.
 *
 * @internal
 */
final class GatewayClientTest extends CIUnitTestCase
{
    private function readPrivate(object $obj, string $prop)
    {
        $ref = new \ReflectionProperty($obj, $prop);
        $ref->setAccessible(true);

        return $ref->getValue($obj);
    }

    private function callPrivate(object $obj, string $method, array $args)
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($obj, $args);
    }

    public function testEpsHashMatchesReferenceVector(): void
    {
        $eps = new EpsPayment();
        $eps->setConfig(['hash_key' => 'myHashKey123']);

        // Reference vector computed independently: base64(HMAC-SHA512('sp_user','myHashKey123')).
        $this->assertSame(
            '5wCZQ1IIwvNPCe7rMtt2j4H23XS23GHVhOQ+uGCnUb65KyW4iKZhvZdpPZB9brBcWZ7N0ZywrdGrzh+KYgYOWw==',
            $this->callPrivate($eps, 'hash', ['sp_user'])
        );
    }

    public function testEpsHostsSwitchOnSandboxFlag(): void
    {
        $live = new EpsPayment();
        $live->setConfig(['sandbox' => false]);
        $this->assertSame('https://pgapi.eps.com.bd/v1/', $this->readPrivate($live, 'baseUrl'));

        $sandbox = new EpsPayment();
        $sandbox->setConfig(['sandbox' => true]);
        $this->assertSame('https://sandboxpgapi.eps.com.bd/v1/', $this->readPrivate($sandbox, 'baseUrl'));
    }

    public function testShurjopayHostsSwitchOnSandboxFlag(): void
    {
        $live = new ShurjopayPayment();
        $live->setConfig(['sandbox' => false]);
        $this->assertSame('https://engine.shurjopayment.com', $this->readPrivate($live, 'baseUrl'));

        $sandbox = new ShurjopayPayment();
        $sandbox->setConfig(['sandbox' => true]);
        $this->assertSame('https://sandbox.shurjopayment.com', $this->readPrivate($sandbox, 'baseUrl'));
    }

    public function testPaystationHostsSwitchOnSandboxFlag(): void
    {
        $live = new PaystationPayment();
        $live->setConfig(['sandbox' => false]);
        $this->assertSame('https://api.paystation.com.bd', $this->readPrivate($live, 'baseUrl'));

        $sandbox = new PaystationPayment();
        $sandbox->setConfig(['sandbox' => true]);
        $this->assertSame('https://sandbox.paystation.com.bd', $this->readPrivate($sandbox, 'baseUrl'));
    }
}

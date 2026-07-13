<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guards the worker's job-dispatch wiring (Phase 3.5 / M2) WITHOUT touching real
 * routers. A malformed `router_enable` job — or any unknown type — must FAIL
 * (throw) so the queue retries and finally dead-letters it; it must never
 * silently no-op a paid customer's provisioning. The `noop` smoke type must
 * return cleanly.
 *
 * Only the payload-guard / routing branches are exercised here (they throw
 * before any network or model call), so no MikroTik / DB access is needed.
 *
 * @internal
 */
final class QueueRouterEnableTest extends CIUnitTestCase
{
    /** A QueueWork with the BaseCommand constructor bypassed + dispatch() exposed. */
    private function worker(): object
    {
        return new class extends \App\Commands\QueueWork {
            public function __construct()
            {
                // bypass BaseCommand's (LoggerInterface, Commands) ctor — dispatch()
                // needs none of it for the routing/guard branches under test.
            }

            public function run(array $params)
            {
                return 0;
            }

            public function dispatchPublic(object $job): void
            {
                $this->dispatch($job);
            }
        };
    }

    public function testRouterEnableWithNoIdThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('router_enable job missing');
        $this->worker()->dispatchPublic((object) ['type' => 'router_enable', 'payload' => json_encode([])]);
    }

    public function testUnknownJobTypeThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler registered');
        $this->worker()->dispatchPublic((object) ['type' => 'does_not_exist', 'payload' => '[]']);
    }

    public function testNoopJobReturnsWithoutThrowing(): void
    {
        $this->worker()->dispatchPublic((object) ['type' => 'noop', 'payload' => '[]']);
        $this->assertTrue(true); // reaching here = noop handled silently
    }
}

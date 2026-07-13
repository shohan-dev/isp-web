<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression gate for the logged latent bug: the PPPoE enable helpers used to
 * return TRUE for a null/empty ppp id, so a failed secret lookup registered as a
 * successful enable (paid customer never connected, nothing retried). An empty id
 * must now read as a FAILURE so the caller's secret fallback runs.
 *
 * Both guards return BEFORE touching the RouterOS client / opening a socket, so a
 * null client and a bogus router id are safe inputs here — nothing external is hit.
 */
final class RouterEnableGuardTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('router');
    }

    public function testApiEnableReturnsFalseForEmptyId(): void
    {
        $this->assertFalse(enablePPPoEUser(null, ''), 'empty string id must fail');
        $this->assertFalse(enablePPPoEUser(null, null), 'null id must fail');
        $this->assertFalse(enablePPPoEUser(null, 0), 'zero id must fail');
    }

    public function testFsockEnableReturnsFalseForEmptyId(): void
    {
        // Guard returns before connect_using_Fsocket(), so the router id is irrelevant.
        $this->assertFalse(enablePPPoEUserFsock(0, ''), 'empty string id must fail');
        $this->assertFalse(enablePPPoEUserFsock(0, null), 'null id must fail');
    }

    public function testSecretEnableReturnsFalseForNullClientOrEmptySecret(): void
    {
        // routerClient() returns null on circuit-open / not-found / connect-fail, and
        // null->query() throws \Error which the function's catch (Exception) misses.
        // Guard returns before touching the client, so a bogus client is safe here.
        $this->assertFalse(enablePPPoEUser_by_pppoe_secret(null, 'anything'), 'null client must fail');
        $this->assertFalse(enablePPPoEUser_by_pppoe_secret(new \stdClass(), ''), 'empty secret must fail');
        $this->assertFalse(enablePPPoEUser_by_pppoe_secret(new \stdClass(), null), 'null secret must fail');
    }
}

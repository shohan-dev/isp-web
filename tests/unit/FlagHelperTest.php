<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Validates the kill-switch flag() helper (Phase 2 / Phase 6 §7) against an
 * in-memory mock cache — no Redis/file dependency.
 *
 * @internal
 */
final class FlagHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper('flag');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    public function testUnsetFlagReturnsTheCallerDefault(): void
    {
        $this->assertFalse(flag('degrade_mode'));            // default false
        $this->assertTrue(flag('live_router_widgets', true)); // default true
    }

    public function testSetThenGet(): void
    {
        $this->assertTrue(setFlag('degrade_mode', true));
        $this->assertTrue(flag('degrade_mode'));

        setFlag('degrade_mode', false);
        $this->assertFalse(flag('degrade_mode'));
    }

    public function testClearRevertsToDefault(): void
    {
        setFlag('live_router_widgets', false);
        $this->assertFalse(flag('live_router_widgets', true));

        clearFlag('live_router_widgets');
        $this->assertTrue(flag('live_router_widgets', true)); // back to default
    }
}

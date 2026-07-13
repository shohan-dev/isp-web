<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Validates the L2 settings-cache plumbing (Phase 2 / C4): sensitive-key
 * classification, the version-bump invalidation stamp, and that getSetting
 * returns its default (and is consistent) for an unknown key against the
 * (empty) test DB while exercising the cache path with a MockCache.
 *
 * @internal
 */
final class SettingsCacheTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper('utility');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    public function testSensitiveKeysAreNeverCached(): void
    {
        foreach (['app_logo', 'app_icon', 'default_sms_gateway', 'bkashpg_app_secret', 'sslcommerz_store_passwd', 'smtp_password', 'greenwebsms_token'] as $k) {
            $this->assertTrue(isSensitiveSettingKey($k), "{$k} must be treated as sensitive");
        }
    }

    public function testNonSensitiveKeysAreCacheable(): void
    {
        foreach (['app_slogan', 'app_title', 'company_address', 'theme_color'] as $k) {
            $this->assertFalse(isSensitiveSettingKey($k), "{$k} should be cacheable");
        }
    }

    public function testVersionBumpChangesTheStamp(): void
    {
        $v0 = settingsCacheVersion();
        bumpSettingsCacheVersion();
        $this->assertSame($v0 + 1, settingsCacheVersion());
    }

    public function testGetSettingReturnsDefaultForUnknownKeyAndStaysConsistent(): void
    {
        // userId null -> no users-table dependency; unknown key -> default.
        $first  = getSetting('nonexistent_l2_test_key', 'fallback_value', null);
        $second = getSetting('nonexistent_l2_test_key', 'fallback_value', null);

        $this->assertSame('fallback_value', $first);
        $this->assertSame('fallback_value', $second);
    }
}

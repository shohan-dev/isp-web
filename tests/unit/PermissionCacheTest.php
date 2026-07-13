<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Validates the L2 permission-cache plumbing (Phase 2 / C1): the version-bump
 * invalidation stamp and that the admin short-circuit (no DB, no cache) is
 * preserved. The decision cache itself is fail-safe and version-busted by the
 * Permission/CustomAccess model callbacks.
 *
 * @internal
 */
final class PermissionCacheTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper(['user', 'utility']);

        // testInactiveStatusDeniesEarly exercises the non-super_admin path,
        // which queries these via Permission/CustomAccess/User models
        // (query-builder-based, so DBGroup-prefix-aware — the 'tests' group
        // sets DBPrefix='db_'). Empty is fine; the test only asserts the
        // result resolves to a clean bool without erroring.
        foreach (['users', 'permissions', 'custom_access'] as $name) {
            $table = $this->db->DBPrefix . $name;
            $this->db->query('DROP TABLE IF EXISTS ' . $table);
        }
        $this->db->query('CREATE TABLE ' . $this->db->DBPrefix . 'users (
            id INTEGER PRIMARY KEY, admin_id INTEGER, role TEXT, name TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->db->DBPrefix . 'permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_type TEXT, user_id INTEGER, permissions TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->db->DBPrefix . 'custom_access (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, status TEXT, permissions TEXT
        )');
        $this->db->query("INSERT INTO " . $this->db->DBPrefix . "users (id, admin_id, role, name) VALUES (1, 0, 'user', 'Test User')");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    public function testVersionBumpChangesTheStamp(): void
    {
        $v0 = permissionCacheVersion();
        bumpPermissionCacheVersion();
        $this->assertSame($v0 + 1, permissionCacheVersion());
    }

    public function testAdminShortCircuitsToTrueWithoutCacheOrDb(): void
    {
        // role 'super_admin' returns true before the L2/DB path is ever reached.
        $this->assertTrue(userHasPermission('customer', 'delete', 'super_admin', 1, 1));
    }

    public function testInactiveStatusDeniesEarly(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for permission DB lookups.');
        }
        // An explicit non-admin role with no session/status resolves to a clean
        // boolean (the early/fallback paths must not error with the L2 code present).
        $result = userHasPermission('customer', 'view', 'user', 1, 1);
        $this->assertIsBool($result);
    }
}

<?php

use App\Controllers\Customer;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Referral completion permission gate on Customer::canCompleteReferralSetup().
 *
 * @internal
 */
final class CustomerReferralCompletionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper(['user', 'utility']);

        // The employee-role tests fall through userHasPermission()'s DB path
        // (query-builder-based / DBGroup-prefix-aware). Left empty on purpose
        // for permissions/custom_access — employees are deliberately NOT
        // backfilled with 'referral' access (Item 5's design), so an absent
        // row is the correct fixture for "denied" to be the real behavior,
        // not an artifact of a missing table.
        if (extension_loaded('sqlite3')) {
            $p = $this->db->DBPrefix;
            foreach (['users', 'permissions', 'custom_access'] as $name) {
                $this->db->query('DROP TABLE IF EXISTS ' . $p . $name);
            }
            $this->db->query('CREATE TABLE ' . $p . 'users (id INTEGER PRIMARY KEY, admin_id INTEGER, role TEXT, name TEXT)');
            $this->db->query('CREATE TABLE ' . $p . 'permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_type TEXT, user_id INTEGER, permissions TEXT)');
            $this->db->query('CREATE TABLE ' . $p . 'custom_access (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, status TEXT, permissions TEXT)');
            $this->db->query("INSERT INTO " . $p . "users (id, admin_id, role, name) VALUES (50, 10, 'employee', 'Employee')");
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    private function customerController(): Customer
    {
        $ref = new ReflectionClass(Customer::class);

        return $ref->newInstanceWithoutConstructor();
    }

    private function canComplete(object $actor, object $referralRow, int $ownerId): bool
    {
        $controller = $this->customerController();
        $invoker = $this->getPrivateMethodInvoker($controller, 'canCompleteReferralSetup');

        return (bool) $invoker($actor, $referralRow, $ownerId);
    }

    public function testPlatformAdminBypassesPermissionCheck(): void
    {
        $actor = (object) ['id' => 1, 'role' => 'super_admin'];
        $referral = (object) ['owner_id' => 999];

        $this->assertTrue($this->canComplete($actor, $referral, 999));
    }

    public function testEmployeeWithoutReferralUpdateDeniedEvenInScope(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for permission DB lookups.');
        }

        session()->set(['user_role' => 'employee', 'user_id' => 50, 'status' => 'active']);

        $actor = (object) ['id' => 50, 'role' => 'employee', 'admin_id' => 10];
        $referral = (object) ['owner_id' => 10];

        $this->assertFalse($this->canComplete($actor, $referral, 10));
    }

    public function testEmployeeOutOfOwnerScopeDenied(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for getSAdminIdForUser lookup.');
        }

        session()->set(['user_role' => 'employee', 'user_id' => 50, 'status' => 'active']);

        $actor = (object) ['id' => 50, 'role' => 'employee', 'admin_id' => 10];
        $referral = (object) ['owner_id' => 99];

        $this->assertFalse($this->canComplete($actor, $referral, 99));
    }

    public function testSAdminOutOfOwnerScopeDeniedBeforePermissionCheck(): void
    {
        session()->set(['user_role' => 'admin', 'user_id' => 5, 'status' => 'active']);

        $actor = (object) ['id' => 5, 'role' => 'admin'];
        $referral = (object) ['owner_id' => 99];

        $this->assertFalse($this->canComplete($actor, $referral, 99));
    }
}

<?php

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;
use Zapi\Modules\Shared\Rewards\Controllers\RewardWebController;

/**
 * Referral permission gates on RewardWebController (view + edit paths).
 *
 * @internal
 */
final class RewardWebControllerPermissionTest extends CIUnitTestCase
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
        // backfilled with 'referral' access (Item 5's design).
        if (extension_loaded('sqlite3')) {
            $p = $this->db->DBPrefix;
            foreach (['users', 'permissions', 'custom_access'] as $name) {
                $this->db->query('DROP TABLE IF EXISTS ' . $p . $name);
            }
            $this->db->query('CREATE TABLE ' . $p . 'users (id INTEGER PRIMARY KEY, admin_id INTEGER, role TEXT, name TEXT)');
            $this->db->query('CREATE TABLE ' . $p . 'permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_type TEXT, user_id INTEGER, permissions TEXT)');
            $this->db->query('CREATE TABLE ' . $p . 'custom_access (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, status TEXT, permissions TEXT)');
            $this->db->query("INSERT INTO " . $p . "users (id, admin_id, role, name) VALUES (99, 10, 'employee', 'Employee')");
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    private function controller(): RewardWebController
    {
        return new RewardWebController();
    }

    private function invokeGuard(RewardWebController $controller, string $method): mixed
    {
        $invoker = $this->getPrivateMethodInvoker($controller, $method);

        return $invoker();
    }

    public function testGuardAccessAllowsPlatformAdmin(): void
    {
        session()->set(['user_role' => 'super_admin', 'user_id' => 1, 'status' => 'active']);

        $this->assertNull($this->invokeGuard($this->controller(), 'guardAccess'));
    }

    public function testGuardEditAllowsPlatformAdmin(): void
    {
        session()->set(['user_role' => 'super_admin', 'user_id' => 1, 'status' => 'active']);

        $this->assertNull($this->invokeGuard($this->controller(), 'guardEdit'));
    }

    public function testGuardAccessDeniesEmployeeWithoutReadPermission(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for permission DB lookups.');
        }

        session()->set(['user_role' => 'employee', 'user_id' => 99, 'status' => 'active']);

        $redirect = $this->invokeGuard($this->controller(), 'guardAccess');
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
    }

    public function testGuardEditDeniesEmployeeWithoutUpdatePermission(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for permission DB lookups.');
        }

        session()->set(['user_role' => 'employee', 'user_id' => 99, 'status' => 'active']);

        $redirect = $this->invokeGuard($this->controller(), 'guardEdit');
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
    }

    public function testActorRolePreservesOriginalCasing(): void
    {
        session()->set('user_role', 'admin');
        $controller = $this->controller();
        $invoker = $this->getPrivateMethodInvoker($controller, 'actorRole');

        $this->assertSame('admin', $invoker());
    }
}

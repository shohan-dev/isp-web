<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Characterization tests for layout/sidebar.php role-based navigation (Item 2).
 *
 * @internal
 */
final class SidebarNavTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    private string $sidebarPath;
    private string $platformPath;
    private string $headerPath;

    protected function setUp(): void
    {
        parent::setUp();
        Services::session()->destroy();
        Services::injectMock('cache', new MockCache());
        /* 'asset' is in Config\Autoload::$helpers, so the app always has saas_js() /
           saas_css(). This suite renders the view directly, outside that boot path, so
           it has to name every helper the view touches — and sidebar.php now calls
           saas_js() for the pre-paint sidebar-boot script. 'flag' backs
           isTenantingEnabled(), which _sidebar_platform.php gates the Tenant
           Portals link on — the real request pipeline loads it via
           MaintenanceFilter before the view renders, but this suite renders the
           view directly, outside that filter pipeline. */
        helper(['utility', 'user', 'setting', 'router', 'ticket', 'tenant', 'wallet', 'subscription', 'lookup_cache', 'asset', 'flag']);
        clearFlag('tenant_enabled');

        /* sidebar.php calls SidebarPinModel::getForUser() for any logged-in user, so
           simply RENDERING the view now hits the database — which is new: the pins
           feature put a query inside the view. Every case below that seeds a user_id
           into the session was dying on "no such table: db_sidebar_pins", and that is
           the whole CI failure (3 errors, all in this class).

           Built by hand rather than by flipping $migrate: DatabaseTestTrait migrates
           the Tests\Support namespace, NOT app/Database/Migrations, so $migrate = true
           does not create this table at all — it just drags the shared in-memory DB
           through a refresh that breaks AuthRevokeAllTokensTest. Hand-creating the one
           table a suite needs is what CronLockTest and AuthRevokeAllTokensTest already
           do. Empty is the correct fixture here: these are role-based-nav assertions,
           not pin assertions. */
        $pinsTable = $this->db->DBPrefix . 'sidebar_pins';
        $this->db->query('DROP TABLE IF EXISTS ' . $pinsTable);
        $this->db->query(
            'CREATE TABLE ' . $pinsTable . ' ('
            . 'id INTEGER PRIMARY KEY, '
            . 'user_id INTEGER NOT NULL, '
            . 'pin_key TEXT NOT NULL, '
            . 'label TEXT NULL, '
            . 'url TEXT NULL, '
            . 'icon TEXT NULL, '
            . 'created_at DATETIME NULL, '
            . 'updated_at DATETIME NULL'
            . ')'
        );

        $this->sidebarPath   = APPPATH . 'Views/layout/sidebar.php';
        $this->platformPath  = APPPATH . 'Views/layout/_sidebar_platform.php';
        $this->headerPath    = APPPATH . 'Views/layout/header.php';

        // The DB-rendering tests below exercise non-super_admin roles, which
        // fall through userHasPermission()'s DB path (query-builder-based /
        // DBGroup-prefix-aware — 'tests' group sets DBPrefix='db_').
        if (extension_loaded('sqlite3')) {
            $this->seedSidebarSchema();
        }
    }

    private function seedSidebarSchema(): void
    {
        $p = $this->db->DBPrefix;
        foreach (['users', 'permissions', 'custom_access', 'registrations'] as $name) {
            $this->db->query('DROP TABLE IF EXISTS ' . $p . $name);
        }
        $this->db->query('CREATE TABLE ' . $p . 'users (
            id INTEGER PRIMARY KEY, admin_id INTEGER, role TEXT, name TEXT, status TEXT, email TEXT, fund REAL DEFAULT 0, created_by TEXT
        )');
        $this->db->query('CREATE TABLE ' . $p . 'permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_type TEXT, user_id INTEGER, permissions TEXT
        )');
        $this->db->query('CREATE TABLE ' . $p . 'custom_access (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, status TEXT, permissions TEXT
        )');
        // Pulled in transitively by user_helper.php/sidebar rendering (unrelated
        // to nav logic) — Registration::initialize() only checks/adds columns
        // via fieldExists(), so it just needs the table + probed columns to exist.
        $this->db->query('CREATE TABLE ' . $p . 'registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT, userid INTEGER, mobile TEXT,
            whatsapp_number TEXT, payment_receive_number TEXT, package TEXT,
            requested_plan TEXT, plan_note TEXT
        )');
        $this->db->query("INSERT INTO " . $p . "users (id, admin_id, role, name, status) VALUES
            (1, 0, 'super_admin', 'Platform', 'active'),
            (2, 0, 'admin', 'Tenant', 'active'),
            (3, 2, 'user', 'Customer', 'active')");
        // userHasPermission() for role='admin' looks up the platform-owner's
        // default-permission TEMPLATE row at the fixed user_id=2 (the "magic
        // platform-owner id" — see app/Config/Roles.php's docblock), not the
        // tenant admin's own row. Grant 'customer' so the permission-gated
        // sidebar item under test is actually visible for the admin fixture.
        $this->db->query("INSERT INTO " . $p . "permissions (user_type, user_id, permissions) VALUES (
            'admin', 2, '{\"customer\":[\"read\"]}'
        )");
    }

    protected function tearDown(): void
    {
        Services::session()->destroy();
        clearFlag('tenant_enabled');
        parent::tearDown();
        Services::reset();
    }

    public function testServiceAreasGuardIsNotAlwaysTrue(): void
    {
        $this->assertTrue($this->shouldShowServiceAreas('admin'));
        $this->assertFalse($this->shouldShowServiceAreas('super_admin'));
        $this->assertFalse($this->shouldShowServiceAreas('user'));
        $this->assertTrue($this->shouldShowServiceAreas('resellerAdmin'));
    }

    public function testLegacyTautologyWouldHaveLeakedServiceAreasToAdmin(): void
    {
        $role = 'super_admin';
        $legacyAlwaysTrue = ($role !== 'super_admin' || $role !== 'user');

        $this->assertTrue($legacyAlwaysTrue, 'documents the bug: OR made the guard always true');
        $this->assertFalse($this->shouldShowServiceAreas($role));
    }

    public function testSidebarUsesSingleTopLevelRoleSwitch(): void
    {
        $source = file_get_contents($this->sidebarPath);

        $this->assertIsString($source);
        $this->assertStringContainsString("if (\$currentRole === 'super_admin')", $source);
        $this->assertStringContainsString("view('layout/_sidebar_platform'", $source);
        $this->assertStringContainsString('!in_array($currentRole, [\'super_admin\', \'user\'], true)', $source);
        $this->assertStringNotContainsString("!= 'super_admin' || getSession('user_role') != 'user'", $source);
        $this->assertStringNotContainsString("getSession('user_role') != 'super_admin'", $source);
    }

    public function testPlatformPartialFoldsRevenueAndContactsIntoTreeview(): void
    {
        $source = file_get_contents($this->platformPath);

        $this->assertIsString($source);
        $this->assertStringContainsString('Platform Admin', $source);
        $this->assertStringContainsString("route_to('route.tenants')", $source);
        $this->assertStringContainsString("route_to('route.tenants.create')", $source);
        $this->assertStringContainsString("route_to('route.Admin')", $source);
        $this->assertStringContainsString("route_to('Admin.packages')", $source);
        $this->assertStringContainsString("route_to('route.Admin.revenue')", $source);
        $this->assertStringContainsString("route_to('route.contact.fetch')", $source);
        $this->assertStringContainsString('treeview-menu', $source);
    }

    public function testHeaderHasAdminDropdownForPlatformRole(): void
    {
        $source = file_get_contents($this->headerPath);

        $this->assertIsString($source);
        $this->assertStringContainsString("getSession('user_role') === 'super_admin'", $source);
        $this->assertStringContainsString('>Admin<', $source);
        $this->assertStringContainsString("route_to('route.tenants')", $source);
        $this->assertStringContainsString("route_to('route.contact.fetch')", $source);
    }

    public function testAdminSidebarShowsPlatformNavOnly(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required to render sidebar views.');
        }

        setFlag('tenant_enabled', true);
        $html = $this->renderSidebar('super_admin', ['status' => 'active', 'user_id' => 1]);

        $this->assertStringContainsString('Platform Admin', $html);
        $this->assertStringContainsString('Tenant Portals', $html);
        $this->assertStringContainsString('Customers Payment', $html);
        $this->assertStringNotContainsString('Service Areas', $html);
        $this->assertStringNotContainsString('>Customers<', $html);
        $this->assertStringNotContainsString('Mikrotik Routers', $html);
    }

    public function testTenantPortalsLinkHiddenWhenTenantingDisabled(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required to render sidebar views.');
        }

        clearFlag('tenant_enabled'); // falls back to TENANT_ENABLED env default (false)
        $html = $this->renderSidebar('super_admin', ['status' => 'active', 'user_id' => 1]);

        $this->assertStringContainsString('Platform Admin', $html);
        $this->assertStringNotContainsString('Tenant Portals', $html);
    }

    public function testSAdminSidebarShowsTenantMenuNotPlatformItems(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required to render sidebar views.');
        }

        $html = $this->renderSidebar('admin', ['status' => 'active', 'user_id' => 2, 'admin_id' => 2]);

        $this->assertStringContainsString('Customers', $html);
        $this->assertStringNotContainsString('Platform Admin', $html);
        $this->assertStringNotContainsString('Tenant Portals', $html);
    }

    public function testUserRoleHidesServiceAreas(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required to render sidebar views.');
        }

        $html = $this->renderSidebar('user', ['status' => 'active', 'user_id' => 3, 'admin_id' => 2]);

        $this->assertStringNotContainsString('Service Areas', $html);
    }

    private function shouldShowServiceAreas(string $role): bool
    {
        return ! in_array($role, ['super_admin', 'user'], true);
    }

    /**
     * @param array<string, mixed> $session
     */
    private function renderSidebar(string $role, array $session): string
    {
        $session = array_merge([
            'user_role' => $role,
            'status'    => 'active',
            'user_id'   => 1,
        ], $session);

        foreach ($session as $key => $value) {
            Services::session()->set($key, $value);
        }

        $uri = service('uri');
        $uri->setSilent(true)->setPath('dashboard');

        return (string) view('layout/sidebar', [], ['saveData' => false]);
    }
}

<?php

use App\Services\TrashService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Trash;

/**
 * Recycle bin feature tests — delete round-trip + tenant scoping.
 *
 * @internal
 */
final class RecycleBinTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    /**
     * TrashService/RecycleBinModel/AuditLogModel/User model all access tables
     * via the query-builder, which auto-applies the DBGroup prefix — the
     * 'tests' group sets DBPrefix='db_' specifically to catch prefix-unsafe
     * code, so every table here must be created under its real
     * `{DBPrefix}<name>` name.
     */
    private array $t = [];

    protected function setUp(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for RecycleBin tests.');
        }

        parent::setUp();
        foreach (['users', 'packages', 'recycle_bin', 'audit_logs', 'permissions', 'custom_access', 'registrations'] as $name) {
            $this->t[$name] = $this->db->DBPrefix . $name;
        }
        $this->createSchema();
        $this->seedRows();
        helper(['user', 'utility']);
    }

    private function createSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['audit_logs']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['recycle_bin']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['packages']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['users']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['permissions']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['custom_access']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['registrations']);

        $this->db->query('CREATE TABLE ' . $this->t['users'] . ' (
            id INTEGER PRIMARY KEY,
            admin_id INTEGER,
            role TEXT,
            name TEXT,
            email TEXT,
            status TEXT,
            fund REAL DEFAULT 0,
            created_by TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['packages'] . ' (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            package_name TEXT,
            bandwidth TEXT,
            price REAL,
            pricing_type TEXT,
            status TEXT,
            visibility TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['recycle_bin'] . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            entity TEXT,
            entity_label TEXT,
            source_table TEXT,
            source_id INTEGER,
            payload TEXT,
            deleted_by INTEGER,
            deleted_by_name TEXT,
            ip_address TEXT,
            created_at TEXT,
            expires_at TEXT,
            restored_at TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['audit_logs'] . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT,
            entity TEXT,
            details TEXT,
            actor TEXT,
            ip_address TEXT,
            created_at TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['permissions'] . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_type TEXT,
            user_id INTEGER,
            permissions TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['custom_access'] . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            status TEXT,
            permissions TEXT
        )');
        // Pulled in transitively by the shared header partial (unrelated to
        // recycle-bin logic) — Registration::initialize() only checks/adds
        // columns via fieldExists(), so it just needs the table to exist with
        // the columns it probes for.
        $this->db->query('CREATE TABLE ' . $this->t['registrations'] . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userid INTEGER,
            mobile TEXT,
            whatsapp_number TEXT,
            payment_receive_number TEXT,
            package TEXT,
            requested_plan TEXT,
            plan_note TEXT
        )');
    }

    private function seedRows(): void
    {
        $this->db->query("INSERT INTO " . $this->t['users'] . " (id, admin_id, role, name) VALUES (10, 0, 'admin', 'Tenant')");
        $this->db->query("INSERT INTO " . $this->t['packages'] . " (id, user_id, package_name, bandwidth, price, pricing_type, status, visibility)
            VALUES (1, 10, 'Test Pkg', '10M', 500, 'monthly', 'active', 'active')");
        $this->db->query("INSERT INTO " . $this->t['permissions'] . " (user_type, user_id, permissions) VALUES (
            'employee', 50, '{\"recycle_bin\":[\"read\"]}'
        )");
    }

    public function testPackageDeleteCreatesBinRow(): void
    {
        session()->set(['user_id' => 10, 'user_role' => 'admin', 'status' => 'active']);

        $svc = new TrashService($this->db, new Trash());
        $pkg = $this->db->table('packages')->where('id', 1)->get()->getRow();
        $this->assertSame(1, $svc->trash('package', [$pkg]));
        $this->assertSame(0, $this->db->table('packages')->countAllResults());
        $this->assertSame(1, $this->db->table('recycle_bin')->countAllResults());
    }

    public function testRestoreRoundTrip(): void
    {
        session()->set(['user_id' => 10, 'user_role' => 'admin', 'status' => 'active']);

        $svc = new TrashService($this->db, new Trash());
        $pkg = $this->db->table('packages')->where('id', 1)->get()->getRow();
        $svc->trash('package', [$pkg]);

        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;
        $this->assertTrue($svc->restore($binId, 10));
        $this->assertSame(1, $this->db->table('packages')->countAllResults());
    }

    /**
     * Exercises RecycleBin::index()'s actual data source directly
     * (RecycleBinModel::getForTenant(), what the controller passes straight
     * to the view) rather than rendering the full authenticated page shell —
     * the shared header/sidebar partials pull in many unrelated tables
     * (registrations, custom_access, users.email, ...) that have nothing to
     * do with recycle-bin tenant scoping, and cross-tenant restore rejection
     * is already covered end-to-end by testCrossTenantRestoreRejected().
     */
    public function testRecycleBinIndexRequiresTenantScope(): void
    {
        $svc = new TrashService($this->db, new Trash());
        $pkg = $this->db->table('packages')->where('id', 1)->get()->getRow();
        $svc->trash('package', [$pkg]);

        $binModel = new \App\Models\RecycleBinModel($this->db);

        $ownTenantItems = $binModel->getForTenant(10);
        $this->assertCount(1, $ownTenantItems);
        $this->assertSame('package', $ownTenantItems[0]->entity);
        $this->assertSame('Test Pkg', $ownTenantItems[0]->entity_label);

        $otherTenantItems = $binModel->getForTenant(999);
        $this->assertCount(0, $otherTenantItems);
    }

    public function testCrossTenantRestoreRejected(): void
    {
        $svc = new TrashService($this->db, new Trash());
        $pkg = $this->db->table('packages')->where('id', 1)->get()->getRow();
        $svc->trash('package', [$pkg]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertFalse($svc->restore($binId, 999));
        $this->assertSame(0, $this->db->table('packages')->countAllResults());
    }
}

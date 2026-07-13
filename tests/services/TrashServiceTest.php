<?php

use App\Services\TrashService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Trash;

/**
 * TrashService unit tests against in-memory SQLite.
 *
 * @internal
 */
final class TrashServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    /**
     * TrashService (and RecycleBinModel/AuditLogModel) access every table via
     * the query-builder (`$this->db->table($name)`), which auto-applies the
     * DBGroup prefix — the 'tests' group sets DBPrefix='db_' specifically to
     * catch prefix-unsafe code. Every table here, including the ones only
     * touched by this test's own raw-SQL helpers, must therefore be created
     * under its real `{DBPrefix}<name>` name or "no such table" results.
     */
    private array $t = [];

    protected function setUp(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for TrashService tests.');
        }

        parent::setUp();
        foreach (['users', 'packages', 'user_router_data', 'registrations', 'recycle_bin', 'audit_logs', 'tickets'] as $name) {
            $this->t[$name] = $this->db->DBPrefix . $name;
        }
        $this->createSchema();
        $this->seedBaseRows();
        helper('user');
    }

    private function createSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['audit_logs']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['recycle_bin']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['user_router_data']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['registrations']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['packages']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['tickets']);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->t['users']);

        $this->db->query('CREATE TABLE ' . $this->t['users'] . ' (
            id INTEGER PRIMARY KEY,
            admin_id INTEGER,
            role TEXT,
            name TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['packages'] . ' (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            package_name TEXT
        )');
        $this->db->query('CREATE TABLE ' . $this->t['user_router_data'] . ' (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            router_id INTEGER
        )');
        $this->db->query('CREATE TABLE ' . $this->t['registrations'] . ' (
            id INTEGER PRIMARY KEY,
            userid INTEGER,
            mobile TEXT
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
        $this->db->query('CREATE TABLE ' . $this->t['tickets'] . ' (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            subject TEXT
        )');
    }

    private function seedBaseRows(): void
    {
        $this->db->query("INSERT INTO " . $this->t['users'] . " (id, admin_id, role, name) VALUES
            (10, 0, 'admin', 'Tenant Admin'),
            (20, 10, 'user', 'Customer One')");
        $this->db->query("INSERT INTO " . $this->t['user_router_data'] . " (id, user_id, router_id) VALUES (1, 20, 5)");
        $this->db->query("INSERT INTO " . $this->t['registrations'] . " (id, userid, mobile) VALUES (1, 20, '01700000000')");
        $this->db->query("INSERT INTO " . $this->t['packages'] . " (id, user_id, package_name) VALUES (1, 10, 'Gold 10Mbps')");
    }

    /** Extra fixture rows used only by the reseller/employee/support_ticket tenant-id tests. */
    private function seedResellerEmployeeTicket(): void
    {
        $this->db->query("INSERT INTO " . $this->t['users'] . " (id, admin_id, role, name) VALUES
            (30, 10, 'resellerAdmin', 'Reseller One'),
            (40, 30, 'user', 'Reseller Customer'),
            (50, 10, 'employee', 'Employee One')");
        $this->db->query("INSERT INTO " . $this->t['user_router_data'] . " (id, user_id, router_id) VALUES (2, 40, 6)");
        $this->db->query("INSERT INTO " . $this->t['registrations'] . " (id, userid, mobile) VALUES (2, 30, '01700000001'), (3, 40, '01700000002'), (4, 50, '01700000003')");
        $this->db->query("INSERT INTO " . $this->t['tickets'] . " (id, user_id, subject) VALUES (1, 20, 'Slow connection')");
    }

    private function resellerRow(): object
    {
        return $this->db->query('SELECT * FROM ' . $this->t['users'] . ' WHERE id = 30')->getRow();
    }

    private function employeeRow(): object
    {
        return $this->db->query('SELECT * FROM ' . $this->t['users'] . ' WHERE id = 50')->getRow();
    }

    private function ticketRow(): object
    {
        return $this->db->query('SELECT * FROM ' . $this->t['tickets'] . ' WHERE id = 1')->getRow();
    }

    private function service(): TrashService
    {
        return new TrashService($this->db, new Trash());
    }

    private function packageRow(): object
    {
        return $this->db->query('SELECT * FROM ' . $this->t['packages'] . ' WHERE id = 1')->getRow();
    }

    private function customerRow(): object
    {
        return $this->db->query('SELECT * FROM ' . $this->t['users'] . ' WHERE id = 20')->getRow();
    }

    public function testTrashPackageSnapshotsAndHardDeletes(): void
    {
        $count = $this->service()->trash('package', [$this->packageRow()]);
        $this->assertSame(1, $count);
        $this->assertSame(0, $this->db->table('packages')->countAllResults());
        $this->assertSame(1, $this->db->table('recycle_bin')->countAllResults());

        $bin = $this->db->table('recycle_bin')->get()->getRow();
        $this->assertSame('package', $bin->entity);
        $this->assertSame(10, (int) $bin->tenant_id);
    }

    public function testTrashCustomerCascadeChildren(): void
    {
        $count = $this->service()->trash('customer', [$this->customerRow()]);
        $this->assertSame(1, $count);
        $this->assertSame(0, $this->db->table('users')->where('role', 'user')->countAllResults());
        $this->assertSame(1, $this->db->table('users')->where('role', 'admin')->countAllResults());
        $this->assertSame(0, $this->db->table('user_router_data')->countAllResults());
        $this->assertSame(0, $this->db->table('registrations')->countAllResults());

        $payload = json_decode((string) $this->db->table('recycle_bin')->get()->getRow()->payload, true);
        $this->assertCount(2, $payload['children']);
    }

    public function testRestoreRecreatesParentAndChildren(): void
    {
        $svc = $this->service();
        $svc->trash('customer', [$this->customerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertNotNull($this->db->table('users')->where('id', 20)->get()->getRow());
        $this->assertSame(1, $this->db->table('user_router_data')->countAllResults());
        $this->assertSame(1, $this->db->table('registrations')->countAllResults());
        $this->assertNotNull($this->db->table('recycle_bin')->where('id', $binId)->get()->getRow()->restored_at);
    }

    public function testRestoreRefusesWhenParentIdExists(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->db->table('packages')->insert([
            'id'           => 1,
            'user_id'      => 10,
            'package_name' => 'Collision',
        ]);

        $this->assertFalse($svc->restore($binId, 10));
    }

    public function testDoubleRestoreIsSafeNoOp(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertTrue($svc->restore($binId, 10));
    }

    public function testTenantScopingRejectsCrossTenantRestore(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertFalse($svc->restore($binId, 999));
    }

    public function testPurgeExpiredDeletesOnlyExpiredRows(): void
    {
        $this->db->table('recycle_bin')->insert([
            'tenant_id'    => 10,
            'entity'       => 'package',
            'entity_label' => 'old',
            'source_table' => 'packages',
            'source_id'    => 99,
            'payload'      => '{}',
            'created_at'   => '2020-01-01 00:00:00',
            'expires_at'   => '2020-01-02 00:00:00',
        ]);
        $this->db->table('recycle_bin')->insert([
            'tenant_id'    => 10,
            'entity'       => 'package',
            'entity_label' => 'fresh',
            'source_table' => 'packages',
            'source_id'    => 100,
            'payload'      => '{}',
            'created_at'   => date('Y-m-d H:i:s'),
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);

        $purged = $this->service()->purgeExpired(1000);
        $this->assertSame(1, $purged);
        $this->assertSame(1, $this->db->table('recycle_bin')->countAllResults());
        $this->assertSame('fresh', $this->db->table('recycle_bin')->get()->getRow()->entity_label);
    }

    public function testTrashWritesAuditLog(): void
    {
        $this->service()->trash('package', [$this->packageRow()]);
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'trash')->countAllResults());
    }

    public function testTrashRollsBackOnFailure(): void
    {
        $svc = $this->service();

        $this->expectException(\InvalidArgumentException::class);
        $svc->trash('unknown_entity', [$this->packageRow()]);

        $this->assertSame(1, $this->db->table('packages')->countAllResults());
    }

    /**
     * Reseller uses 'sadmin:id' tenant mode (like customer/support_ticket) —
     * regression coverage for the TrashService::resolveTenantId() fix (was
     * comparing type==='admin' against a config that only ever emits
     * 'sadmin', so tenant_id silently fell back to the reseller's own id
     * instead of the real tenant admin's id). Also exercises the nested
     * cascade to the reseller's own customers + their children.
     */
    public function testTrashResellerResolvesTenantIdAndCascadesOwnCustomers(): void
    {
        $this->seedResellerEmployeeTicket();
        $count = $this->service()->trash('reseller', [$this->resellerRow()]);
        $this->assertSame(1, $count);

        $bin = $this->db->table('recycle_bin')->get()->getRow();
        $this->assertSame('reseller', $bin->entity);
        $this->assertSame(10, (int) $bin->tenant_id, 'tenant_id must resolve to the tenant admin (10), not the reseller\'s own id (30)');

        // Reseller (30) and its own customer (40, cascaded via admin_id) gone.
        $this->assertNull($this->db->table('users')->where('id', 30)->get()->getRow());
        $this->assertNull($this->db->table('users')->where('id', 40)->get()->getRow());
        // Unrelated rows (tenant admin, direct customer, employee) untouched.
        $this->assertSame(3, $this->db->table('users')->countAllResults());
        // Reseller's own registration (userid=30) + nested customer's (userid=40) gone;
        // direct customer's (userid=20) + employee's (userid=50) remain.
        $this->assertSame(2, $this->db->table('registrations')->countAllResults());
        // Nested customer's router data (user_id=40) gone; direct customer's (user_id=20) remains.
        $this->assertSame(1, $this->db->table('user_router_data')->countAllResults());
    }

    /**
     * Employee uses 'field:admin_id' tenant mode (direct column, not sadmin-
     * routed) — confirms the OTHER branch of resolveTenantId() still works
     * correctly alongside the sadmin-mode fix.
     */
    public function testTrashEmployeeResolvesTenantIdViaDirectField(): void
    {
        $this->seedResellerEmployeeTicket();
        $count = $this->service()->trash('employee', [$this->employeeRow()]);
        $this->assertSame(1, $count);

        $bin = $this->db->table('recycle_bin')->get()->getRow();
        $this->assertSame('employee', $bin->entity);
        $this->assertSame(10, (int) $bin->tenant_id);
        $this->assertNull($this->db->table('users')->where('id', 50)->get()->getRow());
    }

    /**
     * Support ticket uses 'sadmin:user_id' tenant mode — the third of the
     * three entities the resolveTenantId() type==='admin' vs 'sadmin' bug
     * affected (customer/reseller/support_ticket all use sadmin mode).
     */
    public function testTrashSupportTicketResolvesTenantIdViaSadmin(): void
    {
        $this->seedResellerEmployeeTicket();
        $count = $this->service()->trash('support_ticket', [$this->ticketRow()]);
        $this->assertSame(1, $count);

        $bin = $this->db->table('recycle_bin')->get()->getRow();
        $this->assertSame('support_ticket', $bin->entity);
        $this->assertSame(10, (int) $bin->tenant_id, 'ticket.user_id=20 belongs to tenant admin 10, not the ticket\'s own id');
        $this->assertSame(0, $this->db->table('tickets')->countAllResults());
    }

    /**
     * Genuine mid-batch rollback: row 1 (package id=1) succeeds (bin insert +
     * hard delete), row 2 (package id=2) hits a real UNIQUE-constraint failure
     * on its recycle_bin insert. The prior testTrashRollsBackOnFailure only
     * threw from entityDef() before any transaction opened; this forces an
     * actual mid-loop DB error and proves row 1's already-applied changes are
     * rolled back together with row 2's.
     */
    public function testMidBatchFailureRollsBackAllPriorRowsInTheSameCall(): void
    {
        $this->db->query('CREATE UNIQUE INDEX ux_recycle_bin_source ON ' . $this->t['recycle_bin'] . ' (source_table, source_id)');
        $this->db->table('packages')->insert(['id' => 2, 'user_id' => 10, 'package_name' => 'Silver 5Mbps']);
        // Pretend package id=2 was already trashed by an earlier operation, so
        // this batch's attempt to trash it again collides on the unique index.
        $this->db->table('recycle_bin')->insert([
            'tenant_id'    => 10,
            'entity'       => 'package',
            'entity_label' => 'stale',
            'source_table' => 'packages',
            'source_id'    => 2,
            'payload'      => '{}',
            'created_at'   => date('Y-m-d H:i:s'),
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);

        $rows = [
            $this->packageRow(),
            $this->db->query('SELECT * FROM ' . $this->t['packages'] . ' WHERE id = 2')->getRow(),
        ];

        $threw = false;
        try {
            $this->service()->trash('package', $rows);
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected the UNIQUE constraint violation on the 2nd row to throw.');
        $this->assertSame(2, $this->db->table('packages')->countAllResults(), 'Package id=1 must still exist — the whole batch rolled back, not just row 2.');
        $this->assertSame(1, $this->db->table('recycle_bin')->countAllResults(), 'Only the pre-existing stale bin row should remain — row 1\'s insert must have rolled back too.');

        // CodeIgniter's BaseConnection is `transStrict = true` by default: once a
        // query fails inside a transaction, `transStatus` latches to false and is
        // NEVER reset except by transComplete()'s non-strict branch — and this
        // connection is shared (Database::connect() reuse) across every other
        // test in this suite. Left alone, every trash()/restore() call in every
        // test that runs after this one would spuriously throw "transaction
        // failed" forever. Explicitly heal it back to a clean slate.
        $this->db->transStrict(false);
        $this->db->transStart();
        $this->db->transComplete();
        $this->db->transStrict(true);
    }

    public function testCustomerRestoreRefusesWhenParentIdExists(): void
    {
        $svc = $this->service();
        $svc->trash('customer', [$this->customerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->db->table('users')->insert(['id' => 20, 'admin_id' => 10, 'role' => 'user', 'name' => 'Impostor']);

        $this->assertFalse($svc->restore($binId, 10));
    }

    public function testCustomerDoubleRestoreIsSafeNoOp(): void
    {
        $svc = $this->service();
        $svc->trash('customer', [$this->customerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertTrue($svc->restore($binId, 10));
    }

    public function testResellerRestoreRefusesWhenParentIdExists(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('reseller', [$this->resellerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->db->table('users')->insert(['id' => 30, 'admin_id' => 10, 'role' => 'resellerAdmin', 'name' => 'Impostor']);

        $this->assertFalse($svc->restore($binId, 10));
    }

    public function testResellerDoubleRestoreIsSafeNoOp(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('reseller', [$this->resellerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertTrue($svc->restore($binId, 10));
    }

    public function testEmployeeRestoreRefusesWhenParentIdExists(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('employee', [$this->employeeRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->db->table('users')->insert(['id' => 50, 'admin_id' => 10, 'role' => 'employee', 'name' => 'Impostor']);

        $this->assertFalse($svc->restore($binId, 10));
    }

    public function testEmployeeDoubleRestoreIsSafeNoOp(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('employee', [$this->employeeRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertTrue($svc->restore($binId, 10));
    }

    public function testSupportTicketRestoreRefusesWhenParentIdExists(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('support_ticket', [$this->ticketRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->db->table('tickets')->insert(['id' => 1, 'user_id' => 20, 'subject' => 'Impostor ticket']);

        $this->assertFalse($svc->restore($binId, 10));
    }

    public function testSupportTicketDoubleRestoreIsSafeNoOp(): void
    {
        $this->seedResellerEmployeeTicket();
        $svc = $this->service();
        $svc->trash('support_ticket', [$this->ticketRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->restore($binId, 10));
        $this->assertTrue($svc->restore($binId, 10));
    }

    public function testRestoreWritesAuditLog(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $svc->restore($binId, 10);

        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'restore')->countAllResults());
    }

    public function testDeleteForeverWritesAuditLog(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;

        $this->assertTrue($svc->deleteForever($binId, 10));

        $row = $this->db->table('audit_logs')->where('action', 'purge')->get()->getRow();
        $this->assertNotNull($row);
        $details = json_decode((string) $row->details, true);
        $this->assertTrue($details['manual']);
    }

    public function testEmptyTrashWritesAuditLogPerRowPlusSummary(): void
    {
        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $this->db->table('packages')->insert(['id' => 2, 'user_id' => 10, 'package_name' => 'Silver 5Mbps']);
        $svc->trash('package', [$this->db->query('SELECT * FROM ' . $this->t['packages'] . ' WHERE id = 2')->getRow()]);

        $purged = $svc->emptyTrash(10);

        $this->assertSame(2, $purged);
        // 2 per-row 'purge' audits (from deleteForever) + 1 summary 'purge' audit.
        $this->assertSame(3, $this->db->table('audit_logs')->where('action', 'purge')->countAllResults());
        $summary = $this->db->table('audit_logs')->where('action', 'purge')->where('entity', 'recycle_bin')->get()->getRow();
        $this->assertNotNull($summary);
        $details = json_decode((string) $summary->details, true);
        $this->assertTrue($details['empty']);
        $this->assertSame(2, $details['count']);
    }

    public function testPurgeExpiredWritesAuditLog(): void
    {
        $this->db->table('recycle_bin')->insert([
            'tenant_id'    => 10,
            'entity'       => 'package',
            'entity_label' => 'old',
            'source_table' => 'packages',
            'source_id'    => 99,
            'payload'      => '{}',
            'created_at'   => '2020-01-01 00:00:00',
            'expires_at'   => '2020-01-02 00:00:00',
        ]);

        $purged = $this->service()->purgeExpired(1000);

        $this->assertSame(1, $purged);
        $row = $this->db->table('audit_logs')->where('action', 'purge')->where('entity', 'recycle_bin')->get()->getRow();
        $this->assertNotNull($row);
        $details = json_decode((string) $row->details, true);
        $this->assertTrue($details['expired']);
    }

    public function testRestoringPackageBumpsLookupCacheVersion(): void
    {
        helper('lookup_cache');
        $before = lookupCacheVersion(10);

        $svc = $this->service();
        $svc->trash('package', [$this->packageRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;
        $svc->restore($binId, 10);

        $this->assertSame($before + 1, lookupCacheVersion(10));
    }

    public function testRestoringCustomerBumpsLookupCacheVersion(): void
    {
        helper('lookup_cache');
        $before = lookupCacheVersion(10);

        $svc = $this->service();
        $svc->trash('customer', [$this->customerRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;
        $svc->restore($binId, 10);

        $this->assertSame($before + 1, lookupCacheVersion(10));
    }

    public function testRestoringEmployeeDoesNotBumpLookupCacheVersion(): void
    {
        $this->seedResellerEmployeeTicket();
        helper('lookup_cache');
        $before = lookupCacheVersion(10);

        $svc = $this->service();
        $svc->trash('employee', [$this->employeeRow()]);
        $binId = (int) $this->db->table('recycle_bin')->get()->getRow()->id;
        $svc->restore($binId, 10);

        $this->assertSame($before, lookupCacheVersion(10));
    }
}

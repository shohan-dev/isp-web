<?php

use App\Models\AuditLogModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * getFiltered() regression coverage: it used to filter on the table's own
 * auto-increment `id` (primary key) instead of `user_id`, so a customer's
 * audit-log page could return zero rows, or an arbitrary unrelated row that
 * happened to share the same PK value as the requested user id.
 *
 * @internal
 */
final class AuditLogModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    private string $table = '';

    protected function setUp(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required.');
        }

        parent::setUp();
        $this->table = $this->db->DBPrefix . 'audit_logs';
        $this->db->query('DROP TABLE IF EXISTS ' . $this->table);
        $this->db->query('CREATE TABLE ' . $this->table . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT,
            entity TEXT,
            client TEXT,
            router TEXT,
            details TEXT,
            actor TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT
        )');

        // Row 1 (PK=1) belongs to user_id=99; row 2 (PK=2) belongs to user_id=1.
        // The old `where('id =', $id)` bug meant getFiltered(..., 1) would
        // return row PK=1 (user_id=99's row) instead of PK=2 (user_id=1's row).
        $this->db->table('audit_logs')->insert(['id' => 1, 'user_id' => 99, 'action' => 'a', 'details' => '{}', 'created_at' => date('Y-m-d H:i:s')]);
        $this->db->table('audit_logs')->insert(['id' => 2, 'user_id' => 1, 'action' => 'b', 'details' => '{"pppoe_name":"customer123"}', 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function testFiltersByUserIdNotPrimaryKey(): void
    {
        $result = (new AuditLogModel())->getFiltered(null, null, 25, 1);

        $this->assertCount(1, $result);
        $this->assertSame(1, (int) $result[0]->user_id);
        $this->assertSame('b', $result[0]->action);
    }

    public function testPppoeFilterMatchesDetailsContent(): void
    {
        $result = (new AuditLogModel())->getFiltered(null, null, 25, 1, 'customer123');
        $this->assertCount(1, $result);

        $miss = (new AuditLogModel())->getFiltered(null, null, 25, 1, 'no-such-pppoe');
        $this->assertCount(0, $miss);
    }
}

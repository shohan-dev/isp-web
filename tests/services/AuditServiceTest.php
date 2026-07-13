<?php

use App\Services\AuditService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * AuditService actor resolution — regression coverage for the "actor always
 * 'system'" bug: no login path ever sets the session keys ('user_name' /
 * 'username') the old code read, so every audit row's actor column was
 * always the literal string 'system' regardless of who actually acted.
 *
 * @internal
 */
final class AuditServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    private string $usersTable = '';
    private string $auditTable = '';

    protected function setUp(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required.');
        }

        parent::setUp();
        $this->usersTable = $this->db->DBPrefix . 'users';
        $this->auditTable = $this->db->DBPrefix . 'audit_logs';

        $this->db->query('DROP TABLE IF EXISTS ' . $this->usersTable);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->auditTable);

        $this->db->query('CREATE TABLE ' . $this->usersTable . ' (id INTEGER PRIMARY KEY, name TEXT)');
        $this->db->query('CREATE TABLE ' . $this->auditTable . ' (
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
        $this->db->query("INSERT INTO " . $this->usersTable . " (id, name) VALUES (10, 'Tenant Admin One')");

        helper('utility');
        session()->remove(['user_id', 'user_name', 'username']);
    }

    private function lastRow(): object
    {
        return $this->db->table('audit_logs')->orderBy('id', 'DESC')->get(1)->getRow();
    }

    public function testSessionBasedCallResolvesActorNameFromUsersTable(): void
    {
        session()->set(['user_id' => 10]);

        (new AuditService())->record('permission.update', 'permission', ['x' => 1]);

        $row = $this->lastRow();
        $this->assertSame('10', (string) $row->user_id);
        $this->assertSame('Tenant Admin One', $row->actor);
    }

    public function testNoSessionAndNoOverrideFallsBackToSystem(): void
    {
        (new AuditService())->record('some.action');

        $row = $this->lastRow();
        $this->assertNull($row->user_id);
        $this->assertSame('system', $row->actor);
    }

    public function testExplicitActorUserIdOverrideResolvesNameWithNoSession(): void
    {
        // Simulates the zapi/JWT call site: no CI session exists at all, but
        // the caller knows the real actor id from the verified JWT.
        (new AuditService())->record('reward_config.update_global', 'reward_config', [], null, null, 10);

        $row = $this->lastRow();
        $this->assertSame('10', (string) $row->user_id);
        $this->assertSame('Tenant Admin One', $row->actor);
    }

    public function testExplicitActorNameTakesPrecedenceOverLookup(): void
    {
        (new AuditService())->record('some.action', null, [], null, null, 10, 'Explicit Name');

        $row = $this->lastRow();
        $this->assertSame('Explicit Name', $row->actor);
    }

    public function testUnknownUserIdFallsBackToSystemNotError(): void
    {
        (new AuditService())->record('some.action', null, [], null, null, 999999);

        $row = $this->lastRow();
        $this->assertSame('999999', (string) $row->user_id);
        $this->assertSame('system', $row->actor);
    }
}

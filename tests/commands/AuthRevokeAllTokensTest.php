<?php

use App\Commands\AuthRevokeAllTokens;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * auth:revoke-all-tokens — exercised against a small SYNTHETIC in-memory
 * users table (ids 90001-90003, never real production ids) so the real
 * bulk-revoke behavior is genuinely verified without touching any real
 * user's cache/token state.
 *
 * @internal
 */
final class AuthRevokeAllTokensTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    private string $usersTable = '';

    protected function setUp(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required.');
        }

        parent::setUp();
        $this->usersTable = $this->db->DBPrefix . 'users';
        $this->db->query('DROP TABLE IF EXISTS ' . $this->usersTable);
        $this->db->query('CREATE TABLE ' . $this->usersTable . ' (id INTEGER PRIMARY KEY)');
        $this->db->query('INSERT INTO ' . $this->usersTable . ' (id) VALUES (90001), (90002), (90003)');
        helper('token');

        // Clean slate: these synthetic ids must not already carry a revoke stamp.
        foreach ([90001, 90002, 90003] as $id) {
            cache()->delete('jwt_revoke_after_' . $id);
        }
    }

    protected function tearDown(): void
    {
        foreach ([90001, 90002, 90003] as $id) {
            cache()->delete('jwt_revoke_after_' . $id);
        }
        parent::tearDown();
    }

    private function command(): AuthRevokeAllTokens
    {
        $db = $this->db;

        return new class($db) extends AuthRevokeAllTokens {
            private $testDb;

            public function __construct($testDb)
            {
                $this->testDb = $testDb;
            }

            protected function connection()
            {
                return $this->testDb;
            }

            public function runPublic(bool $dry, int $batch): int
            {
                return $this->execute($this->testDb, $dry, $batch);
            }
        };
    }

    public function testDryRunRevokesNothing(): void
    {
        $this->command()->runPublic(true, 1000);

        $this->assertNull(tokensRevokedAfter(90001));
        $this->assertNull(tokensRevokedAfter(90002));
        $this->assertNull(tokensRevokedAfter(90003));
    }

    public function testRealRunStampsEveryUser(): void
    {
        $before = time();
        $this->command()->runPublic(false, 1000);

        foreach ([90001, 90002, 90003] as $id) {
            $stamp = tokensRevokedAfter($id);
            $this->assertNotNull($stamp, "user {$id} should have a revoke stamp");
            $this->assertGreaterThanOrEqual($before, $stamp);
        }
    }

    public function testPaginatesAcrossBatchesSmallerThanTotal(): void
    {
        // batch=1 forces 3 separate LIMIT/OFFSET pages for 3 rows.
        $this->command()->runPublic(false, 1);

        foreach ([90001, 90002, 90003] as $id) {
            $this->assertNotNull(tokensRevokedAfter($id));
        }
    }
}

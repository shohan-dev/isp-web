<?php

use App\Services\CronLock;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Validates App\Services\CronLock against in-memory SQLite. Self-contained:
 * builds the `cron_locks` table in setUp (prefix-free, matching the service's
 * raw SQL), mirroring JobQueueTest.
 *
 * @internal
 */
final class CronLockTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->query('DROP TABLE IF EXISTS cron_locks');
        $this->db->query(
            'CREATE TABLE cron_locks (
                name TEXT PRIMARY KEY,
                owner TEXT,
                locked_at TEXT,
                expires_at TEXT
            )'
        );
    }

    public function testAcquireSucceedsOnceThenBlocksOthers(): void
    {
        $a = new CronLock($this->db);
        $b = new CronLock($this->db);

        $this->assertTrue($a->acquire('cron:daily-payments', 1800, 'A'));
        // A holds it — a second, concurrent run is turned away.
        $this->assertFalse($b->acquire('cron:daily-payments', 1800, 'B'));
    }

    public function testDifferentLockNamesDoNotCollide(): void
    {
        $lock = new CronLock($this->db);

        $this->assertTrue($lock->acquire('cron:daily-payments', 1800, 'A'));
        $this->assertTrue($lock->acquire('cron:sync-credentials', 1800, 'A'));
    }

    public function testReleaseFreesTheLock(): void
    {
        $a = new CronLock($this->db);
        $b = new CronLock($this->db);

        $this->assertTrue($a->acquire('cron:manage-user', 1800, 'A'));
        $a->release('cron:manage-user', 'A');
        // Now free again.
        $this->assertTrue($b->acquire('cron:manage-user', 1800, 'B'));
    }

    public function testReleaseByNonHolderIsIgnored(): void
    {
        $a = new CronLock($this->db);
        $b = new CronLock($this->db);

        $this->assertTrue($a->acquire('cron:manage-user', 1800, 'A'));
        // B does not hold it — its release must NOT free A's lock.
        $b->release('cron:manage-user', 'B');
        $this->assertFalse($b->acquire('cron:manage-user', 1800, 'B'));
    }

    public function testExpiredLeaseIsTakenOver(): void
    {
        $a = new CronLock($this->db);
        $b = new CronLock($this->db);

        // Acquire with a TTL in the past by writing an already-expired lease.
        $this->assertTrue($a->acquire('cron:cleanup-logs', 1, 'A'));
        $past = date('Y-m-d H:i:s', time() - 60);
        $this->db->query('UPDATE cron_locks SET expires_at = ? WHERE name = ?', [$past, 'cron:cleanup-logs']);

        // B can take over the stale lease left by a crashed run.
        $this->assertTrue($b->acquire('cron:cleanup-logs', 1800, 'B'));
    }
}

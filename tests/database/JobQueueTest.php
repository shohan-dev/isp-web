<?php

use App\Services\JobQueue;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Validates App\Services\JobQueue against in-memory SQLite. Self-contained:
 * builds the `jobs` table in setUp (prefix-free, matching the service's raw SQL).
 *
 * @internal
 */
final class JobQueueTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->query('DROP TABLE IF EXISTS jobs');
        $this->db->query(
            'CREATE TABLE jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue TEXT DEFAULT "default",
                type TEXT,
                payload TEXT,
                status TEXT DEFAULT "pending",
                attempts INTEGER DEFAULT 0,
                max_attempts INTEGER DEFAULT 3,
                available_at TEXT,
                reserved_at TEXT,
                reserved_by TEXT,
                error TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
    }

    private function row(int $id): object
    {
        return $this->db->query('SELECT * FROM jobs WHERE id = ?', [$id])->getRow();
    }

    public function testPushCreatesPendingJobWithPayload(): void
    {
        $q  = new JobQueue($this->db);
        $id = $q->push('sms', ['to' => '01700000000', 'text' => 'hi']);

        $this->assertGreaterThan(0, $id);
        $job = $this->row($id);
        $this->assertSame('pending', $job->status);
        $this->assertSame(0, (int) $job->attempts);
        $this->assertSame(['to' => '01700000000', 'text' => 'hi'], json_decode($job->payload, true));
    }

    public function testReserveClaimsExactlyOnce(): void
    {
        $q  = new JobQueue($this->db);
        $id = $q->push('noop');

        $first = $q->reserve('default', 'w1');
        $this->assertNotNull($first);
        $this->assertSame($id, (int) $first->id);
        $this->assertSame('reserved', $first->status);
        $this->assertSame(1, (int) $first->attempts);

        // No more runnable jobs -> second worker gets nothing.
        $this->assertNull($q->reserve('default', 'w2'));
    }

    public function testReserveSkipsDelayedJobs(): void
    {
        $q = new JobQueue($this->db);
        $q->push('noop', [], 'default', 3600); // available in 1h

        $this->assertNull($q->reserve());
    }

    public function testCompleteMarksDone(): void
    {
        $q  = new JobQueue($this->db);
        $id = $q->push('noop');
        $q->reserve();
        $q->complete($id);

        $this->assertSame('done', $this->row($id)->status);
    }

    public function testFailWithRemainingAttemptsRequeuesWithError(): void
    {
        $q  = new JobQueue($this->db);
        $id = $q->push('sms', [], 'default', 0, 3);
        $q->reserve();              // attempts -> 1
        $q->fail($id, 'gateway 500');

        $job = $this->row($id);
        $this->assertSame('pending', $job->status);     // requeued
        $this->assertSame(1, (int) $job->attempts);
        $this->assertSame('gateway 500', $job->error);
        $this->assertNull($job->reserved_by);
    }

    public function testFailExhaustedGoesToDeadLetter(): void
    {
        $q  = new JobQueue($this->db);
        $id = $q->push('sms', [], 'default', 0, 1); // single attempt
        $q->reserve();              // attempts -> 1 (== max)
        $q->fail($id, 'permanent');

        $this->assertSame('dead', $this->row($id)->status);
    }

    public function testCountsByStatus(): void
    {
        $q = new JobQueue($this->db);
        $q->push('noop');
        $q->push('noop');
        $done = $q->push('noop');
        $q->reserve();
        $q->complete($done);

        $counts = $q->counts();
        $this->assertSame(3, $counts['pending'] + $counts['reserved'] + $counts['done']);
        $this->assertSame(1, $counts['done']);
    }
}

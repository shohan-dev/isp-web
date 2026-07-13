<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * JobQueue — DB-backed work queue (Phase 3 / MT-2).
 *
 * push() enqueues; reserve() atomically claims the next runnable job;
 * complete()/fail() finish it (fail = retry with exponential backoff until
 * max_attempts, then dead-letter).
 *
 * The claim is an OPTIMISTIC UPDATE (`... WHERE id=? AND status='pending'` +
 * affectedRows check), which is concurrency-safe on every driver — only one
 * worker's UPDATE can flip a given row, the rest see 0 rows and move on. On
 * MySQL 8 / MariaDB 10.6 a `SELECT ... FOR UPDATE SKIP LOCKED` claim scales
 * better; this portable form is correct everywhere and runs under SQLite tests.
 *
 * Validated by tests/database/JobQueueTest.php.
 */
class JobQueue
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /** Enqueue a job. Returns the new job id. */
    public function push(string $type, array $payload = [], string $queue = 'default', int $delaySeconds = 0, int $maxAttempts = 3): int
    {
        $now       = date('Y-m-d H:i:s');
        $available = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        $this->db->query(
            'INSERT INTO jobs (queue, type, payload, status, attempts, max_attempts, available_at, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?)',
            [$queue, $type, json_encode($payload), 'pending', $maxAttempts, $available, $now, $now]
        );

        return (int) $this->db->insertID();
    }

    /**
     * Atomically claim the next runnable job on $queue, or null if none.
     * Increments attempts on a successful claim.
     */
    public function reserve(string $queue = 'default', string $workerId = 'worker', int $scan = 20): ?object
    {
        $now = date('Y-m-d H:i:s');

        $candidates = $this->db->query(
            'SELECT id FROM jobs WHERE queue = ? AND status = ? AND available_at <= ? ORDER BY id ASC LIMIT ' . (int) $scan,
            [$queue, 'pending', $now]
        )->getResultArray();

        foreach ($candidates as $row) {
            $id = (int) $row['id'];
            $this->db->query(
                'UPDATE jobs SET status = ?, reserved_by = ?, reserved_at = ?, attempts = attempts + 1, updated_at = ? '
                . 'WHERE id = ? AND status = ?',
                ['reserved', $workerId, $now, $now, $id, 'pending']
            );

            if ($this->db->affectedRows() === 1) {
                return $this->db->query('SELECT * FROM jobs WHERE id = ?', [$id])->getRow();
            }
            // Lost the race to another worker — try the next candidate.
        }

        return null;
    }

    /** Mark a reserved job as completed. */
    public function complete(int $id): void
    {
        $this->db->query(
            'UPDATE jobs SET status = ?, error = NULL, updated_at = ? WHERE id = ?',
            ['done', date('Y-m-d H:i:s'), $id]
        );
    }

    /**
     * Mark a reserved job as failed: requeue with exponential backoff until
     * max_attempts is reached, then move it to the dead-letter state.
     */
    public function fail(int $id, string $error = ''): void
    {
        $now = date('Y-m-d H:i:s');
        $job = $this->db->query('SELECT attempts, max_attempts FROM jobs WHERE id = ?', [$id])->getRow();
        if (! $job) {
            return;
        }

        if ((int) $job->attempts >= (int) $job->max_attempts) {
            $this->db->query(
                'UPDATE jobs SET status = ?, error = ?, updated_at = ? WHERE id = ?',
                ['dead', $error, $now, $id]
            );

            return;
        }

        // Exponential backoff: 2^attempts * 10s (20s, 40s, 80s, …).
        $backoff   = (2 ** (int) $job->attempts) * 10;
        $available = date('Y-m-d H:i:s', time() + $backoff);

        $this->db->query(
            'UPDATE jobs SET status = ?, error = ?, available_at = ?, reserved_by = NULL, reserved_at = NULL, updated_at = ? WHERE id = ?',
            ['pending', $error, $available, $now, $id]
        );
    }

    /** Status counts for monitoring/health. */
    public function counts(string $queue = 'default'): array
    {
        $rows = $this->db->query(
            'SELECT status, COUNT(*) AS c FROM jobs WHERE queue = ? GROUP BY status',
            [$queue]
        )->getResultArray();

        $out = ['pending' => 0, 'reserved' => 0, 'done' => 0, 'dead' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }

        return $out;
    }
}

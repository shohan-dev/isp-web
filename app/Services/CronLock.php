<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * CronLock — singleton lock for cron jobs (Phase 3.6 / T4).
 *
 * acquire() succeeds for exactly one caller at a time per lock name; everyone
 * else is turned away until the holder release()s or the lease expires. Used by
 * `php spark cron:run <action>` so overlapping cron runs cannot double-bill or
 * double-provision.
 *
 * It is a self-expiring DB lease, not a connection-scoped GET_LOCK: a crashed
 * run frees automatically once expires_at passes, and the lock survives the
 * connection that took it. The claim is the same OPTIMISTIC UPDATE idiom as
 * App\Services\JobQueue — `UPDATE ... WHERE free-or-expired` + affectedRows()
 * check — so the DB row lock serialises racers and it is portable across
 * MySQL and the SQLite test driver.
 *
 * Validated by tests/database/CronLockTest.php.
 */
class CronLock
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Try to take the named lock for $ttlSeconds. Returns true iff acquired.
     * If a previous holder's lease has expired, it is taken over.
     */
    public function acquire(string $name, int $ttlSeconds = 1800, string $owner = 'cron'): bool
    {
        $now     = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', time() + max(1, $ttlSeconds));

        // Make sure a row exists to claim. Racing creators collide on the
        // primary key; the loser's insert is ignored and it falls through to
        // the UPDATE below, where the DB row lock decides the single winner.
        $this->ensureRow($name);

        $this->db->query(
            'UPDATE cron_locks SET owner = ?, locked_at = ?, expires_at = ? '
            . 'WHERE name = ? AND (expires_at IS NULL OR expires_at <= ?)',
            [$owner, $now, $expires, $name, $now]
        );

        return $this->db->affectedRows() === 1;
    }

    /** Release the lock, but only if we still hold it. */
    public function release(string $name, string $owner = 'cron'): void
    {
        $this->db->query(
            'UPDATE cron_locks SET owner = NULL, locked_at = NULL, expires_at = NULL '
            . 'WHERE name = ? AND owner = ?',
            [$name, $owner]
        );
    }

    /** Insert the lock row if absent; swallow the duplicate-key collision. */
    private function ensureRow(string $name): void
    {
        try {
            $this->db->query(
                'INSERT INTO cron_locks (name, owner, locked_at, expires_at) VALUES (?, NULL, NULL, NULL)',
                [$name]
            );
        } catch (\Throwable $e) {
            // Row already exists — that is the normal steady state.
        }
    }
}

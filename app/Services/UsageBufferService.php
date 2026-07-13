<?php

namespace App\Services;

use CodeIgniter\Cache\CacheInterface;
use Config\Services;

/**
 * UsageBufferService — Redis write-behind buffer for MikroTik traffic data.
 *
 * PATTERN: WRITE-BEHIND + CACHE-ASIDE
 * ─────────────────────────────────────
 * Hot path (cron every 5-15 min):
 *   1. Cron collects rx/tx bytes from MikroTik.
 *   2. Prev-day baseline is fetched from Redis (key: usage:prev:{userId})
 *      instead of a per-user DB query → eliminates the N+1 problem.
 *   3. Computed row is stored in Redis buffer (usage:buf:{date}:{userId}).
 *   4. userId is added to the "dirty" set (usage:dirty:{date}).
 *   5. NO DB write happens here.
 *
 * Flush path (hourly cron OR end-of-day):
 *   1. Read all members of usage:dirty:{date}.
 *   2. For each member, read usage:buf:{date}:{userId}.
 *   3. Batch-upsert into user_data_usage via updateBatch/insertBatch.
 *   4. Clear the dirty set ONLY after a successful DB write.
 *
 * Read path (API / web):
 *   1. Check usage:cache:{userId}:{date} → return immediately if found.
 *   2. On miss: query DB, store in Redis with short TTL (300 s), return.
 *      → After the first miss, all subsequent reads within 5 min are Redis-only.
 *
 * Redis key schema:
 *   usage:prev:{userId}          JSON {rx_mb, tx_mb, date}   TTL 48 h
 *   usage:buf:{date}:{userId}    JSON full row                TTL 26 h
 *   usage:dirty:{date}           Redis SET of userIds         TTL 26 h
 *   usage:cache:{userId}:{date}  JSON row or aggregate        TTL 300 s
 *
 * Fail-safe: every cache() call is wrapped in try/catch.  On any Redis
 * error the cron falls back to direct DB write and the read path falls
 * back to the DB, so the application keeps working without Redis.
 */
class UsageBufferService
{
    private const TTL_PREV    = 172800;  // 48 h  — prev-day baseline
    private const TTL_BUF     = 93600;   // 26 h  — today's buffer row
    private const TTL_DIRTY   = 93600;   // 26 h  — dirty user-id set
    private const TTL_CACHE   = 300;     // 5 min — read cache

    private CacheInterface $cache;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? Services::cache();
    }

    // ── KEY BUILDERS ──────────────────────────────────────────────────────────

    public function keyPrev(int $userId): string
    {
        return "usage:prev:{$userId}";
    }

    public function keyBuf(string $date, int $userId): string
    {
        return "usage:buf:{$date}:{$userId}";
    }

    public function keyDirty(string $date): string
    {
        return "usage:dirty:{$date}";
    }

    public function keyCache(int $userId, string $date): string
    {
        return "usage:cache:{$userId}:{$date}";
    }

    // ── WRITE PATH ────────────────────────────────────────────────────────────

    /**
     * Load the previous-day baseline for a user from Redis.
     * Falls back to a DB query on miss.
     *
     * @return array{rx_mb: float, tx_mb: float, date: string}|null
     */
    public function getPrevBaseline(int $userId, string $today): ?array
    {
        try {
            $cached = $this->cache->get($this->keyPrev($userId));
            if (is_array($cached) && isset($cached['date']) && $cached['date'] !== $today) {
                return $cached;
            }
        } catch (\Throwable $e) {
            log_message('warning', "UsageBuffer: getPrevBaseline cache read failed for user {$userId}: " . $e->getMessage());
        }

        // Cache miss → query DB
        $model = model('App\Models\UserDataUsageModel');
        $prev  = $model
            ->where('admin_id', $userId)
            ->where('date <', $today)
            ->orderBy('date', 'DESC')
            ->first();

        if (!$prev) {
            return null;
        }

        $baseline = [
            'rx_mb' => (float) (is_object($prev) ? $prev->rx_mb : $prev['rx_mb']),
            'tx_mb' => (float) (is_object($prev) ? $prev->tx_mb : $prev['tx_mb']),
            'date'  => (string) (is_object($prev) ? $prev->date : $prev['date']),
        ];

        // Warm the cache so the next poll skips the DB entirely.
        $this->savePrevBaseline($userId, $baseline);

        return $baseline;
    }

    /**
     * Store the previous-day baseline so future polls don't query DB.
     */
    public function savePrevBaseline(int $userId, array $baseline): void
    {
        try {
            $this->cache->save($this->keyPrev($userId), $baseline, self::TTL_PREV);
        } catch (\Throwable $e) {
            log_message('warning', "UsageBuffer: savePrevBaseline failed for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Buffer a computed usage row in Redis and mark the user as dirty.
     * No DB write occurs here.
     *
     * @param array{admin_id: int, user_name: string, interface: string, date: string,
     *              rx_mb: float, tx_mb: float, rx_today: float, tx_today: float} $row
     */
    public function bufferRow(array $row): void
    {
        $userId = (int) $row['admin_id'];
        $date   = (string) $row['date'];

        try {
            $this->cache->save($this->keyBuf($date, $userId), $row, self::TTL_BUF);
            $this->addToDirtySet($date, $userId);
        } catch (\Throwable $e) {
            log_message('warning', "UsageBuffer: bufferRow failed for user {$userId}: " . $e->getMessage());
            // Caller (CronJob) must fall back to direct DB write on false return.
            throw $e;
        }
    }

    /**
     * Read back a buffered row for a specific user/date.
     * Returns null if not in Redis (not yet written or already flushed & evicted).
     */
    public function getBufferedRow(string $date, int $userId): ?array
    {
        try {
            $row = $this->cache->get($this->keyBuf($date, $userId));
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── DIRTY-SET HELPERS ────────────────────────────────────────────────────

    /**
     * Add a userId to the dirty set for the given date.
     * We store it as a cache entry keyed by date+userId; the "set" is
     * a serialised array in the cache value named usage:dirty:{date}.
     *
     * Note: a true Redis SET (SADD/SMEMBERS) would be ideal but CI4's
     * cache abstraction doesn't expose raw SET commands.  We use a
     * compare-and-swap approach that is safe for single-process cron.
     */
    private function addToDirtySet(string $date, int $userId): void
    {
        try {
            $key  = $this->keyDirty($date);
            $set  = $this->cache->get($key);
            $set  = is_array($set) ? $set : [];
            if (!in_array($userId, $set, true)) {
                $set[] = $userId;
                $this->cache->save($key, $set, self::TTL_DIRTY);
            }
        } catch (\Throwable $e) {
            log_message('warning', "UsageBuffer: addToDirtySet failed date={$date} user={$userId}: " . $e->getMessage());
        }
    }

    /**
     * Return all user IDs that have un-flushed data for $date.
     *
     * @return int[]
     */
    public function getDirtyUserIds(string $date): array
    {
        try {
            $set = $this->cache->get($this->keyDirty($date));
            return is_array($set) ? $set : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Clear the dirty set after a successful DB flush.
     */
    public function clearDirtySet(string $date): void
    {
        try {
            $this->cache->delete($this->keyDirty($date));
        } catch (\Throwable $e) {
            // Non-fatal: the set TTL will expire on its own.
        }
    }

    // ── FLUSH PATH ───────────────────────────────────────────────────────────

    /**
     * Flush all buffered rows for $date to the database.
     *
     * Reads the dirty-set, fetches each buffered row from Redis,
     * batch-upserts into user_data_usage, then clears the dirty set.
     *
     * @return array{flushed: int, skipped: int, errors: int}
     */
    public function flushToDb(string $date): array
    {
        $dirtyIds = $this->getDirtyUserIds($date);
        if (empty($dirtyIds)) {
            return ['flushed' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $model    = model('App\Models\UserDataUsageModel');
        $existing = [];

        // BUG-19: Load existing DB rows in 200-ID chunks — a single whereIn with
        // 20k values would exceed MySQL's max_allowed_packet and is slow to parse.
        try {
            foreach (array_chunk($dirtyIds, 200) as $chunk) {
                $rows = $model
                    ->where('date', $date)
                    ->whereIn('admin_id', $chunk)
                    ->findAll();
                foreach ($rows as $r) {
                    $id  = is_object($r) ? $r->admin_id : $r['admin_id'];
                    $pk  = is_object($r) ? $r->id       : $r['id'];
                    $existing[(int) $id] = (int) $pk;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', "UsageBuffer: flush DB read failed for date={$date}: " . $e->getMessage());
            return ['flushed' => 0, 'skipped' => count($dirtyIds), 'errors' => 1];
        }

        $toInsert = [];
        $toUpdate = [];
        $skipped  = 0;
        $errors   = 0;

        foreach ($dirtyIds as $userId) {
            $row = $this->getBufferedRow($date, $userId);
            if (!$row) {
                $skipped++;
                continue;
            }

            if (isset($existing[$userId])) {
                $row['id'] = $existing[$userId];
                $toUpdate[] = $row;
            } else {
                $toInsert[] = $row;
            }
        }

        if (!empty($toInsert)) {
            try {
                $model->insertBatch($toInsert);
            } catch (\Throwable $e) {
                log_message('error', "UsageBuffer: insertBatch failed date={$date}: " . $e->getMessage());
                $errors++;
            }
        }

        if (!empty($toUpdate)) {
            try {
                $model->updateBatch($toUpdate, 'id');
            } catch (\Throwable $e) {
                log_message('error', "UsageBuffer: updateBatch failed date={$date}: " . $e->getMessage());
                $errors++;
            }
        }

        $flushed = count($toInsert) + count($toUpdate);

        // Update the prev-baseline for all flushed users so the NEXT cron
        // poll doesn't need to hit the DB for the baseline either.
        foreach (array_merge($toInsert, $toUpdate) as $r) {
            $this->savePrevBaseline((int) $r['admin_id'], [
                'rx_mb' => (float) $r['rx_mb'],
                'tx_mb' => (float) $r['tx_mb'],
                'date'  => $date,
            ]);
        }

        if ($errors === 0) {
            $this->clearDirtySet($date);
            // Evict read-cache so the next API read reflects the freshly flushed data.
            foreach ($dirtyIds as $userId) {
                try {
                    $this->cache->delete($this->keyCache($userId, $date));
                } catch (\Throwable $e) { /* non-fatal */ }
            }
        }

        return ['flushed' => $flushed, 'skipped' => $skipped, 'errors' => $errors];
    }

    // ── READ PATH (cache-aside) ───────────────────────────────────────────────

    /**
     * Get today's usage row for a user: Redis buffer → Redis cache → DB.
     * Returns null if no data exists anywhere.
     *
     * @return array<string, mixed>|null
     */
    public function getForUser(int $userId, string $date): ?array
    {
        // 1. Check the write-buffer first (most up-to-date).
        $buffered = $this->getBufferedRow($date, $userId);
        if ($buffered !== null) {
            return $buffered;
        }

        // 2. Check the short-TTL read cache.
        try {
            $cached = $this->cache->get($this->keyCache($userId, $date));
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) { /* fall through */ }

        // 3. DB fallback.
        $row = model('App\Models\UserDataUsageModel')
            ->where('admin_id', $userId)
            ->where('date', $date)
            ->first();

        if (!$row) {
            return null;
        }

        $arr = is_object($row) ? (array) $row : $row;

        // Warm the read cache.
        try {
            $this->cache->save($this->keyCache($userId, $date), $arr, self::TTL_CACHE);
        } catch (\Throwable $e) { /* non-fatal */ }

        return $arr;
    }

    /**
     * Invalidate the read cache for a user/date (call after manual DB writes).
     */
    public function invalidateCache(int $userId, string $date): void
    {
        try {
            $this->cache->delete($this->keyCache($userId, $date));
        } catch (\Throwable $e) { /* non-fatal */ }
    }

    /**
     * Get the monthly aggregate from Redis cache, or null on miss.
     * Caller should populate on miss and pass the result back via
     * saveMonthlyCache().
     *
     * @return array<string, mixed>|null
     */
    public function getMonthlyCache(int $userId, string $yearMonth): ?array
    {
        try {
            $v = $this->cache->get("usage:monthly:{$userId}:{$yearMonth}");
            return is_array($v) ? $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveMonthlyCache(int $userId, string $yearMonth, array $data): void
    {
        try {
            $this->cache->save("usage:monthly:{$userId}:{$yearMonth}", $data, self::TTL_CACHE);
        } catch (\Throwable $e) { /* non-fatal */ }
    }

    public function invalidateMonthlyCache(int $userId, string $yearMonth): void
    {
        try {
            $this->cache->delete("usage:monthly:{$userId}:{$yearMonth}");
        } catch (\Throwable $e) { /* non-fatal */ }
    }
}

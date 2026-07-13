<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateCronLocksTable — singleton cron lock (Phase 3.6 / T4).
 *
 * Backs App\Services\CronLock, which `php spark cron:run <action>` uses to make
 * each cron a singleton: two overlapping runs (a slow cPanel run still going
 * when the next minute fires, or two boxes sharing one DB) can no longer
 * double-bill or double-provision. The lock is a self-expiring DB lease
 * (portable optimistic UPDATE) — a crashed run frees automatically at TTL,
 * unlike a connection-scoped GET_LOCK.
 *
 * Guarded so it is safe to (re-)run.
 */
class CreateCronLocksTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('cron_locks')) {
            return;
        }

        $this->forge->addField([
            'name'       => ['type' => 'VARCHAR', 'constraint' => 64],
            'owner'      => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'locked_at'  => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        // name is the lock identity — one row per cron action, claimed/released in place.
        $this->forge->addKey('name', true);

        $this->forge->createTable('cron_locks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('cron_locks', true);
    }
}

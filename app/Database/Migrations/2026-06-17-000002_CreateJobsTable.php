<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateJobsTable — DB-backed job queue (Phase 3 / MT-2).
 *
 * Drained by `php spark queue:work`. Lets the request/cron path enqueue() heavy
 * external I/O (SMS blasts, email, MikroTik provisioning, payment-webhook router
 * enable) instead of running it inline and holding a PHP-FPM worker.
 *
 * Guarded so it is safe to (re-)run.
 */
class CreateJobsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('jobs')) {
            return;
        }

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'queue'        => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'default'],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 128],
            'payload'      => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'], // pending|reserved|done|dead
            'attempts'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'max_attempts' => ['type' => 'INT', 'constraint' => 11, 'default' => 3],
            'available_at' => ['type' => 'DATETIME', 'null' => true],
            'reserved_at'  => ['type' => 'DATETIME', 'null' => true],
            'reserved_by'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'error'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Supports the reserve() scan: WHERE queue=? AND status=? AND available_at<=?
        $this->forge->addKey(['queue', 'status', 'available_at']);

        $this->forge->createTable('jobs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('jobs', true);
    }
}

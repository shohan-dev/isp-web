<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * landing_integrations — super-admin-managed logos for the public landing
 * Integrations section (Core Rail + Also connects). Seeded with the previous
 * hardcoded list so a fresh migrate keeps the page looking the same.
 */
class CreateLandingIntegrationsTable extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('landing_integrations')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'logo_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'icon_class' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'tier' => [
                    'type'       => 'ENUM',
                    'constraint' => ['core', 'also'],
                    'default'    => 'also',
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['active', 'inactive'],
                    'default'    => 'active',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['status', 'tier', 'sort_order']);
            $this->forge->createTable('landing_integrations', true);
        }

        if ($this->db->tableExists('landing_integrations')) {
            $existing = (int) $this->db->table('landing_integrations')->countAllResults();
            if ($existing === 0) {
                $now = date('Y-m-d H:i:s');
                $rows = [
                    [
                        'name' => 'MikroTik',
                        'description' => 'Real-time PPPoE + hotspot sync. Disconnect on expiry, reconnect on payment — nobody SSHes in.',
                        'logo_path' => 'assets/img/landing/logos/mikrotik.svg',
                        'icon_class' => null,
                        'tier' => 'core',
                        'sort_order' => 1,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'bKash',
                        'description' => 'Every Send Money auto-matched to the right subscriber in under a second.',
                        'logo_path' => 'assets/img/landing/logos/bkash.svg',
                        'icon_class' => null,
                        'tier' => 'core',
                        'sort_order' => 2,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Nagad',
                        'description' => 'Same auto-reconciliation, same speed — no manual SMS matching, ever.',
                        'logo_path' => 'assets/img/landing/logos/nagad.svg',
                        'icon_class' => null,
                        'tier' => 'core',
                        'sort_order' => 3,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'SSLCommerz',
                        'description' => null,
                        'logo_path' => 'assets/img/landing/logos/sslcommerz.png',
                        'icon_class' => null,
                        'tier' => 'also',
                        'sort_order' => 1,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'aamarPay',
                        'description' => null,
                        'logo_path' => 'assets/img/landing/logos/aamarpay.png',
                        'icon_class' => null,
                        'tier' => 'also',
                        'sort_order' => 2,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'WhatsApp',
                        'description' => null,
                        'logo_path' => 'assets/img/landing/logos/whatsapp.svg',
                        'icon_class' => null,
                        'tier' => 'also',
                        'sort_order' => 3,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Telegram',
                        'description' => null,
                        'logo_path' => 'assets/img/landing/logos/telegram.svg',
                        'icon_class' => null,
                        'tier' => 'also',
                        'sort_order' => 4,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Google Maps',
                        'description' => null,
                        'logo_path' => 'assets/img/landing/logos/googlemaps.svg',
                        'icon_class' => null,
                        'tier' => 'also',
                        'sort_order' => 5,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'SMS Gateway',
                        'description' => null,
                        'logo_path' => null,
                        'icon_class' => 'fas fa-sms',
                        'tier' => 'also',
                        'sort_order' => 6,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'OLT Devices',
                        'description' => null,
                        'logo_path' => null,
                        'icon_class' => 'fas fa-broadcast-tower',
                        'tier' => 'also',
                        'sort_order' => 7,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];
                $this->db->table('landing_integrations')->insertBatch($rows);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('landing_integrations', true);
    }
}

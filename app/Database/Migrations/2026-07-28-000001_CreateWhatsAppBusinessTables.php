<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * WhatsApp Business AI — accounts, templates, message log, opt-ins, campaigns.
 */
class CreateWhatsAppBusinessTables extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('whatsapp_accounts')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'phone_number_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'display_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                ],
                'waba_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'access_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'verify_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                ],
                'app_secret' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'ai_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'utility_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'authentication_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'marketing_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'otp_channel' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'sms',
                    'null'       => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('phone_number_id');
            $this->forge->addUniqueKey('admin_user_id');
            $this->forge->createTable('whatsapp_accounts', true);
        }

        if (!$this->db->tableExists('whatsapp_templates')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'meta_template_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                ],
                'language' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 16,
                    'default'    => 'en',
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'PENDING',
                ],
                'event_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'body_preview' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'components_json' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'is_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['admin_user_id', 'category']);
            $this->forge->addKey(['admin_user_id', 'event_key']);
            $this->forge->createTable('whatsapp_templates', true);
        }

        if (!$this->db->tableExists('whatsapp_message_log')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'customer_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'wa_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                ],
                'direction' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 8,
                    'default'    => 'out',
                ],
                'template_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                ],
                'wamid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'queued',
                ],
                'error_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'billable' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'payload_redacted' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['admin_user_id', 'created_at']);
            $this->forge->addKey('wamid');
            $this->forge->createTable('whatsapp_message_log', true);
        }

        if (!$this->db->tableExists('whatsapp_opt_ins')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'wa_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                ],
                'marketing_opt_in' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'opted_in_at' => ['type' => 'DATETIME', 'null' => true],
                'opted_out_at' => ['type' => 'DATETIME', 'null' => true],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['admin_user_id', 'wa_phone']);
            $this->forge->createTable('whatsapp_opt_ins', true);
        }

        if (!$this->db->tableExists('whatsapp_campaigns')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'template_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'draft',
                ],
                'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
                'stats_json' => ['type' => 'TEXT', 'null' => true],
                'audience_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['admin_user_id', 'status']);
            $this->forge->createTable('whatsapp_campaigns', true);
        }

        if (!$this->db->tableExists('whatsapp_campaign_recipients')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'campaign_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'wa_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'pending',
                ],
                'wamid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                ],
                'error' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['campaign_id', 'status']);
            $this->forge->createTable('whatsapp_campaign_recipients', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('whatsapp_campaign_recipients', true);
        $this->forge->dropTable('whatsapp_campaigns', true);
        $this->forge->dropTable('whatsapp_opt_ins', true);
        $this->forge->dropTable('whatsapp_message_log', true);
        $this->forge->dropTable('whatsapp_templates', true);
        $this->forge->dropTable('whatsapp_accounts', true);
    }
}

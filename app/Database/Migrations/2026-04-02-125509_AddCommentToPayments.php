<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommentToPayments extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('payments')) {
            return;
        }

        if ($db->fieldExists('comment', 'payments')) {
            return;
        }

        $comment = [
            'type' => 'TEXT',
            'null' => true,
        ];

        if ($db->fieldExists('method_trx', 'payments')) {
            $comment['after'] = 'method_trx';
        }

        $this->forge->addColumn('payments', ['comment' => $comment]);
    }

    public function down()
    {
        if ($this->db->tableExists('payments') && $this->db->fieldExists('comment', 'payments')) {
            $this->forge->dropColumn('payments', 'comment');
        }
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Package-change pending state + free-trial end timestamp for sAdmin tenants.
 */
class AddPendingPackageAndTrialEndsAt extends Migration
{
    public function up()
    {
        $userColumns = [
            'pending_package_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'pre_package',
            ],
            'trial_ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'pending_package_id',
            ],
        ];

        foreach ($userColumns as $name => $definition) {
            if (!$this->db->fieldExists($name, 'users')) {
                $this->forge->addColumn('users', [$name => $definition]);
            }
        }

        $this->repairBrokenPackageChangeRows();
    }

    public function down()
    {
        foreach (['trial_ends_at', 'pending_package_id'] as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }

    /**
     * Repair rows stuck inactive after the old activatePackage swapped package_id.
     */
    private function repairBrokenPackageChangeRows(): void
    {
        if (!$this->db->fieldExists('pending_package_id', 'users')) {
            return;
        }

        $rows = $this->db->table('users')
            ->where('role', 'admin')
            ->where('subscription_status', 'inactive')
            ->where('pre_package IS NOT NULL', null, false)
            ->where('pre_package !=', 0)
            ->where('pre_package !=', '')
            ->get()
            ->getResult();

        $paymentModel = model('App\Models\Payment');
        $now = time();

        foreach ($rows as $row) {
            $prePackage = (int) ($row->pre_package ?? 0);
            $currentPackage = (int) ($row->package_id ?? 0);
            if ($prePackage <= 0 || $currentPackage <= 0 || $prePackage === $currentPackage) {
                continue;
            }

            $willExpireTs = !empty($row->will_expire) ? strtotime($row->will_expire) : false;
            $forcedExpired = $willExpireTs !== false && $willExpireTs <= $now;

            $hasPendingInvoice = false;
            if ($paymentModel) {
                $pending = $paymentModel->where('user_id', $row->id)
                    ->where('status', 'pending')
                    ->like('invoice', 'INV-', 'after')
                    ->first();
                $hasPendingInvoice = !empty($pending);
            }

            if (!$forcedExpired && !$hasPendingInvoice) {
                continue;
            }

            $update = [
                'package_id'          => $prePackage,
                'pending_package_id'  => $currentPackage,
                'pre_package'         => null,
                'subscription_status' => 'active',
                'conn_status'         => 'conn',
            ];

            if ($forcedExpired && $hasPendingInvoice && !empty($row->created_at)) {
                $update['will_expire'] = date('Y-m-d H:i:s', strtotime('+30 days'));
            }

            $this->db->table('users')->where('id', $row->id)->update($update);
        }
    }
}

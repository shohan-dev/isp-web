<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixPasswords extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Custom';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'fix:passwords';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'fix:passwords [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write("Starting PPPoE password repair process...", "yellow");

        helper('router');
        $routerDataModel = model('App\Models\UserRouterDataModel');

        $db = \Config\Database::connect();
        $builder = $db->table('user_router_data');
        $builder->select('user_router_data.*, users.code, users.name');
        $builder->join('users', 'users.id = user_router_data.user_id');
        $builder->like('user_router_data.router_password', '*');
        
        $query = $builder->get();
        $rows = $query->getResultArray();

        if (empty($rows)) {
            CLI::write("No users found with starred passwords. System is healthy.", "green");
            return;
        }

        CLI::write("Found " . count($rows) . " users with starred router passwords. Repairing...", "blue");

        foreach ($rows as $row) {
            $userId = $row['user_id'];
            $routerId = $row['router_id'];
            $pppoeName = $row['pppoe_secret'];
            $backupPassword = $row['code'] ?? '';

            if (empty($backupPassword) || preg_match('/^\*+$/', $backupPassword)) {
                CLI::write("[-] Skipped User {$row['name']} (ID: {$userId}): No valid plain-text password in users.code", "red");
                continue;
            }

            CLI::write("[*] Repairing User: {$row['name']} (ID: {$userId}), Secret: {$pppoeName}...", "yellow");

            // Attempt to connect to MikroTik
            $router_client = routerClient($routerId);
            if (!is_array($router_client)) {
                $pppoe = getPPPoEUserUserId($router_client, $userId);
                $pppoe_id = $pppoe[0]['.id'] ?? null;

                if ($pppoe_id) {
                    $user_ppp = getPPPoEUser($router_client, $pppoe_id);
                    $pppoe_profile = $user_ppp[0]['profile'] ?? '--';
                    $pppoe_service = $user_ppp[0]['service'] ?? 'pppoe';

                    $router_action = updatePPPoEUser($router_client, [
                        'pppoe_name' => $pppoeName,
                        'pppoe_password' => $backupPassword,
                        'pppoe_service' => $pppoe_service,
                        'pppoe_profile' => $pppoe_profile,
                        'pppoe_id' => $pppoe_id,
                    ]);

                    if (is_array($router_action) && $router_action['status'] === 'success') {
                        // Router successfully updated. Now update local DB.
                        $routerDataModel->update($row['id'], [
                            'router_password' => $backupPassword,
                            'last_updated' => date('Y-m-d H:i:s')
                        ]);
                        CLI::write("[+] Successfully repaired password for {$row['name']} (Restored: {$backupPassword})", "green");
                    } else {
                        $errorMsg = is_array($router_action) ? ($router_action['error'] ?? 'Unknown MikroTik error') : 'MikroTik update failed';
                        CLI::write("[-] Failed to update MikroTik for {$row['name']}: {$errorMsg}", "red");
                    }
                } else {
                    CLI::write("[-] PPPoE secret not found on MikroTik for {$row['name']}. Updating DB only.", "yellow");
                    $routerDataModel->update($row['id'], [
                        'router_password' => $backupPassword,
                        'last_updated' => date('Y-m-d H:i:s')
                    ]);
                }
            } else {
                CLI::write("[-] Failed to connect to MikroTik router {$routerId} for {$row['name']}. Updating DB only.", "yellow");
                $routerDataModel->update($row['id'], [
                    'router_password' => $backupPassword,
                    'last_updated' => date('Y-m-d H:i:s')
                ]);
            }
        }

        CLI::write("PPPoE password repair process finished.", "green");
    }
}

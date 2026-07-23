<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorModel extends Model
{
    protected $table = 'vendors';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'admin_id',
        'company_name',
        'contact_person',
        'email',
        'phone_number',
        'mobile_number',
        'facebook_url',
        'skype_id',
        'website',
        'address',
        'image',
    ];
    protected $useTimestamps = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    // Auto-create table if not exists
    public function ensureTableExists()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            $forge = \Config\Database::forge();
            $fields = [
                'id' => ['type' => 'INT', 'auto_increment' => true],
                'admin_id' => ['type'=> 'INT','constraint' => 11,'unsigned' => true,'null' => false],
                'company_name' => ['type' => 'VARCHAR', 'constraint' => 100],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 100],
                'email' => ['type' => 'VARCHAR', 'constraint' => 100],
                'phone_number' => ['type' => 'VARCHAR', 'constraint' => 30],
                'mobile_number' => ['type' => 'VARCHAR', 'constraint' => 30],
                'facebook_url' => ['type' => 'VARCHAR', 'constraint' => 255],
                'skype_id' => ['type' => 'VARCHAR', 'constraint' => 100],
                'website' => ['type' => 'VARCHAR', 'constraint' => 255],
                'address' => ['type' => 'TEXT'],
                'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ];
            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->createTable($this->table, true);
        }
    }

    public function getAllBandwidthPackages()
    {
        return $this->findAll();
    }
public function getCompanyNameById($id)
{
    // Phase-perf: request-scoped memo. vendor_suggestion is stored as free-form
    // TEXT (not a clean FK column) on requisitions/purchases, so a JOIN doesn't
    // fit that query shape — this is called once per grouped row in
    // Requisition/InventoryPurchess index(), an N+1 without memoization.
    static $__cache = [];
    static $__reqStamp = null;
    $stamp = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
    if ($__reqStamp !== $stamp) {
        $__cache = [];
        $__reqStamp = $stamp;
    }
    if (array_key_exists($id, $__cache)) {
        return $__cache[$id];
    }

    $vendor = $this->select('company_name')->find($id);
    return $__cache[$id] = ($vendor['company_name'] ?? null);
}
    // Method to get a specific bandwidth package by ID
    public function getBandwidthPackageById($id)
    {
        return $this->find($id);
    }

    // Method to add a new bandwidth package
    public function addBandwidthPackage($data)
    {
        return $this->insert($data);
    }

    // Method to update an existing bandwidth package
    public function updateBandwidthPackage($id, $data)
    {
        return $this->update($id, $data);
    }

    // Method to delete a bandwidth package
    public function deleteBandwidthPackage($id)
    {
        return $this->delete($id);
    }
}

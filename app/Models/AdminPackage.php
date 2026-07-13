<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminPackage extends Model
{
    protected $table = 'admin_packages';
    protected $primaryKey = 'id';

    public const TYPE_FIXED  = 'fixed';
    public const TYPE_PAYG   = 'payg';
    public const TYPE_CUSTOM = 'custom';

    protected function initialize()
    {
        // Phase-E1: once per FPM worker process
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $fieldsToAdd = [];
        if (!$db->fieldExists('plan_type', $this->table)) {
            $fieldsToAdd['plan_type'] = [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
                'default' => self::TYPE_FIXED,
                'after' => 'pricing_type',
            ];
        }
        if (!$db->fieldExists('base_fee', $this->table)) {
            $fieldsToAdd['base_fee'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => 0,
                'after' => 'price',
            ];
        }
        if (!$db->fieldExists('per_user_rate', $this->table)) {
            $fieldsToAdd['per_user_rate'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => 0,
                'after' => 'base_fee',
            ];
        }
        if (!$db->fieldExists('min_topup', $this->table)) {
            $fieldsToAdd['min_topup'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => 0,
                'after' => 'per_user_rate',
            ];
        }
        if (!$db->fieldExists('trial_days', $this->table)) {
            $fieldsToAdd['trial_days'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
                'after' => 'min_topup',
            ];
        }
        if (!$db->fieldExists('addons', $this->table)) {
            $fieldsToAdd['addons'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'features',
            ];
        }
        if (!$db->fieldExists('assigned_user_id', $this->table)) {
            $fieldsToAdd['assigned_user_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'addons',
            ];
        }
        if (!$db->fieldExists('is_public', $this->table)) {
            $fieldsToAdd['is_public'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'default' => 1,
                'after' => 'assigned_user_id',
            ];
        }
        if (!$db->fieldExists('sort_order', $this->table)) {
            $fieldsToAdd['sort_order'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
                'after' => 'is_public',
            ];
        }

        if (!empty($fieldsToAdd)) {
            $forge->addColumn($this->table, $fieldsToAdd);
        }
    }

    protected $allowedFields = [
        'package_name',
        'duration',
        'price',
        'pricing_type',
        'plan_type',
        'base_fee',
        'per_user_rate',
        'min_topup',
        'trial_days',
        'Activity',
        'preview',
        'features',
        'addons',
        'assigned_user_id',
        'is_public',
        'sort_order',
    ];

    protected $useTimestamps = true; // If you want to use created_at and updated_at fields

    /**
     * Automatically adds the 'features' column if it doesn't exist
     */
    public function checkFeaturesColumn()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('features', $this->table)) {
            $forge = \Config\Database::forge();
            $fields = [
                'features' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'preview',
                ],
            ];
            $forge->addColumn($this->table, $fields);
        }
    }

    /**
     * The single active Pay-As-You-Go plan row. Seeds a default one (matching
     * the rates advertised on the landing page) if none exists yet.
     *
     * @return object|array|null
     */
    public function paygPackage(bool $seedIfMissing = true)
    {
        $row = $this->where(['plan_type' => self::TYPE_PAYG])
            ->where('Activity', 'active')
            ->orderBy('id', 'asc')
            ->first();

        if ($row || !$seedIfMissing) {
            return $row;
        }

        $this->insert([
            'package_name'  => 'Pay As You Go',
            'duration'      => 0, // 0 = unlimited customers
            'price'         => 0,
            'pricing_type'  => 'monthly',
            'plan_type'     => self::TYPE_PAYG,
            'base_fee'      => 500,
            'per_user_rate' => 1.50,
            'min_topup'     => 750,
            'trial_days'    => 14,
            'Activity'      => 'active',
            'preview'       => 30,
            'features'      => json_encode([
                'Billing & Invoicing', 'Customer CRM', 'Mikrotik Integration',
                'SMS Automation', 'No customer limit — pay per active user',
            ]),
            'addons'        => json_encode([
                ['key' => 'sms', 'label' => 'SMS Credits', 'price' => 200],
                ['key' => 'whitelabel', 'label' => 'White Label', 'price' => 500],
                ['key' => 'backup', 'label' => 'Extra Backups', 'price' => 150],
                ['key' => 'whatsapp', 'label' => 'WhatsApp Alerts', 'price' => 100],
            ]),
            'is_public'     => 1,
            'sort_order'    => 99,
        ]);

        return $this->find($this->getInsertID());
    }

    /**
     * Public fixed plans for the landing page / registration form.
     */
    public function publicFixedPackages()
    {
        return $this->where('Activity', 'active')
            ->groupStart()
                ->where('plan_type', self::TYPE_FIXED)
                ->orWhere('plan_type IS NULL', null, false)
                ->orWhere('plan_type', '')
            ->groupEnd()
            ->groupStart()
                ->where('is_public', 1)
                ->orWhere('is_public IS NULL', null, false)
            ->groupEnd()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->findAll();
    }

    /**
     * Decoded PAYG add-on catalog for a payg package row.
     *
     * @param object|array|null $pkg
     */
    public static function addonCatalog($pkg): array
    {
        if (empty($pkg)) {
            return [];
        }
        $raw = is_object($pkg) ? ($pkg->addons ?? null) : ($pkg['addons'] ?? null);
        $list = json_decode((string) $raw, true);
        if (!is_array($list)) {
            return [];
        }

        $catalog = [];
        foreach ($list as $addon) {
            if (!is_array($addon) || empty($addon['key'])) {
                continue;
            }
            $catalog[$addon['key']] = [
                'key'   => (string) $addon['key'],
                'label' => (string) ($addon['label'] ?? $addon['key']),
                'price' => (float) ($addon['price'] ?? 0),
            ];
        }

        return $catalog;
    }

    /**
     * Landing-page pricing payload: public fixed tiers + PAYG rates + add-ons.
     * Falls back to the rates hardcoded on the marketing page when the DB is empty.
     *
     * @return array{tiers: array<string, array{id: ?int, price: float, cap: int, name: string}>,
     *               payg: array{platform: float, perUser: float, minWallet: float, id?: int},
     *               addons: array<string, array{key: string, label: string, price: float}>,
     *               fixedPlans: list<array|object>}
     */
    public function landingPricingPayload(): array
    {
        $tierKeys = ['basic', 'standard', 'premium', 'business', 'enterprise', 'ultimate'];
        $tiers = [
            'basic'      => ['id' => null, 'price' => 999.0,   'cap' => 500,   'name' => 'Basic'],
            'standard'   => ['id' => null, 'price' => 2499.0,  'cap' => 2000,  'name' => 'Standard'],
            'premium'    => ['id' => null, 'price' => 4999.0,  'cap' => 5000,  'name' => 'Premium'],
            'business'   => ['id' => null, 'price' => 8499.0,  'cap' => 10000, 'name' => 'Business'],
            'enterprise' => ['id' => null, 'price' => 14999.0, 'cap' => 20000, 'name' => 'Enterprise'],
            'ultimate'   => ['id' => null, 'price' => 24999.0, 'cap' => 40000, 'name' => 'Ultimate'],
        ];

        $fixed = $this->publicFixedPackages();
        foreach (array_slice($fixed, 0, 6) as $i => $pkg) {
            $key = $tierKeys[$i] ?? ('tier' . $i);
            $row = is_object($pkg) ? (array) $pkg : $pkg;
            $tiers[$key] = [
                'id'    => (int) ($row['id'] ?? 0),
                'price' => (float) ($row['price'] ?? 0),
                'cap'   => (int) ($row['duration'] ?? 0),
                'name'  => (string) ($row['package_name'] ?? ucfirst($key)),
            ];
        }

        $payg = [
            'platform'  => 500.0,
            'perUser'   => 1.5,
            'minWallet' => 750.0,
        ];
        $addons = [
            'sms'        => ['key' => 'sms', 'label' => 'SMS Credits', 'price' => 200.0],
            'whitelabel' => ['key' => 'whitelabel', 'label' => 'White Label', 'price' => 500.0],
            'backup'     => ['key' => 'backup', 'label' => 'Extra Backups', 'price' => 150.0],
            'whatsapp'   => ['key' => 'whatsapp', 'label' => 'WhatsApp Alerts', 'price' => 100.0],
        ];

        $paygRow = $this->paygPackage();
        if (!empty($paygRow)) {
            $row = is_object($paygRow) ? (array) $paygRow : $paygRow;
            $payg = [
                'platform'  => (float) ($row['base_fee'] ?? 500),
                'perUser'   => (float) ($row['per_user_rate'] ?? 1.5),
                'minWallet' => (float) ($row['min_topup'] ?? 750),
                'id'        => (int) ($row['id'] ?? 0),
            ];
            $catalog = self::addonCatalog($paygRow);
            if (!empty($catalog)) {
                $addons = $catalog;
            }
        }

        return [
            'tiers'      => $tiers,
            'payg'       => $payg,
            'addons'     => $addons,
            'fixedPlans' => array_slice($fixed, 0, 6),
        ];
    }

    /**
     * Resolve a landing-page plan token (id, payg, custom, or tier slug) to the
     * value stored in registrations / the registration form.
     */
    public function resolvePlanToken(string $token): string
    {
        $token = trim($token);
        if ($token === '' || in_array($token, ['payg', 'custom'], true) || ctype_digit($token)) {
            return $token;
        }

        $slug = strtolower($token);
        $tierKeys = ['basic' => 0, 'standard' => 1, 'premium' => 2, 'business' => 3, 'enterprise' => 4, 'ultimate' => 5];
        if (isset($tierKeys[$slug])) {
            $fixed = $this->publicFixedPackages();
            if (isset($fixed[$tierKeys[$slug]])) {
                $pkg = $fixed[$tierKeys[$slug]];

                return (string) (is_object($pkg) ? $pkg->id : $pkg['id']);
            }
        }

        foreach ($this->publicFixedPackages() as $pkg) {
            $name = strtolower((string) (is_object($pkg) ? ($pkg->package_name ?? '') : ($pkg['package_name'] ?? '')));
            if ($name !== '' && str_contains($name, $slug)) {
                return (string) (is_object($pkg) ? $pkg->id : $pkg['id']);
            }
        }

        return $token;
    }
}

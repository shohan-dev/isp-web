<?php

namespace Zapi\Modules\Shared\Rewards\Models;

use CodeIgniter\Model;

/**
 * One stable, shareable referral code per existing customer (the referrer).
 */
class ReferralCodeModel extends Model
{
    protected $table         = 'referral_codes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'code',
        'owner_id',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    public function ensureTableExists(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 16],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->addUniqueKey('user_id');
        $forge->addKey('owner_id');
        $forge->createTable($this->table, true);
    }

    /**
     * Return the user's existing code, or create a fresh unique one.
     *
     * Concurrency-safe: the insert uses INSERT IGNORE against the UNIQUE
     * user_id key, then re-reads the authoritative row, so two concurrent
     * first-time calls for the same user never crash on a duplicate-key error
     * and always converge on the same persisted code.
     */
    public function getOrCreateForUser(int $userId, int $ownerId, ?string $nameSeed = null): string
    {
        $row = $this->where('user_id', $userId)->first();
        if ($row) {
            return is_object($row) ? (string) $row->code : (string) $row['code'];
        }

        $code = $this->generateUniqueCode($nameSeed);
        $this->db->table($this->table)->ignore(true)->insert([
            'user_id'    => $userId,
            'code'       => $code,
            'owner_id'   => $ownerId,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Re-read: under a race the other request may have won the unique slot.
        $row = $this->where('user_id', $userId)->first();
        if ($row) {
            return is_object($row) ? (string) $row->code : (string) $row['code'];
        }
        return $code;
    }

    public function findActiveByCode(string $code)
    {
        return $this->where('code', strtoupper(trim($code)))
            ->where('status', 'active')
            ->first();
    }

    private function generateUniqueCode(?string $nameSeed): string
    {
        // Optional readable prefix from the referrer's name (e.g. SHOHAN123).
        $prefix = '';
        if ($nameSeed) {
            $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $nameSeed));
            $prefix = substr($clean, 0, 6);
        }

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $suffix = (string) random_int(100, 999);
            $candidate = $prefix !== ''
                ? $prefix . $suffix
                : $this->randomAlnum(8);
            $candidate = substr(strtoupper($candidate), 0, 16);
            if (!$this->where('code', $candidate)->first()) {
                return $candidate;
            }
        }

        // Extremely unlikely fallback: time-based unique token.
        return strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
    }

    private function randomAlnum(int $len): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}

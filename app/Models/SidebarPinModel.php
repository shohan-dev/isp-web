<?php

namespace App\Models;

use CodeIgniter\Model;

class SidebarPinModel extends Model
{
    protected $table         = 'sidebar_pins';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'pin_key',
        'label',
        'icon',
        'href',
        'created_at',
    ];

    /** Sidebar can only usefully show so many "quick access" links. */
    public const MAX_PINS_PER_USER = 12;

    /**
     * This user's pins, oldest first (stable order, no drag-reorder yet).
     */
    public function getForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Add the pin if it doesn't exist, remove it if it does.
     *
     * @return array{pinned: bool, reason?: string}
     */
    public function toggle(int $userId, string $pinKey, string $label, string $icon, string $href): array
    {
        $existing = $this->where('user_id', $userId)->where('pin_key', $pinKey)->first();

        if ($existing !== null) {
            $this->delete($existing['id']);

            return ['pinned' => false];
        }

        $count = $this->where('user_id', $userId)->countAllResults();
        if ($count >= self::MAX_PINS_PER_USER) {
            return ['pinned' => false, 'reason' => 'limit'];
        }

        $this->insert([
            'user_id'    => $userId,
            'pin_key'    => $pinKey,
            'label'      => $label,
            'icon'       => $icon,
            'href'       => $href,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['pinned' => true];
    }
}

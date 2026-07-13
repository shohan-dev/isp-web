<?php

namespace App\Models;

use CodeIgniter\Model;

class LandingTestimonial extends Model
{
    protected $table = 'landing_testimonials';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'name',
        'role',
        'company',
        'quote',
        'avatar_initials',
        'rating',
        'sort_order',
        'is_active',
        'created_at',
    ];

    /**
     * Active testimonials for the public landing page, ordered for display.
     *
     * @return list<array<string, mixed>>
     */
    public function getActiveForLanding(): array
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        return $this->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}

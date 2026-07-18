<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * product_showcase_images — screenshots that belong to a ProductShowcaseCategory.
 * Timestamps are set manually (created_at only, matching LandingTestimonial's
 * habit), never via CI4's useTimestamps.
 */
class ProductShowcaseImage extends Model
{
    protected $table         = 'product_showcase_images';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'category_id',
        'image_path',
        'caption',
        'sort_order',
        'created_at',
    ];

    /**
     * All images for a category, ordered for display.
     *
     * @return list<array<string, mixed>>
     */
    public function forCategory(int $categoryId): array
    {
        return $this->where('category_id', $categoryId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function nextSortOrder(int $categoryId): int
    {
        $max = $this->where('category_id', $categoryId)->selectMax('sort_order')->first();

        return (int) ($max['sort_order'] ?? 0) + 1;
    }

    /**
     * Removes the physical file (if present) before deleting the DB row —
     * mirrors Plugins.php's delete-before-move order.
     */
    public function deleteAndUnlink(int $id): bool
    {
        $image = $this->find($id);
        if (!$image) {
            return false;
        }

        $path = FCPATH . ltrim((string) $image['image_path'], '/');
        if (file_exists($path)) {
            @unlink($path);
        }

        return (bool) $this->delete($id);
    }

    /**
     * Deletes every image for a category, unlinking each file first.
     */
    public function deleteAndUnlinkForCategory(int $categoryId): void
    {
        foreach ($this->where('category_id', $categoryId)->findAll() as $image) {
            $this->deleteAndUnlink((int) $image['id']);
        }
    }

    /**
     * Batch-updates sort_order for the admin's numeric reorder UI.
     *
     * @param array<int, int> $idToSortOrder image id => new sort_order
     */
    public function reorder(array $idToSortOrder): void
    {
        foreach ($idToSortOrder as $id => $sortOrder) {
            $this->update((int) $id, ['sort_order' => (int) $sortOrder]);
        }
    }
}

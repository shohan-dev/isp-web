<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * product_showcase_categories — super-admin-managed website/mobile screenshot
 * gallery categories shown on the public landing page product showcase.
 * Timestamps are set manually (created_at/updated_at), never via CI4's
 * useTimestamps, matching this codebase's category-model habit.
 */
class ProductShowcaseCategory extends Model
{
    protected $table         = 'product_showcase_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'name',
        'slug',
        'target',
        'bullets',
        'sort_order',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Active categories for a target (website|mobile), ordered for display.
     *
     * @return list<array<string, mixed>>
     */
    public function listActiveForTarget(string $target): array
    {
        return $this->where('target', $target)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * All categories for a target, regardless of status — used by the admin UI.
     *
     * @return list<array<string, mixed>>
     */
    public function listForTarget(string $target): array
    {
        return $this->where('target', $target)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->where('slug', $slug);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Deletes the category's images (unlinking their files) then the category
     * row itself.
     */
    public function deleteWithImages(int $id): bool
    {
        (new ProductShowcaseImage())->deleteAndUnlinkForCategory($id);

        return (bool) $this->delete($id);
    }

    /**
     * Shared data contract consumed by the landing page: active categories
     * (per target, with at least one image) joined with their images.
     *
     * @return array{website: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    public static function landingShowcasePayload(): array
    {
        $categoryModel = new self();
        $imageModel    = new ProductShowcaseImage();

        $payload = ['website' => [], 'mobile' => []];

        foreach (['website', 'mobile'] as $target) {
            $categories = $categoryModel->listActiveForTarget($target);

            foreach ($categories as $category) {
                $images = $imageModel->forCategory((int) $category['id']);
                if (empty($images)) {
                    continue;
                }

                $bullets = json_decode((string) ($category['bullets'] ?? ''), true);
                if (!is_array($bullets)) {
                    $bullets = [];
                }

                $payload[$target][] = [
                    'id'      => (int) $category['id'],
                    'slug'    => (string) $category['slug'],
                    'name'    => (string) $category['name'],
                    'bullets' => array_values($bullets),
                    'images'  => array_map(static fn (array $image) => [
                        'id'      => (int) $image['id'],
                        'url'     => base_url(ltrim((string) $image['image_path'], '/')),
                        'caption' => (string) ($image['caption'] ?? ''),
                    ], $images),
                ];
            }
        }

        return $payload;
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * landing_integrations — super-admin-managed Core Rail / Also-connects logos
 * for the public landing Integrations section.
 */
class LandingIntegration extends Model
{
    protected $table         = 'landing_integrations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'name',
        'description',
        'logo_path',
        'icon_class',
        'tier',
        'sort_order',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllOrdered(): array
    {
        // DESC puts "core" before "also" alphabetically.
        return $this->orderBy('tier', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActiveForTier(string $tier): array
    {
        return $this->where('tier', $tier)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Shared landing payload. Falls back to empty lists on failure so the
     * view can keep rendering (and optionally use its own static defaults).
     *
     * @return array{core: list<array<string, mixed>>, also: list<array<string, mixed>>}
     */
    public static function landingPayload(): array
    {
        $model = new self();
        $payload = ['core' => [], 'also' => []];

        foreach (['core', 'also'] as $tier) {
            foreach ($model->listActiveForTier($tier) as $row) {
                $payload[$tier][] = self::toLandingItem($row);
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{name: string, desc?: string, logo?: string, icon?: string}
     */
    public static function toLandingItem(array $row): array
    {
        $item = [
            'name' => (string) ($row['name'] ?? ''),
        ];

        $desc = trim((string) ($row['description'] ?? ''));
        if ($desc !== '') {
            $item['desc'] = $desc;
        }

        $logoPath = trim((string) ($row['logo_path'] ?? ''));
        if ($logoPath !== '' && is_file(FCPATH . ltrim($logoPath, '/'))) {
            $item['logo'] = base_url(ltrim($logoPath, '/'));
        } else {
            $icon = trim((string) ($row['icon_class'] ?? ''));
            if ($icon !== '') {
                $item['icon'] = $icon;
            }
        }

        return $item;
    }

    public function deleteAndUnlink(int $id): bool
    {
        $row = $this->find($id);
        if (!$row) {
            return false;
        }

        $this->unlinkLogoIfManaged((string) ($row['logo_path'] ?? ''));

        return (bool) $this->delete($id);
    }

    /**
     * Only delete files under the managed upload folder — never wipe the
     * seeded static assets in assets/img/landing/logos/.
     */
    public function unlinkLogoIfManaged(string $logoPath): void
    {
        $logoPath = ltrim($logoPath, '/');
        if ($logoPath === '' || strpos($logoPath, 'assets/img/landing/integrations/') !== 0) {
            return;
        }

        $full = FCPATH . $logoPath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

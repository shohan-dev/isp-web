<?php

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\RecycleBinModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\Trash;

/**
 * Central trash/restore service — snapshots parent + declared children to recycle_bin,
 * then hard-deletes from source tables inside a transaction.
 */
class TrashService
{
    protected BaseConnection $db;
    protected Trash $config;
    protected RecycleBinModel $binModel;
    protected AuditLogModel $auditModel;

    /** @var array<string, string> */
    private array $tablePrimaryKeys = [
        'packages'         => 'id',
        'users'            => 'id',
        'user_router_data' => 'id',
        'registrations'    => 'id',
        'tickets'          => 'id',
    ];

    /** Delete children before parents. */
    private array $deleteTableOrder = [
        'user_router_data',
        'registrations',
        'users',
        'tickets',
        'packages',
    ];

    public function __construct(?BaseConnection $db = null, ?Trash $config = null)
    {
        $this->db         = $db ?? Database::connect();
        $this->config     = $config ?? config('Trash');
        $this->binModel   = new RecycleBinModel($this->db);
        $this->auditModel = new AuditLogModel($this->db);
    }

    /**
     * Snapshot rows to recycle_bin and hard-delete from source.
     *
     * @param array<int, object|array<string, mixed>> $rows
     */
    public function trash(string $entity, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $def = $this->entityDef($entity);
        $count = 0;
        $trashedIds = [];

        $this->db->transException(true)->transStart();

        try {
            foreach ($rows as $row) {
                $parent = $this->rowToArray($row);
                $parentId = (int) ($parent[$def['pk']] ?? 0);
                if ($parentId <= 0) {
                    continue;
                }

                $children = $this->fetchChildren($def['children'] ?? [], $parentId);
                $payload  = json_encode([
                    'parent'   => $parent,
                    'children' => $children,
                ], JSON_UNESCAPED_UNICODE);

                $actor = $this->actorContext();
                $now   = date('Y-m-d H:i:s');
                $label = (string) ($parent[$def['label']] ?? $entity);

                $this->binModel->insert([
                    'tenant_id'       => $this->resolveTenantId($def, $parent),
                    'entity'          => $entity,
                    'entity_label'    => mb_substr($label, 0, 191),
                    'source_table'    => $def['table'],
                    'source_id'       => $parentId,
                    'payload'         => $payload,
                    'deleted_by'      => $actor['deleted_by'],
                    'deleted_by_name' => $actor['deleted_by_name'],
                    'ip_address'      => $actor['ip_address'],
                    'created_at'      => $now,
                    'expires_at'      => date('Y-m-d H:i:s', strtotime('+' . $this->config->retentionDays . ' days')),
                    'restored_at'     => null,
                ]);

                $this->hardDeleteSnapshot($parent, $children, $def['table'], $def['pk']);
                $trashedIds[] = $parentId;
                $count++;
            }

            if ($count > 0) {
                $this->writeAudit('trash', $entity, [
                    'count' => $count,
                    'ids'   => $trashedIds,
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Trash transaction failed.');
            }
        } catch (\Throwable $e) {
            // transRollback() itself is a safe no-op when no transaction is open
            // (transDepth === 0) — do NOT gate this on transStatus(): CodeIgniter
            // sets transStatus to false precisely when a query fails, so gating
            // on "!== false" skipped the rollback on exactly the failures that
            // needed one, leaving the transaction open and the connection's
            // transStatus/transDepth corrupted for every later operation.
            $this->db->transRollback();
            throw $e;
        }

        return $count;
    }

    /**
     * Restore a bin row into source tables. Refuses if parent pk already exists.
     */
    public function restore(int $binId, int $tenantId): bool
    {
        $bin = $this->loadBinRow($binId, $tenantId);
        if ($bin === null) {
            return false;
        }

        if ($bin->restored_at !== null) {
            return true;
        }

        $payload = json_decode((string) $bin->payload, true);
        if (!is_array($payload) || empty($payload['parent'])) {
            return false;
        }

        $def    = $this->entityDef((string) $bin->entity);
        $parent = $payload['parent'];
        $pk     = $def['pk'];
        $parentId = (int) ($parent[$pk] ?? 0);

        if ($parentId <= 0) {
            return false;
        }

        if ($this->db->table($def['table'])->where($pk, $parentId)->countAllResults() > 0) {
            return false;
        }

        $children = $payload['children'] ?? [];

        $this->db->transException(true)->transStart();

        try {
            $this->db->table($def['table'])->insert($parent);

            foreach ($this->sortChildGroups($children) as $group) {
                foreach ($group['rows'] as $childRow) {
                    $table = $group['table'];
                    $childPk = $childRow[$this->pkForTable($table)] ?? null;
                    if ($childPk !== null && $this->db->table($table)->where($this->pkForTable($table), $childPk)->countAllResults() > 0) {
                        throw new \RuntimeException("Restore refused: {$table} id {$childPk} already exists.");
                    }
                    $this->db->table($table)->insert($childRow);
                }
            }

            $this->binModel->update($binId, ['restored_at' => date('Y-m-d H:i:s')]);

            $this->writeAudit('restore', (string) $bin->entity, [
                'bin_id'    => $binId,
                'source_id' => $parentId,
            ]);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Restore transaction failed.');
            }
        } catch (\Throwable $e) {
            // transRollback() itself is a safe no-op when no transaction is open
            // (transDepth === 0) — do NOT gate this on transStatus(): CodeIgniter
            // sets transStatus to false precisely when a query fails, so gating
            // on "!== false" skipped the rollback on exactly the failures that
            // needed one, leaving the transaction open and the connection's
            // transStatus/transDepth corrupted for every later operation.
            $this->db->transRollback();
            throw $e;
        }

        if (in_array($bin->entity, ['package', 'customer'], true) && function_exists('bumpLookupCacheVersion')) {
            bumpLookupCacheVersion($tenantId);
        }

        return true;
    }

    public function deleteForever(int $binId, int $tenantId): bool
    {
        $bin = $this->loadBinRow($binId, $tenantId);
        if ($bin === null) {
            return false;
        }

        $deleted = $this->binModel->delete($binId);
        if ($deleted) {
            $this->writeAudit('purge', (string) $bin->entity, [
                'bin_id'    => $binId,
                'source_id' => (int) $bin->source_id,
                'manual'    => true,
            ]);
        }

        return (bool) $deleted;
    }

    public function emptyTrash(int $tenantId): int
    {
        $rows = $this->binModel
            ->where('tenant_id', $tenantId)
            ->where('restored_at IS NULL', null, false)
            ->findAll();

        $count = 0;
        foreach ($rows as $row) {
            if ($this->deleteForever((int) $row->id, $tenantId)) {
                $count++;
            }
        }

        if ($count > 0) {
            $this->writeAudit('purge', 'recycle_bin', [
                'tenant_id' => $tenantId,
                'count'     => $count,
                'empty'     => true,
            ]);
        }

        return $count;
    }

    /**
     * Delete expired bin rows in bounded batches (cron).
     */
    public function purgeExpired(int $batch = 1000): int
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->binModel
            ->where('expires_at <=', $now)
            ->where('restored_at IS NULL', null, false)
            ->orderBy('id', 'ASC')
            ->findAll($batch);

        $count = 0;
        foreach ($rows as $row) {
            if ($this->binModel->delete((int) $row->id)) {
                $count++;
            }
        }

        if ($count > 0) {
            $this->writeAudit('purge', 'recycle_bin', [
                'count'   => $count,
                'expired' => true,
            ]);
        }

        return $count;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function entityDef(string $entity): array
    {
        $def = $this->config->entities[$entity] ?? null;
        if ($def === null) {
            throw new \InvalidArgumentException("Unknown trash entity: {$entity}");
        }

        return $def;
    }

    /**
     * @param array<int, array<string, mixed>> $childDefs
     * @return array<int, array{table: string, rows: array<int, array<string, mixed>>}>
     */
    private function fetchChildren(array $childDefs, int $parentId): array
    {
        $groups = [];

        foreach ($childDefs as $def) {
            $builder = $this->db->table($def['table'])->where($def['fk'], $parentId);
            if (!empty($def['where']) && is_array($def['where'])) {
                foreach ($def['where'] as $col => $val) {
                    $builder->where($col, $val);
                }
            }

            $rows = $builder->get()->getResultArray();
            if ($rows !== []) {
                $groups[] = ['table' => $def['table'], 'rows' => $rows];
            }

            if (!empty($def['children']) && is_array($def['children'])) {
                foreach ($rows as $childRow) {
                    $childId = (int) ($childRow['id'] ?? 0);
                    if ($childId > 0) {
                        $nested = $this->fetchChildren($def['children'], $childId);
                        $groups = array_merge($groups, $nested);
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * @param array<string, mixed> $parent
     * @param array<int, array{table: string, rows: array<int, array<string, mixed>>}> $children
     */
    private function hardDeleteSnapshot(array $parent, array $children, string $parentTable, string $parentPk): void
    {
        foreach ($this->sortChildGroups($children) as $group) {
            $pkCol = $this->pkForTable($group['table']);
            foreach ($group['rows'] as $row) {
                $id = $row[$pkCol] ?? null;
                if ($id !== null) {
                    $this->db->table($group['table'])->delete([$pkCol => $id]);
                }
            }
        }

        $this->db->table($parentTable)->delete([$parentPk => $parent[$parentPk]]);
    }

    /**
     * @param array<int, array{table: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<int, array{table: string, rows: array<int, array<string, mixed>>}>
     */
    private function sortChildGroups(array $groups): array
    {
        usort($groups, function (array $a, array $b): int {
            $order = array_flip($this->deleteTableOrder);
            $ai = $order[$a['table']] ?? 99;
            $bi = $order[$b['table']] ?? 99;

            return $ai <=> $bi;
        });

        return $groups;
    }

    /**
     * @param array<string, mixed> $def
     * @param array<string, mixed> $parent
     */
    private function resolveTenantId(array $def, array $parent): int
    {
        $mode = (string) ($def['tenant'] ?? 'field:admin_id');
        [$type, $column] = array_pad(explode(':', $mode, 2), 2, 'admin_id');

        // Config\Trash's entity registry uses 'sadmin:<column>' (see customer/reseller/
        // support_ticket) — 'admin' here never matched, so tenant_id silently fell through
        // to the parent row's own id/column value instead of resolving the real tenant admin.
        if ($type === 'sadmin') {
            helper('user');

            return (int) getSAdminIdForUser((int) ($parent[$column] ?? 0));
        }

        return (int) ($parent[$column] ?? 0);
    }

    private function pkForTable(string $table): string
    {
        return $this->tablePrimaryKeys[$table] ?? 'id';
    }

    /**
     * @return array{deleted_by: ?int, deleted_by_name: ?string, ip_address: ?string}
     */
    private function actorContext(): array
    {
        $session = session();
        $request = service('request');

        return [
            'deleted_by'      => $session ? $session->get('user_id') : null,
            'deleted_by_name' => $session
                ? (string) ($session->get('user_name') ?? $session->get('username') ?? $session->get('name') ?? '')
                : null,
            'ip_address'      => $request ? (string) $request->getIPAddress() : null,
        ];
    }

    private function loadBinRow(int $binId, int $tenantId): ?object
    {
        return $this->binModel
            ->where('id', $binId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function rowToArray(object|array $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        return (array) $row;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function writeAudit(string $action, string $entity, array $details): void
    {
        $actor = $this->actorContext();

        $this->auditModel->log([
            'user_id'    => $actor['deleted_by'],
            'action'     => $action,
            'entity'     => $entity,
            'actor'      => $actor['deleted_by_name'] ?: 'system',
            'ip_address' => $actor['ip_address'],
            'details'    => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }
}

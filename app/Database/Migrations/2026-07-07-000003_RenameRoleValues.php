<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Collision-safe role rename: admin→super_admin (platform), sAdmin→admin (tenant).
 *
 * Touches role-string columns only: users.role, permissions.user_type, users.created_by.
 * Does NOT modify numeric created_by in finance tables or admin_id columns.
 */
class RenameRoleValues extends Migration
{
    private const TMP = '__SA_TMP__';

    /** @var list<string> */
    private const ROLE_COLUMNS = [
        ['users', 'role'],
        ['permissions', 'user_type'],
        ['users', 'created_by'],
    ];

    /** Role-string values stored in users.created_by (exclude numeric IDs). */
    private const CREATED_BY_ROLE_VALUES = ['admin', 'sAdmin', 'resellerAdmin'];

    public function up(): void
    {
        $db = $this->db;

        foreach (self::ROLE_COLUMNS as [$table, $column]) {
            if (!$db->tableExists($table) || !$db->fieldExists($column, $table)) {
                continue;
            }

            $this->widenEnumIfNeeded($table, $column, array_merge(
                self::legacyEnumValues(),
                self::targetEnumValues(),
                [self::TMP]
            ));

            if ($table === 'users' && $column === 'created_by') {
                $this->swapCreatedByRoleStrings();
                continue;
            }

            $this->swapColumn($table, $column);
            $this->narrowEnumIfNeeded($table, $column, self::targetEnumValues());
        }
    }

    public function down(): void
    {
        $db = $this->db;

        foreach (self::ROLE_COLUMNS as [$table, $column]) {
            if (!$db->tableExists($table) || !$db->fieldExists($column, $table)) {
                continue;
            }

            $this->widenEnumIfNeeded($table, $column, array_merge(
                self::legacyEnumValues(),
                self::targetEnumValues(),
                [self::TMP]
            ));

            if ($table === 'users' && $column === 'created_by') {
                $this->reverseSwapCreatedByRoleStrings();
                continue;
            }

            $this->reverseSwapColumn($table, $column);
            $this->narrowEnumIfNeeded($table, $column, self::legacyEnumValues());
        }
    }

    private function swapColumn(string $table, string $column): void
    {
        $db = $this->db;
        $tmp = self::TMP;

        $db->table($table)->where($column, 'admin')->update([$column => $tmp]);
        $db->table($table)->where($column, 'sAdmin')->update([$column => 'admin']);
        $db->table($table)->where($column, $tmp)->update([$column => 'super_admin']);
    }

    private function reverseSwapColumn(string $table, string $column): void
    {
        $db = $this->db;
        $tmp = self::TMP;

        $db->table($table)->where($column, 'super_admin')->update([$column => $tmp]);
        $db->table($table)->where($column, 'admin')->update([$column => 'sAdmin']);
        $db->table($table)->where($column, $tmp)->update([$column => 'admin']);
    }

    private function swapCreatedByRoleStrings(): void
    {
        $db = $this->db;
        $tmp = self::TMP;

        $db->table('users')
            ->whereIn('created_by', ['admin'])
            ->update(['created_by' => $tmp]);

        $db->table('users')
            ->whereIn('created_by', ['sAdmin'])
            ->update(['created_by' => 'admin']);

        $db->table('users')
            ->whereIn('created_by', [$tmp])
            ->update(['created_by' => 'super_admin']);
    }

    private function reverseSwapCreatedByRoleStrings(): void
    {
        $db = $this->db;
        $tmp = self::TMP;

        $db->table('users')
            ->where('created_by', 'super_admin')
            ->update(['created_by' => $tmp]);

        $db->table('users')
            ->where('created_by', 'admin')
            ->update(['created_by' => 'sAdmin']);

        $db->table('users')
            ->where('created_by', $tmp)
            ->update(['created_by' => 'admin']);
    }

    /** @return list<string> */
    private static function targetEnumValues(): array
    {
        return ['super_admin', 'admin', 'resellerAdmin', 'employee', 'user'];
    }

    /** @return list<string> */
    private static function legacyEnumValues(): array
    {
        return ['admin', 'sAdmin', 'resellerAdmin', 'employee', 'user'];
    }

    /**
     * Widen ENUM columns to include swap temporaries. No-op for VARCHAR.
     *
     * @param list<string> $values Enum members to allow during the swap.
     */
    private function widenEnumIfNeeded(string $table, string $column, array $values): void
    {
        $field = $this->dbFieldMeta($table, $column);
        if ($field === null || stripos($field['type'] ?? '', 'enum') === false) {
            return;
        }

        $values = array_values(array_unique($values));
        $quoted = array_map(
            fn (string $v): string => $this->db->escape($v),
            $values
        );

        $null = ($field['nullable'] ?? false) ? 'NULL' : 'NOT NULL';
        $default = isset($field['default']) && $field['default'] !== null
            ? ' DEFAULT ' . $this->db->escape($field['default'])
            : '';

        $this->db->query(sprintf(
            'ALTER TABLE %s MODIFY %s ENUM(%s) %s%s',
            $this->db->escapeIdentifiers($table),
            $this->db->escapeIdentifiers($column),
            implode(',', $quoted),
            $null,
            $default
        ));
    }

  /**
     * Narrow ENUM to final role set after swap. No-op for VARCHAR.
     *
     * @param list<string> $values
     */
    private function narrowEnumIfNeeded(string $table, string $column, array $values): void
    {
        $field = $this->dbFieldMeta($table, $column);
        if ($field === null || stripos($field['type'] ?? '', 'enum') === false) {
            return;
        }

        $quoted = array_map(
            fn (string $v): string => $this->db->escape($v),
            $values
        );

        $null = ($field['nullable'] ?? false) ? 'NULL' : 'NOT NULL';
        $default = isset($field['default']) && $field['default'] !== null
            ? ' DEFAULT ' . $this->db->escape($field['default'])
            : '';

        $this->db->query(sprintf(
            'ALTER TABLE %s MODIFY %s ENUM(%s) %s%s',
            $this->db->escapeIdentifiers($table),
            $this->db->escapeIdentifiers($column),
            implode(',', $quoted),
            $null,
            $default
        ));
    }

    /** @return array<string, mixed>|null */
    private function dbFieldMeta(string $table, string $column): ?array
    {
        foreach ($this->db->getFieldData($table) as $field) {
            if (($field->name ?? '') === $column) {
                return [
                    'type'     => $field->type ?? '',
                    'nullable' => ($field->nullable ?? false) === true,
                    'default'  => $field->default ?? null,
                ];
            }
        }

        return null;
    }
}

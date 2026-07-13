<?php

namespace App\Traits;

/**
 * BUG-20: `indexExists()` was duplicated verbatim between DatabaseAuditService
 * and AddHotPathIndexes. Single source of truth as a trait.
 */
trait DbSchemaHelper
{
    protected function indexExists(string $table, string $indexName): bool
    {
        $dbName = $this->db->getDatabase();
        $rows   = $this->db->query(
            'SELECT 1 FROM information_schema.statistics '
            . 'WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        )->getResultArray();

        return ! empty($rows);
    }
}

<?php

namespace App\Libraries;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class DataTables extends DataTablesCodeIgniter4
{
    /**
     * Columns added in PHP (not in SQL). serial maps to id for sorting.
     */
    protected function resolveSqlColumn(string $column): ?string
    {
        if (in_array($column, ['select', 'action', 'pricing', 'package'], true)) {
            return null;
        }

        if ($column === 'serial') {
            $column = 'id';
        }

        if (isset($this->columnAliases[$column])) {
            $column = $this->columnAliases[$column];
        }

        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column);
            $column = implode('`.`', $parts);
        }

        return $column;
    }

    /**
     * Override recordsTotal so the controller can inject the true unfiltered
     * count BEFORE custom WHERE filters are applied.
     */
    public function setRecordsTotal(int $count): static
    {
        $this->recordsTotal = $count;
        return $this;
    }

    /**
     * Override standard order() to resolve column aliases and skip virtual columns.
     */
    protected function order()
    {
        if ($this->request->get('order') && count($this->request->get('order'))) {
            $orders = [];
            $fieldNamesLength = count($this->returnedFieldNames);

            foreach ($this->request->get('order') as $order) {
                $column_idx = $order['column'];
                $request_column = $this->request->get('columns')[$column_idx];

                if (! filter_var($request_column['orderable'], FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $column = $request_column['data'];

                if (! $this->asObject) {
                    if ($this->sequenceNumber && $column == 0) {
                        continue;
                    }

                    $fieldIndex = $this->sequenceNumber ? $column - 1 : $column;

                    if ($fieldIndex > $fieldNamesLength - 1) {
                        break;
                    }

                    $column = $this->returnedFieldNames[$fieldIndex];
                }

                $column = $this->resolveSqlColumn((string) $column);
                if ($column === null) {
                    continue;
                }

                $dir = strtoupper($order['dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = sprintf('`%s` %s', $column, $dir);
            }

            if (! empty($orders)) {
                $this->queryBuilder->{ $this->config->get('orderBy')}(implode(', ', $orders));
            }
        }
    }

    /**
     * Skip virtual columns in global/column search (serial, action, etc.).
     */
    protected function filter()
    {
        $globalSearch = [];
        $columnSearch = [];
        $fieldNamesLength = count($this->returnedFieldNames);

        // BUG-01 fix: keyword is user-supplied; escape it before embedding in SQL.
        $db = \Config\Database::connect();

        if ($this->request->get('search') && ($keyword = $this->request->get('search')['value']) != '') {
            $escapedKeyword = $db->escapeLikeString($keyword);

            foreach ($this->request->get('columns', []) as $request_column) {
                if (! filter_var($request_column['searchable'], FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $column = $request_column['data'];

                if (! $this->asObject) {
                    if ($this->sequenceNumber && $column == 0) {
                        continue;
                    }

                    $fieldIndex = $this->sequenceNumber ? $column - 1 : $column;

                    if ($fieldIndex > $fieldNamesLength - 1) {
                        break;
                    }

                    $column = $this->returnedFieldNames[$fieldIndex];
                }

                $column = $this->resolveSqlColumn((string) $column);
                if ($column === null) {
                    continue;
                }

                $globalSearch[] = sprintf("`%s` LIKE '%%%s%%' ESCAPE '!'", $column, $escapedKeyword);
            }
        }

        foreach ($this->request->get('columns', []) as $request_column) {
            if (
                filter_var($request_column['searchable'], FILTER_VALIDATE_BOOLEAN)
                && ($colKeyword = $request_column['search']['value']) != ''
            ) {
                $column = $request_column['data'];

                if (! $this->asObject) {
                    if ($this->sequenceNumber && $column == 0) {
                        continue;
                    }

                    $fieldIndex = $this->sequenceNumber ? $column - 1 : $column;

                    if ($fieldIndex > $fieldNamesLength - 1) {
                        break;
                    }

                    $column = $this->returnedFieldNames[$fieldIndex];
                }

                $column = $this->resolveSqlColumn((string) $column);
                if ($column === null) {
                    continue;
                }

                $escapedColKeyword = $db->escapeLikeString($colKeyword);
                $columnSearch[] = sprintf("`%s` LIKE '%%%s%%' ESCAPE '!'", $column, $escapedColKeyword);
            }
        }

        $w_filter = '';

        if (! empty($globalSearch)) {
            $w_filter = '(' . implode(' OR ', $globalSearch) . ')';
        }

        if (! empty($columnSearch)) {
            $w_filter = $w_filter === ''
                ? implode(' AND ', $columnSearch)
                : $w_filter . ' AND ' . implode(' AND ', $columnSearch);
        }

        if ($w_filter !== '') {
            $this->queryBuilder->where($w_filter, null, false);
        }

        $this->recordsFiltered = $this->queryBuilder->{ $this->config->get('countAllResults')}('', false);
    }

    /**
     * Phase 1.5b: bound page size server-side.
     */
    protected function limit()
    {
        $start = $this->request->get('start');
        if ($start === null) {
            return;
        }

        $length = (int) $this->request->get('length');
        if ($length <= 0 || $length > 1000) {
            $length = 1000;
        }

        $this->queryBuilder->{ $this->config->get('limit')}($length, max(0, (int) $start));
    }
}

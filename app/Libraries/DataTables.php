<?php

namespace App\Libraries;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

/**
 * Safe server-side DataTables for this app.
 *
 * Vendor order/filter blindly SQL-orders by the client column `data` name.
 * Checkbox/serial/action/addColumn fields are not DB columns, so that yields
 * "Unknown column 'serial|select|…' in order clause" and kills the Ajax grid.
 * This subclass skips virtual columns and only orders real query fields.
 */
class DataTables extends DataTablesCodeIgniter4
{
    /**
     * Columns added in PHP (not in SQL). Never emit them into ORDER BY / LIKE.
     */
    protected function resolveSqlColumn(string $column): ?string
    {
        if ($column === '' || ctype_digit($column)) {
            return null;
        }

        // addColumn() callbacks are never SQL-sortable/searchable
        if (isset($this->extraColumns[$column])) {
            return null;
        }

        // Sequence numbers are computed in PHP — controllers already set a default ORDER BY
        if ($this->sequenceNumber && ($column === $this->sequenceNumberKey || $column === 'serial')) {
            return null;
        }

        static $virtual = [
            'select', 'action', 'pricing', 'package', 'employee', 'area',
            'purpose', 'checkbox', 'options', 'btn', 'buttons',
        ];
        if (in_array($column, $virtual, true)) {
            return null;
        }

        if (isset($this->columnAliases[$column])) {
            $column = $this->columnAliases[$column];
        }

        // Reject unknown fields (stops ORDER BY on junk / renamed UI keys)
        $bare = strpos($column, '.') !== false
            ? substr($column, strrpos($column, '.') + 1)
            : $column;
        $known = array_merge(
            $this->fieldNames ?? [],
            $this->returnedFieldNames ?? [],
            array_keys($this->columnAliases ?? []),
            array_values($this->columnAliases ?? [])
        );
        $knownBare = [];
        foreach ($known as $f) {
            $f = (string) $f;
            $knownBare[] = $f;
            if (strpos($f, '.') !== false) {
                $knownBare[] = substr($f, strrpos($f, '.') + 1);
            }
            // strip SQL aliases: "users.name as name"
            if (preg_match('/\bas\s+`?(\w+)`?$/i', $f, $m)) {
                $knownBare[] = $m[1];
            }
        }
        if (!in_array($column, $knownBare, true) && !in_array($bare, $knownBare, true)) {
            return null;
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
                $request_column = $this->request->get('columns')[$column_idx] ?? null;
                if ($request_column === null) {
                    continue;
                }

                if (! filter_var($request_column['orderable'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
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
                if (! filter_var($request_column['searchable'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
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
                filter_var($request_column['searchable'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && ($colKeyword = $request_column['search']['value'] ?? '') != ''
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

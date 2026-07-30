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

        // Sequence numbers are computed in PHP — controllers already set a default ORDER BY
        if ($this->sequenceNumber && ($column === $this->sequenceNumberKey || $column === 'serial')) {
            return null;
        }

        // Resolve aliases FIRST so UI columns like "package" / "created_at" can map to
        // qualified SQL (admin_packages.package_name / users.created_at) before we skip
        // virtual/extra columns. addColumnAlias stores alias => qualifiedDbColumn.
        if (!empty($this->columnAliases)) {
            if (array_key_exists($column, $this->columnAliases)
                && is_string($this->columnAliases[$column])
                && $this->columnAliases[$column] !== ''
                && $this->columnAliases[$column] !== $column
            ) {
                $column = $this->columnAliases[$column];
            } else {
                // Helper::getColumnAliases may store qualifiedDbColumn => alias
                $dbCol = array_search($column, $this->columnAliases, true);
                if ($dbCol !== false && is_string($dbCol) && strpos($dbCol, '.') !== false) {
                    $column = $dbCol;
                }
            }
        }

        // Pure PHP columns with no SQL mapping are not searchable/orderable
        if (isset($this->extraColumns[$column])) {
            return null;
        }

        static $virtual = [
            'select', 'action', 'pricing', 'package', 'employee', 'area',
            'purpose', 'checkbox', 'options', 'btn', 'buttons',
        ];
        if (in_array($column, $virtual, true)) {
            return null;
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

    /**
     * Return a CodeIgniter JSON response instead of Symfony send()+exit.
     * The vendor exit path skips CI shutdown and, under php spark serve,
     * can leave the browser with an empty body while the worker looks free —
     * DataTables then keeps the skeleton forever.
     */
    public function generate()
    {
        try {
            $this->setReturnedFieldNames();
            $this->filter();
            $this->order();
            $this->limit();

            $result = $this->queryBuilder->{$this->config->get('get')}();
            $output = [];
            $sequenceNumber = (int) $this->request->get('start') + 1;

            foreach ($result->{$this->config->get('getResult')}() as $res) {
                $row = [];
                if ($this->sequenceNumber) {
                    $row[$this->sequenceNumberKey] = $sequenceNumber++;
                }

                foreach ($this->returnedFieldNames as $field) {
                    $row[$field] = isset($this->formatters[$field])
                        ? $this->formatters[$field]($res->$field ?? null, $res)
                        : ($res->$field ?? null);
                }

                foreach ($this->extraColumns as $key => $callback) {
                    $row[$key] = $callback($res);
                }

                $output[] = $this->asObject ? (object) $row : array_values($row);
            }

            $payload = [
                'draw' => (int) $this->request->get('draw'),
                'recordsTotal' => (int) ($this->recordsTotal ?? 0),
                'recordsFiltered' => (int) ($this->recordsFiltered ?? 0),
                'data' => $output,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'DataTables::generate failed: {msg}', ['msg' => $e->getMessage()]);
            $payload = [
                'draw' => (int) ($this->request->get('draw') ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load table data',
            ];
        }

        // Development display_errors can leak notices into the body and break
        // DataTables ("Invalid JSON response"). Drop accidental buffered output.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Callers must `return $datatables->generate()`.
        return \Config\Services::response()
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setJSON($payload);
    }
}

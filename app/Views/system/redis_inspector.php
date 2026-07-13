<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<?php
$connected  = (bool) ($report['connected'] ?? false);
$entries    = $report['entries'] ?? [];
$stats      = $report['stats'] ?? [];
$pagination = $report['pagination'] ?? [];
$pattern    = (string) ($options['pattern'] ?? '*');
$search     = (string) ($options['search'] ?? '');
$category   = (string) ($options['category'] ?? '');
$perPage    = (int) ($options['per_page'] ?? 25);
$sort       = (string) ($options['sort'] ?? 'key_desc');
$page       = (int) ($pagination['page'] ?? 1);
$total      = (int) ($pagination['total'] ?? 0);
$totalPages = (int) ($pagination['total_pages'] ?? 0);
$from       = (int) ($pagination['from'] ?? 0);
$to         = (int) ($pagination['to'] ?? 0);
$baseUrl    = route_to('route.redis_inspector');

$buildUrl = static function (array $overrides = []) use ($options, $baseUrl): string {
    $params = array_merge($options, $overrides);
    $params['page'] = max(1, (int) ($params['page'] ?? 1));

    return $baseUrl . '?' . http_build_query($params);
};

$categories = \App\Libraries\RedisInspector::categoryOptions();
$rowOffset  = $total > 0 ? (($page - 1) * $perPage) : 0;
?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">

        <?= $this->include('components/page-header', [
          'title' => 'Redis Cache Inspector',
          'breadcrumb' => [
            ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
            ['label' => 'Redis Cache'],
          ],
        ]); ?>

        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-database"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Connection</span>
                        <span class="info-box-number"><?= $connected ? 'Connected' : 'Failed'; ?></span>
                        <span class="progress-description"><?= esc((string) ($report['host'] ?? '')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-key"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Matched Keys</span>
                        <span class="info-box-number"><?= $total; ?></span>
                        <span class="progress-description">
                            <?php if ($total > 0): ?>
                                Showing <?= $from; ?>–<?= $to; ?> (loaded <?= (int) ($stats['loaded_keys'] ?? 0); ?>)
                            <?php else: ?>
                                No keys on this page
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-memory"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Memory Used</span>
                        <span class="info-box-number"><?= esc((string) ($stats['used_memory'] ?? '—')); ?></span>
                        <span class="progress-description">Handler: <?= esc((string) ($report['handler'] ?? '')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-purple">
                    <span class="info-box-icon"><i class="fa fa-plug"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Redis DB Size</span>
                        <span class="info-box-number"><?= esc((string) ($stats['total_keys'] ?? '—')); ?></span>
                        <span class="progress-description">Page <?= $page; ?> / <?= max(1, $totalPages); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (! $connected): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i>
                Could not connect to Redis.
                <strong><?= esc((string) ($report['error'] ?? 'Unknown error')); ?></strong>
            </div>
        <?php endif; ?>

        <?php if (! empty($report['truncated'])): ?>
            <div class="alert alert-warning">
                <i class="fa fa-info-circle"></i>
                Scan capped at 5,000 keys. Narrow the pattern or search to refine results.
            </div>
        <?php endif; ?>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Redis Keys</h3>
            </div>

            <div class="box-body">
                <form method="get" action="<?= esc($baseUrl); ?>" class="row redis-filter-bar" style="margin-bottom:15px;">
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">Pattern</label>
                        <input type="text" name="pattern" class="form-control input-sm" value="<?= esc($pattern); ?>" placeholder="e.g. ispc:*">
                    </div>
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">Search key</label>
                        <input type="text" name="search" class="form-control input-sm" value="<?= esc($search); ?>" placeholder="substring in key">
                    </div>
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">Category</label>
                        <select name="category" class="form-control input-sm">
                            <option value="">All categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= esc($cat); ?>" <?= $category === $cat ? 'selected' : ''; ?>><?= esc($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">Sort</label>
                        <select name="sort" class="form-control input-sm">
                            <option value="key_desc" <?= $sort === 'key_desc' ? 'selected' : ''; ?>>Latest key first</option>
                            <option value="key_asc" <?= $sort === 'key_asc' ? 'selected' : ''; ?>>Oldest key first</option>
                            <option value="ttl_desc" <?= $sort === 'ttl_desc' ? 'selected' : ''; ?>>Highest TTL first</option>
                            <option value="ttl_asc" <?= $sort === 'ttl_asc' ? 'selected' : ''; ?>>Lowest TTL first</option>
                            <option value="category_asc" <?= $sort === 'category_asc' ? 'selected' : ''; ?>>Category A→Z</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">Per page</label>
                        <select name="per_page" class="form-control input-sm">
                            <?php foreach ([10, 25, 50, 100] as $n): ?>
                                <option value="<?= $n; ?>" <?= $perPage === $n ? 'selected' : ''; ?>><?= $n; ?> rows</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6" style="margin-bottom:8px;">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <input type="hidden" name="page" value="1">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Apply</button>
                            <a href="<?= esc($baseUrl); ?>" class="btn btn-default btn-sm">Reset</a>
                            <a href="<?= esc($buildUrl(['page' => $page])); ?>" class="btn btn-info btn-sm" title="Reload current page" aria-label="Reload current page"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="redis-cache-table">
                        <caption class="sr-only">Redis cache keys</caption>
                        <thead style="background:#1f2933;color:#fff;">
                            <tr>
                                <th style="width:50px;" scope="col">#</th>
                                <th scope="col">Category</th>
                                <th scope="col">Key</th>
                                <th style="width:80px;" scope="col">Type</th>
                                <th style="width:100px;" scope="col">TTL</th>
                                <th style="width:90px;" scope="col">Size</th>
                                <th scope="col">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No keys match your filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $i => $row): ?>
                                    <tr>
                                        <td><?= $rowOffset + $i + 1; ?></td>
                                        <td><span class="label label-info"><?= esc((string) $row['category']); ?></span></td>
                                        <td><code style="word-break:break-all;"><?= esc((string) $row['key']); ?></code></td>
                                        <td><span class="label label-default"><?= esc((string) $row['type']); ?></span></td>
                                        <td><?= esc((string) $row['ttl_label']); ?></td>
                                        <td><?= esc((string) $row['size_label']); ?></td>
                                        <td>
                                            <pre class="redis-value-preview"><?= esc((string) $row['value']); ?></pre>
                                            <?php if (! empty($row['truncated'])): ?>
                                                <button type="button" class="btn btn-xs btn-default btn-show-full-value" data-index="<?= (int) $i; ?>">
                                                    Show full value
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="row">
                        <div class="col-sm-5">
                            <p class="text-muted" style="margin-top:8px;">
                                Page <?= $page; ?> of <?= $totalPages; ?> — <?= $total; ?> key(s) matched
                            </p>
                        </div>
                        <div class="col-sm-7">
                            <ul class="pagination pagination-sm pull-right" style="margin:0;">
                                <li class="<?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <a href="<?= $page <= 1 ? '#' : esc($buildUrl(['page' => 1])); ?>">First</a>
                                </li>
                                <li class="<?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <a href="<?= $page <= 1 ? '#' : esc($buildUrl(['page' => $page - 1])); ?>">&laquo; Prev</a>
                                </li>
                                <?php
                                $window = 2;
                                $start  = max(1, $page - $window);
                                $end    = min($totalPages, $page + $window);
                                for ($p = $start; $p <= $end; $p++):
                                ?>
                                    <li class="<?= $p === $page ? 'active' : ''; ?>">
                                        <a href="<?= esc($buildUrl(['page' => $p])); ?>"><?= $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="<?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a href="<?= $page >= $totalPages ? '#' : esc($buildUrl(['page' => $page + 1])); ?>">Next &raquo;</a>
                                </li>
                                <li class="<?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a href="<?= $page >= $totalPages ? '#' : esc($buildUrl(['page' => $totalPages])); ?>">Last</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="redisValueModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Full Redis Value</h4>
            </div>
            <div class="modal-body">
                <pre id="redisValueModalBody" style="max-height:60vh;overflow:auto;white-space:pre-wrap;word-break:break-word;"></pre>
            </div>
        </div>
    </div>
</div>

<style>
    .redis-value-preview {
        max-height: 120px;
        overflow: auto;
        margin: 0;
        padding: 8px;
        background: #f8f9fa;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        font-size: 12px;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .redis-filter-bar .control-label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 4px;
    }
</style>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<script>
    $(function () {
        const fullValues = <?= json_encode(array_map(static fn ($row) => $row['value_full'] ?? '', $entries), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        $(document).on('click', '.btn-show-full-value', function () {
            $('#redisValueModalBody').text(fullValues[$(this).data('index')] || '');
            $('#redisValueModal').modal('show');
        });
    });
</script>

<?= $this->endSection(); ?>

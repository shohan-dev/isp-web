<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">

    <!-- ===== HEADER ===== -->
    <!-- ===== CONTENT ===== -->
    <section class="content ipb-saas-list">

        
    <?= $this->include('components/page-header', [
      'title' => 'Customer Audit',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => 'Customer Audit'],
      ],
    ]); ?>

<div class="box box-primary">

            <!-- ===== FILTER BAR ===== -->
            <div class="box-header with-border">
                <form method="get" class="row">

                    <div class="col-md-2">
                        <label>Date From</label>
                        <input type="date" name="from" class="form-control" value="<?= esc($from) ?>">
                    </div>

                    <div class="col-md-2">
                        <label>Date To</label>
                        <input type="date" name="to" class="form-control" value="<?= esc($to) ?>">
                    </div>

                    <div class="col-md-2">
                        <label>Per Page</label>
                        <select name="per_page" class="form-control">
                            <?php foreach ([10, 25, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= $perPage == $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6" style="margin-top:25px;">
                        <button type="button"
                            class="btn btn-warning"
                            id="openTraffic">
                            <i class="fa fa-exchange"></i> View Live Traffic
                        </button>

                        <button class="btn btn-primary">
                            <i class="fa fa-filter"></i> Filter
                        </button>

                        <a class="btn btn-success">
                            <i class="fa fa-file-csv"></i> Export CSV
                        </a>

                        <a class="btn btn-info">
                            <i class="fa fa-file-excel"></i> Export Excel
                        </a>
                    </div>

                </form>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <caption class="sr-only">Customer audit log</caption>
                    <thead style="background:#1f2933;color:#fff">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">When</th>
                            <th scope="col">Action</th>
                            <th scope="col">Entity</th>
                            <th scope="col">Client</th>
                            <th scope="col">Router</th>
                            <th scope="col">Details</th>
                            <th scope="col">Actor</th>
                            <th scope="col">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $row): ?>
                            <tr>
                                <td><?= $row->id ?></td>
                                <td><?= date('Y-m-d H:i:s', strtotime($row->created_at)) ?></td>
                                <td><span class="label label-info"><?= esc($row->action) ?></span></td>
                                <td><?= esc($row->entity ?? '—') ?></td>
                                <td><?= esc($row->client ?? '—') ?></td>
                                <td><?= esc($row->router ?? '—') ?></td>
                                <td><?= esc($row->details ?? '—') ?></td>
                                <td><?= esc($row->actor) ?></td>
                                <td>
                                    <?php
                                    $ipData = json_decode($row->ip_address);
                                    if (json_last_error() === JSON_ERROR_NONE && is_object($ipData)):
                                    ?>
                                        <div style="line-height:1.3">
                                            <strong style="color:var(--text-secondary, #64748b)">Public:</strong>
                                            <span style="color:var(--error-500, #e11d48)"><?= esc($ipData->public_ip ?? 'N/A') ?></span><br>
                                            <strong style="color:var(--text-secondary, #64748b)">Local:</strong>
                                            <span style="color:var(--info-500, #2563eb)"><?= esc($ipData->local_ip ?? 'N/A') ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--error-500, #e11d48)"><?= esc($row->ip_address) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="box-footer clearfix">
                <?= $pager->links() ?>
            </div>

        </div>
    </section>
</div>

<!-- ===== LIVE TRAFFIC MODAL ===== -->
<div class="modal fade" id="trafficModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:8px;overflow:hidden">

            <div class="modal-header" style="background:#1f2933;color:#fff">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-globe"></i> Live Traffic DNS Cache
                </h4>
            </div>

            <div class="modal-body" style="padding:0;max-height:70vh;overflow:auto">

                <div id="loadingTraffic" class="text-center" style="padding:40px">
                    <i class="fa fa-spinner fa-spin fa-3x" style="color:var(--info-500, #2563eb)"></i>
                    <p class="text-muted">Fetching real-time data...</p>
                </div>

                <ul id="trafficList" class="list-group list-group-flush"></ul>

            </div>

            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<!-- ===== JS ===== -->
<script>
    var router = <?= json_encode($router) ?>;

    var pppoe_name = <?= json_encode($pppoe_name) ?>;

    console.log('Router:', router);
    console.log('PPPoE Name:', pppoe_name);

    $(function() {

        // Open modal + fetch data
        $('#openTraffic').on('click', function() {
            $('#trafficModal').modal('show');
            fetchTrafficData();
        });

        // Fetch traffic function (GLOBAL in this scope)
        function fetchTrafficData() {

            $('#loadingTraffic').show();
            $('#trafficList').empty();
            // $router = intval($router);
            var url = "<?= route_to('route.routers.Usersload_Traffic', $router) ?>?pppoe_name=" + encodeURIComponent(pppoe_name);



            console.log('Fetching:', url);

            $.getJSON(url)
                .done(function(res) {
                    $('#loadingTraffic').hide();
                    $('#trafficList').empty();

                    if (res.status === 'success' && res.response?.data?.results?.length) {

                        res.response.data.results.forEach(item => {
                            $('#trafficList').append(`
                    <li class="list-group-item traffic-card">

                        <!-- ROW 1: name + ttl -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <div style="font-weight:600; color:var(--text-primary, #0f172a); font-size:14px;">
                                name: ${item.name}
                            </div>
                            <div style="
                                background:var(--info-100, #e0f2fe);
                                color:#0369a1;
                                font-size:12px;
                                padding:3px 8px;
                                border-radius:999px;
                                font-weight:600;
                            ">
                                ${item.ttl}
                            </div>
                        </div>

                        <!-- ROW 2: data + static -->
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:13px; color:var(--text-secondary, #64748b);">
                                data: ${item.data}
                            </div>
                            <div style="
                                background:${item.static ? '#dcfce7' : '#fee2e2'};
                                color:${item.static ? '#166534' : '#991b1b'};
                                font-size:12px;
                                padding:3px 8px;
                                border-radius:999px;
                                font-weight:600;
                            ">
                                static: ${item.static}
                            </div>
                        </div>

                    </li>
                `);
                        });

                    } else {
                        $('#trafficList').html('<li class="list-group-item text-center text-muted">No active traffic found</li>');
                    }
                })
                .fail(function(xhr) {
                    $('#loadingTraffic').hide();
                    $('#trafficList').html('<li class="list-group-item text-danger text-center">Failed to load traffic data</li>');
                });

        }

    });
</script>


<!-- ===== STYLE ===== -->
<style>
    /* Traffic Card */
    .traffic-card {
        border-left: 4px solid #2563eb;
        padding: 14px 18px;
        margin-bottom: 6px;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .traffic-card:hover {
        background: #f8fafc;
        transform: translateX(3px);
    }

    /* Header */
    .traffic-header {
        font-size: 12px;
        font-weight: 600;
        color: #2563eb;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Rows */
    .traffic-row {
        display: flex;
        gap: 10px;
        margin-bottom: 6px;
    }

    .traffic-label {
        min-width: 55px;
        font-weight: 600;
        color: #64748b;
    }

    .traffic-value {
        color: #0f172a;
        word-break: break-all;
    }

    /* Meta badges */
    .traffic-meta {
        margin-top: 8px;
        display: flex;
        gap: 8px;
    }

    .traffic-badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 999px;
        font-weight: 500;
    }

    /* TTL */
    .traffic-badge.ttl {
        background: #e0f2fe;
        color: #0369a1;
    }

    /* Static flags */
    .static-yes {
        background: #dcfce7;
        color: #166534;
    }

    .static-no {
        background: #fee2e2;
        color: #991b1b;
    }

    #trafficList .list-group-item {
        padding: 16px 20px;
        border-bottom: 1px solid #edf2f7;
    }

    #trafficList .list-group-item:hover {
        background: #f1f5f9;
    }

    .modal-lg {
        width: 80% !important;
    }
</style>

<?= $this->endSection(); ?>
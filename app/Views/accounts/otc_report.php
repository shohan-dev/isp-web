<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=2'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-acc-page">

    <?= $this->include('components/page-header', [
      'title' => 'OTC Report',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Accounting'],
        ['label' => 'OTC Report'],
      ],
    ]); ?>

<div class="box box-warning">
            <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
                    <button type="button" id="exportCsvBtn" class="btn btn-default">
                        <i class="fa fa-download" aria-hidden="true"></i> Export CSV
                    </button>
        <?php
          $otcActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
            'filters' => [],
            'actionsHtml' => $otcActionsHtml,
            'filterLabel' => 'OTC records',
            'showReset' => false,
            'showCount' => false,
            'manualBind' => true,
          ]);
        ?>
      </div>

            <div class="ipb-acc-filters">
                <?= $this->include('components/date-range', [
                  'showApply' => false,
                  'showClear' => false,
                ]); ?>
                <div class="ipb-acc-filter-actions">
                    <button type="button" id="clearFilter" class="btn btn-default">Clear</button>
                    <button type="button" id="applyFilter" class="btn btn-primary">Apply</button>
                </div>
            </div>

            <div class="ipb-acc-stats">
                <div class="ipb-acc-stat is-warn">
                    <span>Total OTC</span>
                    <strong id="total_otc_display">0.00</strong>
                    <em>All records</em>
                </div>
                <div class="ipb-acc-stat is-success">
                    <span>Paid OTC</span>
                    <strong id="paid_otc_display">0.00</strong>
                    <em>Collected</em>
                </div>
                <div class="ipb-acc-stat is-danger">
                    <span>Due OTC</span>
                    <strong id="due_otc_display">0.00</strong>
                    <em>Outstanding</em>
                </div>
            </div>

            <div class="ipb-acc-meta">
                <div class="ipb-acc-meta-item">Paid count: <strong id="paid_count">0</strong></div>
                <div class="ipb-acc-meta-item">Due count: <strong id="due_count">0</strong></div>
                <div class="ipb-acc-meta-item">Pending count: <strong id="pending_count">0</strong></div>
            </div>

            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%" id="otcReportTable">
                        <caption class="sr-only">OTC report</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">User name</th>
                                <th scope="col">Date</th>
                                <th scope="col">Connection type</th>
                                <th scope="col">Fiber code</th>
                                <th scope="col">Core color</th>
                                <th scope="col">Client type</th>
                                <th scope="col">OTC (৳)</th>
                                <th scope="col">OTC status</th>
                                <th scope="col">Billing status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <?php
                          // Zero-blank-frame first paint: skeleton rows show before
                          // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
                          $otcReportSkeletonCols = 11;
                        ?>
                        <?= view('components/skeleton-table', ['cols' => $otcReportSkeletonCols, 'rows' => 8]) ?>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- OTC Update Modal -->
<div class="modal fade" id="otcUpdateModal" tabindex="-1" role="dialog" aria-labelledby="otcUpdateModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="otcUpdateModalLabel">Update OTC Status</h4>
            </div>
            <form id="otcUpdateForm">
                <div class="modal-body">
                    <input type="hidden" id="update_user_id" name="user_id">
                    <input type="hidden" id="update_connection_id" name="connection_id">
                    <div class="form-group">
                        <label for="update_otc_amount">OTC Amount (৳)</label>
                        <input type="number" step="0.01" class="form-control" id="update_otc_amount" name="otc_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="update_otc_status">OTC Status</label>
                        <select class="form-control" id="update_otc_status" name="otc_status" required>
                            <option value="">Select Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="due">Due</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="failed">Failed</option>
                            <option value="na">N/A</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="update_remarks">Remarks (Optional)</label>
                        <textarea class="form-control" id="update_remarks" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<!-- 08 §10 / 07 F3 — CDN tata CSS removed: redundant. This page extends
     layout/main-layout, which already loads self-hosted tata.js + the app's
     own toast.css (which fully re-styles .tata; the main shell never loads
     any base tata CSS at all and toasts work fine there). -->

<style>
    /* Info boxes */
    .info-box {
        min-height: 90px;
        margin-bottom: 15px;
        border-radius: 3px;
        box-shadow: var(--shadow-1, 0 1px 3px rgba(0,0,0,0.1));
    }
    .info-box-icon {
        height: 90px;
        line-height: 90px;
        font-size: 45px;
        border-radius: 3px 0 0 3px;
    }
    .info-box-content {
        padding: 10px 15px;
    }
    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 20px;
    }
    #total_otc_display { font-size: 24px; font-weight: bold; }

    /* Status labels */
    .label {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: normal;
        display: inline-block;
        white-space: nowrap;
    }
    .label-success  { background-color: #00a65a; color: white; }
    .label-warning  { background-color: #f39c12; color: white; }
    .label-danger   { background-color: #dd4b39; color: white; }
    .label-default  { background-color: #777;    color: white; }
    .label-info     { background-color: #00c0ef; color: white; }

    /* Table */
    .table > thead > tr > th {
        vertical-align: middle;
        white-space: nowrap;
        padding: 10px 8px;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
        padding: 8px;
        white-space: nowrap;
    }

    /* OTC status clickable link */
    .otc-status-link {
        display: inline-block;
        padding: 5px 8px;
        min-width: 70px;
        text-align: center;
        text-decoration: none;
        border-radius: 3px;
        transition: all 0.3s ease;
    }
    .otc-status-link:hover {
        opacity: 0.8;
        transform: translateY(-1px);
        box-shadow: var(--shadow-2, 0 2px 5px rgba(0,0,0,0.2));
    }
    .otc-status-link .label { cursor: pointer; }

    /* View button */
    .btn-view {
        background-color: #00c0ef;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        transition: all 0.3s ease;
    }
    .btn-view:hover { background-color: #00acd6; color: white; }

    /* Small count boxes */
    .small-box.bg-gray {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        margin-bottom: 15px;
    }
    .small-box.bg-gray:hover { background-color: #e9ecef; }
    .text-green  { color: #00a65a; }
    .text-red    { color: #dd4b39; }
    .text-yellow { color: #f39c12; }

    /* DataTables controls */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        margin: 10px 0;
        padding: 0 10px;
    }
    .dataTables_filter input {
        margin-left: 5px;
        border-radius: 4px;
        border: 1px solid #d2d6de;
        padding: 5px 10px;
    }
    .dataTables_length select {
        border-radius: 4px;
        border: 1px solid #d2d6de;
        padding: 5px;
    }
    .dataTables_paginate .paginate_button {
        padding: 5px 10px !important;
        margin: 0 2px !important;
        border-radius: 4px !important;
        border: 1px solid #d2d6de !important;
        background: #fff !important;
        color: #333 !important;
        display: inline-block;
    }
    .dataTables_paginate .paginate_button.current {
        background: #f39c12 !important;
        color: white !important;
        border-color: #e08e0b !important;
    }
    .dataTables_paginate .paginate_button:hover {
        background: #f5f5f5 !important;
    }

    /* ===== FIX: Hide ghost/broken paginate buttons ===== */
    .dataTables_paginate .paginate_button.current:empty,
    .dataTables_paginate .paginate_button:empty,
    .dataTables_paginate > span > .paginate_button:empty,
    .dataTables_wrapper .dataTables_paginate span span {
        display: none !important;
    }
    /* Hide any extra scroll-related elements DataTables injects */
    .dataTables_scrollFoot,
    .dataTables_scrollHead {
        display: none !important;
    }
    /* ===== END FIX ===== */

    /* DataTables processing */
    .dataTables_processing {
        background: rgba(255,255,255,0.9);
        padding: 10px;
        border-radius: 5px;
        box-shadow: var(--shadow-2, 0 0 10px rgba(0,0,0,0.1));
        z-index: var(--z-dropdown, 1000);
    }

    /* Modal */
    .modal-sm { max-width: 400px; margin: 30px auto; }

    /* Mobile */
    @media (max-width: 767px) {
        .info-box { min-height: 70px; margin-bottom: 10px; }
        .info-box-icon { height: 70px; line-height: 70px; font-size: 35px; }
        .info-box-content { padding: 5px 10px; }
        .info-box-text { font-size: 12px; }
        .info-box-number { font-size: 16px; }
        #total_otc_display { font-size: 18px; }
        .btn { padding: 5px 10px; font-size: 12px; margin: 2px; }
        .modal-sm { margin: 10px; width: auto; }
        .ipb-otc-filter-actions {
            margin-top: 8px !important;
            justify-content: stretch !important;
        }
        .ipb-otc-filter-actions .btn {
            flex: 1 1 auto;
            min-height: 44px;
        }
        .col-xs-12 { margin-bottom: 10px; }
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate { text-align: left !important; margin-bottom: 10px !important; }
        .dataTables_filter input { width: 100% !important; margin-left: 0 !important; margin-top: 5px; }
        .dataTables_paginate { white-space: nowrap; overflow-x: auto; padding-bottom: 10px; -webkit-overflow-scrolling: touch; }
        .dataTables_paginate .paginate_button { padding: 8px 12px !important; font-size: 14px; }
        .small-box .inner p { font-size: 12px; }
    }

    /* Tablet */
    @media (min-width: 768px) and (max-width: 1024px) {
        .info-box { min-height: 80px; }
        .info-box-icon { height: 80px; line-height: 80px; font-size: 40px; }
    }

    @media (max-width: 480px) {
        #from_date, #to_date { margin-bottom: 10px; }
    }
</style>

<script>
$(document).ready(function () {

    var table = $('#otcReportTable').DataTable({
        processing : false,
        serverSide : true,
        scrollX    : false,   // Let our own div handle horizontal scroll
        autoWidth  : false,   // Prevents DataTables from adding ghost scroll elements
        dom        : 'lfrtip', // Standard layout — no extra scroll widgets
        ajax: {
            url  : '<?= route_to('otc.report.ajax') ?>',
            type : 'POST',
            data : function (d) {
                d.from_date = $('#from_date').val();
                d.to_date   = $('#to_date').val();
            },
            headers: {
                'X-Requested-With' : 'XMLHttpRequest',
                '<?= csrf_header() ?>' : '<?= csrf_hash() ?>'
            },
            dataSrc: function (json) {
                $('#total_otc_display').text('৳ ' + (json.totalOtc  || '0.00'));
                $('#paid_otc_display').text('৳ '  + (json.paidOtc   || '0.00'));
                $('#due_otc_display').text('৳ '   + (json.dueOtc    || '0.00'));
                $('#paid_count').text(json.paidCount    || '0');
                $('#due_count').text(json.dueCount     || '0');
                $('#pending_count').text(json.pendingCount || '0');
                return json.data;
            },
            error: function (xhr, error) {
                console.error('AJAX Error:', error, xhr.responseText);
                if (typeof tata !== 'undefined') {
                    tata.error("Couldn't load OTC report", 'Failed to load data');
                }
            }
        },
        columns: [
            { data: 'user_id' },
            { data: 'user_name' },
            {
                data: 'created_at',
                render: function (data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('en-US', {
                        year: 'numeric', month: 'short', day: 'numeric'
                    });
                }
            },
            { data: 'connection_type' },
            { data: 'fiber_code' },
            { data: 'core_color' },
            { data: 'client_type' },
            {
                data: 'otc',
                className: 'text-right',
                render: function (data) {
                    return data ? '৳ ' + parseFloat(data).toFixed(2) : '৳ 0.00';
                }
            },
            {
                data: 'otc_status',
                render: function (data, type, row) {
                    var status = (row.otc_status || 'na').toLowerCase();
                    var cls = 'label-default';
                    if (status === 'paid')                               cls = 'label-success';
                    else if (status === 'pending')                       cls = 'label-warning';
                    else if (['due','failed','cancelled'].includes(status)) cls = 'label-danger';

                    return '<a href="javascript:void(0)" class="otc-status-link"' +
                        ' data-user-id="'      + row.user_id        + '"' +
                        ' data-connection-id="' + row.id            + '"' +
                        ' data-otc="'          + (row.otc || 0)     + '"' +
                        ' data-status="'       + status             + '">' +
                        '<span class="label ' + cls + '">' + status.toUpperCase() + '</span>' +
                        '</a>';
                }
            },
            {
                data: 'billing_status',
                render: function (data, type, row) {
                    var status = (row.billing_status || 'na').toLowerCase();
                    var cls = 'label-default';
                    if (status === 'paid')    cls = 'label-success';
                    else if (status === 'pending') cls = 'label-warning';
                    else if (status === 'due')     cls = 'label-danger';
                    return '<span class="label ' + cls + '">' + status.toUpperCase() + '</span>';
                }
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function (data, type, row) {
                    return '<button type="button" class="ipb-row-btn tone-info" title="Update OTC" onclick="viewDetails(' + row.user_id + ')">' +
                        '<i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Update</span></button>';
                }
            }
        ],
        order      : [[0, 'desc']],
        pageLength : 10,
        lengthMenu : [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            search            : 'Search:',
            searchPlaceholder : 'Search...',
            processing        : '<i class="fa fa-spinner fa-spin"></i> Loading...',
            lengthMenu        : 'Show _MENU_ entries',
            info              : 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty         : 'Showing 0 to 0 of 0 entries',
            infoFiltered      : '(filtered from _MAX_ total entries)',
            paginate: {
                first    : 'First',
                last     : 'Last',
                next     : 'Next',
                previous : 'Previous'
            }
        },
        drawCallback: function () {
            // Remove any leftover empty paginate buttons injected by DataTables
            $('.dataTables_paginate .paginate_button').filter(function () {
                return $(this).text().trim() === '';
            }).remove();
        }
    });

    // Open update modal on OTC status click
    $(document).on('click', '.otc-status-link', function (e) {
        e.preventDefault();
        $('#update_user_id').val($(this).data('user-id'));
        $('#update_connection_id').val($(this).data('connection-id'));
        $('#update_otc_amount').val($(this).data('otc'));
        $('#update_otc_status').val($(this).data('status'));
        $('#otcUpdateModal').modal('show');
    });

    // Submit OTC update form
    $('#otcUpdateForm').on('submit', function (e) {
        e.preventDefault();
        if (typeof tata !== 'undefined') {
            tata.info('Processing', 'Updating OTC status...', { duration: 2000 });
        }
        $.ajax({
            url    : '<?= route_to('otc.status.update') ?>',
            type   : 'POST',
            data   : $(this).serialize(),
            headers: {
                'X-Requested-With'     : 'XMLHttpRequest',
                '<?= csrf_header() ?>' : '<?= csrf_hash() ?>'
            },
            success: function (response) {
                if (response.success) {
                    $('#otcUpdateModal').modal('hide');
                    if (typeof tata !== 'undefined') {
                        tata.success('OTC status updated', response.message || 'OTC status updated successfully', { duration: 3000, animate: 'slide' });
                    }
                    table.ajax.reload(null, false);
                } else {
                    if (typeof tata !== 'undefined') {
                        tata.error("Couldn't update OTC status", response.message || 'Error updating OTC status', { duration: 4000, animate: 'slide' });
                    }
                }
            },
            error: function (xhr, status, error) {
                var msg = 'An error occurred: ' + error;
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch (ex) {}
                if (typeof tata !== 'undefined') {
                    tata.error("Couldn't update OTC status", msg, { duration: 5000, animate: 'slide' });
                }
                console.error('Error details:', xhr.responseText);
            }
        });
    });

    // Apply filter
    $('#applyFilter').on('click', function () {
        if (typeof tata !== 'undefined') {
            tata.info('Filtering', 'Applying filters...', { duration: 1500 });
        }
        table.ajax.reload();
    });

    // Clear filter
    $('#clearFilter').on('click', function () {
        $('#from_date, #to_date').val('');
        if (typeof tata !== 'undefined') {
            tata.info('Reset', 'Filters cleared', { duration: 1500 });
        }
        table.ajax.reload();
    });

    // Export CSV
    $('#exportCsvBtn').on('click', function (e) {
        e.preventDefault();
        if (typeof tata !== 'undefined') {
            tata.info('Exporting', 'Preparing CSV file...', { duration: 2000 });
        }
        var filters = {
            from_date : $('#from_date').val(),
            to_date   : $('#to_date').val(),
            search    : $('input[type="search"]').val()
        };
        var form = $('<form method="GET" style="display:none;">').attr('action', '<?= route_to('otc.report.export') ?>');
        $.each(filters, function (key, val) {
            if (val) form.append($('<input type="hidden">').attr('name', key).val(val));
        });
        $('body').append(form);
        form.submit();
        form.remove();
    });

    // Adjust columns on resize
    var resizeTimer;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () { table.columns.adjust(); }, 250);
    });
});

function viewDetails(id) {
    var url = '<?= route_to('route.customer.details', 1) ?>';
    window.location.href = url.replace('/1', '/' + id);
}
</script>

<?= $this->endSection('script'); ?>
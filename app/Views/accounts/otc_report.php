<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=5'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-acc-page" id="ipbOtcReportPage">

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
        <div class="ipb-acc-field">
          <label for="from_date">From date</label>
          <input type="date" id="from_date" name="from_date" class="form-control" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="ipb-acc-field">
          <label for="to_date">To date</label>
          <input type="date" id="to_date" name="to_date" class="form-control" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="ipb-acc-filter-actions">
          <button type="button" id="clearFilter" class="btn btn-default">
            <i class="fa fa-times" aria-hidden="true"></i> Clear
          </button>
          <button type="button" id="applyFilter" class="btn btn-primary">
            <i class="fa fa-check" aria-hidden="true"></i> Apply
          </button>
        </div>
      </div>

      <div class="ipb-acc-stats">
        <div class="ipb-acc-stat is-warn">
          <span>Total OTC</span>
          <strong id="total_otc_display">৳ 0.00</strong>
          <em>All records in range</em>
        </div>
        <div class="ipb-acc-stat is-success">
          <span>Paid OTC</span>
          <strong id="paid_otc_display">৳ 0.00</strong>
          <em id="paid_count_label">0 paid</em>
        </div>
        <div class="ipb-acc-stat is-danger">
          <span>Due OTC</span>
          <strong id="due_otc_display">৳ 0.00</strong>
          <em id="due_count_label">0 due · <span id="pending_count_inline">0</span> pending</em>
        </div>
      </div>

      <div class="ipb-acc-meta" aria-live="polite">
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
            <?= view('components/skeleton-table', ['cols' => 11, 'rows' => 8]) ?>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="otcUpdateModal" tabindex="-1" role="dialog" aria-labelledby="otcUpdateModalLabel">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="otcUpdateModalLabel">Update OTC status</h4>
      </div>
      <form id="otcUpdateForm">
        <div class="modal-body">
          <input type="hidden" id="update_user_id" name="user_id">
          <input type="hidden" id="update_connection_id" name="connection_id">
          <div class="form-group">
            <label for="update_otc_amount">OTC amount (৳)</label>
            <input type="number" step="0.01" class="form-control" id="update_otc_amount" name="otc_amount" required>
          </div>
          <div class="form-group">
            <label for="update_otc_status">OTC status</label>
            <select class="form-control" id="update_otc_status" name="otc_status" required>
              <option value="">Select status</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="due">Due</option>
              <option value="cancelled">Cancelled</option>
              <option value="failed">Failed</option>
              <option value="na">N/A</option>
            </select>
          </div>
          <div class="form-group">
            <label for="update_remarks">Remarks (optional)</label>
            <textarea class="form-control" id="update_remarks" name="remarks" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function ($) {
  var CSRF_HEADER = '<?= csrf_header() ?>';
  var CSRF_HASH = '<?= csrf_hash() ?>';
  var AJAX_URL = '<?= route_to('otc.report.ajax') ?>';
  var UPDATE_URL = '<?= route_to('otc.status.update') ?>';
  var EXPORT_URL = '<?= route_to('otc.report.export') ?>';
  var DETAILS_URL = '<?= route_to('route.customer.details', 1) ?>';

  var table = null;

  function money(v) {
    var n = parseFloat(v);
    if (isNaN(n)) n = 0;
    return '৳ ' + n.toFixed(2);
  }

  function csrfHeaders() {
    return {
      'X-Requested-With': 'XMLHttpRequest',
      [CSRF_HEADER]: CSRF_HASH
    };
  }

  function validateDates() {
    var fromEl = document.getElementById('from_date');
    var toEl = document.getElementById('to_date');
    if (!fromEl || !toEl) return true;
    if (fromEl.value && toEl.value && fromEl.value > toEl.value) {
      if (window.tata) tata.error('Invalid range', 'From date cannot be after to date.');
      return false;
    }
    return true;
  }

  function setFilterBusy(busy) {
    var $apply = $('#applyFilter');
    var $clear = $('#clearFilter');
    $apply.prop('disabled', !!busy);
    $clear.prop('disabled', !!busy);
    if (busy) {
      $apply.data('ipbLabel', $apply.html());
      $apply.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Applying…');
    } else if ($apply.data('ipbLabel')) {
      $apply.html($apply.data('ipbLabel'));
      $apply.removeData('ipbLabel');
    }
  }

  function destroyTable() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#otcReportTable')) {
      try {
        $('#otcReportTable').DataTable().clear().destroy();
      } catch (e) {}
    }
    table = null;
  }

  function initTable() {
    destroyTable();

    table = $('#otcReportTable').DataTable({
      processing: true,
      serverSide: true,
      scrollX: false,
      autoWidth: false,
      dom: 'lfrtip',
      ajax: {
        url: AJAX_URL,
        type: 'POST',
        data: function (d) {
          d.from_date = $('#from_date').val();
          d.to_date = $('#to_date').val();
        },
        headers: csrfHeaders(),
        dataSrc: function (json) {
          var paidCount = json.paidCount || '0';
          var dueCount = json.dueCount || '0';
          var pendingCount = json.pendingCount || '0';

          $('#total_otc_display').text(money(json.totalOtc));
          $('#paid_otc_display').text(money(json.paidOtc));
          $('#due_otc_display').text(money(json.dueOtc));
          $('#paid_count').text(paidCount);
          $('#due_count').text(dueCount);
          $('#pending_count').text(pendingCount);
          $('#paid_count_label').text(paidCount + ' paid');
          $('#due_count_label').html(dueCount + ' due · <span id="pending_count_inline">' + pendingCount + '</span> pending');

          setFilterBusy(false);
          return json.data || [];
        },
        error: function (xhr, error) {
          setFilterBusy(false);
          console.error('OTC AJAX Error:', error, xhr && xhr.responseText);
          if (window.tata) tata.error("Couldn't load OTC report", 'Failed to load data');
        }
      },
      columns: [
        { data: 'user_id' },
        { data: 'user_name' },
        {
          data: 'created_at',
          render: function (data) {
            if (!data) return '—';
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
          className: 'text-right ipb-acc-nowrap',
          render: function (data) {
            return money(data);
          }
        },
        {
          data: 'otc_status',
          render: function (data, type, row) {
            var status = String(row.otc_status || 'na').toLowerCase();
            var cls = 'label-default';
            if (status === 'paid') cls = 'label-success';
            else if (status === 'pending') cls = 'label-warning';
            else if (['due', 'failed', 'cancelled'].indexOf(status) !== -1) cls = 'label-danger';

            return '<a href="javascript:void(0)" class="otc-status-link"' +
              ' data-user-id="' + row.user_id + '"' +
              ' data-connection-id="' + row.id + '"' +
              ' data-otc="' + (row.otc || 0) + '"' +
              ' data-status="' + status + '">' +
              '<span class="label ' + cls + '">' + status.toUpperCase() + '</span></a>';
          }
        },
        {
          data: 'billing_status',
          render: function (data, type, row) {
            var status = String(row.billing_status || 'na').toLowerCase();
            var cls = 'label-default';
            if (status === 'paid') cls = 'label-success';
            else if (status === 'pending') cls = 'label-warning';
            else if (status === 'due') cls = 'label-danger';
            return '<span class="label ' + cls + '">' + status.toUpperCase() + '</span>';
          }
        },
        {
          data: null,
          className: 'text-center ipb-acc-actions',
          orderable: false,
          render: function (data, type, row) {
            return '<button type="button" class="ipb-row-btn tone-info" title="Update OTC" data-user-id="' + row.user_id + '">' +
              '<i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Update</span></button>';
          }
        }
      ],
      order: [[0, 'desc']],
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      language: {
        search: 'Search:',
        searchPlaceholder: 'Search…',
        processing: '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Loading…',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'Showing 0 to 0 of 0 entries',
        infoFiltered: '(filtered from _MAX_ total entries)',
        emptyTable: 'No OTC records for this period',
        zeroRecords: 'No matching OTC records',
        paginate: {
          first: 'First',
          last: 'Last',
          next: 'Next',
          previous: 'Previous'
        }
      },
      drawCallback: function () {
        $('.dataTables_paginate .paginate_button').filter(function () {
          return $(this).text().trim() === '';
        }).remove();
      }
    });
  }

  function bindPage() {
    var page = document.getElementById('ipbOtcReportPage');
    if (!page || page.dataset.ipbBound === '1') {
      if (page && page.dataset.ipbBound === '1' && !$.fn.DataTable.isDataTable('#otcReportTable')) {
        initTable();
      }
      return;
    }
    page.dataset.ipbBound = '1';

    initTable();

    $('#applyFilter').off('click.ipbOtc').on('click.ipbOtc', function () {
      if (!validateDates() || !table) return;
      setFilterBusy(true);
      table.ajax.reload();
    });

    $('#clearFilter').off('click.ipbOtc').on('click.ipbOtc', function () {
      $('#from_date, #to_date').val('');
      if (!table) return;
      setFilterBusy(true);
      table.ajax.reload();
    });

    $('#from_date, #to_date').off('keydown.ipbOtc').on('keydown.ipbOtc', function (e) {
      if (e.key === 'Enter' || e.which === 13) {
        e.preventDefault();
        $('#applyFilter').trigger('click');
      }
    });

    $(document).off('click.ipbOtcStatus', '.otc-status-link').on('click.ipbOtcStatus', '.otc-status-link', function (e) {
      e.preventDefault();
      $('#update_user_id').val($(this).data('user-id'));
      $('#update_connection_id').val($(this).data('connection-id'));
      $('#update_otc_amount').val($(this).data('otc'));
      $('#update_otc_status').val($(this).data('status'));
      $('#update_remarks').val('');
      $('#otcUpdateModal').modal('show');
    });

    $(document).off('click.ipbOtcEdit', '#otcReportTable .ipb-row-btn').on('click.ipbOtcEdit', '#otcReportTable .ipb-row-btn', function () {
      var id = $(this).data('user-id');
      if (id) window.viewOtcDetails(id);
    });

    $('#otcUpdateForm').off('submit.ipbOtc').on('submit.ipbOtc', function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      $btn.prop('disabled', true);

      $.ajax({
        url: UPDATE_URL,
        type: 'POST',
        data: $form.serialize(),
        headers: csrfHeaders()
      })
        .done(function (response) {
          if (response && response.success) {
            $('#otcUpdateModal').modal('hide');
            if (window.tata) {
              tata.success('OTC status updated', response.message || 'OTC status updated successfully');
            }
            if (table) table.ajax.reload(null, false);
          } else if (window.tata) {
            tata.error("Couldn't update OTC status", (response && response.message) || 'Error updating OTC status');
          }
        })
        .fail(function (xhr) {
          var msg = 'An error occurred';
          try {
            var r = JSON.parse(xhr.responseText);
            if (r.message) msg = r.message;
          } catch (ex) {}
          if (window.tata) tata.error("Couldn't update OTC status", msg);
        })
        .always(function () {
          $btn.prop('disabled', false);
        });
    });

    $('#exportCsvBtn').off('click.ipbOtc').on('click.ipbOtc', function (e) {
      e.preventDefault();
      var params = new URLSearchParams();
      var from = $('#from_date').val();
      var to = $('#to_date').val();
      var search = $('#otcReportTable_filter input').val() || '';
      if (from) params.set('from_date', from);
      if (to) params.set('to_date', to);
      if (search) params.set('search', search);
      var url = EXPORT_URL + (params.toString() ? '?' + params.toString() : '');
      window.location.assign(url);
    });

    var resizeTimer;
    $(window).off('resize.ipbOtc').on('resize.ipbOtc', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (table) table.columns.adjust();
      }, 250);
    });
  }

  window.viewOtcDetails = function (id) {
    window.location.href = DETAILS_URL.replace('/1', '/' + id);
  };
  // Keep legacy name used by any cached markup
  window.viewDetails = window.viewOtcDetails;

  window.ipbOtcReportInit = bindPage;
  bindPage();
  $(bindPage);
})(jQuery);
</script>
<?= $this->endSection('script'); ?>

<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('content'); ?>
<?php
$users = $users ?? [];
$todayAmount = (float) ($todayAmount ?? 0);
$status = $status ?? '';

// 06 §9 — domain empty states for the DataTables emptyTable/zeroRecords language overrides.
$paymentRecordAction = userHasPermission('customer_payment', 'create')
    ? '<a href="' . esc(route_to('route.customer.payment.new'), 'attr') . '" class="btn btn-primary btn-sm"><i class="fa fa-plus" aria-hidden="true"></i> Record a payment</a>'
    : '';
$paymentEmptyHtml = '<div class="ipb-empty ipb-dt-empty"><div class="ipb-empty-icon"><i class="fa fa-receipt" aria-hidden="true"></i></div>'
    . '<div class="ipb-empty-title">No payments in this range</div>'
    . '<div class="ipb-empty-sub">Collections will show here. Widen the date range to see more.</div>'
    . ($paymentRecordAction !== '' ? '<div class="ipb-empty-action">' . $paymentRecordAction . '</div>' : '')
    . '</div>';
$paymentZeroHtml = '<div class="ipb-empty ipb-dt-empty"><div class="ipb-empty-icon"><i class="fa fa-filter" aria-hidden="true"></i></div>'
    . '<div class="ipb-empty-title">Nothing matches these filters</div>'
    . '<div class="ipb-empty-sub">Your filters are hiding everything. Clear them to see the full list.</div>'
    . '<div class="ipb-empty-action"><button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById(\'clearFilter\').click()">Clear filters</button></div>'
    . '</div>';
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Customers Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers Payment'],
      ],
    ]); ?>

    <div class="ipb-pay-stats">
      <div class="ipb-pay-stat">
        <span class="stat-kicker">Total Amount</span>
        <strong id="totalAmount">৳0.00</strong>
        <span class="stat-sub">Filtered payments</span>
      </div>
      <div class="ipb-pay-stat tone-info">
        <span class="stat-kicker">Today's Amount</span>
        <strong>৳<?= esc(number_format($todayAmount, 2)); ?></strong>
        <span class="stat-sub">Today's payments</span>
      </div>
      <div class="ipb-pay-stat tone-success">
        <span class="stat-kicker">Paid Amount</span>
        <strong id="paidAmount">৳0.00</strong>
        <span class="stat-sub">Successful payments</span>
      </div>
      <div class="ipb-pay-stat tone-warn">
        <span class="stat-kicker">Due Amount</span>
        <strong id="dueAmount">৳0.00</strong>
        <span class="stat-sub">Pending / failed</span>
      </div>
    </div>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          $paymentFilters = [
            [
              'id' => 'paid_via',
              'type' => 'select',
              'ariaLabel' => 'Payment method',
              'emptyLabel' => 'All methods',
              'options' => [
                ['value' => 'Cash', 'label' => 'Cash Payment'],
                ['value' => 'Bkash', 'label' => 'Bkash'],
                ['value' => 'Bkash Send Money', 'label' => 'Bkash Send Money'],
                ['value' => 'Nagad', 'label' => 'Nagad'],
                ['value' => 'Rocket', 'label' => 'Rocket'],
                ['value' => 'Upay', 'label' => 'Upay'],
                ['value' => 'SSLCommerz', 'label' => 'SSLCommerz'],
              ],
            ],
            [
              'id' => 'fromDate',
              'type' => 'date',
              'ariaLabel' => 'From date',
            ],
            [
              'id' => 'toDate',
              'type' => 'date',
              'ariaLabel' => 'To date',
            ],
          ];

          ob_start();
        ?>
              <button type="button" id="applyFilter" class="btn btn-primary btn-sm">Apply</button>
              <button type="button" id="clearFilter" class="btn btn-default btn-sm">Clear</button>
              <?php if (userHasPermission('customer_payment', 'create')): ?>
                <a class="btn btn-primary" href="<?= route_to('route.customer.payment.new'); ?>">
                  <i class="fa fa-plus" aria-hidden="true"></i> New Payment
                </a>
                <button type="button" class="btn btn-default" id="createManualInvoiceBtn">
                  <i class="fa fa-file-text" aria-hidden="true"></i> Manual Invoice
                </button>
              <?php endif; ?>

              <?php if (userHasPermission('customer_payment', 'delete')): ?>
                <button type="button" class="btn btn-danger delete-btn">
                  <i class="far fa-trash-can" aria-hidden="true"></i> Delete Selected
                </button>
              <?php endif; ?>
        <?php
          $paymentActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
            'filters' => $paymentFilters,
            'actionsHtml' => $paymentActionsHtml,
            'filtersBarId' => 'payment-filter-bar',
            'showReset' => false,
            'showCount' => false,
            'manualBind' => true,
          ]);
        ?>
      </div>

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" id="customerPaymentsTable" width="100%">
            <caption class="sr-only">Customers payment list</caption>
            <thead class="text-nowrap">
              <tr>
                <?php if (userHasPermission('customer_payment', 'delete')): ?>
                  <th data-data="select" scope="col">
                    <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                  </th>
                <?php endif; ?>
                <th data-data="serial" scope="col">#</th>
                <th data-data="invoice" scope="col">Invoice Id</th>
                <th data-data="customer" scope="col">Customer</th>
                <th data-data="amount" scope="col">Amount (৳)</th>
                <?php if (getSession('user_role') === 'resellerAdmin'): ?>
                  <th data-data="pay_amount" scope="col">Package Price (৳)</th>
                <?php endif; ?>
                <th data-data="month" scope="col">Month</th>
                <th data-data="created_at" scope="col">Invoice Date</th>
                <th data-data="paid_at" scope="col">Payment Date</th>
                <th data-data="paid_to" scope="col">Paid To</th>
                <th data-data="paid_via" scope="col">Paid Via</th>
                <th data-data="method_trx" scope="col">Trx Id</th>
                <th data-data="status" scope="col">Status</th>
                <?php if (userHasPermission('customer_payment', 'update') || userHasPermission('customer_payment', 'invoice')): ?>
                  <th data-data="action" scope="col">Action</th>
                <?php endif; ?>
              </tr>
            </thead>
            <?php
              // 04 §4 — zero-blank-frame first paint: skeleton rows show before
              // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
              // Uses the view() helper, not $this->include() — the latter is View::include(),
              // whose 2nd param is $options (cache/debug flags), not view data, so cols/rows
              // silently never reached the component and it fell back to its default of 5.
              $customerPaymentsSkeletonCols = 11
                + (userHasPermission('customer_payment', 'delete') ? 1 : 0)
                + (getSession('user_role') === 'resellerAdmin' ? 1 : 0)
                + ((userHasPermission('customer_payment', 'update') || userHasPermission('customer_payment', 'invoice')) ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $customerPaymentsSkeletonCols, 'rows' => 8]) ?>
          </table>
        </div>
      </div>
    </div>
  </section>

  <div class="modal fade" id="manualInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="manualInvoiceModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form id="manualInvoiceForm">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="manualInvoiceModalLabel">Manual Invoice</h4>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="invoice_id">
            <div class="row">
              <div class="col-md-6">
                <h4>Company Details</h4>
                <hr>
                <div class="form-group">
                  <label>Company Name</label>
                  <input type="text" name="company_name" id="company_name" class="form-control">
                </div>
                <div class="form-group">
                  <label>Company Mobile</label>
                  <input type="text" name="company_mobile" id="company_mobile" class="form-control">
                </div>
                <div class="form-group">
                  <label>Company Address</label>
                  <textarea name="company_address" id="company_address" class="form-control"></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <h4>Customer Details</h4>
                <hr>
                <div class="form-group" id="customerSelectGroup">
                  <label>Select Customer</label>
                  <select name="user_id" id="user_id" class="form-control select2" style="width:100%">
                    <option value="">--Select--</option>
                    <?php foreach ($users as $user): ?>
                      <option value="<?= (int) $user->id; ?>"><?= esc($user->name); ?> (<?= esc($user->email); ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Customer Name Override</label>
                  <input type="text" name="customer_name" id="customer_name" class="form-control">
                </div>
                <div class="form-group">
                  <label>Customer Mobile Override</label>
                  <input type="text" name="customer_mobile" id="customer_mobile" class="form-control">
                </div>
                <div class="form-group">
                  <label>Customer Email Override</label>
                  <input type="email" name="customer_email" id="customer_email" class="form-control">
                </div>
                <div class="form-group">
                  <label>Customer Address Override</label>
                  <textarea name="customer_address" id="customer_address" class="form-control"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <h4>Invoice Information</h4>
                <hr>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Invoice ID (leave blank for auto)</label>
                  <input type="text" name="invoice" id="invoice_num" class="form-control">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Amount</label>
                  <input type="number" name="amount" id="amount" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Month</label>
                  <input type="text" name="month" id="month" class="form-control" value="<?= date('F'); ?>" required>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Invoice</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function () {
    // Filter params shared by the grid's own ajax.data() and the totals
    // endpoint, so the stat cards always match what the table is showing.
    function currentFilterParams() {
      return {
        paid_via: $('#paid_via').val(),
        status: <?= json_encode($status); ?>,
        fromDate: $('#fromDate').val(),
        toDate: $('#toDate').val()
      };
    }

    function loadTotals() {
      $.ajax({
        url: '<?= route_to("route.customer.payment.fetch_totals"); ?>',
        type: 'post',
        data: currentFilterParams(),
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
        success: function (result) {
          var totals = result.response || {};
          $('#totalAmount').text('৳' + (parseFloat(totals.total) || 0).toFixed(2));
          $('#paidAmount').text('৳' + (parseFloat(totals.paid) || 0).toFixed(2));
          $('#dueAmount').text('৳' + (parseFloat(totals.due) || 0).toFixed(2));
        }
      });
    }

    var table = $('#customerPaymentsTable').DataTable({
      order: [],
      processing: true,
      serverSide: true,
      language: {
        emptyTable: <?= json_encode($paymentEmptyHtml) ?>,
        zeroRecords: <?= json_encode($paymentZeroHtml) ?>
      },
      ajax: {
        url: '<?= route_to("route.customer.payment.fetch"); ?>',
        type: 'post',
        data: function (d) {
          $.extend(d, currentFilterParams());
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        }
      },
      columnDefs: [{
        targets: '_all',
        defaultContent: '-'
      }]
    });

    loadTotals();

    $('#applyFilter').on('click', function () {
      table.ajax.reload();
      loadTotals();
    });

    $('#clearFilter').on('click', function () {
      $('#paid_via').val('');
      $('#fromDate').val('');
      $('#toDate').val('');
      table.ajax.reload();
      loadTotals();
    });

    <?php if (userHasPermission('customer_payment', 'delete')): ?>
      $('#select_all').on('click', function () {
        $('input:checkbox').prop('checked', this.checked);
      });

      $(document).on('click', '.input-check-selected:checkbox', function () {
        $('#select_all').prop(
          'checked',
          $('.input-check-selected:checkbox:checked').length === $('.input-check-selected:checkbox').length
        );
      });

      $(document).on('click', '.delete-btn', function () {
        swal({
          title: 'Confirmation',
          text: 'Are you sure you want to delete the selected records?',
          dangerMode: true,
          icon: 'warning',
          buttons: ['No', { text: 'Yes', closeModal: false }]
        }).then(function (willDelete) {
          if (!willDelete) return;
          var ids = [];
          $('.input-check-selected:checkbox:checked').each(function () {
            ids.push($(this).val());
          });
          $.ajax({
            url: '<?= route_to("route.customer.payment.delete"); ?>',
            type: 'DELETE',
            data: { ids: ids },
            headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
            success: function (result) {
              swal.close();
              tata.success('Payment deleted', result.response);
              table.ajax.reload(null, false);
            },
            error: function (response) {
              var result = jQuery.parseJSON(response.responseText);
              swal.close();
              tata.error("Couldn't delete payment", result.response);
            }
          });
        });
      });
    <?php endif; ?>

    if ($('.select2').length) {
      $('.select2').select2({ dropdownParent: $('#manualInvoiceModal'), width: '100%' });
    }

    $('#createManualInvoiceBtn').on('click', function () {
      $('#manualInvoiceForm')[0].reset();
      $('#invoice_id').val('');
      $('#user_id').val('').trigger('change');
      $('#customerSelectGroup').show();
      $('#manualInvoiceModalLabel').text('Create Manual Invoice');
      $('#manualInvoiceModal').modal('show');
    });

    window.editManualInvoice = function (id) {
      $.ajax({
        url: '<?= base_url("customer-payments/get-details"); ?>/' + id,
        type: 'GET',
        success: function (result) {
          var data = result.response;
          $('#invoice_id').val(data.id);
          $('#company_name').val(data.company_name);
          $('#company_mobile').val(data.company_mobile);
          $('#company_address').val(data.company_address);
          $('#user_id').val(data.user_id).trigger('change');
          $('#customerSelectGroup').hide();
          $('#customer_name').val(data.customer_name);
          $('#customer_mobile').val(data.customer_mobile);
          $('#customer_email').val(data.customer_email);
          $('#customer_address').val(data.customer_address);
          $('#invoice_num').val(data.invoice);
          $('#amount').val(data.amount);
          $('#month').val(data.month);
          $('#manualInvoiceModalLabel').text('Edit Invoice: ' + data.invoice);
          $('#manualInvoiceModal').modal('show');
        }
      });
    };

    $('#manualInvoiceForm').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        url: '<?= route_to("route.customer.payment.save_manual_invoice"); ?>',
        type: 'POST',
        data: $(this).serialize(),
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        success: function (result) {
          $('#manualInvoiceModal').modal('hide');
          tata.success('Invoice saved', result.response);
          table.ajax.reload(null, false);
        },
        error: function (response) {
          var result = JSON.parse(response.responseText);
          tata.error("Couldn't save invoice", result.response);
        }
      });
    });
  });
</script>
<?= $this->endSection('script'); ?>

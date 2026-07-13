<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<?php
$successfulAmount = (float) ($successfulAmount ?? 0);
$pendingAmount = (float) ($pendingAmount ?? 0);
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Users Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Users Payment'],
      ],
    ]); ?>

    <div class="ipb-pay-stats" style="grid-template-columns:1fr 1fr">
      <div class="ipb-pay-stat tone-success">
        <span class="stat-kicker">Paid Amount</span>
        <strong id="totalAmount">৳<?= esc(number_format($successfulAmount, 2)); ?></strong>
        <span class="stat-sub">Successful payments</span>
      </div>
      <div class="ipb-pay-stat tone-warn">
        <span class="stat-kicker">Due Amount</span>
        <strong>৳<?= esc(number_format($pendingAmount, 2)); ?></strong>
        <span class="stat-sub">Pending payments</span>
      </div>
    </div>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
        <?php if (userHasPermission('customer_payment', 'create')): ?>
          <a class="btn btn-primary" href="<?= route_to('route.customer.payment.new'); ?>">
            <i class="fa fa-plus" aria-hidden="true"></i> New Payment
          </a>
        <?php endif; ?>
        <?php if (userHasPermission('customer_payment', 'delete')): ?>
          <button type="button" class="btn btn-danger delete-btn">
            <i class="far fa-trash-can" aria-hidden="true"></i> Delete Selected
          </button>
        <?php endif; ?>
        <?php
          $userPaymentActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
            'filters' => [],
            'actionsHtml' => $userPaymentActionsHtml,
            'filtersBarId' => 'payment-filter-bar',
            'showReset' => false,
            'showCount' => false,
            'manualBind' => true,
          ]);
        ?>
        <div class="ipb-list-toolbar-filters">
          <span class="ipb-filter-label"><i class="fa fa-filter" aria-hidden="true"></i> Filter</span>
          <?= $this->include('components/date-range', [
            'fromId' => 'fromDate',
            'toId' => 'toDate',
            'fromName' => 'fromDate',
            'toName' => 'toDate',
            'showApply' => false,
            'showClear' => false,
          ]); ?>
          <button type="button" id="applyFilter" class="btn btn-primary btn-sm">Apply</button>
          <button type="button" id="clearFilter" class="btn btn-default btn-sm">Clear</button>
        </div>
      </div>

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
            <caption class="sr-only">Users payment list</caption>
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
              $userPaymentSkeletonCols = 11
                + (userHasPermission('customer_payment', 'delete') ? 1 : 0)
                + (getSession('user_role') === 'resellerAdmin' ? 1 : 0)
                + ((userHasPermission('customer_payment', 'update') || userHasPermission('customer_payment', 'invoice')) ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $userPaymentSkeletonCols, 'rows' => 8]) ?>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function () {
    const USER_ID = <?= json_encode($user_id) ?>;

    var table = $('.datatable').DataTable({
      order: [],
      processing: false,
      ajax: {
        url: '<?= route_to("route.customer.payment.user_fetch"); ?>',
        type: 'post',
        data: function (d) {
          d.fromDate = $('#fromDate').val();
          d.toDate = $('#toDate').val();
          d.user_id = USER_ID;
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        }
      },
      columnDefs: [{ targets: '_all', defaultContent: '-' }]
    });

    $('#applyFilter').on('click', function () { table.ajax.reload(); });
    $('#clearFilter').on('click', function () {
      $('#fromDate').val('');
      $('#toDate').val('');
      table.ajax.reload();
    });

    <?php if (userHasPermission('customer_payment', 'delete')): ?>
      $('#select_all').on('click', function () {
        $('input:checkbox').prop('checked', this.checked);
      });
      $(document).on('click', '.input-check-selected:checkbox', function () {
        $('#select_all').prop('checked', $('.input-check-selected:checkbox:checked').length === $('.input-check-selected:checkbox').length);
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
          $('.input-check-selected:checkbox:checked').each(function () { ids.push($(this).val()); });
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
  });
</script>
<?= $this->endSection('script'); ?>

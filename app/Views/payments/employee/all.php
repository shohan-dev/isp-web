<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<?php $status = $status ?? ''; ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Employees Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Employees Payment'],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-filter" aria-hidden="true"></i> Payments</span>
            <?php if ($status !== ''): ?>
              <span class="ipb-pay-badge is-<?= $status === 'successful' ? 'success' : ($status === 'pending' ? 'warning' : 'danger'); ?>">
                Filter: <?= esc(ucfirst($status)); ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="ipb-list-toolbar-actions">
            <?php if (userHasPermission('employee_payment', 'create')): ?>
              <a class="btn btn-primary" href="<?= route_to('route.employee.payment.new'); ?>">
                <i class="fa fa-plus" aria-hidden="true"></i> New Payment
              </a>
            <?php endif; ?>
            <?php if (userHasPermission('employee_payment', 'delete')): ?>
              <button type="button" class="btn btn-danger delete-btn">
                <i class="far fa-trash-can" aria-hidden="true"></i> Delete Selected
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
            <caption class="sr-only">Employees payment list</caption>
            <thead class="text-nowrap">
              <tr>
                <?php if (userHasPermission('employee_payment', 'delete')): ?>
                  <th data-data="select" scope="col">
                    <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                  </th>
                <?php endif; ?>
                <th data-data="serial" scope="col">#</th>
                <th data-data="invoice" scope="col">Invoice Id</th>
                <th data-data="employee" scope="col">Employee</th>
                <th data-data="area" scope="col">Service Area</th>
                <th data-data="amount" scope="col">Amount (৳)</th>
                <th data-data="month" scope="col">Month</th>
                <th data-data="paid_at" scope="col">Payment Date</th>
                <th data-data="paid_via" scope="col">Paid Via</th>
                <th data-data="status" scope="col">Status</th>
                <?php if (userHasPermission('employee_payment', 'update')): ?>
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
              $employeePaymentsSkeletonCols = 9
                + (userHasPermission('employee_payment', 'delete') ? 1 : 0)
                + (userHasPermission('employee_payment', 'update') ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $employeePaymentsSkeletonCols, 'rows' => 8]) ?>
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
    var table = $('.datatable').DataTable({
      processing: false,
      ajax: {
        url: '<?= route_to("route.employee.payment.fetch"); ?>',
        type: 'post',
        data: function (d) {
          d.status = <?= json_encode($status); ?>;
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        }
      },
      columnDefs: [{ targets: '_all', defaultContent: '-' }]
    });

    <?php if (userHasPermission('employee_payment', 'delete')): ?>
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
            url: '<?= route_to("route.employee.payment.delete"); ?>',
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

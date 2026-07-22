<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'My Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Payment'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
        </div>
      </div>

      <div class="box-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">My payment history</caption>
          <thead class="text-nowrap">
            <tr>
              <th data-data="serial" scope="col">#</th>
              <th data-data="invoice" scope="col">Invoice Id</th>
              <th data-data="amount" scope="col">Amount (৳)</th>
              <th data-data="month" scope="col">Month</th>
              <th data-data="purpose" scope="col">Purpose</th>
              <th data-data="created_at" scope="col">Invoice Date</th>
              <th data-data="paid_at" scope="col">Payment Date</th>
              <th data-data="paid_via" scope="col">Paid Via</th>
              <th data-data="paid_to" scope="col">Paid To</th>
              <th data-data="method_trx" scope="col">Trx Id</th>
              <th data-data="status" scope="col">Status</th>

              <?php if (userHasPermission('payment', 'invoice') || userHasPermission('payment', 'payment') || getSession('status') === 'inactive') : ?>

                <th data-data="action" scope="col">Action</th>

              <?php endif; ?>

            </tr>
          </thead>
          <?php
            // 04 §4 — zero-blank-frame first paint: skeleton rows show before
            // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
            $paymentsSkeletonCols = 11 + ((userHasPermission('payment', 'invoice') || userHasPermission('payment', 'payment') || getSession('status') === 'inactive') ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $paymentsSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>


<?php if (!empty(session()->getFlashdata('pay-error'))) : ?>

  <script>
    tata.error('Payment failed', "<?= session()->getFlashdata('pay-error'); ?>", {
      duration: 3000,
    });
  </script>

<?php endif; ?>

<script>
  $(document).ready(function() {

    $('.datatable').DataTable({
      // Skeleton tbody is the load cue (same as customers/all.php). processing:true
      // would stack the branded "Loading..." box on top of the skeleton.
      processing: false,
      serverSide: true,
      ajax: {
        url: '<?= route_to("route.payment.fetch"); ?>',
        type: 'post',
        beforeSend: function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>', );
        }
      },
      columnDefs: [
            {
              "targets": "_all",  
              "defaultContent": "-"
            }
          ],
    });
  })
</script>

<?= $this->endSection('script'); ?>
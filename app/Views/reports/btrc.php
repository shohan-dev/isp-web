<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'BTRC Report Report for BTRC',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'BTRC Report Report for BTRC'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          $btrcFilters = [
            [
              'id' => 'filter-area',
              'type' => 'select',
              'ariaLabel' => 'Filter by area',
              'emptyLabel' => 'All Areas',
              'options' => array_map(static function ($a) {
                  return ['value' => $a->id, 'label' => $a->area_name];
              }, $areas),
            ],
          ];
          if (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin') {
              $btrcFilters[] = [
                'id' => 'filter-reseller',
                'type' => 'select',
                'ariaLabel' => 'Filter by reseller',
                'emptyLabel' => 'All Resellers',
                'options' => array_map(static function ($r) {
                    return ['value' => $r->id, 'label' => $r->name];
                }, $resellers),
              ];
          }
          $btrcFilters[] = [
            'id' => 'filter-status',
            'type' => 'select',
            'ariaLabel' => 'Filter by status',
            'emptyLabel' => 'All Status',
            'options' => [
              ['value' => 'active', 'label' => 'Active'],
              ['value' => 'inactive', 'label' => 'Inactive'],
            ],
          ];
          $btrcFilters[] = [
            'id' => 'filter-from-date',
            'type' => 'date',
            'ariaLabel' => 'From date',
          ];
          $btrcFilters[] = [
            'id' => 'filter-to-date',
            'type' => 'date',
            'ariaLabel' => 'To date',
          ];

          ob_start();
        ?>
              <button type="button" id="filter-btn" class="btn btn-sm btn-primary"><i class="fa fa-filter" aria-hidden="true"></i> Filter</button>
              <button type="button" id="reset-btn" class="btn btn-sm btn-default"><i class="fa fa-refresh" aria-hidden="true"></i> Reset</button>
              <button type="button" id="export-pdf" class="btn btn-sm btn-danger"><i class="fa fa-file-pdf" aria-hidden="true"></i> Generate PDF</button>
              <button type="button" id="export-excel" class="btn btn-sm btn-success"><i class="fa fa-file-excel" aria-hidden="true"></i> Generate Excel</button>
        <?php
          $btrcActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
            'filters' => $btrcFilters,
            'actionsHtml' => $btrcActionsHtml,
            'filtersBarId' => 'report-filter-bar',
            'showReset' => false,
            'showCount' => false,
            'manualBind' => true,
          ]);
        ?>
      </div>

      <div class="box-body">
        <div class="table-responsive">
        <table id="btrc-table" class="table table-bordered table-striped">
          <caption class="sr-only">BTRC report</caption>
          <thead>
            <tr>
              <th scope="col">SL</th>
              <th scope="col">Client Name</th>
              <th scope="col">Mobile</th>
              <th scope="col">Email</th>
              <th scope="col">Package</th>
              <th scope="col">Bandwidth</th>
              <th scope="col">Price</th>
              <th scope="col">Area</th>
              <th scope="col">Conn. Type</th>
              <th scope="col">Client Type</th>
              <th scope="col">Address</th>
              <th scope="col">Activation Date</th>
            </tr>
          </thead>
          <?php
            // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
            $btrcSkeletonCols = 12;
          ?>
          <?= view('components/skeleton-table', ['cols' => $btrcSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection(); ?>

<?= $this->section('css'); ?>
<style>
  .box.box-warning {
    border-top: 3px solid #f39c12 !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
  }
  #report-filter-bar select, #report-filter-bar input {
    border-radius: 6px;
  }
</style>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function() {
    const table = $('#btrc-table').DataTable({
      serverSide: true,
      processing: false,
      ajax: {
        url: '<?= route_to("route.reports.btrc.fetch"); ?>',
        type: 'POST',
        data: function(d) {
          d.area_id = $('#filter-area').val();
          d.reseller_id = $('#filter-reseller').val();
          d.status = $('#filter-status').val();
          d.from_date = $('#filter-from-date').val();
          d.to_date = $('#filter-to-date').val();
          d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
        }
      },
      columns: [
        { data: "serial", orderable: false, searchable: false },
        { data: "name", name: "users.name" },
        { data: "mobile", name: "users.mobile" },
        { data: "email", name: "users.email" },
        { data: "package_name", name: "packages.package_name" },
        { data: "bandwidth", name: "packages.bandwidth" },
        { data: "price", name: "packages.price" },
        { data: "area_name", name: "areas.area_name" },
        { data: "connection_type", name: "connection_details.connection_type" },
        { data: "client_type", name: "connection_details.client_type" },
        { data: "address", name: "users.address" },
        { data: "activation_date", name: "users.created_at" }
      ],
      lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
      pageLength: 25
    });

    $('#filter-btn').on('click', function() {
      table.ajax.reload();
    });

    $('#reset-btn').on('click', function() {
      $('#report-filter-bar select, #report-filter-bar input').val('');
      table.ajax.reload();
    });

    $('#export-pdf').on('click', function(e) {
      e.preventDefault();
      const params = $.param({
        area_id: $('#filter-area').val(),
        reseller_id: $('#filter-reseller').val(),
        status: $('#filter-status').val(),
        from_date: $('#filter-from-date').val(),
        to_date: $('#filter-to-date').val()
      });
      window.location.href = '<?= route_to("route.reports.btrc.pdf"); ?>?' + params;
    });

    $('#export-excel').on('click', function(e) {
      e.preventDefault();
      const params = $.param({
        area_id: $('#filter-area').val(),
        reseller_id: $('#filter-reseller').val(),
        status: $('#filter-status').val(),
        from_date: $('#filter-from-date').val(),
        to_date: $('#filter-to-date').val()
      });
      window.location.href = '<?= route_to("route.reports.btrc.excel"); ?>?' + params;
    });
  });
</script>
<?= $this->endSection(); ?>

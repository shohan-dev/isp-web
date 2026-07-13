<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Admins',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Admins'],
      ],
    ]); ?>

    <?php if (getSession('user_role') === 'super_admin'): ?>
      <?php helper('flag'); $maintOn = isMaintenanceMode(); ?>
      <div class="alert <?= $maintOn ? 'alert-warning' : 'alert-info'; ?> ipb-maintenance-toggle" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
          <strong><i class="fa fa-screwdriver-wrench"></i> Maintenance mode</strong>
          — <?= $maintOn ? 'ON (visitors see 503)' : 'OFF'; ?>
        </div>
        <form method="post" action="<?= route_to('route.maintenance.toggle'); ?>" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="on" value="<?= $maintOn ? '0' : '1'; ?>">
          <button type="submit" class="btn btn-sm <?= $maintOn ? 'btn-success' : 'btn-warning'; ?>">
            <?= $maintOn ? 'Disable maintenance' : 'Enable maintenance'; ?>
          </button>
        </form>
      </div>
    <?php endif; ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<!-- <?php if (userHasPermission('customer', 'create')): ?>

            <a class="btn btn-primary" href="<?= route_to('reseller.add'); ?>">
              <i class="fa fa-plus"></i> New Reseller
            </a>

          <?php endif; ?> -->

          <?php if (userHasPermission('customer', 'delete')): ?>

            <button class="btn btn-danger delete-btn">
              <i class="far fa-trash-can"></i> Delete Selected
            </button>

          <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="box-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">Admin list</caption>
          <thead class="text-nowrap">
            <tr>

              <?php if (userHasPermission('customer', 'delete')): ?>

                <th scope="col" data-data="select">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>
              <?php endif; ?>

              <th scope="col" data-data="serial">#</th>
              <th scope="col" data-data="name">Name</th>
              <th scope="col" data-data="package">Package</th>
              <th scope="col" data-data="mobile">Mobile</th>
              <th scope="col" data-data="email">Email</th>
              <th scope="col" data-data="created_at">Registered</th>
              <th scope="col" data-data="subscription_status">Payment</th>
              <th scope="col" data-data="auto_disconnect">Auto Disconnect</th>
              <th scope="col" data-data="conn_status">Conn. Status</th>
              <th scope="col" data-data="status">Acc. Status</th>
              <th scope="col" data-data="action">Action</th>
            </tr>
          </thead>
          <?php
            $adminsSkeletonCols = 11 + (userHasPermission('customer', 'delete') ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $adminsSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  const status = "<?= esc($status) ?>";

  $(document).ready(function () {

    $('.datatable').DataTable({
      scrollX: true,
      scrollY: "55vh",
      scrollCollapse: true,
      paging: false,
      ajax: {
        url: '<?= route_to("route.Admin.fetch"); ?>',
        type: 'post',
        data: function (d) {
          d.status = status; // send status to the server
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>',);
        }
      },
      columnDefs: [
        {
          "targets": "_all",
          "defaultContent": "-"
        }
      ],
    });

    <?php if (userHasPermission('customer', 'delete')): ?>

      //check all checkbox function
      $("#select_all").click(function () {

        if (this.checked) {

          $(".input-check-selected").each(function () {
            this.checked = true;
          });

        } else {

          $(".input-check-selected").each(function () {
            this.checked = false;
          });
        }
      });

      $(document).on("click", ".input-check-selected:checkbox", function () {

        if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

          $("#select_all").prop("checked", true);

        } else {

          $("#select_all").prop("checked", false);
        }
      });

      //Function for delete users
      $(document).on('click', '.delete-btn', function () {

        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete the selected admin? ",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Yes",
            closeModal: false,
          }],
        }).then((willDelete) => {

          if (willDelete) {

            const selectedIds = $('.input-check-selected:checkbox:checked');

            const ids = [];

            $(selectedIds).each(function () {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.Admin.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function (result) {

                swal.close();

                tata.success('Admin deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function (response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();

                tata.error("Couldn't delete admin", result.response);
              }

            });
          }
        });
      });

    <?php endif; ?>

  });
</script>

<?= $this->endSection('script'); ?>
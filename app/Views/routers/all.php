<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?php
// 06 §9 — domain empty state for the routers list DataTable's emptyTable language override.
$routerAddAction = userHasPermission('routers', 'create')
    ? '<a href="' . esc(route_to('route.routers.new'), 'attr') . '" class="btn btn-primary btn-sm"><i class="fa fa-plus" aria-hidden="true"></i> Add router</a>'
    : '';
$routerEmptyHtml = '<div class="ipb-empty ipb-dt-empty"><div class="ipb-empty-icon"><i class="fa fa-server" aria-hidden="true"></i></div>'
    . '<div class="ipb-empty-title">No MikroTik connected</div>'
    . '<div class="ipb-empty-sub">Connect a router to push PPPoE users and read live traffic.</div>'
    . ($routerAddAction !== '' ? '<div class="ipb-empty-action">' . $routerAddAction . '</div>' : '')
    . '</div>';
?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Mikrotik Routers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers'],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <?php if (userHasPermission('routers', 'create')) : ?>
              <a class="btn btn-primary" href="<?= route_to('route.routers.new'); ?>">
                <i class="fa fa-plus" aria-hidden="true"></i> New Router
              </a>
            <?php endif; ?>

            <?php if (userHasPermission('routers', 'delete')) : ?>
              <button type="button" class="btn btn-danger delete-btn" id="deleteSelectedBtn" disabled>
                <i class="far fa-trash-can" aria-hidden="true"></i> Disable Selected
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
            <caption class="sr-only">Mikrotik routers</caption>
            <thead class="text-nowrap">
              <tr>
                <?php if (userHasPermission('routers', 'delete')) : ?>
                  <th data-data="select" scope="col">
                    <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                  </th>
                <?php endif; ?>
                <th data-data="serial" scope="col">#</th>
                <th data-data="name" scope="col">Name</th>
                <th data-data="host" scope="col">Host</th>
                <th data-data="username" scope="col">Username</th>
                <th data-data="password" scope="col">Password</th>
                <th data-data="port" scope="col">Port</th>
                <th data-data="status" scope="col">Status</th>
                <th data-data="action" scope="col">Action</th>
              </tr>
            </thead>
            <?php
              // Server-rendered skeleton loading tbody: shows before JS/DataTables boots;
              // DataTables replaces it on its first AJAX draw. Uses the view() helper, not
              // $this->include() — the latter's 2nd param is $options (cache/debug flags),
              // not view data, so cols/rows would silently never reach the component.
              $routersSkeletonCols = 8 + (userHasPermission('routers', 'delete') ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $routersSkeletonCols, 'rows' => 8]) ?>
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
  $(document).ready(function() {

    var table = $('.datatable').DataTable({
      language: {
        emptyTable: <?= json_encode($routerEmptyHtml) ?>
      },
      ajax: {
        url: '<?= route_to("route.routers.fetch"); ?>',
        type: 'post',
        data: {
          <?php if (getSession('user_role') === 'super_admin' && !empty($id)): ?>
            id: '<?= esc($id) ?>'
          <?php endif; ?>
        },
        beforeSend: function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
      },
      columnDefs: [{
        "targets": "_all",
        "defaultContent": "-"
      }],
    });

    <?php if (userHasPermission('routers', 'delete')) : ?>

      function updateDeleteButtonState() {
        const hasSelection = $('.input-check-selected:checkbox:checked').length > 0;
        $('#deleteSelectedBtn').prop('disabled', !hasSelection);
      }

      $("#select_all").click(function() {
        $('.input-check-selected:checkbox').prop('checked', this.checked);
        updateDeleteButtonState();
      });

      $(document).on("change", ".input-check-selected:checkbox", function() {
        const total = $(".input-check-selected:checkbox").length;
        const checked = $(".input-check-selected:checkbox:checked").length;

        $("#select_all").prop("checked", total > 0 && checked === total);
        updateDeleteButtonState();
      });

      table.on('draw', function() {
        $("#select_all").prop("checked", false);
        updateDeleteButtonState();
      });

      $(document).on('click', '#deleteSelectedBtn', function() {
        const selectedIds = $('.input-check-selected:checkbox:checked');

        if (selectedIds.length === 0) {
          return;
        }

        swal({
          title: "Confirmation",
          text: "Are you sure you want to disable the selected routers?",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Yes",
            closeModal: false,
          }],
        }).then((willDelete) => {

          if (willDelete) {
            const ids = [];

            selectedIds.each(function() {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.routers.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Router deleted', result.response);

                $("#select_all").prop("checked", false);
                updateDeleteButtonState();
                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                swal.close();

                const result = jQuery.parseJSON(response.responseText);
                tata.error("Couldn't delete router", result.response);
              }
            });
          }
        });
      });

    <?php endif; ?>

    // Setup Expired Profile
    $(document).on('click', '.btn-setup-expired', function(e) {
      e.preventDefault();
      const url = $(this).attr('href');
      const btn = $(this);

      btn.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Setting up...');
      btn.addClass('disabled');

      $.get(url, function(result) {
        if (result.status === 'success') {
          tata.success('Expired profile set up', result.message);
        } else {
          tata.error("Couldn't set up expired profile", result.message);
        }
        btn.html('<i class="far fa-pen-to-square" aria-hidden="true"></i> Setup Expired Profile');
        btn.removeClass('disabled');
      }).fail(function() {
        tata.error("Couldn't set up expired profile", 'Something went wrong');
        btn.html('<i class="far fa-pen-to-square" aria-hidden="true"></i> Setup Expired Profile');
        btn.removeClass('disabled');
      });
    });

    // Setup RADIUS
    $(document).on('click', '.btn-setup-radius', function(e) {
      e.preventDefault();
      const url = $(this).attr('href');
      const btn = $(this);

      btn.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Configuring...');
      btn.addClass('disabled');

      $.get(url, function(result) {
        if (result.status === 'success') {
          tata.success('RADIUS configured', result.message);
        } else {
          tata.error("Couldn't configure RADIUS", result.message);
        }
        btn.html('<i class="fa fa-broadcast-tower" aria-hidden="true"></i> Setup RADIUS');
        btn.removeClass('disabled');
      }).fail(function() {
        tata.error("Couldn't configure RADIUS", 'Something went wrong');
        btn.html('<i class="fa fa-broadcast-tower" aria-hidden="true"></i> Setup RADIUS');
        btn.removeClass('disabled');
      });
    });

  })
</script>

<?= $this->endSection('script'); ?>

<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'SMS',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'SMS'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (userHasPermission('sms_message', 'create')||getSession('user_role') === 'super_admin') : ?>

            <a class="btn btn-primary" href="<?= route_to('route.sms.new'); ?>">
              <i class="fa fa-plus"></i> New SMS
            </a>

          <?php endif; ?>

          <?php if (userHasPermission('sms_message', 'delete')||getSession('user_role') === 'super_admin') : ?>

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
          <caption class="sr-only">SMS list</caption>
          <thead class="text-nowrap">
            <tr>

              <?php if (userHasPermission('sms_message', 'delete')||getSession('user_role') === 'super_admin') : ?>

                <th scope="col" data-data="select">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>

              <?php endif; ?>

              <th scope="col" data-data="serial">#</th>
              <th scope="col" data-data="datetime">Date</th>
              <th scope="col" data-data="send_by">Sent By</th>
              <th scope="col" data-data="gateway">Gateway</th>
              <th scope="col" data-data="send_to">Receiver</th>
              <th scope="col" data-data="content">Message</th>
              <th scope="col" data-data="status">Status</th>
              <th scope="col" data-data="action">Action</th>
            </tr>
          </thead>
          <?php
            $smsSkeletonCols = 8 + ((userHasPermission('sms_message', 'delete')||getSession('user_role') === 'super_admin') ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $smsSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<div id="log_modal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">SMS Logs</h4>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {

    $('.datatable').DataTable({
      ajax: {
        url: '<?= route_to("route.sms.fetch"); ?>',
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

    <?php if (userHasPermission('sms_message', 'delete')||getSession('user_role') === 'super_admin') : ?>
      //check all checkbox function
      $("#select_all").click(function() {

        if (this.checked) {

          $("input:checkbox").each(function() {
            this.checked = true;
          });

        } else {

          $("input:checkbox").each(function() {
            this.checked = false;
          });
        }
      });

      $(document).on("click", ".input-check-selected:checkbox", function() {

        if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

          $("#select_all").prop("checked", true);

        } else {

          $("#select_all").prop("checked", false);
        }
      });

      //Function for delete packages
      $(document).on('click', '.delete-btn', function() {

        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete selected records?",
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

            $(selectedIds).each(function() {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.sms.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();
                tata.success('SMS records deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();
                tata.error("Couldn't delete SMS records", result.response);
              }
            });
          }
        });
      });
    <?php endif; ?>

    $(document).on('click', '.log-btn', function() {

      const logs = $(this).data('log');

      $('#log_modal .modal-body').html(logs);
      $('#log_modal').modal();
    });
  })
</script>

<?= $this->endSection('script'); ?>
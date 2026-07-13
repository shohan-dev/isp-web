<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  .form-group {
    margin-bottom: 25px !important;
  }
</style>

<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Custom User Access',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Custom User Access'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<button class="btn btn-primary new-access-btn">
            <i class="fa fa-plus"></i> New Access
          </button>
          <button class="btn btn-danger delete-btn">
            <i class="far fa-trash-can"></i> Delete Selected
          </button>
          </div>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">Custom user access list</caption>
          <thead class="text-nowrap">
            <tr>
              <th data-data="select" scope="col">
                <input type="checkbox" class="form-check-input" id="select_all">
              </th>
              <th data-data="serial" scope="col">#</th>
              <th data-data="user" scope="col">Name</th>
              <th data-data="role" scope="col">Role</th>
              <th data-data="status" scope="col">Status</th>
              <th data-data="action" scope="col">Action</th>
            </tr>
          </thead>
        </table>
        </div>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>


<div id="access_modal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content"></div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {

    $('.datatable').DataTable({
      ajax: {
        url: '<?= route_to("route.useraccess.custom.fetch"); ?>',
        type: 'post',
        beforeSend : function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>',);
        }
      },
    });

    //check all checkbox function
    $("#select_all").click(function(){

      if(this.checked){

          $("input:checkbox").each(function(){
              this.checked = true;
          });

      } else {

          $("input:checkbox").each(function(){
              this.checked = false;
          });
      }
    });

    $(document).on("click", ".input-check-selected:checkbox", function(){

      if($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length){

        $("#select_all").prop("checked",true);

      }else{

        $("#select_all").prop("checked",false);
      }
    });

    $(document).on('click', '.new-access-btn', function() {

      const self = this;

      $.ajax({
        url: '<?= route_to("route.useraccess.custom.new"); ?>',
        type: 'GET',
        beforeSend: function() {
          $(self).html('<i class="fa fa-spinner fa-spin"></i>');
          $(self).attr('disabled', 'disabled');
        },

        success: function(result) {
          $(self).html('<i class="fa fa-plus"></i> New Access');
          $(self).removeAttr('disabled');
          $('#access_modal .modal-content').html(result.response);
          $('#access_modal').modal();
        },

        error: function({responseText}) {
          $(self).html('<i class="fa fa-plus"></i> New Access');
          $(self).removeAttr('disabled');
          const result = JSON.parse(responseText);
          tata.error("Couldn't load form", result.response);
        }
      });
    });

    $(document).on('click', '.access-btn', function() {

      const access_id = $(this).data('access_id');
      const self = this;

      $.ajax({
        url: '<?= route_to("route.useraccess.custom.getaccess"); ?>',
        type: 'POST',
        data: {access_id},
        headers: {
          '<?= csrf_header() ?>' : '<?= csrf_hash() ?>',
        },

        beforeSend: function() {
          $(self).html('<i class="fa fa-spinner fa-spin"></i>');
          $(self).attr('disabled', 'disabled');
        },

        success: function(result) {
          $(self).html('<i class="far fa-pen-to-square"></i> Update');
          $(self).removeAttr('disabled');
          $('#access_modal .modal-content').html(result.response);
          $('#access_modal').modal();
        },

        error: function({responseText}) {
          $(self).html('<i class="far fa-pen-to-square"></i> Update');
          $(self).removeAttr('disabled');
          const result = JSON.parse(responseText);
          tata.error("Couldn't load access", result.response);
        }
      });
    });

    $(document).on('click', '.delete-btn', function() {
      swal({
        title: "Confirmation",
        text: "Are you sure you wanted to delete the selected records?",
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

            $(selectedIds).each(function(){
                ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.useraccess.custom.delete"); ?>',
              type: 'DELETE',
              data: { ids },
              headers: {
                  '<?= csrf_header() ?>' : '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();
                tata.success('Access deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();
                tata.error("Couldn't delete access", result.response);
              }
            });
          }
      });
    });
  
    $(document).on('submit', '.form', function(e) {

      const form = this;

      const btn_text = $(this).find('button[type="submit"]').html();

      $.ajax({
        url: $(form).attr('action'),
        type: 'POST',
        data: new FormData(form),
        contentType: false,
        cache: false,
        processData: false,

        beforeSend: function() {

          $(form).find('.error').html("");
          $(form).find('#feedback').html("");

          $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

          $(form).find('button[type="submit"]').attr('disabled', 'true');
        },

        success: function(result) {

          $(form).find('button[type="submit"]').html(btn_text);

          $(form).find('button[type="submit"]').removeAttr('disabled');

          $('.datatable').DataTable().ajax.reload(null, false);

          tata.success('Access saved', result.response,{
            onClose: () => {
              $('#access_modal').modal('hide');
            }
          });
        },

        error: function({ responseText }) {

          const result = JSON.parse(responseText);

          $(form).find('button[type="submit"]').html(btn_text);

          $(form).find('button[type="submit"]').removeAttr('disabled');

          if (result.status === 'validation-error') {

            $.each(result.response, function(prefix, val) {

              $(form).find('#' + prefix + '-error').text(val);
            });

          } else {

            tata.error("Couldn't save access", result.response);
          }
        }
      });

      e.preventDefault();
    });

  });
</script>

<?= $this->endSection('script'); ?>
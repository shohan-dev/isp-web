<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

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
      'title' => 'User Access Management',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'User Access Management'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-shield" aria-hidden="true"></i> Default access</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <a href="<?= route_to('route.useraccess.custom'); ?>" class="btn btn-primary">
              <i class="fa fa-user-check" aria-hidden="true"></i> Custom Access
            </a>
          </div>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">Default role access list</caption>
          <thead class="text-nowrap">
            <tr>
              <th data-data="serial" scope="col">#</th>
              <th data-data="role" scope="col">User Role</th>
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
        url: '<?= route_to("route.useraccess.fetch"); ?>',
        type: 'post',
        beforeSend : function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>',);
        }
      },
    });

    $(document).on('click', '.access-btn', function(event) {

      const access_id = $(this).data('access_id');
      const self = this;

      $.ajax({
        url: '<?= route_to("route.useraccess.getaccess"); ?>',
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
  });
</script>


<script>
  $(document).on('submit', '.form', function(e) {

    const form = this;

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

        console.log(result);

        $(form).find('button[type="submit"]').html('Update');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Access updated', result.response,{
          onClose: () => {
            $('#access_modal').modal('hide');
          }
        });
      },

      error: function({ responseText }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't update access", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
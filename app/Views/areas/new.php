<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content ipb-saas-list">
      
      
    <?= $this->include('components/page-header', [
      'title' => 'New Service Area',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Service Area', 'url' => route_to('route.area')],
        ['label' => 'New Service Area'],
      ],
    ]); ?>

<div class="box box-warning">
        <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-pen" aria-hidden="true"></i> Details</span>
          </div>
        </div>
      </div>

        <?= form_open('', 'id="form"'); ?>

          <div class="box-body">

            <div class="form-group">
              <label>Area Name</label>

              <?= form_input([
                'name'  => 'area_name',
                'class'=> 'form-control',
              ]); ?>

              <small id="area_name-error" class="error text-danger"></small>
            </div>

            <div class="form-group">
              <label>Area Code</label>

              <?= form_input([
                'name'  => 'area_code',
                'class' => 'form-control',
              ]); ?>

              <small id="area_code-error" class="error text-danger"></small>
            </div>

            <div class="form-group">
              <label>Status</label>

              <div class="radio">
                <label class="radio-inline">
                  <?= form_radio([
                    'name'  => 'status',
                    'value' => 'active',
                  ]); ?>
                  Active
                </label>

                <label class="radio-inline">
                  <?= form_radio([
                    'name'  => 'status',
                    'value' => 'inactive',
                  ]); ?>
                  Inactive
                </label>
              </div>

              <small id="status-error" class="error text-danger"></small>
            </div>

          </div>

          <div class="box-body">
            <?= form_button([
              "content" => "Add Area",
              "class"   => "btn btn-warning",
              "type"    => "submit",
            ]); ?>
          </div>

        <?= form_close(); ?>
      </div>
    </section>
    <!-- /.content -->
  </div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

  <script>
    $("#form").submit(function(e) {

        const form = this;

        $.ajax({
          url: '<?= route_to('route.area.create'); ?>',
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

            $(form).find('button[type="submit"]').html('Add Area');

            $(form).find('button[type="submit"]').removeAttr('disabled');

            $(form).trigger('reset');

            tata.success('Area added', result.response,{
              onClose: () =>{
                  location.href = '<?= route_to("route.area"); ?>';
              },
            });
          },

          error: function({responseText}){

            const result = JSON.parse(responseText);

            $(form).find('button[type="submit"]').html('Add Area');
            
            $(form).find('button[type="submit"]').removeAttr('disabled');

            if (result.status === 'validation-error') {

                $.each(result.response, function(prefix, val) {

                    $(form).find('#' + prefix + '-error').text(val);
                });

            } else {

              tata.error("Couldn't add area", result.response);
            }
          }
        });

        e.preventDefault();
    });
  </script>

<?= $this->endSection('script'); ?>

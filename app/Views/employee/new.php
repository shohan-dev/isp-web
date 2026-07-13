<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'New Employee',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Employees', 'url' => route_to('route.employee')],
        ['label' => 'New Employee'],
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
          <label>Employee Name</label>

          <?= form_input([
            'name' => 'name',
            'class' => 'form-control',
          ]); ?>

          <small id="name-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Employee Designation</label>

          <?= form_dropdown('designation', [
            '' => '--Select--',
            'billman' => 'Billman',
            'lineman' => 'Lineman',
            'manager' => 'Manager',
            'noc' => 'Noc',
            'marketing' => 'Marketing',
          ], '', 'class="form-control"'); ?>

          <small id="designation-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Service Area</label>

          <?php
          $data = array();

          if (empty($areas)) :
            $data[''] = 'No area found!';
          else :
            foreach ($areas as $area) {
              $data[$area->id] = $area->area_name;
            }
          endif;

          // Add multiple="multiple" and [] to name for multiple selection
          echo form_dropdown(
            'area_id[]',
            $data,
            isset($details) ? explode(',', $details->area_id) : [],
            'class="form-control" multiple="multiple"'
          );
          ?>

          <small id="area_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Mobile Number</label>

          <?= form_input([
            'type' => 'number',
            'name' => 'mobile',
            'class' => 'form-control',
          ]); ?>

          <small id="mobile-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Address</label>

          <?= form_textarea([
            'name' => 'address',
            'class' => 'form-control',
            'style' => 'max-height: 80px'
          ]); ?>

          <small id="address-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Email Id</label>

          <?= form_input([
            'type' => 'email',
            'name' => 'email',
            'class' => 'form-control',
          ]); ?>

          <small id="email-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Password</label>

          <?= form_input([
            'type' => 'password',
            'name' => 'password',
            'class' => 'form-control',
          ]); ?>

          <small id="password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Rewrite Password</label>

          <?= form_input([
            'type' => 'password',
            'name' => 're_password',
            'class' => 'form-control',
          ]); ?>

          <small id="re_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Status</label>

          <div class="radio">
            <label class="radio-inline">
              <?= form_radio([
                'name' => 'status',
                'value' => 'active',
              ]); ?>
              Active
            </label>

            <label class="radio-inline">
              <?= form_radio([
                'name' => 'status',
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
          "content" => "Add Employee",
          "class" => "btn btn-warning",
          "type" => "submit",
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
      url: '<?= route_to('route.employee.create'); ?>',
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
        $(form).find('button[type="submit"]').html('Add Employee');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        $(form).trigger('reset');
        console.log(result);

        tata.success('Employee added', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.employee"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);
        console.log(result.response);

        $(form).find('button[type="submit"]').html('Add Employee');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't add employee", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Update Admin',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Admin'],
        ['label' => 'Update Admin'],
      ],
    ]); ?>
<div class="box box-warning">

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">

        <div class="row">

          <div class="col-lg-12">
            <h4 class="text-primary">Account Info</h4>
            <br />
          </div>

          <div class="form-group col-lg-6">
            <label>Customer Name</label>

            <?= form_input([
              'name'  => 'name',
              'class' => 'form-control',
              'value' => $details->name,
            ]); ?>

            <small id="name-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6">
            <label>Service Area</label>

            <?php $data = array();

            if (empty($areas)) :

              $data[''] = 'No service area found!';

            else :

              $data = ['' => '--Select--'];

              foreach ($areas as $area) {
                $data[$area->id] = $area->area_name;
              }

            endif;

            echo form_dropdown('area_id', $data, $details->area_id, 'class="form-control"'); ?>

            <small id="area_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Mobile Number</label>

            <?= form_input([
              'type'  => 'number',
              'name'  => 'mobile',
              'class' => 'form-control',
              'value' => $details->mobile,
            ]); ?>

            <small id="mobile-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Discount (৳)</label>

            <?= form_input([
              'type'  => 'number',
              'name'  => 'discount',
              'class' => 'form-control',
              'value' => $rdetails->discount ?? $rdetails['discount'] ?? '',
            ]); ?>

            <small id="discount-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Email Id</label>

            <?= form_input([
              'type'  => 'email',
              'name'  => 'email',
              'class' => 'form-control',
              'value' => $details->email,
            ]); ?>

            <small id="email-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-12">
            <label>Address</label>

            <?= form_textarea([
              'name'  => 'address',
              'class' => 'form-control',
              'style' => 'max-height: 80px',
              'value' => $details->address,
            ]); ?>

            <small id="address-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Password</label>

            <?= form_input([
              'type'  => 'password',
              'name'  => 'password',
              'class' => 'form-control',
            ]); ?>

            <small id="password-error" class="error text-danger"></small>
            <p><small class="text-info">Keep it blank if you dont want to change the password</small></p>
          </div>

          <div class="form-group col-lg-6">
            <label>Rewrite Password</label>

            <?= form_input([
              'type'  => 'password',
              'name'  => 're_password',
              'class' => 'form-control',
            ]); ?>

            <small id="re_password-error" class="error text-danger"></small>
            <p><small class="text-info">Keep it blank if you dont want to change the password</small></p>
          </div>

          <div class="form-group col-lg-12">
            <label>Acc. Status</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'status',
                  'value'   => 'active',
                  'checked' => $details->status === 'active',
                ]); ?>
                Active
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'status',
                  'value'   => 'inactive',
                  'checked' => $details->status === 'inactive',
                ]); ?>
                Inactive
              </label>
            </div>

            <small id="status-error" class="error text-danger"></small>
          </div>



          <div class="form-group col-lg-6">
            <div class="checkbox">
              <label>
                <?= form_checkbox([
                  'name'    => 'auto_disconnect',
                  'checked' => ($details->auto_disconnect === 'yes'),
                  'value'   => 'yes'
                ]); ?>
                Auto Disconnect
              </label>
            </div>
            <small id="auto_disconnect-error" class="error text-danger"></small>
          </div>

        </div>

      </div>
      <div class="box-footer">
        <?= form_button([
          "content" => "Update Customer",
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
      url: '<?= route_to('route.Reseller.update', $details->id); ?>',
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

        $(form).find('button[type="submit"]').html('Update Reseller');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Reseller updated', result.response, {
          onClose: () => {
            <?php /* 'route.Reseller' is not a defined route name; route_to() returns
                     false, so this echoed location.href = '' and simply reloaded the
                     edit page instead of navigating back to the list. */ ?>
            location.href = '<?= route_to("route.reseller"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update Reseller');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't update reseller", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
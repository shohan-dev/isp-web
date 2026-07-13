<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Update POP',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POP'],
        ['label' => 'Update POP'],
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
            <label>Commission</label>

            <?= form_input([
              'name'  => 'discount',
              'class' => 'form-control',
              'value' => $rdetails['discount'] ?? "--",
            ]); ?>

            <small id="discount-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Month Cycle date (5,10,15,30)</label>

            <?= form_input([
              'name'  => 'area_id',
              'class' => 'form-control',
              'value' => $details->area_id ?? "--",
            ]); ?>

            <small id="area_id-error" class="error text-danger"></small>
          </div>


          <div class="form-group col-lg-6">
            <label>Router</label>

            <?php $data = array();

            if (empty($routers)) :

              $data[''] = 'No Router found!';

            else :

              $data = ['' => '--Select--'];

              foreach ($routers as $area) {
                $data[$area->id] = $area->name;
              }

            endif;

            echo form_dropdown('router_id', $data, $details->router_id, 'class="form-control"'); ?>

            <small id="router_id-error" class="error text-danger"></small>
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

          <div class="form-group col-lg-6">
            <label>Billing Type</label>
            <select name="billing_type" class="form-control">
              <option value="postpaid" <?= ($details->billing_type ?? 'postpaid') === 'postpaid' ? 'selected' : ''; ?>>Postpaid</option>
              <option value="prepaid" <?= ($details->billing_type ?? '') === 'prepaid' ? 'selected' : ''; ?>>Prepaid</option>
            </select>
            <small id="billing_type-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6" id="validity_periods_group">
            <label>Allowed Validity Periods (Days)</label>
            <?= form_input([
              'name'        => 'reseller_validity_periods',
              'id'          => 'reseller_validity_periods',
              'class'       => 'form-control',
              'value'       => $details->reseller_validity_periods ?? '3,5,7,30',
              'placeholder' => 'e.g. 3,5,7,15,30',
            ]); ?>
            <small class="text-muted">Enter days separated by commas. Example: <code>3,5,7,30</code></small>
            <small id="reseller_validity_periods-error" class="error text-danger"></small>
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
  $(function() {
    function toggleValidityPeriods() {
      const billingType = $('select[name="billing_type"]').val();
      if (billingType === 'prepaid') {
        $('#validity_periods_group').hide();
      } else {
        $('#validity_periods_group').show();
      }
    }
    $('select[name="billing_type"]').on('change', toggleValidityPeriods);
    toggleValidityPeriods();
  });

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
            <?php /* Route is named 'route.reseller' (lowercase). route_to() returns
                     false for an unknown name, which echoes as '', so this used to
                     set location.href = '' — reloading the edit page instead of
                     returning to the reseller list after a successful save. */ ?>
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
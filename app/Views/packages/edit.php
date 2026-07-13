<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Update Package',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Update Package'],
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
          <label>Package Name</label>

          <?= form_input([
            'name'  => 'package_name',
            'class' => 'form-control',
            'value' => $details->package_name,
          ]); ?>

          <small id="package_name-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Bandwidth</label>

          <?= form_input([
            'name'        => 'bandwidth',
            'class'       => 'form-control',
            'placeholder' => 'eg : 10Mb/s',
            'value'       => $details->bandwidth,
          ]); ?>

          <small id="bandwidth-error" class="error text-danger"></small>
        </div>

        <div class="row">

          <div class="col-xs-6">
            <div class="form-group">
              <label>Package Price (৳)</label>

              <?= form_input([
                'name'  => 'price',
                'class' => 'form-control',
                'value' => $details->price,
              ]); ?>

              <small id="price-error" class="error text-danger"></small>
            </div>
          </div>

          <div class="col-xs-6">
            <div class="form-group">
              <label>Pricing Type</label>

              <?php

              $options = [
                ''        => '--Select--',
                'weekly'  => 'Per Week',
                'monthly' => 'Per Month',
                'yearly'  => 'Per Year',
              ];

              echo form_dropdown('pricing_type', $options, $details->pricing_type, 'class="form-control"'); ?>

              <small id="pricing_type-error" class="error text-danger"></small>
            </div>
          </div>

        </div>
        <div class="col-xs-6">
          <div class="form-group">
            <label>Status</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'status',
                  'value'   => 'active',
                  'checked' => ($details->status === 'active'),
                ]); ?>
                Active
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'status',
                  'value'   => 'inactive',
                  'checked' => ($details->status === 'inactive'),
                ]); ?>
                Inactive
              </label>
            </div>

            <small id="status-error" class="error text-danger"></small>
          </div>
        </div>
        <div class="col-xs-6">

          <div class="form-group">
            <label>Visibility to Customer</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'visibility',
                  'value'   => 'active',
                  'checked' => ($details->visibility === 'active'),
                ]); ?>
                Visible
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name'    => 'visibility',
                  'value'   => 'inactive',
                  'checked' => ($details->visibility === 'inactive'),
                ]); ?>
                Hiden
              </label>
            </div>

            <small id="visibility-error" class="error text-danger"></small>
          </div>
        </div>
      </div>

      <div class="box-body">
        <?= form_button([
          "content" => "Update Package",
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
      url: '<?= route_to('route.packages.update', $details->id); ?>',
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

        $(form).find('button[type="submit"]').html('Update Package');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Package updated', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.packages"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update Package');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't update package", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
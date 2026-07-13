<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content ipb-saas-list">
      
      
    <?= $this->include('components/page-header', [
      'title' => 'New Employee Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Employees Payment'],
        ['label' => 'New Payment'],
        ['label' => 'New Employee Payment'],
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
              <label>Employee</label>

              <?php 

              $options = ['' => '--Select--'];

              foreach ($employees as $employee) {
                
                $options[$employee->id] = $employee->name;

              }

              echo form_dropdown('employee', $options, '', 'class="form-control"'); ?>

              <small id="employee-error" class="error text-danger"></small>
            </div>

            <div class="row">
              
              <div class="col-xs-6">
                <div class="form-group">
                  <label>Amount (৳)</label>

                  <?= form_input([
                    'type'  => 'number',
                    'name'  => 'amount',
                    'class' => 'form-control',
                  ]); ?>

                  <small id="amount-error" class="error text-danger"></small>
                </div>
              </div>

              <div class="col-xs-6">
                <div class="form-group">
                  <label>Payment Month</label>

                  <?php 

                  $months = ['' => '--Select--'];

                  for($m = 1; $m <= 12; ++$m){

                    $month = date('F', mktime(0, 0, 0, $m, 1));

                    $months[$month] = $month;
                  }

                  echo form_dropdown('month', $months, date('F'), 'class="form-control"'); ?>

                  <small id="month-error" class="error text-danger"></small>
                </div>
              </div>

            </div>

            <div class="form-group">
              <label>Payment Method</label>

              <?php $options = [
                ''        => '--Select--',
                'Cash'    => 'Cash Payment',
                'Bkash'   => 'Bkash',
                'Nagad'   => 'Nagad',
                'Rocket'  => 'Rocket',
                'Upay'    => 'Upay',
              ];

              echo form_dropdown('paid_via', $options, '', 'class="form-control"'); ?>

              <small id="paid_via-error" class="error text-danger"></small>
            </div>

            <div class="form-group" style="margin-top: 20px;">
              <label>Status</label>

              <div class="radio">
                <label class="radio-inline">
                  <?= form_radio([
                    'name'  => 'status',
                    'value' => 'successful',
                  ]); ?>
                  Successful
                </label>

                <label class="radio-inline">
                  <?= form_radio([
                    'name'  => 'status',
                    'value' => 'pending',
                    'checked' => true,
                  ]); ?>
                  Pending
                </label>
              </div>

              <small id="status-error" class="error text-danger"></small>
            </div>

          </div>

          <div class="box-body">
            <?= form_button([
              "content" => "Add Payment",
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
          url: '<?= route_to('route.employee.payment.create'); ?>',
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

            $(form).find('button[type="submit"]').html('Add Payment');

            $(form).find('button[type="submit"]').removeAttr('disabled');

            $(form).trigger('reset');

            tata.success('Payment added', result.response,{
              onClose: () =>{
                location.href = '<?= route_to("route.employee.payment"); ?>';
              },
            });
          },

          error: function({responseText}){

            const result = JSON.parse(responseText);

            $(form).find('button[type="submit"]').html('Add Payment');
            
            $(form).find('button[type="submit"]').removeAttr('disabled');

            if (result.status === 'validation-error') {

                $.each(result.response, function(prefix, val) {

                    $(form).find('#' + prefix + '-error').text(val);
                });

            } else {

              tata.error("Couldn't add payment", result.response);
            }
          }
        });

        e.preventDefault();
    });
  </script>

<?= $this->endSection('script'); ?>

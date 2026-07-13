<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Update Customer Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers Payment'],
        ['label' => 'Update Payment'],
        ['label' => 'Update Customer Payment'],
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
          <label>Customer</label>

          <?= form_input([
            'type' => 'text',
            'class' => 'form-control',
            'value' => getUserById($details->user_id)->name ?? '--',
            'readonly' => 'readonly'
          ]); ?>
        </div>

        <div class="row">

          <div class="col-xs-6">
            <div class="form-group">
              <label>Amount (৳)</label>

              <?= form_input([
                'type' => 'number',
                'name' => 'amount',
                'class' => 'form-control',
                'value' => $details->amount
              ]); ?>

              <small id="amount-error" class="error text-danger"></small>
            </div>
          </div>

          <div class="col-xs-6">
            <div class="form-group">
              <label>Payment Month</label>

              <?php

              $months = ['' => '--Select--'];

              for ($m = 1; $m <= 12; ++$m) {

                $month = date('F', mktime(0, 0, 0, $m, 1));

                $months[$month] = $month;
              }

              echo form_dropdown('month', $months, $details->month, 'class="form-control"'); ?>

              <small id="month-error" class="error text-danger"></small>
            </div>
          </div>

        </div>

        <div class="form-group">
          <label>Payment Method</label>

          <?php

          $options = [
            '' => '--Select--',
            'Cash' => 'Cash Payment',
            'Bkash' => 'Bkash',
            'Bkash Send Money' => 'Bkash Send Money',
            'Nagad' => 'Nagad',
            'Rocket' => 'Rocket',
            'Upay' => 'Upay',
            'SSLCommerz' => 'SSLCommerz',
          ];

          echo form_dropdown('paid_via', $options, $details->paid_via, 'class="form-control"'); ?>

          <small id="paid_via-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Paid At</label>
          <?= form_input([
            'name' => 'paid_at',
            'type' => 'datetime-local',
            'class' => 'form-control',
            'value' => !empty($details->paid_at) ? date('Y-m-d\TH:i', strtotime($details->paid_at)) : date('Y-m-d\TH:i')
          ]); ?>
          <small id="paid_at-error" class="error text-danger"></small>
        </div>


        <?php if (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin'): ?>

          <div class="form-group">
            <label>Method Transaction Id</label>

            <?= form_input([
              'name' => 'method_trx',
              'type' => 'text',
              'class' => 'form-control',
              'value' => $details->method_trx ?? strtoupper(random_string('alnum', 11)),
            ]); ?>

            <small id="method_trx-error" class="error text-danger"></small>
          </div>

          <?php if ($details->status === 'failed'): ?>
            <div class="row">
              <div class="col-xs-4">
                <div class="form-group">
                  <label>User ID</label>
                  <?= form_input([
                    'name' => 'user_id',
                    'type' => 'number',
                    'class' => 'form-control',
                    'value' => $details->user_id
                  ]); ?>
                </div>
              </div>
              <div class="col-xs-4">
                <div class="form-group">
                  <label>Admin ID</label>
                  <?= form_input([
                    'name' => 'admin_id',
                    'type' => 'number',
                    'class' => 'form-control',
                    'value' => $details->admin_id
                  ]); ?>
                </div>
              </div>
              <div class="col-xs-4">
                <div class="form-group">
                  <label>Paid By</label>
                  <?= form_input([
                    'name' => 'paidby',
                    'type' => 'number',
                    'class' => 'form-control',
                    'value' => $details->paidby
                  ]); ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Comment</label>
            <?= form_textarea([
              'name' => 'comment',
              'class' => 'form-control',
              'value' => $details->comment ?? '',
              'rows' => 2
            ]); ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <div class="checkbox">
            <label class="text-primary">
              <?= form_checkbox([
                'name' => 'renew',
                'value' => 'yes'
              ]); ?>
              Renew Subscription
            </label>
          </div>
          <small id="renew-error" class="error text-danger"></small>
        </div>

        <div id="renew_date"></div>

        <div class="form-group" style="margin-top: 20px;">
          <label>Status</label>

          <div class="radio">
            <label class="radio-inline">
              <?= form_radio([
                'name' => 'status',
                'value' => 'successful',
                'checked' => ($details->status === 'successful')
              ]); ?>
              Successful
            </label>

            <label class="radio-inline">
              <?= form_radio([
                'name' => 'status',
                'value' => 'pending',
                'checked' => ($details->status === 'pending')
              ]); ?>
              Pending
            </label>

            <label class="radio-inline">
              <?= form_radio([
                'name' => 'status',
                'value' => 'failed',
                'checked' => ($details->status === 'failed')
              ]); ?>
              Failed
            </label>
          </div>

          <small id="status-error" class="error text-danger"></small>
        </div>

      </div>

      <div class="box-body">
        <?= form_button([
          "content" => "Update Payment",
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
  $("#form").submit(function (e) {

    const form = this;

    $.ajax({
      url: '<?= route_to('route.customer.payment.update', $details->id); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,

      beforeSend: function () {

        $(form).find('.error').html("");
        $(form).find('#feedback').html("");

        $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

        $(form).find('button[type="submit"]').attr('disabled', 'true');
      },

      success: function (result) {

        $(form).find('button[type="submit"]').html('Update Payment');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Payment updated', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.customer.payment"); ?>';
          },
        });
      },

      error: function ({ responseText }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update Payment');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function (prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't update payment", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<!-- Calculate expire -->
<script>
  $("input[name='renew']").change(function () {

    if (this.checked) {

      const form = $('#form');

      const customer = $('select[name="customer"]').val();

      $.ajax({
        url: '<?= route_to('route.customer.payment.getexpdate'); ?>',
        type: 'POST',
        data: { customer: '<?= $details->user_id; ?>' },

        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },

        beforeSend: function () {
          $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

          $(form).find('button[type="submit"]').attr('disabled', 'true');
        },

        success: function (result) {

          $(form).find('button[type="submit"]').html('Update Payment');

          $(form).find('button[type="submit"]').removeAttr('disabled');

          $('#renew_date').html(`
                <div class="form-group">
                  <label>মেয়াদ শেষের তারিখ</label>

                  <input type="datetime-local" name="will_expire" value="${result.response.expiry}" class="form-control">

                  <small id="will_expire-error" class="error text-danger"></small>
                </div>
              `);
        },

        error: function ({ responseText }) {

          const result = JSON.parse(responseText);

          $(form).find('button[type="submit"]').html('Update Payment');

          $(form).find('button[type="submit"]').removeAttr('disabled');

          if (result.status === 'validation-error') {

            $.each(result.response, function (prefix, val) {

              $(form).find('#' + prefix + '-error').text(val);
            });

          } else {

            swal({
              icon: 'error',
              title: "সমস্যা",
              text: result.response
            });
          }
        }
      });

    } else {

      $('#renew_date').html("");
    }
  });
</script>

<?= $this->endSection('script'); ?>
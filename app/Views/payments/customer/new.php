<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'New Customer Payment',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers Payment'],
        ['label' => 'New Payment'],
        ['label' => 'New Customer Payment'],
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
      <?= form_open('', ['id' => 'form']); ?>
      <div class="box-body">
        <div class="form-group">
          <label>Customer</label>
          <?php
          $options = ['' => '--Select--'];
          foreach ($customers as $customer) {
            $customer_id = $customer->id ?? '';
            $customer_name = $customer->name ?? '';
            $customer_number = isset($customer->mobile) ? $customer->mobile : 'N/A';
            $options[$customer_id] = "{$customer_id} - {$customer_name} ({$customer_number})";
          }
          echo form_dropdown('customer', $options, '', 'class="form-control" id="customer"');
          ?>
          <small id="customer-error" class="error text-danger"></small>
        </div>

        <div class="row">
          <div class="col-xs-6">
            <div class="form-group">
              <label>Amount (৳)</label>
              <?= form_input([
                'type' => 'number',
                'name' => 'amount',
                'id'   => 'amount',
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
              for ($m = 1; $m <= 12; ++$m) {
                $month = date('F', mktime(0, 0, 0, $m, 1));
                $months[$month] = $month;
              }
              echo form_dropdown('month', $months, date('F'), 'class="form-control"');
              ?>
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
          echo form_dropdown('paid_via', $options, '', 'class="form-control"');
          ?>
          <small id="paid_via-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Paid At</label>
          <?= form_input([
            'name' => 'paid_at',
            'type' => 'datetime-local',
            'class' => 'form-control',
            'value' => date('Y-m-d\TH:i')
          ]); ?>
          <small id="paid_at-error" class="error text-danger"></small>
        </div>

        <?php if (session()->get('user_role') === 'super_admin' || session()->get('user_role') === 'admin') : ?>
          <div class="form-group">
            <label>Method Transaction Id</label>
            <?= form_input([
              'name' => 'method_trx',
              'type' => 'text',
              'class' => 'form-control',
              'value' => strtoupper(random_string('alnum', 11)),
            ]); ?>
            <small id="method_trx-error" class="error text-danger"></small>
          </div>

          <div class="row">
            <div class="col-xs-4">
              <div class="form-group">
                <label>User ID</label>
                <?= form_input([
                  'name'  => 'user_id_override',
                  'type'  => 'number',
                  'class' => 'form-control',
                  'placeholder' => 'Override User ID'
                ]); ?>
              </div>
            </div>
            <div class="col-xs-4">
              <div class="form-group">
                <label>Admin ID</label>
                <?= form_input([
                  'name'  => 'admin_id_override',
                  'type'  => 'number',
                  'class' => 'form-control',
                  'placeholder' => 'Override Admin ID'
                ]); ?>
              </div>
            </div>
            <div class="col-xs-4">
              <div class="form-group">
                <label>Paid By</label>
                <?= form_input([
                  'name'  => 'paidby_override',
                  'type'  => 'number',
                  'class' => 'form-control',
                  'placeholder' => 'Override Paid By ID'
                ]); ?>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Comment</label>
            <?= form_textarea([
              'name'  => 'comment',
              'class' => 'form-control',
              'rows'  => 2
            ]); ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <div class="checkbox">
            <label class="text-primary">
              <?= form_checkbox(['name' => 'renew', 'value' => 'yes']); ?>
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
              <?= form_radio(['name' => 'status', 'value' => 'successful']); ?>
              Successful
            </label>
            <label class="radio-inline">
              <?= form_radio(['name' => 'status', 'value' => 'pending', 'checked' => true]); ?>
              Pending
            </label>
            <label class="radio-inline">
              <?= form_radio(['name' => 'status', 'value' => 'failed']); ?>
              Failed
            </label>
          </div>
          <small id="status-error" class="error text-danger"></small>
        </div>
      </div>
      <div class="box-body">
        <?= form_button([
          "content" => "Add Payment",
          "class" => "btn btn-warning",
          "type" => "submit",
        ]); ?>
      </div>
      <?= form_close(); ?>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $("#form").submit(function(e) {
    e.preventDefault();
    const form = this;
    $.ajax({
      url: '<?= route_to('route.customer.payment.create'); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: function() {
        $(form).find('.error').html("");
        $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', 'true');
      },
      success: function(result) {
        $(form).find('button[type="submit"]').html('Add Payment').removeAttr('disabled');
        $(form).trigger('reset');
        tata.success('Payment added', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.customer.payment"); ?>';
          },
        });
      },
      error: function({
        responseText
      }) {
        const result = JSON.parse(responseText);
        $(form).find('button[type="submit"]').html('Add Payment').removeAttr('disabled');
        if (result.status === 'validation-error') {
          $.each(result.response, function(prefix, val) {
            $(form).find('#' + prefix + '-error').text(val);
          });
        } else {
          tata.error("Couldn't add payment", result.response);
        }
      }
    });
  });
</script>



<script>
  $(document).ready(function() {

    // When customer changes → load package price
    $('#customer').on('change', function() {

      const customer = $(this).val();

      if (customer === '') {
        $('#amount').val('');
        $('#renew_date').empty();
        return;
      }

      $.ajax({
        url: '<?= route_to('route.customer.payment.getexpdate'); ?>',
        type: 'POST',
        data: {
          customer: customer
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        },

        success: function(result) {

          // console.log(result.response);

          // show package price instantly
          if (result.response.package) {
            $('#amount').val(result.response.package.price);
          }

        },

        error: function() {
          $('#amount').val('');
        }

      });

    });


    // Renew checkbox logic
    $('input[name="renew"]').on('change', function() {

      const form = $('#form');
      const customer = $('#customer').val();

      if (!customer) {
        alert("Please select customer first");
        this.checked = false;
        return;
      }

      if (this.checked) {

        form.find('button[type="submit"]')
          .prop('disabled', true)
          .html("<i class='fas fa-spinner fa-spin'></i> Please wait");

        $.ajax({
          url: '<?= route_to('route.customer.payment.getexpdate'); ?>',
          type: 'POST',
          data: {
            customer: customer
          },
          headers: {
            '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
          },

          success: function(result) {

            form.find('button[type="submit"]')
              .prop('disabled', false)
              .html('Add Payment');

            $('#renew_date').html(`
            <div class="form-group">
              <label>মেয়াদ শেষের তারিখ</label>
              <input
                type="datetime-local"
                name="will_expire"
                value="${result.response.expiry || new Date().toISOString().slice(0,16)}"
                class="form-control"
              >
              <small id="will_expire-error" class="error text-danger"></small>
            </div>
          `);

          },

          error: function() {

            form.find('button[type="submit"]')
              .prop('disabled', false)
              .html('Add Payment');

          }

        });

      } else {

        $('#renew_date').empty();

      }

    });

  });
</script>


<?= $this->endSection('script'); ?>
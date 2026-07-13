<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'New Ticket',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Support Tickets', 'url' => route_to('route.ticket')],
        ['label' => 'New Ticket'],
      ],
      'actions' => '<a class="btn btn-default" href="' . route_to('route.ticket') . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to inbox</a>',
    ]); ?>
<div class="box box-solid">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-pen" aria-hidden="true"></i> Ticket details</span>
          </div>
        </div>
      </div>

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">

        <div class="form-group">
          <label>Subject</label>

          <?= form_input([
            "name" => "subject",
            "class" => "form-control",
            "type" => "text",
          ]); ?>

          <small id="subject-error" class="error text-danger"></small>
        </div>


        <div class="form-group">
          <label>Category</label>

          <?= form_dropdown('category', [
            '' => '--Select--',
            'sales' => 'Sales',
            'technical' => 'Technical',
            'noc' => 'Noc',
            'marketing' => 'Marketing',
            'general' => 'General',
            'none' => 'Other',

          ], "", 'class="form-control"'); ?>

          <small id="category-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Priority</label>

          <?= form_dropdown('priority', [
            '' => '--Select--',
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',

          ], "", 'class="form-control"'); ?>

          <small id="priority-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Employee</label>
          <?php
          $options = ['' => '--Select--'];
          foreach ($employees as $customer) {
              $customer_id = $customer->id ?? ''; 
              $customer_name = $customer->name ?? ''; 
              $customer_number = isset($customer->mobile) ? $customer->mobile : 'N/A';
              $options[$customer_id] = "{$customer_id} - {$customer_name} ({$customer_number})";
          }
          echo form_dropdown('customer', $options, '', 'class="form-control"');
          ?>
          <small id="customer-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Message</label>

          <?= form_textarea([
            "name" => "message",
            "class" => "form-control",
            "rows" => 3
          ]); ?>

          <small id="message-error" class="error text-danger"></small>
        </div>

      </div>

      <div class="box-footer">
        <?= form_button([
          "type" => "submit",
          "class" => "btn btn-warning",
          "content" => "Open Ticket"
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
      url: '<?= route_to('route.ticket.create'); ?>',
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

        $(form).find('button[type="submit"]').html('Open Ticket');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        $(form).trigger('reset');

        tata.success('Ticket opened', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.ticket"); ?>';
          },
        });
      },

      error: function ({ responseText }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Open Ticket');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function (prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't open ticket", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
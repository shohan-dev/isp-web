<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Update Ticket',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Support Tickets', 'url' => route_to('route.ticket') . '?id=' . (int) $details->id],
        ['label' => 'Update Ticket'],
      ],
      'actions' => '<a class="btn btn-default" href="' . route_to('route.ticket') . '?id=' . (int) $details->id . '"><i class="fa fa-inbox" aria-hidden="true"></i> Inbox</a>',
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
            "value" => $details->subject
          ]); ?>

          <small id="subject-error" class="error text-danger"></small>
        </div>

        <div class="row">

          <div class="form-group col-md-6">
            <label>Category</label>

            <?= form_dropdown('category', [
              '' => '--Select--',
              'sales' => 'Sales',
              'technical' => 'Technical',
              'noc' => 'Noc',
              'marketing' => 'Marketing',
              'general' => 'General',
              'none' => 'None',

            ], $details->category, 'class="form-control"'); ?>

            <small id="category-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-md-6">
            <label>Priority</label>

            <?= form_dropdown('priority', [
              '' => '--Select--',
              'low' => 'Low',
              'medium' => 'Medium',
              'high' => 'High',

            ], $details->priority, 'class="form-control"'); ?>

            <small id="priority-error" class="error text-danger"></small>
          </div>

        </div>

        <?php if (getSession('user_role') != 'user'): ?>

          <div class="form-group">
            <label>Remarks</label>

            <?= form_textarea([
              "name" => "remarks",
              "class" => "form-control",
              "value" => $details->remarks,
              "rows" => 3
            ]); ?>

            <small id="remarks-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label>Status</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'opened',
                  'checked' => ($details->status === 'opened'),
                ]); ?>
                Opened
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'processing',
                  'checked' => ($details->status === 'processing'),
                ]); ?>
                Processing
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'closed',
                  'checked' => ($details->status === 'closed'),
                ]); ?>
                Closed
              </label>
            </div>

            <small id="status-error" class="error text-danger"></small>
          </div>

        <?php endif; ?>

      </div>

      <div class="box-footer">
        <?= form_button([
          "type" => "submit",
          "class" => "btn btn-warning",
          "content" => "Update Ticket"
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
      url: '<?= route_to('route.ticket.update', $details->id); ?>',
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

        $(form).find('button[type="submit"]').html('Update Ticket');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Ticket updated', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.ticket"); ?>';
          },
        });
      },

      error: function ({ responseText }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update Ticket');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function (prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't update ticket", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>
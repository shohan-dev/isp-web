<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'SMS Template',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'SMS Template'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (getSession('user_role') === 'super_admin' || getSession('user_role') === 'admin') : ?>
            <a href="<?= route_to('route.sms_templates.event_config'); ?>" class="btn btn-info">
              <i class="fa fa-bell"></i> Event Notifications
            </a>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal">
              <i class="fa fa-plus"></i> Add Template
            </button>
          <?php endif; ?>

          <!-- <?php if (userHasPermission('sms_template', 'delete')) : ?>
            <button class="btn btn-danger delete-btn">
              <i class="far fa-trash-can"></i> Delete Selected
            </button>
          <?php endif; ?> -->
          </div>
        </div>
      </div>

      <div class="box-body">
        <?php if (isset($message)) : ?>
          <div class="alert alert-info">
            <?= esc($message) ?>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">SMS templates</caption>
          <thead class="text-nowrap">
            <tr>
              <!-- <?php if (userHasPermission('sms_template', 'delete')) : ?>
                <th>
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>
              <?php endif; ?> -->
              <th scope="col">Serial</th>
              <th scope="col">Template Name</th>
              <th scope="col">Template Type</th>
              <th scope="col">Template</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($templates as $index => $template) : ?>
              <tr>
                
                <td><?= esc($index + 1) ?></td>
                <td><?= esc($template['template_name']) ?></td>
                <td><?= esc($template['template_type']) ?></td>
                <td><?= esc($template['message_body']) ?></td>
                
                <td>
                
                <?php if (getSession('user_role') === 'super_admin' || $template['template_type']==='custom') : ?>

                  <button class="btn btn-info btn-edit" data-id="<?= esc($template['id']) ?>"
                          data-name="<?= esc($template['template_name']) ?>"
                          data-type="<?= esc($template['template_type']) ?>"
                          data-body="<?= esc($template['message_body']) ?>">Edit
                  </button>
                  <?php endif; ?>
                  <?php if ($template['template_type']==='custom') : ?>
                    <button style="margin-top: 5px;" class="btn btn-danger btn-delete" data-id="<?= esc($template['id']) ?>">
                      Delete
                  </button>
                  <?php endif; ?>
                
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTemplateModalLabel">Add SMS Template</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
<?php /* Route is registered as 'route.sms_Tamplates.store' (sic — the typo is the
         real name in Routes.php). route_to() with a name that does not exist throws
         a RouterException, so this line crashed the whole SMS Templates page. */ ?>
      <form action="<?= route_to('route.sms_Tamplates.store'); ?>" method="post">
        <?= csrf_field(); ?>
        <div class="modal-body">
          <div class="form-group">
            <label for="template_name">Template Name</label>
            <input type="text" name="template_name" id="template_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>SMS Content <sup class="text-danger">*</sup></label>
            <small class="text-info">
              You can use the following placeholders in the SMS content: 
              {EmployeeName}, {Mobile}, {Email}, {CustomerName},{PackageAmount},{will_expire}, {ClientCode}, {UserName}, {Password}, 
              {LoginUserName}, {LoginPassword}, {BaseSiteURL}, {PaidAmount}, {MonthName}, {CompanyName}, {CompanyMobile}
            </small>
           
          </div>

          <div class="form-group">
            <label for="message_body">Message Body</label>
            <textarea name="message_body" id="message_body" class="form-control" required></textarea>
          </div>

          <div class="form-group">
            <label for="template_type">Template Type</label>
            <select name="template_type" id="template_type" class="form-control">
              <option value="custom" selected>Custom</option>
              <?php if (getSession('user_role') === 'super_admin' ): ?>
              <option value="default">Default</option>
              <?php endif; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTemplateModalLabel">Edit SMS Template</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="editTemplateForm" action="<?= route_to('route.sms_templates.update'); ?>" method="post">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" id="edit_template_id">
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_template_name">Template Name</label>
            <input type="text" name="template_name" id="edit_template_name" class="form-control" required>
          </div>
          <small class="text-info">
              You can use the following placeholders in the SMS content: 
              {EmployeeName}, {Mobile}, {Email}, {CustomerName},{PackageAmount},{will_expire}, {ClientCode}, {UserName}, {Password}, 
              {LoginUserName}, {LoginPassword}, {BaseSiteURL}, {PaidAmount}, {MonthName}, {CompanyName}, {CompanyMobile}
            </small>
          <div class="form-group">
            <label for="edit_message_body">Message Body</label>
            <textarea name="message_body" id="edit_message_body" class="form-control" required></textarea>
          </div>

          <div class="form-group">
            <label for="edit_template_type">Template Type</label>
            <select name="template_type" id="edit_template_type" class="form-control">
              <option value="custom">Custom</option>
              <option value="default">Default</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {
    // Initialize DataTable
    var table = $('.datatable').DataTable();

    $(document).on('click', '.btn-delete', function() {
  const id = $(this).data('id');

  if (confirm('Are you sure you want to delete this template?')) {
    $.ajax({
      url: '<?= route_to('route.sms_templates.delete'); ?>', // Use route_to directly
      type: 'POST',
      data: { id: id }, // Pass ID as part of the request body
      headers: {
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      success: function(result) {
        if (result.response === 'success') {
          tata.success('Template deleted', 'Template deleted successfully');
          setTimeout(function() {
            window.location.href = '<?= route_to('route.sms_Tamplates'); ?>';
          }, 500);
        } else {
          tata.error("Couldn't delete template", 'Failed to delete template');
        }
      },
      error: function(response) {
        tata.error("Couldn't delete template", 'Failed to delete template');
      }
    });
  }
});



    // Edit button click handler
    $(document).on('click', '.btn-edit', function() {
      const id = $(this).data('id');
      const name = $(this).data('name');
      const type = $(this).data('type');
      const body = $(this).data('body');

      $('#edit_template_id').val(id);
      $('#edit_template_name').val(name);
      $('#edit_message_body').val(body);
      $('#edit_template_type').val(type);

      $('#editTemplateModal').modal('show');
    });

    // Handle form submission for editing
    $('#editTemplateForm').submit(function(e) {
      e.preventDefault();
      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        },
        success: function(result) {
            if (result.response === 'success') {
                $('#editTemplateModal').modal('hide'); // Close the modal

                // Redirect to the SMS Templates page after closing the modal
                setTimeout(function() {
                    window.location.href = '<?= route_to('route.sms_Tamplates'); ?>';
                }, 500); // You can adjust the delay if needed
            } else {
                tata.error("Couldn't update template", 'Failed to update template');
            }
        },
        error: function(response) {
            tata.error("Couldn't update template", 'Failed to update template');
        }
      });
    });

  });
</script>

<?= $this->endSection('script'); ?>

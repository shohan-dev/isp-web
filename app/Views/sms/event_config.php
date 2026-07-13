<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'SMS Event Notifications',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'SMS Templates', 'url' => route_to('route.sms_Tamplates')],
        ['label' => 'SMS Event Notifications'],
      ],
    ]); ?>
<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-bell" aria-hidden="true"></i> Event SMS templates</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <a href="<?= route_to('route.sms_Tamplates'); ?>" class="btn btn-default">
              <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to Templates
            </a>
          </div>
        </div>
      </div>
      <div class="box-body">

        <div class="alert alert-info">
          <i class="fa fa-info-circle"></i>
          <strong>How it works:</strong>
          When an event occurs (e.g. a payment is recorded), the system will send the SMS template you select here.
          If you select <em>Use Default</em>, the system falls back to the built-in default template.
          If you <em>disable</em> an event, no SMS is sent at all for that event.
        </div>

        <form id="eventConfigForm">
          <?= csrf_field(); ?>
          <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <caption class="sr-only">Event SMS notification settings</caption>
            <thead>
              <tr>
                <th scope="col" width="5%">Enabled</th>
                <th scope="col" width="35%">Event</th>
                <th scope="col" width="60%">SMS Template to Use</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $key => $event): ?>
                <?php
                  $config     = $configs[$key] ?? null;
                  $isEnabled  = $config ? (bool) $config->is_enabled : true;
                  // If admin has saved a custom template, use it; otherwise pre-select the default
                  $templateId = ($config && $config->template_id) ? $config->template_id : $event['default_id'];
                  $isUsingDefault = !($config && $config->template_id);
                ?>
              <tr>
                <td class="text-center" style="vertical-align:middle;">
                  <input type="checkbox"
                         name="enabled_<?= $key ?>"
                         value="1"
                         <?= $isEnabled ? 'checked' : '' ?>
                         class="event-toggle"
                         data-row="<?= $key ?>">
                </td>
                <td style="vertical-align:middle;">
                  <strong><?= esc($event['label']) ?></strong>
                  <br>
                  <small class="text-muted">
                    Default template ID: <code><?= $event['default_id'] ?></code>
                  </small>
                </td>
                <td>
                  <select name="template_<?= $key ?>"
                          class="form-control template-select"
                          id="select_<?= $key ?>"
                          <?= !$isEnabled ? 'disabled' : '' ?>>
                    <option value="">-- Use Default (ID <?= $event['default_id'] ?>) --</option>
                    <?php foreach ($templates as $tpl): ?>
                      <?php $tplId = is_object($tpl) ? $tpl->id : $tpl['id']; ?>
                      <?php $tplName = is_object($tpl) ? $tpl->template_name : $tpl['template_name']; ?>
                      <?php $tplType = is_object($tpl) ? $tpl->template_type : $tpl['template_type']; ?>
                      <option value="<?= esc($tplId) ?>"
                              <?= ((string)$templateId === (string)$tplId) ? 'selected' : '' ?>>
                        [<?= ucfirst(esc($tplType)) ?>] <?= esc($tplName) ?>
                        <?= ($isUsingDefault && (string)$tplId === (string)$event['default_id']) ? '← (system default)' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if ($isUsingDefault): ?>
                    <small class="text-muted">
                      <i class="fa fa-info-circle"></i>
                      No custom template set — using system default (ID <?= $event['default_id'] ?>)
                    </small>
                  <?php else: ?>
                    <small class="text-info">
                      <i class="fa fa-check-circle"></i>
                      Custom template selected (ID <?= esc($templateId) ?>)
                    </small>
                  <?php endif; ?>
                </td>
              </tr>

              <?php endforeach; ?>
            </tbody>
          </table>
          </div>

          <div class="box-footer">
            <button type="submit" class="btn btn-warning btn-flat" id="saveBtn">
              <i class="fa fa-save"></i> Save Event Settings
            </button>
          </div>
        </form>

      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  // Toggle select disabled state when checkbox is toggled
  $(document).on('change', '.event-toggle', function () {
    const key = $(this).data('row');
    const checked = $(this).is(':checked');
    $('#select_' + key).prop('disabled', !checked);
  });

  // Save form via AJAX
  $('#eventConfigForm').submit(function (e) {
    e.preventDefault();
    const form = this;

    $.ajax({
      url: '<?= route_to('route.sms_templates.event_config.save'); ?>',
      type: 'POST',
      data: $(form).serialize(),
      headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
      beforeSend: function () {
        $('#saveBtn').html('<i class="fa fa-spinner fa-spin"></i> Saving...').attr('disabled', true);
      },
      success: function (result) {
        $('#saveBtn').html('<i class="fa fa-save"></i> Save Event Settings').removeAttr('disabled');
        tata.success('Saved', result.response);
      },
      error: function (xhr) {
        $('#saveBtn').html('<i class="fa fa-save"></i> Save Event Settings').removeAttr('disabled');
        const result = JSON.parse(xhr.responseText || '{"response":"Error saving settings"}');
        tata.error("Couldn't save event settings", result.response);
      }
    });
  });
</script>
<?= $this->endSection('script'); ?>

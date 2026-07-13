<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Voice SMS',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Voice SMS'],
      ],
    ]); ?>


<div class="row">
            <!-- GATEWAY SETTINGS -->
            <div class="col-md-4">
                <div class="box box-warning">
                    <div class="box-header with-border ipb-box-toolbar">
                        <div class="ipb-list-toolbar">
                          <div class="ipb-list-toolbar-filters">
                            <span class="ipb-filter-label"><i class="fa fa-gears" aria-hidden="true"></i> Voice gateway</span>
                          </div>
                        </div>
                    </div>
                    <form id="gateway-form">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="default_voice_sms_gateway">Default Gateway</label>
                                <select name="default_voice_sms_gateway" id="default_voice_sms_gateway" class="form-control select2" style="width: 100%;">
                                    <option value="">-- Choose Gateway --</option>
                                    <?php foreach ($gateways as $gw): ?>
                                        <option value="<?= $gw ?>" <?= ($current_gateway == $gw) ? 'selected' : '' ?>><?= ucwords($gw) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="help-block small text-muted">This gateway will be used for all outgoing voice notifications.</p>
                            </div>
                        </div>
                        <div class="box-footer">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary btn-block btn-flat shadow">
                                <i class="fa fa-save"></i> UPDATE GATEWAY
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SAVED MESSAGES -->
            <div class="col-md-8">
                <div class="box box-warning">
                    <div class="box-header with-border ipb-box-toolbar">
                        <div class="ipb-list-toolbar">
                          <div class="ipb-list-toolbar-filters">
                            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Voice message library</span>
                          </div>
                          <div class="ipb-list-toolbar-actions">
                            <span id="sync-voices-container" style="display:none;">
                                <button type="button" class="btn btn-info" id="sync-voices-btn">
                                    <i class="fa fa-refresh" aria-hidden="true"></i> Sync from gateway
                                </button>
                            </span>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addMessageModal">
                                <i class="fa fa-plus" aria-hidden="true"></i> New message
                            </button>
                          </div>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <div class="table-responsive">
                            <table class="table table-hover" id="voice-messages-table">
                                <caption class="sr-only">Voice message library</caption>
                                <thead>
                                    <tr>
                                        <th style="padding-left: 20px;" scope="col">#</th>
                                        <th scope="col">Subject / Name</th>
                                        <th scope="col">Provider ID</th>
                                        <th scope="col">Added On</th>
                                        <th class="text-right" style="padding-right: 20px;" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($messages)): ?>
                                        <?php $i = 1; foreach ($messages as $msg): ?>
                                            <tr>
                                                <td style="padding-left: 20px;"><?= $i++ ?></td>
                                                <td><strong><?= $msg->name ?></strong></td>
                                                <td><span class="badge bg-blue"><?= $msg->message_id ?></span></td>
                                                <td><?= date('d M Y', strtotime($msg->created_at)) ?></td>
                                                <td class="text-right" style="padding-right: 20px;">
                                                    <button type="button" class="btn btn-primary btn-xs btn-flat edit-msg-btn"
                                                        data-id="<?= $msg->id ?>"
                                                        data-name="<?= esc($msg->name) ?>"
                                                        data-msgid="<?= esc($msg->message_id) ?>"
                                                        aria-label="Edit message">
                                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <a href="<?= route_to('route.voice-sms.delete-message', $msg->id) ?>"
                                                       class="btn btn-danger btn-xs btn-flat"
                                                       onclick="return confirm('Delete this message?')"
                                                       aria-label="Delete message">
                                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted" style="padding: 30px;">Your voice message library is empty.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- VOICE EVENT NOTIFICATIONS -->
            <div class="col-md-12">
                <div class="box box-warning shadow-lg border-radius-15">
                    <div class="box-header with-border bg-gray-light">
                        <h3 class="box-title text-orange"><i class="fa fa-bell"></i> Voice Event Notifications</h3>
                        <p class="text-muted small no-margin">Configure which voice message alerts fire automatically for each system event.</p>
                    </div>
                    <form id="event-config-form">
                        <div class="box-body no-padding">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped no-margin">
                                    <caption class="sr-only">Voice event notifications</caption>
                                    <thead>
                                        <tr class="bg-gray">
                                            <th style="width: 80px;" class="text-center" scope="col">Enabled</th>
                                            <th style="width: 250px;" scope="col">System Event</th>
                                            <th scope="col">Voice Message to Use</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $slug => $event): 
                                            $cfg = $configs[$slug] ?? null;
                                            $isEnabled = $cfg ? $cfg->is_enabled : 0;
                                            $selectedId = $cfg ? $cfg->voice_template_id : '';
                                        ?>
                                            <tr>
                                                <td class="text-center" style="vertical-align: middle;">
                                                    <input type="checkbox" name="enabled_<?= $slug ?>" <?= $isEnabled ? 'checked' : '' ?> class="minimal">
                                                </td>
                                                <td style="vertical-align: middle;">
                                                    <strong><?= $event['label'] ?></strong>
                                                    <br><small class="text-muted">Slug: <?= $slug ?></small>
                                                </td>
                                                <td>
                                                    <select name="template_<?= $slug ?>" class="form-control select2" style="width: 100%;">
                                                        <option value="">-- Use Default / None --</option>
                                                        <?php foreach ($messages as $msg): ?>
                                                            <option value="<?= $msg->id ?>" <?= ($selectedId == $msg->id) ? 'selected' : '' ?>>
                                                                <?= $msg->name ?> (ID: <?= $msg->message_id ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="box-footer" style="background: var(--surface, #fcfcfc);">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary btn-flat pull-right shadow">
                                <i class="fa fa-save"></i> SAVE EVENT SETTINGS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Create Modal -->
<div class="modal fade" id="addMessageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-radius-15">
            <div class="modal-header bg-green shadow">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff">&times;</button>
                <h4 class="modal-title" style="color:#fff"><i class="fa fa-plus-circle"></i> Create Voice Template</h4>
            </div>
            <form id="add-message-form">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group p-v-10">
                        <label for="create_target_name">System Name (e.g. Bill Payment Alert)</label>
                        <input type="text" name="name" id="create_target_name" class="form-control input-lg" placeholder="Template Name" required>
                    </div>
                    <div class="form-group p-v-10">
                        <label for="create_target_message_id">Voice Provider Voice ID</label>
                        <input type="text" name="message_id" id="create_target_message_id" class="form-control input-lg" placeholder="Voice ID" required>
                        <span class="help-block small">This code is provided by your Gateway.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left shadow" data-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-success btn-flat shadow">SAVE TEMPLATE</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editMessageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-radius-15">
            <div class="modal-header bg-blue shadow">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff">&times;</button>
                <h4 class="modal-title" style="color:#fff"><i class="fa fa-pencil-square-o"></i> Edit Voice Template</h4>
            </div>
            <form id="edit-message-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_target_id">
                <div class="modal-body">
                    <div class="form-group p-v-10">
                        <label for="edit_target_name">System Name</label>
                        <input type="text" name="name" id="edit_target_name" class="form-control input-lg" required>
                    </div>
                    <div class="form-group p-v-10">
                        <label for="edit_target_message_id">Voice Provider Voice ID</label>
                        <input type="text" name="message_id" id="edit_target_message_id" class="form-control input-lg" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left shadow" data-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-primary btn-flat shadow">UPDATE TEMPLATE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sync Voices Modal -->
<div class="modal fade" id="syncVoicesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-radius-15">
            <div class="modal-header bg-info shadow">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff">&times;</button>
                <h4 class="modal-title" style="color:#fff"><i class="fa fa-refresh"></i> Voices from Gateway</h4>
            </div>
            <div class="modal-body">
                <p>The following voices were found in your <strong>Awaj Digital</strong> account. Click <strong>Import</strong> to save them to your library.</p>
                <div class="table-responsive">
                    <table class="table table-striped" id="gateway-voices-table">
                        <caption class="sr-only">Gateway voices</caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th style="width: 150px;" scope="col">Status</th>
                                <th style="width: 120px;" class="text-right" scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="gateway-voices-body">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat shadow" data-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('css'); ?>
<style>
    .border-radius-15 { border-radius: 12px !important; overflow: hidden; }
    .shadow-lg { box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important; }
    .box { border: none !important; margin-bottom: 25px; }
    .box-header { border-bottom: 1px solid #f4f4f4; padding: 15px 20px; }
    .box-title { font-weight: 700 !important; font-size: 16px !important; }
    .text-orange { color: #f39c12 !important; }
    .input-lg { border-radius: 8px !important; }
    .p-v-10 { padding: 10px 0; }
    .bg-gray-light { background-color: #f9fafb !important; }
    .table thead th { border-top: none !important; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #64748b; }
</style>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Save Gateway
        $('#gateway-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> SAVING...');
            
            $.ajax({
                url: '<?= route_to('route.voice-sms.save-gateway') ?>',
                type: 'POST',
                data: $(this).serialize(),
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function(res) {
                    if (res.status === 'success') {
                        tata.success('Gateway saved', res.response);
                    } else {
                        tata.error("Couldn't save gateway", res.response);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function() {
                    tata.error("Couldn't save gateway", 'Something went wrong');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Add Message
        $('#add-message-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> SAVING...');

            $.ajax({
                url: '<?= route_to('route.voice-sms.add-message') ?>',
                type: 'POST',
                data: $(this).serialize(),
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function(res) {
                    if (res.status === 'success') {
                        tata.success('Template saved', res.response);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        tata.error("Couldn't save template", res.response);
                        btn.prop('disabled', false).html('SAVE TEMPLATE');
                    }
                },
                error: function() {
                    tata.error("Couldn't save template", 'Failed to save template');
                    btn.prop('disabled', false).html('SAVE TEMPLATE');
                }
            });
        });

        // Edit Message Button Action (using delegation for DataTables compatibility)
        $(document).on('click', '.edit-msg-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const msgId = $(this).data('msgid');

            $('#edit_target_id').val(id);
            $('#edit_target_name').val(name);
            $('#edit_target_message_id').val(msgId);
            $('#editMessageModal').modal('show');
        });

        // Update Message Form Action
        $('#edit-message-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> UPDATING...');

            $.ajax({
                url: '<?= route_to('route.voice-sms.update-message') ?>',
                type: 'POST',
                data: $(this).serialize(),
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function(res) {
                    if (res.status === 'success') {
                        tata.success('Template updated', res.response);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        tata.error("Couldn't update template", res.response);
                        btn.prop('disabled', false).html('UPDATE TEMPLATE');
                    }
                },
                error: function() {
                    tata.error("Couldn't update template", 'Failed to update template');
                    btn.prop('disabled', false).html('UPDATE TEMPLATE');
                }
            });
        });

        // Save Event Config
        $('#event-config-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> UPDATING SETTINGS...');

            $.ajax({
                url: '<?= route_to('route.voice-sms.save-event-config') ?>',
                type: 'POST',
                data: $(this).serialize(),
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function(res) {
                    if (res.status === 'success') {
                        tata.success('Settings Saved', res.response);
                    } else {
                        tata.error("Couldn't save event settings", res.response);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function() {
                    tata.error("Couldn't save event settings", 'Failed to update event settings');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Sync Voices Logic
        function checkShowSyncBtn() {
            if ($('#default_voice_sms_gateway').val() === 'awajdigital') {
                $('#sync-voices-container').show();
            } else {
                $('#sync-voices-container').hide();
            }
        }
        checkShowSyncBtn();
        $('#default_voice_sms_gateway').on('change', checkShowSyncBtn);

        $('#sync-voices-btn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> FETCHING...');

            $.ajax({
                url: '<?= route_to('route.voice-sms.get-gateway-voices') ?>',
                type: 'GET',
                success: function(res) {
                    if (res.status === 'success') {
                        let html = '';
                        res.response.forEach(voice => {
                            html += `
                                <tr>
                                    <td><strong>${voice.name}</strong></td>
                                    <td><span class="badge ${voice.status === 'approved' ? 'bg-green' : 'bg-orange'}">${voice.status}</span></td>
                                    <td class="text-right">
                                        ${voice.status === 'approved' ? 
                                            `<button type="button" class="btn btn-success btn-xs btn-flat import-voice-btn" data-name="${voice.name}" data-id="${voice.name}">IMPORT</button>` : 
                                            `<span class="text-muted small">Not Approved</span>`}
                                    </td>
                                </tr>
                            `;
                        });
                        $('#gateway-voices-body').html(html || '<tr><td colspan="3" class="text-center">No voices found</td></tr>');
                        $('#syncVoicesModal').modal('show');
                    } else {
                        tata.error("Couldn't sync voices", res.response);
                    }
                    btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> SYNC FROM GATEWAY');
                },
                error: function() {
                    tata.error("Couldn't sync voices", 'Failed to fetch voices');
                    btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> SYNC FROM GATEWAY');
                }
            });
        });

        $(document).on('click', '.import-voice-btn', function() {
            const name = $(this).data('name');
            const voiceId = $(this).data('id');
            const btn = $(this);
            btn.prop('disabled', true).text('IMPORTING...');

            $.ajax({
                url: '<?= route_to('route.voice-sms.add-message') ?>',
                type: 'POST',
                data: {
                    name: name,
                    message_id: voiceId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                success: function(res) {
                    if (res.status === 'success') {
                        tata.success('Voice imported', name + ' imported successfully');
                        btn.removeClass('btn-success').addClass('btn-default').text('IMPORTED').prop('disabled', true);
                    } else {
                        tata.error("Couldn't import voice", res.response);
                        btn.prop('disabled', false).text('IMPORT');
                    }
                },
                error: function() {
                    tata.error("Couldn't import voice", 'Import failed');
                    btn.prop('disabled', false).text('IMPORT');
                }
            });
        });
    });
</script>
<?= $this->endSection(); ?>

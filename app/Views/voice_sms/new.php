<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'New Voice Notification Send voice alerts to your customers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Voice SMS', 'url' => route_to('route.voice-sms')],
        ['label' => 'New Voice Notification Send voice alerts to your customers'],
      ],
    ]); ?>
<div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="box box-primary shadow-lg border-radius-15">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-paper-plane text-blue"></i> Voice Broadcaster</h3>
                    </div>

                    <?= form_open(route_to('route.voice-sms.create'), 'id="voice-form"'); ?>
                    <div class="box-body row">
                        <div class="col-md-6 border-right">
                            <h4 class="text-muted"><i class="fa fa-users"></i> Recipients</h4>
                            <hr>
                            <div class="form-group">
                                <label>Service Area</label>
                                <?php $area_data = empty($area) ? ['' => 'No area found!'] : ['' => '--Select Area--'] + array_combine(array_column($area, 'id'), array_map(fn($a) => "$a->area_name ($a->area_code)", $area)); ?>
                                <?= form_dropdown('area', $area_data, '', 'class="form-control select2" style="width:100%"'); ?>
                            </div>

                            <div class="form-group">
                                <label>Target Customers <sup class="text-danger">*</sup></label>
                                <select name="send_to[]" id="send_to" class="form-control select2" multiple="multiple" style="width:100%">
                                    <?php if (!empty($customers)): ?>
                                        <option value="all">All Active Customers</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= $c->id ?>"><?= esc($c->name) ?> (<?= $c->mobile ?? '--' ?>)</option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">You can select multiple customers or 'All'.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h4 class="text-muted"><i class="fa fa-volume-up"></i> Voice Message</h4>
                            <hr>
                            <div class="form-group">
                                <label>Select Saved Message <sup class="text-danger">*</sup></label>
                                <select name="voice_msg_id" id="voice_msg_id" class="form-control select2" style="width:100%" required>
                                    <option value="">-- Choose a message --</option>
                                    <?php foreach ($voice_messages as $msg): ?>
                                        <option value="<?= $msg->message_id ?>"><?= $msg->name ?> (ID: <?= $msg->message_id ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="help-block small">Choose one of your saved voice messages from the library.</p>
                            </div>

                            <div class="form-group">
                                <label>Additional Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Enter any internal notes for this broadcast..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="box-footer text-center" style="padding: 20px;">
                        <button type="submit" class="btn btn-primary btn-lg btn-flat" id="send_btn" style="padding: 12px 40px; border-radius: 30px; font-weight: bold;">
                            <i class="fa fa-play-circle"></i> START BROADCAST
                        </button>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection(); ?>

<?= $this->section('css'); ?>

<style>
    .border-radius-15 { border-radius: 15px !important; overflow: hidden; }
    .shadow-lg { box-shadow: 0 15px 45px rgba(0,0,0,0.1) !important; }
    .border-right { border-right: 1px solid #f4f4f4; }
    @media (max-width: 768px) { .border-right { border-right: none; border-bottom: 1px solid #f4f4f4; margin-bottom: 20px; } }
</style>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Click to select',
            allowClear: true
        });

        // Pre-select customers from URL params
        const urlParams = new URLSearchParams(window.location.search);
        const ids = urlParams.get('ids');
        if (ids) {
            $('#send_to').val(ids.split(',')).trigger('change');
        }

        // Live Area Filter
        $('select[name="area"]').change(function() {
            const area = $(this).val();
            $.ajax({
                url: '<?= route_to("route.sms.getuser"); ?>',
                type: 'POST',
                data: { area },
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function(result) {
                    $('#send_to').html(result.response).trigger('change');
                }
            });
        });

        $('#voice-form').submit(function(e) {
            e.preventDefault();
            const btn = $('#send_btn');
            const data = $(this).serialize();

            swal({
                title: "Confirm Broadcast?",
                text: "This will start the voice call process for selected customers.",
                icon: "warning",
                buttons: true,
                dangerMode: false,
            }).then((willSend) => {
                if (willSend) {
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Initializing...');
                    $.ajax({
                        url: '<?= route_to("route.voice-sms.create") ?>',
                        type: 'POST',
                        data: data,
                        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                        success: function(res) {
                            if (res.status === 'success') {
                                tata.success('Broadcast Started', res.response);
                            } else {
                                tata.error("Couldn't start broadcast", res.response);
                            }
                            btn.prop('disabled', false).html('<i class="fa fa-play-circle"></i> START BROADCAST');
                        },
                        error: function(xhr) {
                            let message = 'Failed to start broadcast';
                            try {
                                if (xhr.responseText) {
                                    const res = JSON.parse(xhr.responseText);
                                    message = res.response || message;
                                }
                            } catch(e) {}
                            tata.error("Couldn't start broadcast", message);
                            btn.prop('disabled', false).html('<i class="fa fa-play-circle"></i> START BROADCAST');
                        }
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection(); ?>

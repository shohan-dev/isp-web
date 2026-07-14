<div class="form-group">
    <label>Webmaster Email</label>
    <?= form_input([
        'name'  => 'webmaster_email',
        'class' => 'form-control',
        'value' => getSetting('webmaster_email')
    ]); ?>
    <small id="webmaster_email-error" class="error text-danger"></small>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">SMTP Settings</div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Hostname</label>
            <?= form_input([
                'name'  => 'smtp_host',
                'class' => 'form-control',
                'value' => getSetting('smtp_host')
            ]); ?>
            <small id="smtp_host-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Username</label>
            <?= form_input([
                'name'  => 'smtp_user',
                'class' => 'form-control',
                'value' => getSetting('smtp_user')
            ]); ?>
            <small id="smtp_user-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Password</label>
            <?= form_input([
                'type'  => 'password',
                'name'  => 'smtp_password',
                'class' => 'form-control',
                'value' => getSetting('smtp_password')
            ]); ?>
            <small id="smtp_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Port Number</label>
            <?= form_input([
                'type'  => 'number',
                'name'  => 'smtp_port',
                'class' => 'form-control',
                'value' => getSetting('smtp_port')
            ]); ?>
            <small id="smtp_port-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Encryption Type</label>
            <?= form_dropdown('smtp_crypto', [
                '' => '--Select--',
                'ssl' => 'SSL',
                'tls' => 'TLS',
            ], getSetting('smtp_crypto'), 'class="form-control"');
            ?>
            <small id="smtp_crypto-error" class="error text-danger"></small>
        </div>

    </div>

</div>
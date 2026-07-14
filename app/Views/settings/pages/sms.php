<div class="form-group">
    <label>Default SMS Gateway</label>

    <?= form_dropdown('default_sms_gateway', [
    '' => '--Select--',
    'bulksmsbd' => 'BulkSmsBd',
    'bulksmsdhaka' => 'BulkSmsDhaka',
    'greenwebsms' => 'GreenWeb Sms',
    'smsq' => 'SmsQ',
    'telnet' => 'Telnet',
    'awajdigital' => 'Awaj Digital (Voice/OTP)',
], getSetting('default_sms_gateway'), 'class="form-control"'); ?>

    <small id="default_sms_gateway-error" class="error text-danger"></small>
</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            BulkSmsBd SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="bulksmsbd">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://bulksmsbd.com/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Api Key</label>
            <?= form_input([
    'name' => 'bulksmsbd_api_key',
    'class' => 'form-control',
    'value' => getSetting('bulksmsbd_api_key')
]); ?>
            <small id="bulksmsbd_api_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Sender Id</label>
            <?= form_input([
    'name' => 'bulksmsbd_sender_id',
    'class' => 'form-control',
    'value' => getSetting('bulksmsbd_sender_id')
]); ?>
            <small id="bulksmsbd_sender_id-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            BulkSmsDhaka SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="bulksmsdhaka">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://bulksmsdhaka.com/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Api Key</label>
            <?= form_input([
    'name' => 'bulksmsdhaka_api_key',
    'class' => 'form-control',
    'value' => getSetting('bulksmsdhaka_api_key')
]); ?>
            <small id="bulksmsdhaka_api_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Sender Id</label>
            <?= form_input([
    'name' => 'bulksmsdhaka_sender_id',
    'class' => 'form-control',
    'value' => getSetting('bulksmsdhaka_sender_id')
]); ?>
            <small id="bulksmsdhaka_sender_id-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            GreenWeb SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="greenwebsms">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://sms.greenweb.com.bd/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div class="form-group">
            <label>Api Token</label>
            <?= form_input([
    'name' => 'greenwebsms_token',
    'class' => 'form-control',
    'value' => getSetting('greenwebsms_token')
]); ?>
            <small id="greenwebsms_token-error" class="error text-danger"></small>
        </div>

    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            SmsQ SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="smsq">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://smsq.com.bd" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Api Key</label>
            <?= form_input([
    'name' => 'smsq_api_key',
    'class' => 'form-control',
    'value' => getSetting('smsq_api_key')
]); ?>
            <small id="smsq_api_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Client Id</label>
            <?= form_input([
    'name' => 'smsq_client_id',
    'class' => 'form-control',
    'value' => getSetting('smsq_client_id')
]); ?>
            <small id="smsq_client_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Sender Id</label>
            <?= form_input([
    'name' => 'smsq_sender_id',
    'class' => 'form-control',
    'value' => getSetting('smsq_sender_id')
]); ?>
            <small id="smsq_sender_id-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            Telnet SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="telnet">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://sms.telnet.com.bd/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Username</label>
            <?= form_input([
    'name' => 'telnet_username',
    'class' => 'form-control',
    'value' => getSetting('telnet_username')
]); ?>
            <small id="telnet_username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Password</label>
            <?= form_input([
    'name' => 'telnet_password',
    'class' => 'form-control',
    'value' => getSetting('telnet_password')
]); ?>
            <small id="telnet_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>CLI</label>
            <?= form_input([
    'name' => 'telnet_cli',
    'class' => 'form-control',
    'value' => getSetting('telnet_cli')
]); ?>
            <small id="telnet_cli-error" class="error text-danger"></small>
        </div>
        <div class="form-group">
            <label>API Token <span class="text-primary">(Optional)</span></label>
            <?= form_input([
    'name' => 'telnet_api_token',
    'class' => 'form-control',
    'placeholder' => 'Enter your API token here...',
    'value' => getSetting('telnet_api_token')
]); ?>
            <small class="text-muted">Use the API Token instead of Username/Password .</small>
            <small id="telnet_api_token-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            Awaj Digital Voice SMS Gateway

            <div class="pull-right">

                <button type="button" class="btn btn-info btn-xs check-balance" data-toggle="tooltip" title="View Credit Balance" data-gateway="awajdigital">
                    <i class="fa fa-rotate" style="color: #ffffff;"></i>
                </button>

                <a href="https://awajdigital.com/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Website">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>API Token</label>
            <?= form_input([
                'name' => 'awajdigital_api_token',
                'class' => 'form-control',
                'value' => getSetting('awajdigital_api_token')
            ]); ?>
            <small id="awajdigital_api_token-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Sender Number</label>
            <?= form_input([
                'name' => 'awajdigital_sender_number',
                'class' => 'form-control',
                'placeholder' => 'e.g. 8801234567890',
                'value' => getSetting('awajdigital_sender_number')
            ]); ?>
            <small id="awajdigital_sender_number-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Default Voice Name</label>
            <?= form_input([
                'name' => 'awajdigital_default_voice',
                'class' => 'form-control',
                'placeholder' => 'Enter approved voice name',
                'value' => getSetting('awajdigital_default_voice')
            ]); ?>
            <small id="awajdigital_default_voice-error" class="error text-danger"></small>
        </div>

    </div>

</div>
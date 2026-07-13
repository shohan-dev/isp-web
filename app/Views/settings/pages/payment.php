<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            Bkash Merchant Gateway
            <div class="pull-right">
                <a href="https://pgw-integration.bkash.com/#/merchant/signin" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Bkash Payment Number</label>
            <?= form_input([
                'name'        => 'bkash_payment_number',
                'class'       => 'form-control',
                'placeholder' => 'e.g. 01XXXXXXXXX',
                'value'       => getSetting('bkash_payment_number')
            ]); ?>
            <small id="bkash_payment_number-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>App Key</label>
            <?= form_input([
                'name'  => 'bkashpg_app_key',
                'class' => 'form-control',
                'value' => getSetting('bkashpg_app_key')
            ]); ?>
            <small id="bkashpg_app_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>App Secret</label>
            <?= form_input([
                'name'  => 'bkashpg_app_secret',
                'class' => 'form-control',
                'value' => getSetting('bkashpg_app_secret')
            ]); ?>
            <small id="bkashpg_app_secret-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Username</label>
            <?= form_input([
                'name'  => 'bkashpg_username',
                'class' => 'form-control',
                'value' => getSetting('bkashpg_username')
            ]); ?>
            <small id="bkashpg_username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Password</label>
            <?= form_input([
                'name'  => 'bkashpg_password',
                'class' => 'form-control',
                'value' => getSetting('bkashpg_password')
            ]); ?>
            <small id="bkashpg_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'bkashpg_charge',
                'class' => 'form-control',
                'value' => getSetting('bkashpg_charge')
            ]); ?>
            <small id="bkashpg_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'bkashpg_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('bkashpg_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'bkashpg_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('bkashpg_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="bkashpg_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_bkashpg',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_bkashpg') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_bkashpg',
                        'value' => 'no',
                        'checked' => (getSetting('enable_bkashpg') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_bkashpg-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            Nagad Merchant Gateway
            <div class="pull-right">
                <a href="https://auth.mynagad.com:10900/authentication-service-provider-1.0/login" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Nagad Payment Number</label>
            <?= form_input([
                'name'        => 'nagad_payment_number',
                'class'       => 'form-control',
                'placeholder' => 'e.g. 01XXXXXXXXX',
                'value'       => getSetting('nagad_payment_number')
            ]); ?>
            <small id="nagad_payment_number-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Account Number</label>
            <?= form_input([
                'name'  => 'nagadpg_merchant_account',
                'class' => 'form-control',
                'value' => getSetting('nagadpg_merchant_account')
            ]); ?>
            <small id="nagadpg_merchant_account-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Id</label>
            <?= form_input([
                'name'  => 'nagadpg_merchant_id',
                'class' => 'form-control',
                'value' => getSetting('nagadpg_merchant_id')
            ]); ?>
            <small id="nagadpg_merchant_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Private Key</label>
            <?= form_textarea([
                'name'  => 'nagadpg_merchant_private_key',
                'class' => 'form-control',
                'style'   => 'max-height: 100px',
                'value' => getSetting('nagadpg_merchant_private_key')
            ]); ?>
            <small id="nagadpg_merchant_private_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Public Key</label>
            <?= form_textarea([
                'name'  => 'nagadpg_merchant_public_key',
                'class' => 'form-control',
                'style'   => 'max-height: 100px',
                'value' => getSetting('nagadpg_merchant_public_key')
            ]); ?>
            <small id="nagadpg_merchant_public_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'nagadpg_charge',
                'class' => 'form-control',
                'value' => getSetting('nagadpg_charge')
            ]); ?>
            <small id="nagadpg_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'nagadpg_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('nagadpg_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'nagadpg_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('nagadpg_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="nagadpg_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_nagadpg',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_nagadpg') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_nagadpg',
                        'value' => 'no',
                        'checked' => (getSetting('enable_nagadpg') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_nagadpg-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            SSLCommerz
            <div class="pull-right">
                <a href="https://sslcommerz.com/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Store Id</label>
            <?= form_input([
                'name'  => 'sslcommerz_store_id',
                'class' => 'form-control',
                'value' => getSetting('sslcommerz_store_id')
            ]); ?>
            <small id="sslcommerz_store_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Store Password (API/Secret Key)</label>
            <?= form_input([
                'name'  => 'sslcommerz_store_passwd',
                'class' => 'form-control',
                'value' => getSetting('sslcommerz_store_passwd')
            ]); ?>
            <small id="sslcommerz_store_passwd-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'sslcommerz_charge',
                'class' => 'form-control',
                'value' => getSetting('sslcommerz_charge')
            ]); ?>
            <small id="sslcommerz_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'sslcommerz_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('sslcommerz_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'sslcommerz_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('sslcommerz_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="sslcommerz_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_sslcommerz',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_sslcommerz') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_sslcommerz',
                        'value' => 'no',
                        'checked' => (getSetting('enable_sslcommerz') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_sslcommerz-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            EPS (Easy Payment System)
            <div class="pull-right">
                <a href="https://www.eps.com.bd/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Merchant Id</label>
            <?= form_input([
                'name'  => 'eps_merchant_id',
                'class' => 'form-control',
                'value' => getSetting('eps_merchant_id')
            ]); ?>
            <small id="eps_merchant_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Store Id</label>
            <?= form_input([
                'name'  => 'eps_store_id',
                'class' => 'form-control',
                'value' => getSetting('eps_store_id')
            ]); ?>
            <small id="eps_store_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Username</label>
            <?= form_input([
                'name'  => 'eps_username',
                'class' => 'form-control',
                'value' => getSetting('eps_username')
            ]); ?>
            <small id="eps_username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Password</label>
            <?= form_input([
                'name'  => 'eps_password',
                'class' => 'form-control',
                'value' => getSetting('eps_password')
            ]); ?>
            <small id="eps_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Hash Key</label>
            <?= form_input([
                'name'  => 'eps_hash_key',
                'class' => 'form-control',
                'value' => getSetting('eps_hash_key')
            ]); ?>
            <small id="eps_hash_key-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'eps_charge',
                'class' => 'form-control',
                'value' => getSetting('eps_charge')
            ]); ?>
            <small id="eps_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'eps_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('eps_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'eps_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('eps_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="eps_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_eps',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_eps') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_eps',
                        'value' => 'no',
                        'checked' => (getSetting('enable_eps') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_eps-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            shurjoPay
            <div class="pull-right">
                <a href="https://shurjopay.com.bd/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Username</label>
            <?= form_input([
                'name'  => 'shurjopay_username',
                'class' => 'form-control',
                'value' => getSetting('shurjopay_username')
            ]); ?>
            <small id="shurjopay_username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Password</label>
            <?= form_input([
                'name'  => 'shurjopay_password',
                'class' => 'form-control',
                'value' => getSetting('shurjopay_password')
            ]); ?>
            <small id="shurjopay_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Prefix</label>
            <?= form_input([
                'name'  => 'shurjopay_prefix',
                'class' => 'form-control',
                'placeholder' => 'e.g. NOK',
                'value' => getSetting('shurjopay_prefix')
            ]); ?>
            <small id="shurjopay_prefix-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'shurjopay_charge',
                'class' => 'form-control',
                'value' => getSetting('shurjopay_charge')
            ]); ?>
            <small id="shurjopay_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'shurjopay_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('shurjopay_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'shurjopay_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('shurjopay_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="shurjopay_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_shurjopay',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_shurjopay') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_shurjopay',
                        'value' => 'no',
                        'checked' => (getSetting('enable_shurjopay') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_shurjopay-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading" style="overflow: auto">
        <div class="panel-title">
            PayStation
            <div class="pull-right">
                <a href="https://paystation.com.bd/" class="btn btn-warning btn-xs" target="_blank" data-toggle="tooltip" title="Visit Integration Portal">
                    <i class="fa fa-arrow-up-right-from-square" style="color: #ffffff;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Merchant Id</label>
            <?= form_input([
                'name'  => 'paystation_merchant_id',
                'class' => 'form-control',
                'value' => getSetting('paystation_merchant_id')
            ]); ?>
            <small id="paystation_merchant_id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Merchant Password</label>
            <?= form_input([
                'name'  => 'paystation_password',
                'class' => 'form-control',
                'value' => getSetting('paystation_password')
            ]); ?>
            <small id="paystation_password-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Service Charge</label>
            <?= form_input([
                'name'  => 'paystation_charge',
                'class' => 'form-control',
                'value' => getSetting('paystation_charge')
            ]); ?>
            <small id="paystation_charge-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Mode</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'paystation_sandbox_mode',
                        'value' => 'yes',
                        'checked' => (getSetting('paystation_sandbox_mode') == 'yes'),
                    ]); ?>
                    Sandbox
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'paystation_sandbox_mode',
                        'value' => 'no',
                        'checked' => (getSetting('paystation_sandbox_mode') == 'no')
                    ]); ?>
                    Live
                </label>
            </div>

            <small id="paystation_sandbox_mode-error" class="error text-danger"></small>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Gateway Status</label>

            <div class="radio">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_paystation',
                        'value' => 'yes',
                        'checked' => (getSetting('enable_paystation') == 'yes'),
                    ]); ?>
                    Active
                </label>

                <label class="radio-inline">
                    <?= form_radio([
                        'name'  => 'enable_paystation',
                        'value' => 'no',
                        'checked' => (getSetting('enable_paystation') == 'no')
                    ]); ?>
                    Inactive
                </label>
            </div>

            <small id="enable_paystation-error" class="error text-danger"></small>
        </div>

    </div>

</div>
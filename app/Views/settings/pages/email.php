<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">Password Reset Email Templates</div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Reset Request Email</label>
            <?= form_textarea([
                'name'  => 'email_password_reset_request',
                'class' => 'summernote',
                'value' => getSetting('email_password_reset_request')
            ]); ?>
            <small id="email_password_reset_request-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Reset Successful Email</label>
            <?= form_textarea([
                'name'  => 'email_password_reset_successful',
                'class' => 'summernote',
                'value' => getSetting('email_password_reset_successful')
            ]); ?>
            <small id="email_password_reset_successful-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">Support Ticket Email Templates</div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Support Ticket Answered Email</label>
            <?= form_textarea([
                'name'  => 'email_support_ticket_answerd',
                'class' => 'summernote',
                'value' => getSetting('email_support_ticket_answerd')
            ]); ?>
            <small id="email_support_ticket_answerd-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Support Ticket Update Email</label>
            <?= form_textarea([
                'name'  => 'email_support_ticket_open_closed',
                'class' => 'summernote',
                'value' => getSetting('email_support_ticket_open_closed')
            ]); ?>
            <small id="email_support_ticket_open_closed-error" class="error text-danger"></small>
        </div>

    </div>

</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">Subscription Email Templates</div>
    </div>

    <div class="panel-body">

        <div class="form-group">
            <label>Advanced Expiry Notice Email</label>
            <?= form_textarea([
                'name'  => 'email_subscription_will_expire',
                'class' => 'summernote',
                'value' => getSetting('email_subscription_will_expire')
            ]); ?>
            <small id="email_subscription_will_expire-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
            <label>Subscription Expired Email</label>
            <?= form_textarea([
                'name'  => 'email_subscription_expired',
                'class' => 'summernote',
                'value' => getSetting('email_subscription_expired')
            ]); ?>
            <small id="email_subscription_expired-error" class="error text-danger"></small>
        </div>

    </div>

</div>
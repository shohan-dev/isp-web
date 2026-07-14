<h5>CronJob Command</h5>

<div class="well well-sm" style="margin-bottom: 40px;">
    * * * * * /usr/local/bin/php /<?= ROOTPATH; ?>/spark cronjob:run >> /dev/null 2>&1 >> /dev/null 2>&1
</div>

<div class="form-group">
    <label>Send Expiry Notification Before (Ex: 3, 7, 10)</label>

    <?php
echo form_input(
    'notify_user_subscription_expire_before_days',
    getSetting('notify_user_subscription_expire_before_days'),
    'class="form-control" placeholder="Ex: 3, 7, 10"'
); ?>
    <small id="notify_user_subscription_expire_before_days-error" class="error text-danger"></small>
</div>
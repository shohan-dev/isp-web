<?= str_replace(

    array("%{{user}}%", "%{{expire_date}}%", "%{{webmaster_email}}%", "%{{app_name}}%"),

    array(
        $user,
        $expire_date,
        getSetting('webmaster_email'),
        getSetting('app_name'),
    ),

    getSetting('email_subscription_will_expire')
); ?>
<?= str_replace(

    array("%{{user}}%", "%{{reset_link}}%", "%{{webmaster_email}}%", "%{{app_name}}%"),

    array(
        $user,
        $link,
        getSetting('webmaster_email'),
        getSetting('app_name')
    ),

    getSetting('email_password_reset_request')
); ?>
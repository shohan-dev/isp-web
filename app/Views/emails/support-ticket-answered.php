<?= str_replace(

    array("%{{user}}%", "%{{subject}}%", "%{{app_name}}%"),

    array(
        $user,
        $subject,
        getSetting('app_name')
    ),

    getSetting('email_support_ticket_answerd')
); ?>
<?= str_replace(

    array("%{{user}}%", "%{{subject}}%", "%{{app_name}}%", "%{{status_text}}%"),

    array(
        $user,
        $subject,
        getSetting('app_name'),
        $status_text
    ),

    getSetting('email_support_ticket_open_closed')
); ?>
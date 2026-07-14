<?= str_replace(

    array("%{{user}}%", "%{{app_name}}%","%{{email}}%", "%{{password}}%", "%{{webmaster_email}}%"),

    array(
        $user,
        getSetting('app_name'),
        $email,
        $password,
        getSetting('webmaster_email'),
    ),

    getSetting('email_password_reset_successful')
); ?>

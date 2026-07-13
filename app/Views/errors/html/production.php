<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <!-- Standalone page (doesn't extend layout/main-layout) — never inherited
         that layout's viewport meta. This is the screen real customers hit
         when production throws, so it has to render correctly on a phone. -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title><?= lang('Errors.whoops') ?></title>

    <style>
        <?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug.css')) ?>
    </style>
</head>
<body>

    <div class="container text-center">

        <h1 class="headline"><?= lang('Errors.whoops') ?></h1>

        <p class="lead"><?= lang('Errors.weHitASnag') ?></p>

    </div>

</body>

</html>

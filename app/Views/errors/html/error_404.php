<?php
if (session()->has('original_user') && session()->get('user_role') === 'resellerAdmin') {
    header('Location: ' . route_to('route.dashboard'), true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!-- Standalone error page — does not extend layout/main-layout, so it never
         inherited that layout's viewport meta. Without it mobile browsers laid
         this out at the ~980px desktop fallback width and shrank it to fit,
         leaving 404 unreadably tiny and pinch-zoom-only on a phone. -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= lang('Errors.pageNotFound') ?></title>

    <style>
        div.logo {
            height: 200px;
            width: 155px;
            display: inline-block;
            opacity: 0.08;
            position: absolute;
            top: 2rem;
            left: 50%;
            margin-left: -73px;
        }
        body {
            height: 100%;
            background: #fafafa;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #777;
            font-weight: 300;
        }
        h1 {
            font-weight: lighter;
            letter-spacing: normal;
            font-size: 3rem;
            margin-top: 0;
            margin-bottom: 0;
            color: #222;
        }
        .wrap {
            max-width: 1024px;
            margin: 5rem auto;
            padding: 2rem;
            background: #fff;
            text-align: center;
            border: 1px solid #efefef;
            border-radius: 0.5rem;
            position: relative;
        }
        pre {
            white-space: normal;
            margin-top: 1.5rem;
        }
        code {
            background: #fafafa;
            border: 1px solid #efefef;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: block;
        }
        p {
            margin-top: 1.5rem;
        }
        .footer {
            margin-top: 2rem;
            border-top: 1px solid #efefef;
            padding: 1em 2em 0 2em;
            font-size: 85%;
            color: #999;
        }
        a:active,
        a:link,
        a:visited {
            color: #dd4814;
        }
        /* Phone (<=767px, this codebase's established ladder) — the desktop
           5rem top/bottom margin plus 2rem padding ate most of a short phone
           viewport's height above the fold; this page has no header/nav to
           anchor against like the app shell does. */
        @media (max-width: 767px) {
            .wrap {
                margin: 1.5rem auto;
                padding: 1.25rem;
            }
            h1 {
                font-size: 2.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>404</h1>

        <p>
            <?php if (ENVIRONMENT !== 'production') : ?>
                <?= nl2br(esc($message)) ?>
            <?php else : ?>
                <?= lang('Errors.sorryCannotFind') ?>
            <?php endif ?>
        </p>
    </div>
</body>
</html>

<?php

$zapiEnabled = filter_var((string) env('zapi.enabled', 'true'), FILTER_VALIDATE_BOOLEAN);
$zapiRequireAuth = filter_var((string) env('zapi.requireAuth', 'true'), FILTER_VALIDATE_BOOLEAN);
$authFilters = $zapiRequireAuth ? ['zapijwt'] : [];
$customerFilters = $zapiRequireAuth ? ['zapijwt', 'zapirole:customer'] : [];
$resellerFilters = $zapiRequireAuth ? ['zapijwt', 'zapirole:reseller'] : [];

if ($zapiEnabled) {
    $routeFiles = [
        __DIR__ . '/routes/common_routes.php',
        __DIR__ . '/routes/monitor_routes.php',
        __DIR__ . '/routes/customer_routes.php',
        __DIR__ . '/routes/reseller_routes.php',
        __DIR__ . '/routes/cron_routes.php',
        __DIR__ . '/routes/web_routes.php',
    ];

    foreach ($routeFiles as $routeFile) {
        if (is_file($routeFile)) {
            require $routeFile;
        }
    }
}

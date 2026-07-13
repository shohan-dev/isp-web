<?php

$routes->group('api', function ($routes) {
    // Public monitor endpoints (temporary for testing)
    $routes->get('monitor/traffic', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::traffic');
    $routes->get('monitor/overview', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::overview');
    $routes->get('monitor/top-endpoints', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::topEndpoints');
    $routes->get('monitor/timeline', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::timeline');
    $routes->get('monitor/recent', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::recent');
    $routes->get('monitor/snapshot', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::snapshot');
    $routes->get('monitor/flush-queue', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::flushQueue');
    $routes->get('monitor/maintain-queue', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::maintainQueue');
    $routes->get('monitor/retention-cleanup', '\Zapi\Modules\Monitor\Traffic\Controllers\TrafficMonitorController::retentionCleanup');
});

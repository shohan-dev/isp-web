<?php

// Reward-engine cron endpoints. No JWT (a server crontab / daycry job hits
// them); each action is guarded by a shared secret (reward.cronSecret) inside
// CronController. See CronController docblock for the suggested schedule.

if (!isset($routes)) {
    return;
}

$routes->group('api/cron', function ($routes) {
    $routes->get('reward-reconcile', '\Zapi\Modules\Cron\Controllers\CronController::reconcileRewards');
    $routes->get('reward-release-holds', '\Zapi\Modules\Cron\Controllers\CronController::releaseHolds');
    $routes->get('reward-expire-points', '\Zapi\Modules\Cron\Controllers\CronController::expirePoints');
    $routes->get('reward-loyalty', '\Zapi\Modules\Cron\Controllers\CronController::loyalty');
    $routes->get('reward-birthday', '\Zapi\Modules\Cron\Controllers\CronController::birthday');
});

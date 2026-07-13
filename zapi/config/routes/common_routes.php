<?php

if (!isset($authFilters) || !is_array($authFilters)) {
    $authFilters = [];
}

if (!isset($routes)) {
    return;
}

$routes->group('api', function ($routes) use ($authFilters) {
    $routes->get('docs', '\Zapi\Modules\Common\SwaggerUi\Controllers\DocsController::index');
    $routes->get('docs/', '\Zapi\Modules\Common\SwaggerUi\Controllers\DocsController::index');
    $routes->get('docs/swagger.json', '\Zapi\Modules\Common\SwaggerUi\Controllers\DocsController::swagger');
    $routes->get('docs/swagger-ui/(:any)', '\Zapi\Modules\Common\SwaggerUi\Controllers\DocsController::asset/$1');


    // Public auth endpoints
    $routes->post('common/login', '\Zapi\Modules\Common\Auth\Controllers\AuthController::validateLogin');
    $routes->post('common/check-user', '\Zapi\Modules\Common\Auth\Controllers\AuthController::checkUserExists');
    $routes->post('common/refresh', '\Zapi\Modules\Common\Auth\Controllers\AuthController::refreshToken');

    // Public referral self-registration (creates a PENDING lead) + code validation
    $routes->post('common/register', '\Zapi\Modules\Common\Registration\Controllers\RegistrationController::register');
    $routes->get('common/referral/validate/(:segment)', '\Zapi\Modules\Common\Registration\Controllers\RegistrationController::validateCode/$1');

    // Public bKash callbacks (SMS listener, IPN). Must not require JWT — native clients post multipart only.
    // this route is expetional for the sms service app only dont change the route 
    $routes->post('common/bkash/get_bkash_sendmoney', '\App\Controllers\Bkash_webhook::get_bkash_sendmoney');
    
    // Legacy paths (older mobile builds)
    $routes->post('bkash/webhook', '\Zapi\Modules\Common\BkashWebhook\Controllers\BkashWebhookController::Test');
    $routes->post('bkash/get_bkash_sendmoney', '\Zapi\Modules\Common\BkashWebhook\Controllers\BkashWebhookController::get_bkash_sendmoney');

    $routes->group('common', ['filter' => $authFilters], function ($routes) {
        $routes->get('current-user', '\Zapi\Modules\Common\Auth\Controllers\AuthController::currentUser');
        $routes->get('pppoe-expiry-check', '\Zapi\Modules\Common\Common\Controllers\CommonController::pppoeExpiryCheck');
        $routes->get('captive-portal', '\Zapi\Modules\Common\CaptivePortal\Controllers\CaptivePortalController::index');
        $routes->get('generate_204', '\Zapi\Modules\Common\CaptivePortal\Controllers\CaptivePortalController::index');
        $routes->get('hotspot-detect.html', '\Zapi\Modules\Common\CaptivePortal\Controllers\CaptivePortalController::index');
        $routes->get('connecttest.txt', '\Zapi\Modules\Common\CaptivePortal\Controllers\CaptivePortalController::index');
        $routes->get('ncsi.txt', '\Zapi\Modules\Common\CaptivePortal\Controllers\CaptivePortalController::index');
        $routes->get('exhome', '\Zapi\Modules\Common\Auth\Controllers\AuthController::exhome');

        $routes->group('movieservers', function ($routes) {
            $routes->get('/', '\Zapi\Modules\Common\Common\Controllers\CommonController::movie_index');
            $routes->get('view/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::view/$1');
            $routes->post('add', '\Zapi\Modules\Common\Common\Controllers\CommonController::add');
            $routes->post('update/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::update/$1');
            $routes->get('delete/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::delete/$1');
        });

        $routes->group('news', function ($routes) {
            $routes->get('/', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_index');
            $routes->get('view/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_view/$1');
            $routes->post('add', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_add');
            $routes->post('update/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_update/$1');
            $routes->post('news_view_update/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_view_update/$1');
            $routes->get('delete/(:num)', '\Zapi\Modules\Common\Common\Controllers\CommonController::news_delete/$1');
        });

    });
});

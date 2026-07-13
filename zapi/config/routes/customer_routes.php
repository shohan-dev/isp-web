<?php

if (!isset($customerFilters) || !is_array($customerFilters)) {
    $customerFilters = [];
}

if (!isset($routes)) {
    return;
}

$routes->group('api', function ($routes) use ($customerFilters) {
    // Portal: Customer (role = customer)
    /* Backward-compatible legacy path. It was registered with NO filter while the
       identical route inside the customer group below carries the JWT filter, so
       GET /api/users-load-traffic/{router_id}?pppoe_name=... returned live
       bandwidth data for any router/pppoe pair with no Authorization header at
       all. Keep the URL for old clients, but require the same auth as its twin. */
    $routes->get('users-load-traffic/(:num)', '\Zapi\Modules\Customer\User\Controllers\RouterTrafficController::UsersloadTraffic_api/$1', ['filter' => $customerFilters]);


    $routes->group('customer', ['filter' => $customerFilters], function ($routes) {
            // Section: Router Traffic (customer path used by apps)
            $routes->get('users-load-traffic/(:num)', '\Zapi\Modules\Customer\User\Controllers\RouterTrafficController::UsersloadTraffic_api/$1');

            // Section: User
            $routes->get('users/(:num)', '\Zapi\Modules\Customer\User\Controllers\UserController::index/$1');
            $routes->get('payment-fetch', '\Zapi\Modules\Customer\User\Controllers\UserController::fetch');
            $routes->get('packages', '\Zapi\Modules\Customer\User\Controllers\UserController::packages');
            $routes->get('ping-user', '\Zapi\Modules\Customer\User\Controllers\UserController::pingUserApi');

            // Section: Subscription
            $routes->get('subscription/index', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::index');
            $routes->get('subscription/renew', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::renew');
            $routes->post('subscription/renew', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::renew');
            $routes->post('subscription/activate-package', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::activatePackage');
            $routes->get('subscription/quota', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::quota');
            $routes->post('subscription/update', '\Zapi\Modules\Customer\Subscription\Controllers\SubscriptionController::update');

            // Section: Support
            $routes->get('support/fetch', '\Zapi\Modules\Customer\Support\Controllers\SupportController::fetch');
            $routes->get('support/contact', '\Zapi\Modules\Customer\Support\Controllers\SupportController::contact');
            $routes->get('support/details', '\Zapi\Modules\Customer\Support\Controllers\SupportController::details');
            $routes->post('support/send-message', '\Zapi\Modules\Customer\Support\Controllers\SupportController::sendMessage');
            $routes->post('support/create-ticket', '\Zapi\Modules\Customer\Support\Controllers\SupportController::createTicket');

            // Section: Profile
            $routes->post('profile/update', '\Zapi\Modules\Customer\Profile\Controllers\ProfileController::update');

            // Section: Permission
            $routes->get('permission', '\Zapi\Modules\Customer\Permission\Controllers\PermissionController::index');

            // Section: Payment
            $routes->get('make-payment/(:num)', '\Zapi\Modules\Customer\Payment\Controllers\PaymentController::makePayment/$1');
            $routes->get('make-reseller-payment/(:num)', '\Zapi\Modules\Customer\Payment\Controllers\PaymentController::makeResellerPayment/$1');
            $routes->get('json/make-payment/(:num)', '\Zapi\Modules\Customer\Payment\Controllers\PaymentController::makePaymentJson/$1');
            $routes->get('json/make-reseller-payment/(:num)', '\Zapi\Modules\Customer\Payment\Controllers\PaymentController::makeResellerPaymentJson/$1');
            $routes->get('invoice-print', '\Zapi\Modules\Common\Common\Controllers\CommonController::invoicePrint');
            $routes->get('json/invoice-print', '\Zapi\Modules\Common\Common\Controllers\CommonController::invoicePrintJson');
            $routes->get('usage', '\Zapi\Modules\Common\Common\Controllers\CommonController::get_user_data_usage');
            $routes->get('routers/load-traffic/(:num)', '\Zapi\Modules\Customer\User\Controllers\RouterTrafficController::loadTraffic/$1');

            // Section: Router Auto-Fix (safe self-service — per-subscriber session reset, never /system/reboot)
            $routes->post('autofix/reboot', '\Zapi\Modules\Customer\AutoFix\Controllers\AutoFixController::rebootRouter');
            $routes->post('autofix/reconnect', '\Zapi\Modules\Customer\AutoFix\Controllers\AutoFixController::reconnectPPPoE');
            $routes->post('autofix/flush-dns', '\Zapi\Modules\Customer\AutoFix\Controllers\AutoFixController::flushDNS');
            $routes->post('autofix/reset-session', '\Zapi\Modules\Customer\AutoFix\Controllers\AutoFixController::resetSession');
            $routes->post('autofix/quick-fix', '\Zapi\Modules\Customer\AutoFix\Controllers\AutoFixController::quickFix');

            // Section: Connected Devices (ISP-side connection view + WebView guidance for home LAN)
            $routes->get('device/connected', '\Zapi\Modules\Customer\Device\Controllers\DeviceController::getDevices');

            // Section: Unified Router Control (3-action app: reboot / change-wifi / devices)
            // Dispatches by capability_type (mikrotik_pppoe / tr069 / vendor_api / web_only).
            $routes->get('router-control/targets', '\Zapi\Modules\Customer\RouterControl\Controllers\RouterControlController::targets');
            $routes->post('router-control/reboot', '\Zapi\Modules\Customer\RouterControl\Controllers\RouterControlController::reboot');
            $routes->post('router-control/wifi', '\Zapi\Modules\Customer\RouterControl\Controllers\RouterControlController::changeWifi');
            $routes->get('router-control/devices', '\Zapi\Modules\Customer\RouterControl\Controllers\RouterControlController::devices');
            $routes->post('router-control/onboard-tr069', '\Zapi\Modules\Customer\RouterControl\Controllers\RouterControlController::onboardTr069');

            // Section: Referral (customer is the referrer)
            $routes->get('referral/overview', '\Zapi\Modules\Customer\Referral\Controllers\ReferralController::overview');
            $routes->get('referral/history', '\Zapi\Modules\Customer\Referral\Controllers\ReferralController::history');

            // Section: Reward Wallet
            $routes->get('reward/wallet', '\Zapi\Modules\Customer\Reward\Controllers\RewardController::wallet');
            $routes->get('reward/transactions', '\Zapi\Modules\Customer\Reward\Controllers\RewardController::transactions');
            $routes->get('reward/redeem-preview', '\Zapi\Modules\Customer\Reward\Controllers\RewardController::redeemPreview');

            // Section: Notifications (in-app inbox)
            $routes->get('notifications', '\Zapi\Modules\Customer\Notification\Controllers\NotificationController::getNotifications');
            $routes->post('notifications/read', '\Zapi\Modules\Customer\Notification\Controllers\NotificationController::markAsRead');
        });
});

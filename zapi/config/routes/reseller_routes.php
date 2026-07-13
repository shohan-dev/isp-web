<?php

if (!isset($resellerFilters) || !is_array($resellerFilters)) {
    $resellerFilters = [];
}

if (!isset($routes)) {
    return;
}

/**
 * Per-endpoint permission gating.
 *
 * CI4 (4.3.7) merges group options with route options via
 * array_merge($groupOptions, $routeOptions) — so a per-route 'filter' key
 * OVERRIDES the group 'filter' rather than appending to it. Therefore each
 * gated route must carry the FULL stack ($resellerFilters + zapipermission),
 * not just the permission filter, or it would silently drop zapijwt/zapirole.
 *
 * $perm('menu,sub') returns the options array for a route: the reseller role
 * gate plus the portal permission pair. When zapi auth is disabled
 * ($resellerFilters is empty) the permission filter is omitted too, keeping the
 * $zapiRequireAuth escape hatch consistent (auth off => permission off).
 *
 * Routes with no $perm(...) 3rd arg intentionally inherit only the group's
 * role gate (self-service / read landing endpoints with no portal permission
 * analog): dashboard, make-reseller-payment, permission (self), rewards/*.
 */
$permAuthOn = !empty($resellerFilters);
$perm = static function (string $menuSub) use ($resellerFilters, $permAuthOn): array {
    if (!$permAuthOn) {
        return ['filter' => $resellerFilters];
    }

    return ['filter' => array_merge($resellerFilters, ['zapipermission:' . $menuSub])];
};

$routes->group('api', function ($routes) use ($resellerFilters, $perm) {
    // Portal: Reseller (role = reseller)
    $routes->group('reseller', ['filter' => $resellerFilters], function ($routes) use ($perm) {
        // Section: Router Traffic
        $routes->get('users-load-traffic/(:num)', '\Zapi\Modules\Customer\User\Controllers\RouterTrafficController::UsersloadTraffic_api/$1', $perm('routers,read'));

        // Section: Dashboard (no portal permissioncheck — role-gate only)
        $routes->get('dashboard/(:num)', '\Zapi\Modules\Reseller\Dashboard\Controllers\DashboardController::dashboard/$1');

        // Section: Area/Subarea
        $routes->get('areas/(:num?)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::index/$1', $perm('area,read'));
        $routes->get('areas/(:num)/sub/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::subindex/$1/$2', $perm('area,read'));
        $routes->post('areas/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::create/$1', $perm('area,create'));
        $routes->post('subareas', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::subcreate', $perm('area,create'));
        $routes->get('areas/edit/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::edit/$1', $perm('area,update'));
        $routes->get('subareas/edit/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::editsub/$1', $perm('area,update'));
        $routes->put('areas/update/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::update/$1', $perm('area,update'));
        $routes->put('subareas/update/(:num)', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::updatesub/$1', $perm('area,update'));
        $routes->delete('areas/(:num)/delete', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::delete/$1', $perm('area,delete'));
        $routes->delete('areas/delete', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::delete', $perm('area,delete'));
        $routes->delete('subareas/delete', '\Zapi\Modules\Reseller\Area\Controllers\ServiceAreaController::deletesub', $perm('area,delete'));

        // Section: Package
        $routes->get('packages/(:num?)', '\Zapi\Modules\Reseller\Package\Controllers\PackageController::fetch/$1', $perm('packages,read'));
        $routes->delete('packages/(:num)/(:num)', '\Zapi\Modules\Reseller\Package\Controllers\PackageController::delete/$1/$2', $perm('packages,delete'));

        // Section: Customer
        $routes->post('customers/create/(:num?)', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::create/$1', $perm('customer,create'));
        $routes->post('customers/(:num)/sync-pppoe', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::syncPppoeIds/$1', $perm('customer,update'));
        $routes->post('customers/(:num)/import-excel', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::importExcel/$1', $perm('customer,create'));
        $routes->post('customers/(:num)/bulk-recharge', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::bulkRecharge/$1', $perm('customer_payment,create'));
        $routes->post('customers/(:num)/transfer', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::transfer/$1', $perm('customer,delete'));
        $routes->post('customers/(:num)/bulk-delete', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::bulkDelete/$1', $perm('customer,delete'));
        $routes->delete('customers/(:num)/bulk-delete', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::bulkDelete/$1', $perm('customer,delete'));
        $routes->post('customers/(:num)/pppoe-status', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::pppoeStatus/$1', $perm('customer,update_conn'));
        $routes->post('customers/(:num)/update-pop', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::updatePop/$1', $perm('customer,update'));
        $routes->post('customers/(:num)/bulk-update-pop', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::bulkUpdatePop/$1', $perm('customer,update'));
        $routes->post('customers/(:num)/update-router', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::updateRouter/$1', $perm('customer,update'));
        $routes->post('customers/(:num)/bulk-update-router', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::bulkUpdateRouter/$1', $perm('customer,update'));
        $routes->get('customers/(:num)/export-excel', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::exportExcel/$1', $perm('customer,read'));
        $routes->get('customers/(:num)/index', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::index/$1', $perm('customer,read'));
        $routes->get('customers/(:num)/(:num)/audit-logs', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::auditLogs/$1/$2', $perm('customer,read'));
        $routes->get('customers/(:num)/(:num)/mac-status', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::macStatus/$1/$2', $perm('customer,read'));
        $routes->post('customers/(:num)/(:num)/mac-bind', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::macBind/$1/$2', $perm('customer,update'));
        $routes->post('customers/(:num)/(:num)/mac-unbind', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::macUnbind/$1/$2', $perm('customer,update'));
        $routes->get('customers/(:num?)', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::fetch/$1', $perm('customer,read'));
        $routes->get('customers/(:num?)/(:num?)', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::details/$1/$2', $perm('customer,read'));
        $routes->post('customers/(:num?)/(:num?)', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::update/$1/$2', $perm('customer,update'));
        $routes->delete('customers/(:num?)/(:num?)', '\Zapi\Modules\Reseller\Customer\Controllers\CustomerController::delete/$1/$2', $perm('customer,delete'));

        // Section: Customer Payment
        $routes->get('customer-payments/(:num)', '\Zapi\Modules\Reseller\CustomerPayment\Controllers\CustomerPaymentController::fetch/$1', $perm('customer_payment,read'));
        $routes->get('customer-payments/(:num)/user/(:num)', '\Zapi\Modules\Reseller\CustomerPayment\Controllers\CustomerPaymentController::userPayments/$1/$2', $perm('customer_payment,read'));
        $routes->post('customer-payments/(:num)', '\Zapi\Modules\Reseller\CustomerPayment\Controllers\CustomerPaymentController::create/$1', $perm('customer_payment,create'));
        $routes->put('customer-payments/(:num)/(:num)', '\Zapi\Modules\Reseller\CustomerPayment\Controllers\CustomerPaymentController::update/$1/$2', $perm('customer_payment,update'));
        $routes->delete('customer-payments/(:num)', '\Zapi\Modules\Reseller\CustomerPayment\Controllers\CustomerPaymentController::delete/$1', $perm('customer_payment,delete'));

        // Section: Employee
        $routes->get('employees/(:num)', '\Zapi\Modules\Reseller\Employee\Controllers\EmployeeController::fetch/$1', $perm('employee,read'));
        $routes->get('employees/(:num)/(:num)', '\Zapi\Modules\Reseller\Employee\Controllers\EmployeeController::details/$1/$2', $perm('employee,read'));
        $routes->post('employees/(:num)', '\Zapi\Modules\Reseller\Employee\Controllers\EmployeeController::create/$1', $perm('employee,create'));
        $routes->put('employees/(:num)/(:num)', '\Zapi\Modules\Reseller\Employee\Controllers\EmployeeController::update/$1/$2', $perm('employee,update'));
        $routes->delete('employees/(:num)', '\Zapi\Modules\Reseller\Employee\Controllers\EmployeeController::delete/$1', $perm('employee,delete'));

        // Section: Employee Payment
        $routes->get('employee-payments/(:num)', '\Zapi\Modules\Reseller\EmployeePayment\Controllers\EmployeePaymentController::fetch/$1', $perm('employee_payment,read'));
        $routes->post('employee-payments/(:num)', '\Zapi\Modules\Reseller\EmployeePayment\Controllers\EmployeePaymentController::create/$1', $perm('employee_payment,create'));
        $routes->put('employee-payments/(:num)/(:num)', '\Zapi\Modules\Reseller\EmployeePayment\Controllers\EmployeePaymentController::update/$1/$2', $perm('employee_payment,update'));
        $routes->delete('employee-payments/(:num)', '\Zapi\Modules\Reseller\EmployeePayment\Controllers\EmployeePaymentController::delete/$1', $perm('employee_payment,delete'));

        // Section: Support Ticket
        $routes->get('support-tickets/(:num)', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::fetch/$1', $perm('support_ticket,read'));
        $routes->get('support-tickets/(:num)/(:num)', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::details/$1/$2', $perm('support_ticket,read'));
        $routes->post('support-tickets/(:num)', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::create/$1', $perm('support_ticket,create'));
        $routes->post('support-tickets/(:num)/(:num)/message', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::sendMessage/$1/$2', $perm('support_ticket,send_msg'));
        $routes->put('support-tickets/(:num)/(:num)', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::update/$1/$2', $perm('support_ticket,update'));
        $routes->delete('support-tickets/(:num)', '\Zapi\Modules\Reseller\SupportTicket\Controllers\SupportTicketController::delete/$1', $perm('support_ticket,delete'));

        // Section: Transaction (portal ResellerFunding permissioncheck:customer_payment,*)
        $routes->get('transactions/(:num)', '\Zapi\Modules\Reseller\Transaction\Controllers\TransactionController::fetch/$1', $perm('customer_payment,read'));
        $routes->delete('transactions/(:num)', '\Zapi\Modules\Reseller\Transaction\Controllers\TransactionController::delete/$1', $perm('customer_payment,delete'));

        // Section: Funding (portal ResellerFunding permissioncheck:customer_payment,*) — MONEY
        $routes->get('funding/(:num)', '\Zapi\Modules\Reseller\Funding\Controllers\FundingController::fetch/$1', $perm('customer_payment,read'));
        $routes->post('funding/(:num)', '\Zapi\Modules\Reseller\Funding\Controllers\FundingController::create/$1', $perm('customer_payment,create'));
        $routes->delete('funding/(:num)', '\Zapi\Modules\Reseller\Funding\Controllers\FundingController::delete/$1', $perm('customer_payment,delete'));

        // Section: Subscription — MONEY (renew)
        $routes->get('subscription/(:num)/(:num)', '\Zapi\Modules\Reseller\Subscription\Controllers\SubscriptionController::info/$1/$2', $perm('customer,update_subscription'));
        $routes->post('subscription/(:num)/renew', '\Zapi\Modules\Reseller\Subscription\Controllers\SubscriptionController::renew/$1', $perm('customer,update_subscription'));
        $routes->post('subscription/(:num)/bulk-renew', '\Zapi\Modules\Reseller\Subscription\Controllers\SubscriptionController::bulkRenew/$1', $perm('customer,update_subscription'));

        // Section: SMS — MONEY (send)
        $routes->get('sms/(:num)/recipients', '\Zapi\Modules\Reseller\Sms\Controllers\SmsController::recipients/$1', $perm('sms_message,create'));
        $routes->post('sms/(:num)/send', '\Zapi\Modules\Reseller\Sms\Controllers\SmsController::send/$1', $perm('sms_message,create'));
        $routes->get('sms/(:num)', '\Zapi\Modules\Reseller\Sms\Controllers\SmsController::fetch/$1', $perm('sms_message,read'));
        $routes->delete('sms/(:num)', '\Zapi\Modules\Reseller\Sms\Controllers\SmsController::delete/$1', $perm('sms_message,delete'));

        // Section: Voice SMS — MONEY (send). No portal analog; closest sms_message,*
        $routes->get('voice-sms/(:num)/recipients', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::recipients/$1', $perm('sms_message,create'));
        $routes->post('voice-sms/(:num)/send', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::send/$1', $perm('sms_message,create'));
        $routes->get('voice-sms/(:num)/templates', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::templates/$1', $perm('sms_message,read'));
        $routes->post('voice-sms/(:num)/templates', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::createTemplate/$1', $perm('sms_message,create'));
        $routes->put('voice-sms/(:num)/templates/(:num)', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::updateTemplate/$1/$2', $perm('sms_message,create'));
        $routes->delete('voice-sms/(:num)/templates/(:num)', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::deleteTemplate/$1/$2', $perm('sms_message,delete'));
        $routes->get('voice-sms/(:num)/settings', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::settings/$1', $perm('sms_message,read'));
        $routes->put('voice-sms/(:num)/settings', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::updateSettings/$1', $perm('sms_message,create'));
        $routes->put('voice-sms/(:num)/event-config', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::updateEventConfig/$1', $perm('sms_message,create'));
        $routes->get('voice-sms/(:num)/gateway-voices', '\Zapi\Modules\Reseller\VoiceSms\Controllers\VoiceSmsController::gatewayVoices/$1', $perm('sms_message,read'));

        // Section: Payment (reseller's own platform payment history — read).
        $routes->get('payments/(:num)', '\Zapi\Modules\Reseller\Payment\Controllers\PaymentController::fetch/$1', $perm('customer_payment,read'));
        // Self-serve gateway HTML (reseller pays own platform bill). No portal
        // permissioncheck analog — role-gate only.
        $routes->get('make-reseller-payment/(:num)', '\Zapi\Modules\Customer\Payment\Controllers\PaymentController::makeResellerPayment/$1');

        // Section: Profile
        $routes->get('profile/(:num)', '\Zapi\Modules\Reseller\Profile\Controllers\ProfileController::fetch/$1', $perm('profile_update,read'));
        $routes->put('profile/(:num)', '\Zapi\Modules\Reseller\Profile\Controllers\ProfileController::update/$1', $perm('profile_update,update'));
        $routes->put('profile/(:num)/organization', '\Zapi\Modules\Reseller\Profile\Controllers\ProfileController::updateOrganization/$1', $perm('profile_update,update'));
        $routes->post('profile/(:num)/change-password', '\Zapi\Modules\Reseller\Profile\Controllers\ProfileController::changePassword/$1', $perm('password_change,update'));
        // Self permission set — every authenticated user needs it to render gates.
        $routes->get('permission', '\Zapi\Modules\Customer\Permission\Controllers\PermissionController::index');

        // Section: Router
        $routes->get('routers/(:num)', '\Zapi\Modules\Reseller\Router\Controllers\RouterController::list/$1', $perm('routers,read'));
        $routes->get('router-users/(:num)/(:num)', '\Zapi\Modules\Reseller\Router\Controllers\RouterController::fetch/$1/$2', $perm('routers,read'));

        // Section: Referral verification (reseller/admin approves referred customers)
        $routes->get('referrals/(:num)', '\Zapi\Modules\Reseller\Referral\Controllers\ReferralController::list/$1', $perm('customer,read'));
        $routes->get('referrals/(:num)/(:num)', '\Zapi\Modules\Reseller\Referral\Controllers\ReferralController::details/$1/$2', $perm('customer,read'));
        $routes->post('referrals/(:num)/(:num)/approve', '\Zapi\Modules\Reseller\Referral\Controllers\ReferralController::approve/$1/$2', $perm('customer,create'));
        $routes->post('referrals/(:num)/(:num)/reject', '\Zapi\Modules\Reseller\Referral\Controllers\ReferralController::reject/$1/$2', $perm('customer,update'));

        // Section: Reward (reports, wallets, config). Global config is sAdmin/admin
        // only (enforced in-service). No portal permission menu exists for rewards
        // — role-gate + in-service checks only (do NOT invent a permissions menu
        // key, which would deny-all legitimately entitled users).
        $routes->get('rewards/global-config', '\Zapi\Modules\Reseller\Reward\Controllers\RewardConfigController::getGlobal');
        $routes->put('rewards/global-config', '\Zapi\Modules\Reseller\Reward\Controllers\RewardConfigController::updateGlobal');
        $routes->get('rewards/(:num)/report', '\Zapi\Modules\Reseller\Reward\Controllers\RewardController::report/$1');
        $routes->get('rewards/(:num)/wallets', '\Zapi\Modules\Reseller\Reward\Controllers\RewardController::wallets/$1');
        $routes->get('rewards/(:num)/config', '\Zapi\Modules\Reseller\Reward\Controllers\RewardConfigController::get/$1');
        $routes->put('rewards/(:num)/config', '\Zapi\Modules\Reseller\Reward\Controllers\RewardConfigController::update/$1');
    });
});

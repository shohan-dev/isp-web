<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Dashboard');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);

// 404 route
$routes->set404Override();

// Route Definitions
// --------------------------------------------------------------------
$routes->get('api/dashboard/bandwidth-usage/(:any)', 'Dashboard::bandwidthUsage/$1');
$routes->get('check-admin-data', 'CheckAdminData::index');
$routes->post('api/chat', '\App\Controllers\AiChatController::aiChat');


// Sitemap
$routes->get('sitemap.xml', 'Sitemap::index');

// Phase 7: load-balancer / uptime health probe (public, no auth).
$routes->get('healthz', 'Health::index');

// Phase O (O1/O3): Prometheus-compatible metrics scrape endpoint.
// Protected by IP allowlist (METRICS_ALLOWED_CIDR env var, default 127.0.0.1).
// Deny at the nginx level too: `location /metrics { deny all; allow 127.0.0.1; }`
$routes->get('metrics', 'Metrics::index');

if (is_file(ROOTPATH . 'zapi/config/api_routes.php')) {
    require_once ROOTPATH . 'zapi/config/api_routes.php';
}

// Change line 34 to this (using full namespace)
// Removed from here and moved to bottom to ensure priority over Zapi


$routes->group('auth', ['filter' => 'logincheck'], function ($routes) {

    $routes->get('registration', 'RegistrationController::index', [
        'as' => 'route.auth.registration'
    ]);
    $routes->get('home', 'AuthController::home', [
        'as' => 'route.auth.home'
    ]);
    $routes->post('storesubmit', 'AuthController::store', [
        'as' => 'route.auth.store'
    ]);

    $routes->get('footer', 'AuthController::footer', [
        'as' => 'route.auth.footer'
    ]);
    $routes->get('pricing', 'AuthController::pricing', [
        'as' => 'route.auth.pricing'
    ]);
    $routes->post('submit', 'RegistrationController::submit', [
        'as' => 'route.auth.submit'
    ]);

    $routes->group(
        'login',
        function ($routes) {

            $routes->get('', 'AuthController::index', [
                'as' => 'route.auth.login'
            ]);

            $routes->post('validate', 'AuthController::validateLogin', [
                'as' => 'route.auth.login.validate'
            ]);
        }
    );

    $routes->group(
        'forgot-password',
        function ($routes) {

            $routes->get('', 'AuthController::forgot', [
                'as' => 'route.auth.forgot'
            ]);

            $routes->post('validate', 'AuthController::validateForgot', [
                'as' => 'route.auth.forgot.validate'
            ]);

            $routes->get('reset', 'AuthController::resetPassword', [
                'as' => 'route.auth.forgot.reset'
            ]);
        }
    );
});

$routes->group('audit', ['filter' => 'authcheck'], function ($routes) {
    // Audit log listing page
    $routes->get('/', 'Audit::index', ['as' => 'route.audit.index']);
});

// Sidebar quick-access pins (any logged-in role may pin their own items).
$routes->post('sidebar/pins/toggle', 'SidebarPin::toggle', [
    'as'     => 'route.sidebar.pins.toggle',
    'filter' => 'authcheck',
]);

$routes->get('news', 'NewsController::index', ['as' => 'route.news', 'filter' => 'authcheck']);
$routes->get('news/manage', 'NewsManageController::index', ['as' => 'route.news.manage', 'filter' => 'authcheck']);
$routes->post('news/save', 'NewsManageController::save', ['as' => 'route.news.save', 'filter' => 'authcheck']);
$routes->get('news/delete/(:num)', 'NewsManageController::delete/$1', ['as' => 'route.news.delete', 'filter' => 'authcheck']);


/**
 * Platform super-admin (role=admin): multi-tenant portal management.
 */
$routes->group('tenants', ['filter' => ['authcheck', 'role:super_admin']], function ($routes) {
    $routes->get('', 'Tenants::index', ['as' => 'route.tenants']);
    $routes->get('create', 'Tenants::create', ['as' => 'route.tenants.create']);
    $routes->post('store', 'Tenants::store', ['as' => 'route.tenants.store']);
    $routes->get('details/(:num)', 'Tenants::details/$1', ['as' => 'route.tenants.details']);
    $routes->get('edit/(:num)', 'Tenants::edit/$1', ['as' => 'route.tenants.edit']);
    $routes->post('update/(:num)', 'Tenants::update/$1', ['as' => 'route.tenants.update']);
    $routes->post('status/(:num)', 'Tenants::setStatus/$1', ['as' => 'route.tenants.status']);
    $routes->delete('delete', 'Tenants::destroy', ['as' => 'route.tenants.delete']);
});

$routes->group('admins', function ($routes) {

    $routes->get('', 'Admin::index', [
        'as' => 'route.Admin',
        'filter' => 'role:super_admin',
    ]);
    /* Renamed from 'reseller.add' / 'route.reseller.submit': the reseller group
       below defines those same two names, and CI4's reverse router keeps the
       LAST definition, so route_to('reseller.add') always resolved to
       /reseller/add and these two names silently pointed nowhere. Distinct
       names now; no URL that resolves today changes. */
    $routes->get('add', 'Reseller::add', [
        'as' => 'admins.reseller_add',
    ]);

    $routes->get('contactfetch', 'Admin::contactfetch', [
        'as' => 'route.contact.fetch',
    ]);
    $routes->post('contactfetchall', 'Admin::contactfetchall', [
        'as' => 'route.contact.fetchall',
    ]);
    $routes->delete('contactdelete', 'Admin::contactdelete', [
        'as' => 'route.contactdelete',
        'filter' => 'role:super_admin',
    ]);



    $routes->post('submit', 'RegistrationController::submit', [
        'as' => 'admins.reseller_submit'
    ]);
    $routes->post('Resellersubmit', 'Reseller::submit', [
        'as' => 'route.Reseller.submit'
    ]);
    $routes->post('admin', 'Admin::fetch', [
        'as' => 'route.Admin.fetch',
        'filter' => 'role:super_admin',
    ]);

    $routes->delete('delete', 'Admin::delete', [
        'as' => 'route.Admin.delete',
        'filter' => 'role:super_admin',
    ]);
    $routes->get('packages', 'Admin::packages', [
        'as' => 'Admin.packages',
        // 'filter' => 'permissioncheck:customer,read',
    ]);
    $routes->post('maintenance-toggle', 'Sadmin::toggleMaintenance', [
        'as' => 'route.maintenance.toggle',
        'filter' => 'authcheck',
    ]);
    $routes->get('revenue', 'Admin::revenue', [
        'as' => 'route.Admin.revenue',
        'filter' => 'authcheck',
    ]);
    $routes->post('revenue/fetch', 'Admin::revenueFetch', [
        'as' => 'route.Admin.revenue.fetch',
        'filter' => 'authcheck',
    ]);
    $routes->get('revenue/fetch', 'Admin::revenueFetch', [
        'as' => 'route.Admin.revenue.fetch.get',
        'filter' => 'authcheck',
    ]);
    $routes->post('revenue/package-stats', 'Admin::revenuePackageStats', [
        'as' => 'route.Admin.revenue.packageStats',
        'filter' => 'authcheck',
    ]);
    /* These five carried no filter at all, while their siblings below use
       role:super_admin. SecondAdmin/package.php only *renders* the add/edit/
       delete controls behind `user_role === 'super_admin'`, and none of the
       Admin:: methods re-check the role, so the guard was client-side only:
       a tenant admin could POST /admins/deletePackage/{id} directly and delete
       a platform subscription package every tenant depends on.
       activatePackage is different — it is self-service (it acts on
       session('user_id') to switch the caller's own plan) and is used by the
       'admin' and 'user' roles, so it only needs authcheck. */
    $routes->post('save-package', 'Admin::savePackage', [
        'as' => 'Admin.savePackage',
        'filter' => 'role:super_admin',
    ]);
    $routes->post('activatePackage/(:num)', 'Admin::activatePackage/$1', [
        'as' => 'Admin.activatePackage',
        'filter' => 'authcheck',
    ]);

    $routes->get('getPackage/(:num)', 'Admin::getPackage/$1', [
        'as' => 'Admin.getPackage',
        'filter' => 'role:super_admin',
    ]);
    $routes->post('updatePackage/(:num)', 'Admin::updatePackage/$1', [
        'as' => 'Admin.updatePackage',
        'filter' => 'role:super_admin',
    ]);
    $routes->delete('deletePackage/(:num)', 'Admin::deletePackage/$1', [
        'as' => 'Admin.deletePackage',
        'filter' => 'role:super_admin',
    ]);


    $routes->get('details/(:num)', 'Admin::details/$1', [
        'as' => 'route.Admin.details',
        'filter' => 'role:super_admin',
    ]);

    $routes->get('(:num)', 'Admin::subscription/$1', [
        'as' => 'route.Admin.subscription',
        'filter' => 'authcheck',
    ]);

    $routes->get('edit/(:num)', 'Admin::edit/$1', [
        'as' => 'route.Admin.edit',
        'filter' => 'role:super_admin',
    ]);
    $routes->get('subscription/(:num)', 'Admin::adminsubscription/$1', [
        'as' => 'route.Admin.adminsubscription',
        'filter' => 'role:super_admin',
    ]);

    $routes->post('update/(:num)', 'Admin::updateSubscription/$1', [
        'as' => 'route.Admin.update_subscription',
        // 'filter' => 'permissioncheck:customer,update_subscription',
    ]);

    // Platform admin: tenant wallet management + billing-mode switching
    $routes->post('wallet-adjust/(:num)', 'Admin::walletAdjust/$1', [
        'as' => 'route.Admin.wallet_adjust',
    ]);
    $routes->post('billing-mode/(:num)', 'Admin::switchBillingMode/$1', [
        'as' => 'route.Admin.billing_mode',
    ]);

    $routes->post('edit/update/(:num)', 'Admin::update/$1', [
        'as' => 'route.Admin.update',
        'filter' => 'permissioncheck:Admin,update',
    ]);
});

/**
 * Homepage redirect
 */
// $routes->addRedirect('/', route_to('route.auth.login'));
$routes->get('/', 'AuthController::home', ['as' => 'route.home', 'filter' => 'logincheck']);


$routes->get('network_diagram', 'Sadmin::diagram', [
    'as' => 'network.diagram',
    'filter' => 'permissioncheck:network,read',
]);
$routes->get('network_map', 'Sadmin::map', [
    'as' => 'network.map',
    'filter' => 'permissioncheck:network,read',
]);

$routes->get('network', 'Sadmin::index', [
    'as' => 'network.index',
    'filter' => 'permissioncheck:network,read',
]);
$routes->post('network_addNode', 'Sadmin::addNode', [
    'as' => 'network.addNode',
    'filter' => 'permissioncheck:network,create',

]);

$routes->post('network_editNode', 'Sadmin::editNode', [
    'as' => 'network.editNode',
    'filter' => 'permissioncheck:network,update',

]);
$routes->post('network_deleteNode', 'Sadmin::deleteNode', [
    'as' => 'network.deleteNode',
    'filter' => 'permissioncheck:network,delete',

]);



/* Same exposure as the groups below: these top-level bandwidth/inventory routes
   sit outside the global authcheck wrapper, so POST /provider_delete,
   /item_delete, /purchess_delete, /inventory_item_delete etc. all mutated
   catalog data with no login. Empty group prefix => every path is unchanged,
   only the filter is added. */
$routes->group('', ['filter' => 'authcheck'], function ($routes) {

//Bandwidth routes
$routes->get('bandwidth', 'BandwidthController::index', ['as' => 'bandwidth.index']);
$routes->get('category', 'BandwidthController::category_index', ['as' => 'bandwidth.category_index']);
$routes->get('item_index', 'BandwidthController::item_index', ['as' => 'bandwidth.item_index']);
$routes->get('purchess', 'BandwidthController::purchess', ['as' => 'bandwidth.purchess']);


// In your routes file
$routes->put('item_category', 'BandwidthController::catagory_update', ['as' => 'bandwidth.catagory_update']);
$routes->post('item-category_delete', 'BandwidthController::item_category_delete', ['as' => 'item_category.delete']);


$routes->get('getsubcategories', 'BandwidthController::getSubcategories', ['as' => 'bandwidth.getSubcategories']);
$routes->post('item_store', 'BandwidthController::item_store', ['as' => 'bandwidth.item_store']);
$routes->post('item_update', 'BandwidthController::item_update', ['as' => 'bandwidth.item_update']);
$routes->post('item_delete', 'BandwidthController::item_delete', ['as' => 'bandwidth.item_delete']);


$routes->post('item_category_store', 'BandwidthController::item_category_store', ['as' => 'item_category.store']);

$routes->get('testcode', 'TestCode::index');
$routes->get('provider', 'BandwidthController::provider_index', ['as' => 'bandwidth.provider_index']);
$routes->post('provider_store', 'BandwidthController::save', ['as' => 'bandwidth.provider_store']);
$routes->post('provider_delete', 'BandwidthController::provider_delete', ['as' => 'bandwidth.provider_delete']);


$routes->post('purchess_save', 'BandwidthController::purchess_save', ['as' => 'bandwidth.purchess_save']);
$routes->post('purchess_fetch', 'BandwidthController::purchess_fetch', [
    'as' => 'route.purchess.fetch',
    // 'filter' => 'permissioncheck:customer_payment,read',
]);
$routes->post('purchess_delete', 'BandwidthController::purchess_delete', ['as' => 'bandwidth.purchess_delete']);


//inventory

// $routes->get('inventory', 'BandwidthController::index', ['as' => 'bandwidth.index']);
$routes->get('inventory_category', 'InventoryController::category_index', ['as' => 'inventory.category_index']);
$routes->get('inventory_item_index', 'InventoryController::item_index', ['as' => 'inventory.item_index']);
$routes->get('inventory_purchess', 'InventoryController::purchess_stock', ['as' => 'inventory.purchess_stock']);

$routes->put('inventory_item_category', 'InventoryController::catagory_update', ['as' => 'inventory.catagory_update']);
$routes->post('inventory_item-category_delete', 'InventoryController::item_category_delete', ['as' => 'inventory_item_category.delete']);


$routes->get('inventory_getsubcategories', 'InventoryController::getSubcategories', ['as' => 'inventory.getSubcategories']);
$routes->get('inventory_getSubcategoriesStock', 'InventoryController::getSubcategoriesStock', ['as' => 'inventory.getSubcategoriesStock']);
$routes->post('inventory_item_store', 'InventoryController::item_store', ['as' => 'inventory.item_store']);
$routes->post('inventory_item_update', 'InventoryController::item_update', ['as' => 'inventory.item_update']);
$routes->post('inventory_item_delete', 'InventoryController::item_delete', ['as' => 'inventory.item_delete']);


$routes->post('inventory_item_category_store', 'InventoryController::item_category_store', ['as' => 'inventory_item_category.store']);

}); // end authcheck wrapper for top-level bandwidth/inventory routes

// inventory unit

/* These feature groups were registered BEFORE the app's global
   $routes->group('', ['filter' => 'authcheck'], ...) wrapper further down, and
   Config\Filters.php adds no path rule for them, so every route inside them was
   reachable with no login at all — including reseller impersonation, reseller
   password reset, OLT create/delete, and expense/income approve+delete. Each
   group is admin-only (the public surfaces are auth/*, subscription/*,
   payment/gateway/*, api/*, cron/*, all of which are untouched here), so gate
   them with the same authcheck filter the rest of the panel uses. */
$routes->group('units', ['namespace' => 'App\Controllers', 'filter' => 'authcheck'], function ($routes) {
    $routes->get('/', 'UnitController::index', ['as' => 'inventory.unit_index']);
    $routes->post('create', 'UnitController::create', ['as' => 'inventory.unit_create']);
    $routes->get('delete/(:num)', 'UnitController::delete/$1', ['as' => 'inventory.unit_delete']);
    $routes->post('update', 'UnitController::update', ['as' => 'inventory.unit_update']);
});


$routes->group('inventory', ['filter' => 'authcheck'], function ($routes): void {
    $routes->get('store_location', 'StoreLocationController::index', ['as' => 'inventory.store_location']);
    $routes->post('store-location/create', 'StoreLocationController::create', ['as' => 'inventory.store_location_create']);
    $routes->post('store-location/update', 'StoreLocationController::update', ['as' => 'inventory.store_location_update']);
    $routes->get('store-location/delete/(:num)', 'StoreLocationController::delete/$1', ['as' => 'inventory.store_location_delete']);
});

//Vendor

$routes->get('vendors', 'BandwidthController::vendor_index', ['as' => 'bandwidth.vendor_index']);
$routes->post('vendor_store', 'BandwidthController::vendor_save', ['as' => 'bandwidth.vendor_store']);
$routes->post('vendor_delete', 'BandwidthController::vendor_delete', ['as' => 'bandwidth.vendor_delete']);


$routes->group('requisitions', ['filter' => 'authcheck'], function ($routes): void {
    $routes->get('/', 'RequisitionController::index', ['as' => 'purchase.requisition_lists']);
    $routes->post('requisitions/create', 'RequisitionController::create', ['as' => 'purchase.requisition_create']);
    $routes->get('requisition-get', 'RequisitionController::getRequisition', ['as' => 'purchase.requisition_get']);
    $routes->get('requisitions/edit/(:num)', 'RequisitionController::edit/$1', ['as' => 'purchase.requisition_edit']);
    $routes->post('requisitions/update', 'RequisitionController::update', ['as' => 'purchase.requisition_update']);
    $routes->get('requisitions/delete/(:num)', 'RequisitionController::delete/$1', ['as' => 'purchase.requisition_delete']);
});

$routes->group('Purchases', ['filter' => 'authcheck'], function ($routes): void {
    $routes->get('purchase_list', 'InventoryPurchessController::purchase_list', ['as' => 'purchase_bill.purchase_list']);
    $routes->post('requisitions/create', 'InventoryPurchessController::create', ['as' => 'purchase_bill.requisition_create']);
    $routes->get('requisition-get', 'InventoryPurchessController::getRequisition', ['as' => 'purchase_bill.requisition_get']);
    $routes->get('requisitions/edit/(:num)', 'InventoryPurchessController::edit/$1', ['as' => 'purchase_bill.requisition_edit']);
    $routes->post('requisitions/update', 'InventoryPurchessController::update', ['as' => 'purchase_bill.requisition_update']);
    $routes->get('requisitions/delete/(:num)', 'InventoryPurchessController::delete/$1', ['as' => 'purchase_bill.requisition_delete']);
});

$routes->group('bandwidth_sell', ['filter' => 'authcheck'], function ($routes): void {
    //Bandwidth Sell routes
    $routes->get('', 'bandwidth_sell_controller::index', [
        'as' => 'bandwidth.sell.index',
        // 'filter' => 'permissioncheck:customer,read',
    ]);
    $routes->get('Sells_invoices', 'bandwidth_sell_controller::purchase_list', [
        'as' => 'bandwidth.sell.purchase_list',
        // 'filter' => 'permissioncheck:customer,read',
    ]);
    $routes->get('purchase_list_invoice', 'bandwidth_sell_controller::purchase_list_invoice', [
        'as' => 'bandwidth.sell.purchase_list_invoice',
        // 'filter' => 'permissioncheck:customer,read',
    ]);
    $routes->post('save', 'bandwidth_sell_controller::save', ['as' => 'bandwidth_sell_client.save']);
    $routes->get('edit/(:num)', 'bandwidth_sell_controller::edit/$1', ['as' => 'bandwidth_sell_client.edit']);
    $routes->post('update/(:num)', 'bandwidth_sell_controller::update/$1', ['as' => 'bandwidth_sell_client.update']);


    //
    $routes->post('create', 'bandwidth_sell_controller::create', ['as' => 'bandwidth_sell.requisition_create']);
    $routes->get('requisition-get', 'bandwidth_sell_controller::getRequisition', ['as' => 'bandwidth_sell.requisition_get']);
    $routes->get('edit/(:num)', 'bandwidth_sell_controller::bandwidth_selledit/$1', ['as' => 'bandwidth_sell.requisition_edit']);
    $routes->post('update', 'bandwidth_sell_controller::bandwidth_sellupdate', ['as' => 'bandwidth_sell.requisition_update']);
    $routes->post('pay_update', 'bandwidth_sell_controller::bandwidth_sell_payment_update', ['as' => 'bandwidth_sell_payment_update.requisition_update']);
    $routes->post('filter-invoices', 'bandwidth_sell_controller::filterInvoices', ['as' => 'bandwidth_sell.filter_invoices']);

    // CHANGE THIS LINE - Use (:any) instead of (:num) for alphanumeric IDs
    $routes->get('delete/(:any)', 'bandwidth_sell_controller::bandwidth_selldelete/$1', ['as' => 'bandwidth_sell.requisition_delete']);

    $routes->get('daily-bill', 'bandwidth_sell_controller::dailyindex', ['as' => 'bandwidth.dailyindex']);

    $routes->get('daily-bill-data', 'bandwidth_sell_controller::getDailyBillData', ['as' => 'bandwidth.dailyBillData']);
});


// reseller
$routes->group('reseller', ['filter' => 'authcheck'], function ($routes) {

    $routes->get('', 'Reseller::index', [
        'as' => 'route.reseller',
        // 'filter' => 'permissioncheck:reseller,update',

    ]);
    $routes->get('payment_details/(:any)', 'Reseller::payment_details/$1', [
        'as' => 'resellers.payment_details',

    ]);

    $routes->get('add', 'Reseller::add', [
        'as' => 'reseller.add',
    ]);
    $routes->post('submit', 'RegistrationController::submit', [
        'as' => 'route.reseller.submit'
    ]);
    $routes->post('reseller', 'Reseller::fetch', [
        'as' => 'route.Reseller.fetch',
    ]);
    $routes->get('resellerpackages', 'Reseller::packages', [
        'as' => 'reseller.packages'
    ]);
    $routes->post('resellersave-package', 'Reseller::savePackage', [
        'as' => 'reseller.savePackage'
    ]);

    $routes->get('router-profiles/(:num)', 'Reseller::getRouterProfiles/$1', [
        'as' => 'reseller.routerProfiles'
    ]);



    $routes->get('resellergetPackage/(:num)', 'Reseller::getPackage/$1', [
        'as' => 'reseller.getPackage'
    ]);


    //dsdsf
    $routes->get('resellerpackages/(:num)', 'AllResellersPackage::index/$1', [
        'as' => 'resellers.packages'
    ]);
    $routes->get('resellerpackages/json/(:num)', 'AllResellersPackage::packages/$1', [
        'as' => 'resellers.packages.json'
    ]);
    $routes->get('admin/packages/json', 'AllResellersPackage::adminPackagesJson', [
        'as' => 'admin.packages.json'
    ]);


    $routes->delete('resellerdelete', 'Reseller::delete', [
        'as' => 'route.reseller.delete',
        // 'filter' => 'permissioncheck:customer,delete',
    ]);

    // $routes->post('packages/update', 'AllResellersPackage::update');

    // $routes->post('resellersave-package', 'AllResellersPackage::savePackage', [
    //     'as' => 'resellers.savePackage'
    // ]);

    // $routes->get('resellergetPackages/(:num)', 'AllResellersPackage::getPackage/$1', [
    //     'as' => 'resellers.getPackage'
    // ]);
    $routes->post('updatePackages', 'AllResellersPackage::updatePackage', [
        'as' => 'resellers.updatePackage'
    ]);
    $routes->post('syncPackages', 'AllResellersPackage::syncPackages', [
        'as' => 'resellers.syncPackages'
    ]);

    // $routes->delete('deletePackages/(:num)', 'AllResellersPackage::deletePackage/$1', [
    //     'as' => 'resellers.deletePackage'
    // ]);

    //end

    $routes->get('details/(:num)', 'Reseller::details/$1', [
        'as' => 'route.Reseller.details'
        // 'filter' => 'permissioncheck:customer,read',
    ]);

    // $routes->get('(:num)', 'Reseller::subscription/$1', [
    //     'as' => 'route.Reseller.subscription',
    //     // 'filter' => 'permissioncheck:customer,update_subscription',
    // ]);

    $routes->get('edit/(:num)', 'Reseller::edit/$1', [
        'as' => 'route.Reseller.edit',
        // 'filter' => 'permissioncheck:reseller,update',
    ]);
    $routes->get('reseller_login/(:num)', 'Reseller::reseller_login/$1', [
        'as' => 'route.Reseller.login',
        // 'filter' => 'permissioncheck:reseller,update',
    ]);
    $routes->get('returnToAdmin/(:num)', 'Reseller::returnToAdmin/$1', [
        'as' => 'route.Reseller.returnToAdmin',
        // 'filter' => 'permissioncheck:reseller,update',
    ]);

    $routes->post('update/(:num)', 'Reseller::update/$1', [
        'as' => 'route.Reseller.update',

    ]);
    $routes->get('transaction', 'ResellerFunding::transactionindex', [
        'as' => 'route.reseller.transactionindex',
        'filter' => 'permissioncheck:customer_payment,update',
    ]);
    $routes->post('transactions', 'ResellerFunding::transactionsfetch', [
        'as' => 'route.Reseller.transaction.fetch',
        'filter' => 'permissioncheck:customer_payment,read',
    ]);
    $routes->delete('transactiondelete', 'ResellerFunding::transactiondelete', [
        'as' => 'route.Reseller.transaction.delete',
        'filter' => 'permissioncheck:customer_payment,delete',
    ]);

    $routes->get('Funding', 'ResellerFunding::paymentindex', [
        'as' => 'route.reseller.funding',
        // 'filter' => 'permissioncheck:reseller,update',
    ]);
    $routes->post('Fundings', 'ResellerFunding::fundingfetch', [
        'as' => 'route.Reseller.Funding.fetch',
        // 'filter' => 'permissioncheck:customer_payment,read',
    ]);
    $routes->get('New_Funding', 'ResellerFunding::new', [
        'as' => 'route.Reseller.Funding.new',
        // 'filter' => 'permissioncheck:customer_payment,create',
    ]);
    // $routes->get('/reseller-funding/(:num)?', 'ResellerFunding::index/$1');
    // $routes->post('/reseller-funding/save', 'ResellerFunding::save');


    $routes->get('reseller_fundings/(:num)', 'ResellerFunding::index/$1', [
        'as' => 'route.Reseller.Funding.index',
        'filter' => 'permissioncheck:customer_payment,create',
    ]);

    $routes->post('Reseller_Funding/save', 'ResellerFunding::save', [
        'as' => 'route.Reseller.Funding.save',
        'filter' => 'permissioncheck:customer_payment,create',
    ]);
    $routes->delete('delete', 'ResellerFunding::delete', [
        'as' => 'route.Reseller.Funding.delete',
        'filter' => 'permissioncheck:customer_payment,delete',
    ]);

    $routes->get('payment', 'Reseller::paymentindex', [
        'as' => 'route.Reseller.payment',
        // 'filter' => 'permissioncheck:reseller,update',
    ]);
    $routes->post('payments', 'Reseller::paymentfetch', [
        'as' => 'route.Reseller.payment.fetch',
        // 'filter' => 'permissioncheck:customer_payment,read',
    ]);

    // Toggle routes
    $routes->post('toggle-status', 'Reseller::toggleStatus', [
        'as' => 'route.reseller.toggle.status',
    ]);
    $routes->post('toggle-fund', 'Reseller::toggleFund', [
        'as' => 'route.reseller.toggle.fund',
    ]);
    $routes->post('toggle-clients', 'Reseller::toggleClientsStatus', [
        'as' => 'route.reseller.toggle.clients',
    ]);

    // $routes->post('update/(:num)', 'Customer::updateSubscription/$1', [
    //     'as' => 'route.customer.update_subscription',
    //     'filter' => 'permissioncheck:customer,update_subscription',
    // ]);

});



// $routes->get('packages/edit/(:num)', 'AllResellersPackage::edit/$1');


// $routes->get('edit/(:num)', 'Area::edit/$1', ['as' => 'route.area.edit',
//     'filter' => 'permissioncheck:area,update',
// ]);

$routes->post('reseller/updatePackage/(:num)', 'Reseller::updatePackage/$1', ['as' => 'reseller.updatePackage']);
$routes->delete('reseller/deletePackage/(:num)', 'Reseller::deletePackage/$1', ['as' => 'reseller.deletePackage']);
$routes->post('reseller/deleteUserPackage/(:num)', 'Reseller::deleteUserPackage/$1', ['as' => 'reseller.deleteUserPackage']);

/**
 * Dashboard routes
 */

$routes->group('olts', ['filter' => 'authcheck'], function ($routes) {
    $routes->get('olt', 'OltController::index', [
        'as' => 'olt.list',
    ]);
    $routes->post('store', 'OltController::store', [
        'as' => 'olt.store',
    ]);
    $routes->post('update', 'OltController::update', ['as' => 'olt.update']);
    $routes->post('delete/(:num)', 'OltController::delete/$1');
    $routes->post('run/(:num)', 'OltController::run/$1', ['as' => 'olt.run']);
});


$routes->group('accounts', ['filter' => 'authcheck'], function ($routes) {

    // Existing expense routes...
    $routes->get('expenses', 'ExpenseController::index', ['as' => 'route.expense.list']);
    // In your Routes file
    $routes->post('expense-type/save', 'ExpenseController::saveType', ['as' => 'route.expense.type.save']);
    $routes->post('expense/save', 'ExpenseController::save', ['as' => 'route.expense.save']);


    $routes->post('expense-type/update', 'ExpenseController::updateType', ['as' => 'route.expense.type.update']);
    $routes->post('expense-type/delete', 'ExpenseController::deleteType', ['as' => 'route.expense.type.delete']);

    $routes->post('expense/update', 'ExpenseController::update', ['as' => 'route.expense.update']);
    $routes->get('expense/get/(:num)', 'ExpenseController::get/$1', ['as' => 'route.expense.get']);
    $routes->get('expense/delete/(:num)', 'ExpenseController::delete/$1', ['as' => 'route.expense.delete']);

    // ===== APPROVE AND REJECT ROUTES =====
    $routes->post('expense/approve', 'ExpenseController::approve');
    $routes->post('expense/reject', 'ExpenseController::reject');
    // Add this route
    $routes->get('expense/q-select-criteria', 'ExpenseController::qSelectCriteria', [
        'as' => 'route.expense.qSelectCriteria',
    ]);



    // Income Routes
    $routes->get('incomes', 'IncomeController::index', ['as' => 'route.income.list']);
    $routes->post('income-category/save', 'IncomeController::saveCategory', ['as' => 'route.income.category.save']);
    $routes->post('income/save', 'IncomeController::save', ['as' => 'route.income.save']);
    $routes->post('income-category/update', 'IncomeController::updateCategory', ['as' => 'route.income.category.update']);
    $routes->post('income-category/delete', 'IncomeController::deleteCategory', ['as' => 'route.income.category.delete']);
    $routes->post('income/update', 'IncomeController::update', ['as' => 'route.income.update']);
    $routes->get('income/get/(:num)', 'IncomeController::get/$1', ['as' => 'route.income.get']);
    $routes->get('income/delete/(:num)', 'IncomeController::delete/$1', ['as' => 'route.income.delete']);
    $routes->post('income/approve', 'IncomeController::approve');
    $routes->post('income/reject', 'IncomeController::reject');


    //otc
    $routes->get('otc-report', 'ExpenseController::otcReport', ['as' => 'otc.report']);
    $routes->post('ajax-get-otc-data', 'ExpenseController::ajaxGetOtcData', ['as' => 'otc.report.ajax']);
    // In your routes file
    $routes->get('otc/report/export', 'ExpenseController::exportCsv', ['as' => 'otc.report.export']);
    // OTC Report Routes
    $routes->post('otc/status/update', 'ExpenseController::updateStatus', ['as' => 'otc.status.update']);
});



// Public Subscription Routes
$routes->group('subscription', function ($routes) {
    $routes->get('(:num)', 'Subscription::index/$1', ['as' => 'route.subscription.id']);
    $routes->get('', 'Subscription::index', ['as' => 'route.subscription']);

    $routes->get('reseller_renew/(:num)', 'Subscription::reseller_renew/$1', ['as' => 'route.resellersubscription.renew']);
    $routes->get('index/(:num)', 'Subscription::reseller_index/$1', ['as' => 'route.reseller.subscription']);

    $routes->post('renew', 'Subscription::renew', ['as' => 'route.subscription.renew']);

    $routes->get('callback', 'Subscription::callback', ['as' => 'route.subscription.callback']);
});

// Public Payment & Gateway Routes
$routes->group('payment', function ($routes) {
    $routes->get('make-payment/(:num)', 'Payment::makePayment/$1', ['as' => 'route.payment.pay']);

    $routes->group('gateway/bkash', function ($routes) {
        $routes->post('get-payment-url', 'gateway\Bkash::getBkashPaymentUrl', ['as' => 'route.payment.gateway.bkash.geturl']);
        $routes->get('callback/(:num)', 'gateway\Bkash::callback/$1', ['as' => 'route.payment.gateway.bkash.callback']);
    });

    $routes->group('gateway/nagad', function ($routes) {
        $routes->post('get-payment-url', 'gateway\Nagad::getNagadPaymentUrl', ['as' => 'route.payment.gateway.nagad.geturl']);
        $routes->get('query-payment/(:num)', 'gateway\Nagad::queryPayment/$1', ['as' => 'route.payment.gateway.nagad.query']);
    });

    $routes->group('gateway/sslcommerz', function ($routes) {
        $routes->post('get-payment-url', 'gateway\SSLCommerz::getSSLCommerzPaymentUrl', ['as' => 'route.payment.gateway.sslcommerz.geturl']);
        $routes->post('query-payment/(:num)', 'gateway\SSLCommerz::queryPayment/$1', ['as' => 'route.payment.gateway.sslcommerz.query']);
    });

    $routes->group('gateway/eps', function ($routes) {
        $routes->post('get-payment-url', 'gateway\Eps::getEpsPaymentUrl', ['as' => 'route.payment.gateway.eps.geturl']);
        $routes->get('callback/(:num)', 'gateway\Eps::callback/$1', ['as' => 'route.payment.gateway.eps.callback']);
    });

    $routes->group('gateway/shurjopay', function ($routes) {
        $routes->post('get-payment-url', 'gateway\Shurjopay::getShurjopayPaymentUrl', ['as' => 'route.payment.gateway.shurjopay.geturl']);
        $routes->get('callback/(:num)', 'gateway\Shurjopay::callback/$1', ['as' => 'route.payment.gateway.shurjopay.callback']);
    });

    $routes->group('gateway/paystation', function ($routes) {
        $routes->post('get-payment-url', 'gateway\Paystation::getPaystationPaymentUrl', ['as' => 'route.payment.gateway.paystation.geturl']);
        $routes->get('callback/(:num)', 'gateway\Paystation::callback/$1', ['as' => 'route.payment.gateway.paystation.callback']);
    });
});

$routes->get('plugins', 'Plugins::index', ['as' => 'route.plugins.index']);

// Public Hotspot Portal Info API
$routes->get('hotspot/get-portal-info', 'Hotspot::get_portal_info');

$routes->group('', ['filter' => 'authcheck'], function ($routes) {

    //Dashboard routes
    $routes->get('dashboard', 'Dashboard::index', [
        'as' => 'route.dashboard',
    ]);
    $routes->get('api/dashboard/sadmin-data', 'Dashboard::sadminData', [
        'as' => 'route.dashboard.sadmin_data',
    ]);
    $routes->get('api/dashboard/sadmin-charts-data', 'Dashboard::sadminChartsData', [
        'as' => 'route.dashboard.sadmin_charts_data',
    ]);
    $routes->get('exhome', 'AuthController::exhome', [
        'as' => 'route.exhome',
    ]);

    // Tenant platform wallet (Pay-As-You-Go balance, top-ups, ledger)
    $routes->group('wallet', function ($routes) {
        $routes->get('', 'Wallet::index', ['as' => 'route.wallet']);
        $routes->post('topup', 'Wallet::topup', ['as' => 'route.wallet.topup']);
        $routes->post('addons', 'Wallet::updateAddons', ['as' => 'route.wallet.addons']);
        $routes->post('switch-payg', 'Wallet::switchToPayg', ['as' => 'route.wallet.switch_payg']);
        $routes->post('transactions', 'Wallet::transactions', ['as' => 'route.wallet.transactions']);
    });

    $routes->get('system/redis-cache', 'RedisInspector::index', [
        'as' => 'route.redis_inspector',
    ]);
    $routes->get('system/redis-cache/refresh', 'RedisInspector::refresh', [
        'as' => 'route.redis_inspector.refresh',
    ]);

    $routes->post('ai-chat', 'AiChatController::aiChat');

    $routes->group('plugins', function ($routes) {
        $routes->post('store', 'Plugins::store', ['as' => 'route.plugins.store']);
        $routes->post('update/(:num)', 'Plugins::update/$1', ['as' => 'route.plugins.update']);
        $routes->delete('delete/(:num)', 'Plugins::delete/$1', ['as' => 'route.plugins.delete']);
    });

    //Area routes
    $routes->group(
        'area',
        function ($routes) {

            $routes->get('', 'Area::index', [
                'as' => 'route.area',
                'filter' => 'permissioncheck:area,read',
            ]);
            $routes->get('sub/(:num)', 'Area::subindex/$1', [
                'as' => 'route.subarea',
                'filter' => 'permissioncheck:area,read',
            ]);

            $routes->post('fetch', 'Area::fetch', [
                'as' => 'route.area.fetch',
                'filter' => 'permissioncheck:area,read',
            ]);
            $routes->post('fetchsub', 'Area::fetchsub', [
                'as' => 'route.area.fetchsub',
                'filter' => 'permissioncheck:area,read',
            ]);
            $routes->post('tree', 'Area::treeData', [
                'as' => 'route.area.tree',
                'filter' => 'permissioncheck:area,read',
            ]);
            $routes->post('subtree/(:num)', 'Area::subTree/$1', [
                'as' => 'route.area.subtree',
                'filter' => 'permissioncheck:area,read',
            ]);

            $routes->get('new', 'Area::new', [
                'as' => 'route.area.new',
                'filter' => 'permissioncheck:area,create',
            ]);
            $routes->get('subnew/(:num)', 'Area::subnew/$1', [
                'as' => 'route.subarea.new',
                'filter' => 'permissioncheck:area,create',
            ]);

            $routes->post('create', 'Area::create', [
                'as' => 'route.area.create',
                'filter' => 'permissioncheck:area,create',
            ]);
            $routes->post('subcreate', 'Area::subcreate', [
                'as' => 'route.subarea.create',
                'filter' => 'permissioncheck:area,create',
            ]);

            $routes->delete('delete', 'Area::delete', [
                'as' => 'route.area.delete',
                'filter' => 'permissioncheck:area,delete',
            ]);
            $routes->delete('deletesub', 'Area::deletesub', [
                'as' => 'route.subarea.delete',
                'filter' => 'permissioncheck:area,delete',
            ]);

            $routes->get('edit/(:num)', 'Area::edit/$1', [
                'as' => 'route.area.edit',
                'filter' => 'permissioncheck:area,update',
            ]);
            $routes->get('subedit/(:num)', 'Area::editsub/$1', [
                'as' => 'route.subarea.edit',
                'filter' => 'permissioncheck:area,update',
            ]);

            $routes->post('update/(:num)', 'Area::update/$1', [
                'as' => 'route.area.update',
                'filter' => 'permissioncheck:area,update',
            ]);
            $routes->post('updatesub/(:num)', 'Area::updatesub/$1', [
                'as' => 'route.subarea.update',
                'filter' => 'permissioncheck:area,update',
            ]);
        }
    );

    //Package routes
    $routes->group(
        'packages',
        function ($routes) {

            $routes->get('', 'Packages::index', [
                'as' => 'route.packages',
                'filter' => 'permissioncheck:packages,read',
            ]);

            $routes->post('fetch', 'Packages::fetch', [
                'as' => 'route.packages.fetch',
                'filter' => 'permissioncheck:packages,read',
            ]);

            $routes->get('new', 'Packages::new', [
                'as' => 'route.packages.new',
                'filter' => 'permissioncheck:packages,create',
            ]);

            $routes->post('create', 'Packages::create', [
                'as' => 'route.packages.create',
                'filter' => 'permissioncheck:packages,create',
            ]);

            $routes->delete('delete', 'Packages::delete', [
                'as' => 'route.packages.delete',
                'filter' => 'permissioncheck:packages,delete',
            ]);

            $routes->get('edit/(:num)', 'Packages::edit/$1', [
                'as' => 'route.packages.edit',
                'filter' => 'permissioncheck:packages,update',
            ]);

            $routes->post('update/(:num)', 'Packages::update/$1', [
                'as' => 'route.packages.update',
                'filter' => 'permissioncheck:packages,update',
            ]);
        }
    );

    //Customer routes
    $routes->group(
        'customers',
        function ($routes) {

            $routes->get('mac_ajax/(:num)/(:any)', 'Customer::mac_ajax/$1/$2');


            $routes->get('', 'Customer::index', [
                'as' => 'route.customer',
                'filter' => 'permissioncheck:customer,read',
            ]);
            $routes->POST('fetchDataByAreaId', 'Customer::fetchDataByAreaId', [
                'as' => 'route.fetchDataByAreaId',
                // 'filter' => 'permissioncheck:customer,read',
            ]);
            $routes->POST('get-pppoe-status', 'Customer::getPPPoEUserStatus', [
                'as' => 'route.getPppoeStatus',
            ]);


            // $routes->get('get-pppoe-status/(:num)/(:num)', 'YourController::getPppoeStatus/$1/$2');


            $routes->post('fetch', 'Customer::fetch', [
                'as' => 'route.customer.fetch',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->get('import_excel', 'Customer::Excel_index', [
                'as' => 'route.customer.excel_index',
                // 'filter' => 'permissioncheck:customer,read',
            ]);
            $routes->POST('preview_excel', 'Customer::preview_Excel', [
                'as' => 'route.customer.preview_Excel',
            ]);
            $routes->POST('process_import', 'Customer::process_import', [
                'as' => 'route.customer.process_import',
            ]);
            $routes->get('get_progress', 'Customer::getProgress', [
                'as' => 'route.customer.get_progress',
                // 'filter' => 'permissioncheck:customer_payment,read',
            ]);
            $routes->get('pingUserApi', 'Customer::pingUserApi', ['as' => 'route.customer.pingUserApi']);

            /* These were the only two mutating routes in the customers group with
               no permissioncheck (transfer's was commented out), while their
               siblings create/update/delete all carry one. Both live inside the
               global authcheck group, so ANY logged-in session — including a plain
               'user' (a customer) — could POST arbitrary customer ids and reassign
               them to another reseller/package or another router. */
            $routes->post('transfer', 'Customer::transfer', [
                'as' => 'route.customer.transfer',
                'filter' => 'permissioncheck:customer,delete',
            ]);

            $routes->post('change-router', 'Customer::changeRouter', [
                'as' => 'route.customer.change_router',
                'filter' => 'permissioncheck:customer,update',
            ]);

            $routes->get('inactive_index', 'Customer::inactive_index', [
                'as' => 'route.inactive_index',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->post('inactive__fetch', 'Customer::inactive_fetch', [
                'as' => 'route.customer.inactive_fetch',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->get('expired_index', 'Customer::expired_index', [
                'as' => 'route.expired_customer',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->post('expired_fetch', 'Customer::expired_fetch', [
                'as' => 'route.customer.expired_fetch',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->get('new_index', 'Customer::new_index', [
                'as' => 'route.new_customer',
                'filter' => 'permissioncheck:customer,read',
            ]);

            $routes->post('new_fetch', 'Customer::new_fetch', [
                'as' => 'route.customer.new_fetch',
                'filter' => 'permissioncheck:customer,read',
            ]);




            $routes->get('new', 'Customer::new', [
                'as' => 'route.customer.new',
                'filter' => 'permissioncheck:customer,create',
            ]);

            $routes->post('create', 'Customer::create', [
                'as' => 'route.customer.create',
                'filter' => 'permissioncheck:customer,create',
            ]);

            $routes->post('connection-status-update', 'Customer::updateConnStatus', [
                'as' => 'route.customer.update_conn_status',
                'filter' => 'permissioncheck:customer,update_conn',
            ]);

            $routes->delete('delete', 'Customer::delete', [
                'as' => 'route.customer.delete',
                'filter' => 'permissioncheck:customer,delete',
            ]);

            $routes->post('get-profiles', 'Customer::getProfiles', [
                'as' => 'route.customer.getprofiles',
            ]);

            /**
             * Router Group for 
             */
            $routes->group(
                'subscription',
                function ($routes) {

                    $routes->get('(:num)', 'Customer::subscription/$1', [
                        'as' => 'route.customer.subscription',
                        'filter' => 'permissioncheck:customer,update_subscription',
                    ]);
                    $routes->get('reseller/(:num)', 'Customer::Resellersubscription/$1', [
                        'as' => 'route.customer.Resellersubscription',
                        'filter' => 'permissioncheck:customer,update_subscription',
                    ]);

                    $routes->post('update/(:num)', 'Customer::updateSubscription/$1', [
                        'as' => 'route.customer.update_subscription',
                        'filter' => 'permissioncheck:customer,update_subscription',
                    ]);
                }
            );

            $routes->get('details/(:num)', 'Customer::details/$1', [
                'as' => 'route.customer.details',
                'filter' => 'permissioncheck:customer,read',
            ]);
            $routes->get('kick/(:num)', 'Customer::kick/$1', [
                'as' => 'route.customer.kick',
                'filter' => 'permissioncheck:customer,update',
            ]);
            $routes->get('refresh-olt-data', 'Customer::refreshOltData', [
                'as' => 'route.customer.refreshOltData'
            ]);
            $routes->get('get-mikrotik-info', 'Customer::get_mikrotik_info', [
                'as' => 'route.customer.getMikrotikInfo'
            ]);


            $routes->get('edit/(:num)', 'Customer::edit/$1', [
                'as' => 'route.customer.edit',
                'filter' => 'permissioncheck:customer,update',
            ]);

            $routes->post('update/(:num)', 'Customer::update/$1', [
                'as' => 'route.customer.update',
                'filter' => 'permissioncheck:customer,update',
            ]);

            $routes->get('free-requests', 'Customer::freeRequests', [
                'as' => 'route.customer.free_requests',
            ]);
            $routes->post('free-requests/approve', 'Customer::approveFreeRequest', [
                'as' => 'route.customer.free_requests.approve',
            ]);
            $routes->post('free-requests/reject', 'Customer::rejectFreeRequest', [
                'as' => 'route.customer.free_requests.reject',
            ]);

            $routes->get('export', 'Customer::exportCustomers', [
                'as' => 'route.customer.export'
            ]);
        }
    );

    //Customer payment routes
    $routes->group(
        'customer-payments',
        function ($routes) {

            $routes->get('', 'CustomerPayment::index', [
                'as' => 'route.customer.payment',
                'filter' => 'permissioncheck:customer_payment,read',
            ]);

            $routes->post('fetch', 'CustomerPayment::fetch', [
                'as' => 'route.customer.payment.fetch',
                'filter' => 'permissioncheck:customer_payment,read',
            ]);
            $routes->get('user/(:num)', 'CustomerPayment::user_index/$1', [
                'as' => 'route.customer.payment.user',
                'filter' => 'permissioncheck:customer_payment,read',
            ]);


            $routes->post('user_fetch', 'CustomerPayment::user_fetch', [
                'as' => 'route.customer.payment.user_fetch',
                'filter' => 'permissioncheck:customer_payment,read',
            ]);

            $routes->get('generate-invoice', 'CustomerPayment::generateInvoices', [
                'as' => 'route.customer.payment.generate_invoice',
                'filter' => 'permissioncheck:customer_payment,create',
            ]);

            $routes->get('new', 'CustomerPayment::new', [
                'as' => 'route.customer.payment.new',
                'filter' => 'permissioncheck:customer_payment,create',
            ]);

            $routes->post('create', 'CustomerPayment::create', [
                'as' => 'route.customer.payment.create',
                'filter' => 'permissioncheck:customer_payment,create',
            ]);

            $routes->delete('delete', 'CustomerPayment::delete', [
                'as' => 'route.customer.payment.delete',
                'filter' => 'permissioncheck:customer_payment,delete',
            ]);

            $routes->get('edit/(:num)', 'CustomerPayment::edit/$1', [
                'as' => 'route.customer.payment.edit',
                'filter' => 'permissioncheck:customer_payment,update',
            ]);

            $routes->post('update/(:num)', 'CustomerPayment::update/$1', [
                'as' => 'route.customer.payment.update',
                'filter' => 'permissioncheck:customer_payment,update',
            ]);


            $routes->post('get-expiry-date', 'CustomerPayment::getExpiryDate', [
                'as' => 'route.customer.payment.getexpdate'
            ]);

            $routes->get('get-details/(:num)', 'CustomerPayment::getPaymentDetails/$1', [
                'as' => 'route.customer.payment.get_details',
            ]);
            $routes->post('save-manual-invoice', 'CustomerPayment::saveManualInvoice', [
                'as' => 'route.customer.payment.save_manual_invoice',
                'filter' => 'permissioncheck:customer_payment,create',
            ]);

            $routes->get('invoice/print/(:num)', 'CustomerPayment::invoicePrint/$1', [
                'as' => 'route.customer.payment.invoice',
                'filter' => 'permissioncheck:customer_payment,invoice',
            ]);
            $routes->get('POS/print/(:num)', 'CustomerPayment::receiptPrint/$1', [
                'as' => 'route.customer.payment.receiptPrint',
                // 'filter' => 'permissioncheck:customer_payment,invoice',
            ]);
            $routes->get('POS/newPrint', 'CustomerPayment::newPrint', [
                'as' => 'route.customer.payment.newPrint',
                // 'filter' => 'permissioncheck:customer_payment,invoice',
            ]);
            $routes->get('POS/prints/(:num)', 'CustomerPayment::directPrintReceipt/$1', [
                'as' => 'route.customer.payment.receiptPrints',
                // 'filter' => 'permissioncheck:customer_payment,invoice',
            ]);
        }
    );

    //Employee routes
    $routes->group(
        'employees',
        function ($routes) {

            $routes->get('', 'Employee::index', [
                'as' => 'route.employee',
                'filter' => 'permissioncheck:employee,read',
            ]);

            $routes->post('fetch', 'Employee::fetch', [
                'as' => 'route.employee.fetch',
                'filter' => 'permissioncheck:employee,read',
            ]);

            $routes->get('new', 'Employee::new', [
                'as' => 'route.employee.new',
                'filter' => 'permissioncheck:employee,create',
            ]);

            $routes->post('create', 'Employee::create', [
                'as' => 'route.employee.create',
                'filter' => 'permissioncheck:employee,create',
            ]);

            $routes->delete('delete', 'Employee::delete', [
                'as' => 'route.employee.delete',
                'filter' => 'permissioncheck:employee,delete',
            ]);

            $routes->get('details/(:num)', 'Employee::details/$1', [
                'as' => 'route.employee.details',
                'filter' => 'permissioncheck:employee,read',
            ]);

            $routes->get('edit/(:num)', 'Employee::edit/$1', [
                'as' => 'route.employee.edit',
                'filter' => 'permissioncheck:employee,update',
            ]);

            $routes->post('update/(:num)', 'Employee::update/$1', [
                'as' => 'route.employee.update',
                'filter' => 'permissioncheck:employee,update',
            ]);
        }
    );


    //Employee's payment routes
    $routes->group(
        'employee-payments',
        function ($routes) {

            $routes->get('', 'EmployeePayment::index', [
                'as' => 'route.employee.payment',
                'filter' => 'permissioncheck:employee_payment,read',
            ]);

            $routes->post('fetch', 'EmployeePayment::fetch', [
                'as' => 'route.employee.payment.fetch',
                'filter' => 'permissioncheck:employee_payment,read',
            ]);

            $routes->get('new', 'EmployeePayment::new', [
                'as' => 'route.employee.payment.new',
                'filter' => 'permissioncheck:employee_payment,create',
            ]);

            $routes->post('create', 'EmployeePayment::create', [
                'as' => 'route.employee.payment.create',
                'filter' => 'permissioncheck:employee_payment,create',
            ]);

            $routes->delete('delete', 'EmployeePayment::delete', [
                'as' => 'route.employee.payment.delete',
                'filter' => 'permissioncheck:employee_payment,delete',
            ]);

            $routes->get('edit/(:num)', 'EmployeePayment::edit/$1', [
                'as' => 'route.employee.payment.edit',
                'filter' => 'permissioncheck:employee_payment,update',
            ]);

            $routes->post('update/(:num)', 'EmployeePayment::update/$1', [
                'as' => 'route.employee.payment.update',
                'filter' => 'permissioncheck:employee_payment,update',
            ]);
        }
    );


    //Reseller Subscription routes
    $routes->group(
        'Resellersubscription',
        function ($routes) {

            $routes->get('', 'Subscription::reseller_index', [
                'as' => 'reseller.subscription',
                'filter' => 'permissioncheck:subscription,read',
            ]);

            $routes->post('renew', 'Subscription::reseller_renew', [
                'as' => 'reseller.subscription.renew',
                // 'filter' => 'permissioncheck:subscription,renew',
            ]);

            // $routes->get('callback', 'Subscription::callback', [
            //     'as' => 'route.subscription.callback',
            //     'filter' => [],
            // ]);
        }
    );

    //Support ticket routes
    $routes->group(
        'support-tickets',
        function ($routes) {

            $routes->get('', 'SupportTicket::index', [
                'as' => 'route.ticket',
                'filter' => 'permissioncheck:support_ticket,read',
            ]);
            $routes->post('transfer', 'SupportTicket::transfer', [
                'as' => 'route.ticket.transfer',
                'filter' => 'permissioncheck:support_ticket,update',
            ]);

            $routes->post('quick-status/(:num)', 'SupportTicket::quickStatus/$1', [
                'as' => 'route.ticket.quickstatus',
                'filter' => 'permissioncheck:support_ticket,update',
            ]);


            $routes->post('fetch', 'SupportTicket::fetch', [
                'as' => 'route.ticket.fetch',
                'filter' => 'permissioncheck:support_ticket,read',
            ]);

            $routes->get('inbox', 'SupportTicket::inbox', [
                'as' => 'route.ticket.inbox',
                'filter' => 'permissioncheck:support_ticket,read',
            ]);

            $routes->get('new', 'SupportTicket::new', [
                'as' => 'route.ticket.new',
                'filter' => 'permissioncheck:support_ticket,create',
            ]);

            $routes->post('create', 'SupportTicket::create', [
                'as' => 'route.ticket.create',
                'filter' => 'permissioncheck:support_ticket,create',
            ]);

            $routes->post('send-message/(:num)', 'SupportTicket::sendMessage/$1', [
                'as' => 'route.ticket.sendmsg',
                'filter' => 'permissioncheck:support_ticket,send_msg',
            ]);

            $routes->delete('delete', 'SupportTicket::delete', [
                'as' => 'route.ticket.delete',
                'filter' => 'permissioncheck:support_ticket,delete',
            ]);

            $routes->get('details/(:num)', 'SupportTicket::details/$1', [
                'as' => 'route.ticket.details',
                'filter' => 'permissioncheck:support_ticket,read',
            ]);

            $routes->get('edit/(:num)', 'SupportTicket::edit/$1', [
                'as' => 'route.ticket.edit',
                'filter' => 'permissioncheck:support_ticket,update',
            ]);

            $routes->post('update/(:num)', 'SupportTicket::update/$1', [
                'as' => 'route.ticket.update',
                'filter' => 'permissioncheck:support_ticket,update',
            ]);
        }
    );

    // Recycle bin routes
    $routes->group(
        'recycle-bin',
        function ($routes) {
            $routes->get('', 'RecycleBin::index', [
                'as' => 'route.recycle_bin',
                'filter' => 'permissioncheck:recycle_bin,read',
            ]);
            $routes->post('restore', 'RecycleBin::restore', [
                'as' => 'route.recycle_bin.restore',
                'filter' => 'permissioncheck:recycle_bin,restore',
            ]);
            $routes->post('delete-forever', 'RecycleBin::deleteForever', [
                'as' => 'route.recycle_bin.delete_forever',
                'filter' => 'permissioncheck:recycle_bin,delete_forever',
            ]);
            $routes->post('empty', 'RecycleBin::emptyTrash', [
                'as' => 'route.recycle_bin.empty',
                'filter' => 'permissioncheck:recycle_bin,empty',
            ]);
        }
    );

    //Sms routes
    $routes->group(
        'sms',
        function ($routes) {

            $routes->get('', 'Sms::index', [
                'as' => 'route.sms',
                'filter' => 'permissioncheck:sms_message,read',
            ]);
            $routes->get('sms_Tamplates', 'SmsTemplateController::index', [
                'as' => 'route.sms_Tamplates',
                'filter' => 'permissioncheck:sms_message,read',
            ]);
            $routes->post('sms_Tamplates', 'SmsTemplateController::store', [
                'as' => 'route.sms_Tamplates.store',
                // 'filter' => 'permissioncheck:sms_message,read',
            ]);
            $routes->post('sms_Tamplates/update', 'SmsTemplateController::update', [
                'as' => 'route.sms_templates.update',
                // 'filter' => 'permissioncheck:sms_message,read',
            ]);

            $routes->post('sms_Tamplates/delete', 'SmsTemplateController::delete', [
                'as' => 'route.sms_templates.delete',
                // 'filter' => 'permissioncheck:sms_message,read',
            ]);

            $routes->get('sms_Tamplates/event-config', 'SmsTemplateController::eventConfig', [
                'as' => 'route.sms_templates.event_config',
            ]);
            $routes->post('sms_Tamplates/event-config/save', 'SmsTemplateController::saveEventConfig', [
                'as' => 'route.sms_templates.event_config.save',
            ]);


            $routes->post('fetch', 'Sms::fetch', [
                'as' => 'route.sms.fetch',
                'filter' => 'permissioncheck:sms_message,read',
            ]);

            $routes->get('new', 'Sms::new', [
                'as' => 'route.sms.new',
                'filter' => 'permissioncheck:sms_message,create',
            ]);

            $routes->post('create', 'Sms::create', [
                'as' => 'route.sms.create',
                'filter' => 'permissioncheck:sms_message,create',
            ]);

            $routes->delete('delete', 'Sms::delete', [
                'as' => 'route.sms.delete',
                'filter' => 'permissioncheck:sms_message,delete',
            ]);

            $routes->post('get-user', 'Sms::getUser', [
                'as' => 'route.sms.getuser',
                'filter' => 'permissioncheck:sms_message,create',
            ]);
            $routes->post('get-customer-details', 'Sms::getCustomerDetails', [
                'as' => 'route.sms.getcustomerdetails',
                'filter' => 'permissioncheck:sms_message,create',
            ]);
            $routes->post('get-multi-customer-details', 'Sms::getMultipleCustomerDetails', [
                'as' => 'route.sms.getmulticustomerdetails',
                'filter' => 'permissioncheck:sms_message,create',
            ]);
        }
    );

    //Voice Sms routes
    $routes->group(
        'voice-sms',
        function ($routes) {

            $routes->get('', 'VoiceSms::index', [
                'as' => 'route.voice-sms',
            ]);

            $routes->get('get-gateway-voices', 'VoiceSms::getGatewayVoices', [
                'as' => 'route.voice-sms.get-gateway-voices'
            ]);

            $routes->get('new', 'VoiceSms::new', [
                'as' => 'route.voice-sms.new',
                // 'filter' => 'permissioncheck:sms_message,create',
            ]);

            $routes->post('create', 'VoiceSms::create', [
                'as' => 'route.voice-sms.create',
                // 'filter' => 'permissioncheck:sms_message,create',
            ]);

            $routes->post('add-message', 'VoiceSms::addMessage', [
                'as' => 'route.voice-sms.add-message',
            ]);

            $routes->post('update-message', 'VoiceSms::updateMessage', [
                'as' => 'route.voice-sms.update-message',
            ]);

            $routes->get('delete-message/(:num)', 'VoiceSms::deleteMessage/$1', [
                'as' => 'route.voice-sms.delete-message',
            ]);

            $routes->post('save-gateway', 'VoiceSms::saveGateway', [
                'as' => 'route.voice-sms.save-gateway',
            ]);

            $routes->post('save-event-config', 'VoiceSms::saveEventConfig', [
                'as' => 'route.voice-sms.save-event-config',
            ]);
        }
    );


    //Settings routes
    $routes->group(
        'settings',
        function ($routes) {

            $routes->match(['get', 'post'], 'software', 'Settings::index', [
                'as' => 'route.settings.software',
                'filter' => [
                    'permissioncheck:software_settings,read',
                    'permissioncheck:software_settings,update',
                ],
            ]);

            $routes->post('check-balance', 'Settings::checkBalance', [
                'as' => 'route.settings.checkbalance',
                'filter' => [
                    'permissioncheck:software_settings,read',
                    'permissioncheck:software_settings,update',
                ],
            ]);
        }
    );


    //Router routes
    $routes->group(
        'routers',
        function ($routes) {

            $routes->get('', 'Routers::index', [
                'as' => 'route.routers',
                'filter' => 'permissioncheck:routers,read',
            ]);

            $routes->post('fetch', 'Routers::fetch', [
                'as' => 'route.routers.fetch',
                'filter' => 'permissioncheck:routers,read',
            ]);

            $routes->get('new', 'Routers::new', [
                'as' => 'route.routers.new',
                'filter' => 'permissioncheck:routers,create',
            ]);

            $routes->post('create', 'Routers::create', [
                'as' => 'route.routers.create',
                'filter' => 'permissioncheck:routers,create',
            ]);

            $routes->delete('delete', 'Routers::delete', [
                'as' => 'route.routers.delete',
                'filter' => 'permissioncheck:routers,delete',
            ]);

            $routes->get('details/(:num)', 'Routers::details/$1', [
                'as' => 'route.routers.details',
                'filter' => 'permissioncheck:routers,read',
            ]);

            $routes->get('load-traffic/(:num)', 'Routers::loadTraffic/$1', [
                'as' => 'route.routers.load_traffic',
                // 'filter' => 'permissioncheck:routers,read',
            ]);
            $routes->get('users_load-traffic/(:num)', 'Routers::UsersloadTraffic/$1', [
                'as' => 'route.routers.Usersload_Traffic',
                // 'filter' => 'permissioncheck:routers,read',
            ]);
            $routes->get('allusers', 'Routers::allusers', [
                'as' => 'route.routers.allusers',
                // 'filter' => 'permissioncheck:routers,read',
            ]);
            $routes->get('activeusers', 'Routers::activeusers', [
                'as' => 'route.routers.activeusers',
                // 'filter' => 'permissioncheck:routers,read',
            ]);
            $routes->get('inactiveusers', 'Routers::inactiveusers', [
                'as' => 'route.routers.inactiveusers',
                // 'filter' => 'permissioncheck:routers,read',
            ]);

            $routes->get('edit/(:num)', 'Routers::edit/$1', [
                'as' => 'route.routers.edit',
                'filter' => 'permissioncheck:routers,update',
            ]);

            $routes->post('update/(:num)', 'Routers::update/$1', [
                'as' => 'route.routers.update',
                'filter' => 'permissioncheck:routers,update',
            ]);

            $routes->get('sync/(:num)', 'Routers::sync/$1', [
                'as' => 'route.routers.sync',
                'filter' => 'permissioncheck:routers,sync',
            ]);

            $routes->post('import/(:num)', 'Routers::import/$1', [
                'as' => 'route.routers.import',
                'filter' => 'permissioncheck:routers,sync',
            ]);
            $routes->post('sync_pppoe', 'Routers::getRouterPassById', [
                'as' => 'route.routers.getRouterPassById',
                // 'filter' => 'permissioncheck:routers,sync',
            ]);
            $routes->get('setup-expired-profile/(:num)', 'MikrotikSetup::setupExpiredProfile/$1', [
                'as' => 'route.routers.setup_expired_profile',
                'filter' => 'permissioncheck:routers,update',
            ]);
            /* Was MikrotikSetup::resetExpiredProfile, which does not exist on the
               controller (only setupExpiredProfile/removeExpiredProfile do), so
               every "reset expired profile" click 404'd and the router firewall
               cleanup never ran. */
            $routes->get('reset-expired-profile/(:num)', 'MikrotikSetup::removeExpiredProfile/$1', [
                'as' => 'route.routers.reset_expired_profile',
                'filter' => 'permissioncheck:routers,update',
            ]);
            $routes->get('setup-radius/(:num)', 'MikrotikSetup::setupRadius/$1', [
                'as' => 'route.routers.setup_radius',
                'filter' => 'permissioncheck:routers,update',
            ]);
        }
    );

    // Theme Studio (personal UI prefs — any authenticated user)
    $routes->get('theme-studio', 'Settings::themeStudio', [
        'as' => 'route.theme.studio',
    ]);

    //Change password route
    $routes->match(['get', 'post'], 'change-password', 'Settings::changePassword', [
        'as' => 'route.cngpass',
        'filter' => 'permissioncheck:password_change,update',
    ]);

    //User access routes
    $routes->group(
        'user-access',
        function ($routes) {

            $routes->get('', 'Access::index', [
                'as' => 'route.useraccess'
            ]);

            $routes->post('fetch', 'Access::fetch', [
                'as' => 'route.useraccess.fetch'
            ]);

            $routes->post('get-access', 'Access::getAccess', [
                'as' => 'route.useraccess.getaccess'
            ]);

            $routes->post('update-access/(:num)', 'Access::updateAccess/$1', [
                'as' => 'route.useraccess.update_access'
            ]);


            /**
             * Router Group for custom access
             */
            $routes->group(
                'custom',
                function ($routes) {

                    $routes->get('', 'Access::custom', [
                        'as' => 'route.useraccess.custom'
                    ]);

                    $routes->post('fetch', 'Access::fetchCustomAccess', [
                        'as' => 'route.useraccess.custom.fetch'
                    ]);

                    $routes->get('new', 'Access::newCustomAccess', [
                        'as' => 'route.useraccess.custom.new'
                    ]);

                    $routes->post('create', 'Access::createCustomAccess', [
                        'as' => 'route.useraccess.custom.create'
                    ]);

                    $routes->post('get-access', 'Access::getCustomAccess', [
                        'as' => 'route.useraccess.custom.getaccess'
                    ]);

                    $routes->post('update-access/(:num)', 'Access::updateCustomAccess/$1', [
                        'as' => 'route.useraccess.custom.update'
                    ]);

                    $routes->delete('delete', 'Access::deleteCustomAccess', [
                        'as' => 'route.useraccess.custom.delete'
                    ]);
                }
            );
        }
    );



    //Payment routes
    $routes->group(
        'payment',
        function ($routes) {

            $routes->get('', 'Payment::index', [
                'as' => 'route.payment',
                // 'filter' => 'permissioncheck:payment,read',
            ]);

            $routes->post('fetch', 'Payment::fetch', [
                'as' => 'route.payment.fetch',
                // 'filter' => 'permissioncheck:payment,read',
            ]);
        }
    );


    $routes->get('invoice/print/(:num)', 'Payment::invoicePrint/$1', [
        'as' => 'route.payment.invoice',
        // 'filter' => 'permissioncheck:payment,invoice',
    ]);

    //Hotspot routes
    $routes->group(
        'hotspot',
        function ($routes) {
            $routes->get('', 'Hotspot::user_profiles', [
                'as' => 'route.hotspot.user_profiles',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);
            $routes->post('profile/add', 'Hotspot::addHotspotUserProfile', [
                'as' => 'route.user.profile.store',
            ]);
            $routes->get('get_user_profiles', 'Hotspot::get_user_profiles', [
                'as' => 'route.user.profile.get',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);
            $routes->post('user-profile/delete', 'Hotspot::user_profiles_delete', [
                'as' => 'route.user.profile.delete'
            ]);
            $routes->post('user-profile/update', 'Hotspot::updateHotspotUserProfile', [
                'as' => 'route.user.profile.update'
            ]);
            //users 

            $routes->get('users', 'Hotspot::users', [
                'as' => 'route.hotspot.users',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);

            $routes->get('get_users', 'Hotspot::get_users', [
                'as' => 'route.user.get',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);
            $routes->post('user/add', 'Hotspot::addHotspotUser', [
                'as' => 'route.user.store',
            ]);

            $routes->post('user/generate', 'Hotspot::generateUsers', [
                'as' => 'route.user.generate',
            ]);

            $routes->post('user/update', 'Hotspot::updateHotspotUser', [
                'as' => 'route.user.update',
            ]);

            $routes->post('user/delete', 'Hotspot::deleteHotspotUser', [
                'as' => 'route.user.delete',
            ]);
            //dashboard
            $routes->get('dashboard', 'Hotspot::Hotspot_Dashboard', [
                'as' => 'route.hotspot.dashboard',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);

            $routes->get('get_Hotspot_Dashboard', 'Hotspot::get_Hotspot_Dashboard', [
                'as' => 'route.user.dashboard.get',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);

            $routes->get('reports', 'Hotspot::report', [
                'as' => 'hotspot.report',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);

            $routes->get('get_reports', 'Hotspot::get_report', [
                'as' => 'hotspot.report.data',
                // 'filter' => 'permissioncheck:hotspot,read',
            ]);
        }
    );

    //Profile routes
    $routes->group(
        'profile',
        function ($routes) {

            $routes->get('', 'Profile::index', [
                'as' => 'route.profile',
                'filter' => 'permissioncheck:profile_update,read',
            ]);

            $routes->post('update', 'Profile::update', [
                'as' => 'route.profile.update',
                'filter' => 'permissioncheck:profile_update,update',
            ]);
            $routes->post('orgupdate', 'Profile::Orgupdate', [
                'as' => 'route.organization.update',
                'filter' => 'permissioncheck:profile_update,update',
            ]);
            $routes->post('routers/user-profile', 'Profile::userProfile', [
                'as' => 'route.user.profile'
            ]);
        }
    );

    // Movie & News Admin APIs (Session protected)
    $routes->group('api/movieservers', function ($routes) {
        $routes->get('/', 'MovieNewsApiController::movieIndex');
        $routes->get('view/(:num)', 'MovieNewsApiController::movieView/$1');
        $routes->post('add', 'MovieNewsApiController::movieAdd');
        $routes->post('update/(:num)', 'MovieNewsApiController::movieUpdate/$1');
        $routes->get('delete/(:num)', 'MovieNewsApiController::movieDelete/$1');
    });

    $routes->group('api/news', function ($routes) {
        $routes->get('/', 'MovieNewsApiController::newsIndex');
        $routes->get('view/(:num)', 'MovieNewsApiController::newsView/$1');
        $routes->post('add', 'MovieNewsApiController::newsAdd');
        $routes->post('update/(:num)', 'MovieNewsApiController::newsUpdate/$1');
        $routes->get('delete/(:num)', 'MovieNewsApiController::newsDelete/$1');
    });

    //Logout route
    $routes->get('logout', 'LogoutController::index', [
        'as' => 'route.logout'
    ]);
});


/**
 * CronJob routesall
 */
$routes->group('cron', ['filter' => 'cronauth'], function ($routes) {

    // Web Routes
    $routes->get('manage-user', 'CronJob::index', ['as' => 'route.cronjob.manageuser']);
    $routes->get('customer_data_usages', 'CronJob::customer_data_usages', ['as' => 'route.cronjob.customer_data_usages']);
    $routes->get('backupDatabaseAndSendEmail', 'CronJob::backupDatabaseAndSendEmail', ['as' => 'route.cronjob.backupDatabaseAndSendEmail']);
    $routes->get('send-notification', 'CronJob::sendNotification', ['as' => 'route.cronjob.send_notification']);
    $routes->get('usersactivity', 'CronJob::usersactivity', ['as' => 'route.cronjob.usersactivity']);
    $routes->get('deleteWriteAbleLogs', 'CronJob::deleteWriteAbleLogs', ['as' => 'route.cronjob.deleteWriteAbleLogs']);
    $routes->get('daily_payment_generate', 'CronJob::daily_payment_generate', ['as' => 'route.cronjob.daily_payment_generate']);
    $routes->get('payg_billing', 'CronJob::paygBilling', ['as' => 'route.cronjob.payg_billing']);
    $routes->get('updateUser_activity', 'CronJob::updateUser_activity', ['as' => 'route.cronjob.updateUser_activity']);
    $routes->get('sync-credentials', 'CronJob::sync_all_credentials', ['as' => 'route.cronjob.sync_all_credentials']);
    $routes->get('usage-flush', 'CronJob::flushUsage', ['as' => 'route.cronjob.usage_flush']);
    $routes->get('purge-trash', 'CronJob::purgeTrash', ['as' => 'route.cronjob.purge_trash']);

    // CLI Routes for cPanel Cron Jobs
    $routes->cli('manage-user', 'CronJob::index');
    $routes->cli('customer_data_usages', 'CronJob::customer_data_usages');
    $routes->cli('backupDatabaseAndSendEmail', 'CronJob::backupDatabaseAndSendEmail');
    $routes->cli('send-notification', 'CronJob::sendNotification');
    $routes->cli('usersactivity', 'CronJob::usersactivity');
    $routes->cli('deleteWriteAbleLogs', 'CronJob::deleteWriteAbleLogs');
    $routes->cli('daily_payment_generate', 'CronJob::daily_payment_generate');
    $routes->cli('payg_billing', 'CronJob::paygBilling');
    $routes->cli('updateUser_activity', 'CronJob::updateUser_activity');
    $routes->cli('sync-credentials', 'CronJob::sync_all_credentials');
    $routes->cli('usage-flush', 'CronJob::flushUsage');
    $routes->cli('purge-trash', 'CronJob::purgeTrash');
});

// File Manager Routes
$routes->group('file-manager', ['filter' => 'authcheck'], function ($routes) {
    $routes->get('/', 'FileManager::index');
    $routes->get('view', 'FileManager::viewFile');
    $routes->get('download', 'FileManager::downloadFile');
    $routes->post('save', 'FileManager::saveFile');
    $routes->post('delete', 'FileManager::deleteItem');
    $routes->post('create-folder', 'FileManager::createFolder');
});







/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

$routes->group('reports', ['filter' => 'authcheck'], function ($routes) {
    $routes->get('btrc', 'Reports::btrc', ['as' => 'route.reports.btrc']);
    $routes->post('btrc/fetch', 'Reports::fetchBtrc', ['as' => 'route.reports.btrc.fetch']);
    $routes->get('btrc/pdf', 'Reports::exportBtrcPdf', ['as' => 'route.reports.btrc.pdf']);
    $routes->get('btrc/excel', 'Reports::exportBtrcExcel', ['as' => 'route.reports.btrc.excel']);
});

/*
 * --------------------------------------------------------------------
 * bKash Webhook (Override for priority)
 * --------------------------------------------------------------------
 */
$routes->group('api', function ($routes) {
    $routes->post('bkash/get_bkash_sendmoney', '\App\Controllers\Bkash_webhook::get_bkash_sendmoney');
    $routes->get('bkash/get_bkash_sendmoney', '\App\Controllers\Bkash_webhook::get_bkash_sendmoney');
});

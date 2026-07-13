<?php

// Website (non-API) routes for the Referral & Reward admin page. These render
// INSIDE the isp-core website (layout/main-layout) and use the website session,
// so they sit behind the site's 'authcheck' filter (not JWT). Reachable from
// the sidebar at: /reward-center

if (!isset($routes)) {
    return;
}

$routes->get('reward-center', '\Zapi\Modules\Shared\Rewards\Controllers\RewardWebController::index', ['filter' => 'authcheck']);
$routes->post('reward-center/referrals/(:num)/approve', '\Zapi\Modules\Shared\Rewards\Controllers\RewardWebController::approve/$1', ['filter' => 'authcheck']);
$routes->post('reward-center/referrals/(:num)/reject', '\Zapi\Modules\Shared\Rewards\Controllers\RewardWebController::reject/$1', ['filter' => 'authcheck']);
$routes->post('reward-center/config', '\Zapi\Modules\Shared\Rewards\Controllers\RewardWebController::saveConfig', ['filter' => 'authcheck']);

// Customer portal (role=user) — referral sharing + reward wallet
$routes->get('my-rewards', '\Zapi\Modules\Customer\RewardPortal\Controllers\CustomerRewardPortalController::index', ['filter' => 'authcheck']);
$routes->get('my-rewards/redeem-preview', '\Zapi\Modules\Customer\RewardPortal\Controllers\CustomerRewardPortalController::redeemPreview', ['filter' => 'authcheck']);

// Public referral registration (shared link from customer portal)
$routes->get('register', '\Zapi\Modules\Common\Registration\Controllers\ReferralRegistrationWebController::index');
$routes->post('register/submit', '\Zapi\Modules\Common\Registration\Controllers\ReferralRegistrationWebController::submit');

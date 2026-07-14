<?php

/**
 * User Helper File
 */

use CodeIgniter\CLI\Console;
use App\Models\AdminPackage;
use App\Models\allResellerPackage;
use App\Models\Registration;
use App\Models\Package;
use App\Models\User;
use App\Models\Sms;
use App\Models\AuditLogModel;

/**
 * Get user by id
 */
if (!function_exists('getUserById')) {

    function getUserById($id)
    {
        if (empty($id))
            return null;

        $userModel = model('App\Models\User');
        $user = $userModel->find($id);


        return $user ?? null;
    }
}

/**
 * Resolve the top-level tenant (ISP owner) user id for a given user (hierarchy traversal).
 *
 * @return int|null Tenant owner user id, or null when the hierarchy cannot be resolved.
 */
if (!function_exists('getSAdminIdForUser')) {
    function getSAdminIdForUser($userId)
    {
        if (empty($userId)) {
            return null;
        }

        // Per-request memoization: the sAdmin hierarchy is stable within a
        // request and this walk is invoked repeatedly (notably by userHasPermission).
        // BUG-13: static survives across jobs in queue:work — bust cache when the
        // request stamp changes (new HTTP request or new queue job context).
        static $__sadminCache = [];
        static $__sadminStamp  = null;
        $__curStamp = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        if ($__sadminStamp !== $__curStamp) {
            $__sadminCache = [];
            $__sadminStamp = $__curStamp;
        }
        if (array_key_exists($userId, $__sadminCache)) {
            return $__sadminCache[$userId];
        }

        return $__sadminCache[$userId] = (function () use ($userId) {
            $user = getUserById($userId);
            if (!$user) {
                return null;
            }

            $role = is_object($user) ? ($user->role ?? '') : ($user['role'] ?? '');
            $adminId = is_object($user) ? ($user->admin_id ?? 0) : ($user['admin_id'] ?? 0);

            if ($role === 'admin') {
                return is_object($user) ? $user->id : $user['id'];
            }

            // Traverse up to find the sAdmin
            $curr = $user;
            while ($curr) {
                $currRole = is_object($curr) ? ($curr->role ?? '') : ($curr['role'] ?? '');
                if ($currRole === 'admin') {
                    return is_object($curr) ? $curr->id : $curr['id'];
                }

                $nextId = is_object($curr) ? ($curr->admin_id ?? 0) : ($curr['admin_id'] ?? 0);
                if (empty($nextId)) {
                    break;
                }

                $curr = getUserById($nextId);
            }

            return null;
        })();
    }
}

if (!function_exists('isPlatformSuperAdmin')) {
    /**
     * True when the role is the platform / SaaS owner (super_admin).
     */
    function isPlatformSuperAdmin(?string $role = null): bool
    {
        if ($role === null) {
            $role = function_exists('getSession') ? (string) getSession('user_role') : '';
        }

        return strtolower((string) $role) === \Config\Roles::PLATFORM;
    }
}

if (!function_exists('getOrgById')) {

    function getOrgById($id)
    {
        $userModel = model('App\Models\Registration');

        $user = $userModel->where('userid', $id)->first();

        // log_message('info', 'getOrgById user: ' . print_r($user, true));


        return $user ?? null;
    }
}

if (!function_exists('getUserByNumber')) {

    function getUserByNumber($number)
    {
        $db = db_connect();

        $user = $db->table('registrations')
            ->select('
                users.id        as user_id,
                users.mobile    as user_mobile,
                users.name      as user_name,
                registrations.id as registration_id,
                registrations.userid,
                registrations.mobile,
                registrations.reference_mobile
            ')
            ->join('users', 'users.id = registrations.userid', 'left')
            ->groupStart()
            ->where('users.mobile', $number)
            ->orWhere('registrations.mobile', $number)
            ->orWhere('registrations.reference_mobile', $number)
            ->groupEnd()
            ->get()
            ->getRowArray();

        return $user ?? null;
    }
}


/**
 * Get user by pppoe id
 */
if (!function_exists('getUserByPPPoEId')) {

    function getUserByPPPoEId($id, $router_id)
    {
        $userModel = model('App\Models\User');

        $user = $userModel->where(['router_id' => $router_id])->where(['pppoe_id' => $id])->first();

        return $user ?? null;
    }
}

/**
 * Get user's area
 */
if (!function_exists('getUserArea')) {
    function getUserArea($id)
    {

        $userModel = model('App\Models\User');

        $user = $userModel->find($id);

        if (!empty($user)) {

            $areaModel = model('App\Models\Area');

            $area = $areaModel->find($user->area_id);

            return $area ?? null;
        }

        return null;
    }
}
if (!function_exists('getEmpArea')) {
    function getEmpArea($id)
    {
        $userModel = model('App\Models\User');
        $areaModel = model('App\Models\Area');

        $user = $userModel->find($id);
        if (empty($user) || empty($user->area_id)) {
            return null;
        }

        // Check if multiple areas (comma-separated)
        $area_ids = is_array($user->area_id) ? $user->area_id : explode(',', $user->area_id);

        // Get all areas
        $areas = $areaModel->whereIn('id', $area_ids)->findAll();

        return $areas; // returns array of area objects
    }
}

if (!function_exists('getUserSubArea')) {
    function getUserSubArea($id)
    {

        if (!empty($id)) {
            log_message('info', 'getUserSubArea id: ' . print_r($id, true));
            $areaModel = model('App\Models\AreaSub');

            $area = $areaModel->find($id);
            log_message('info', 'getUserSubArea area: ' . print_r($area, true));

            return $area ?? null;
        }

        return null;
    }
}
/**
 * Get user's package
 */
if (!function_exists('getUserPackage')) {

    // function getUserPackage($id , $packageId=null)
    // {
    //     // Load the User model
    //     $userModel = model('App\Models\User');

    //     // Find the user by ID
    //     $user = $userModel->find($id);

    //     if (!empty($packageId)) {

    //         // Load the Package model
    //         $packageModel = model('App\Models\Package');

    //         // Find the package by the user's package ID
    //         $package = $packageModel->find($packageId);
    //         // log_message('info', 'User package details: ' . print_r($package, true));


    //         return $package ?? null;
    //     }


    //     $role = getSession('user_role');

    //     $ruser = $userModel->find($id);
    //     // log_message('info', 'ruser Detail Found: ' . print_r($ruser, true));

    //     $created_by = $ruser->created_by;

    //     // Log the retrieved reseller user data
    //     if ($created_by === 'resellerAdmin') {

    //         if (!empty($ruser)) {
    //             // Load the ResellerPackages model
    //             $packageModel = model('App\Models\ResellerPackages');
    //             // Find the package by the user's package ID
    //             $packages = $packageModel->find($ruser->package_id);

    //             if (is_object($packages)) {
    //                 $package_name = $packages->package_name ?? '--';
    //             } elseif (is_array($packages)) {
    //                 $package_name = $packages['package_name'] ?? '--';
    //             }
    //             // Load the AllResellerPackages model
    //             $allResellerPackagesModel = model('App\Models\allResellerPackage');

    //             // Check if any data exists in all_reseller_packages where user_id = $ruser->admin_id
    //             $allPackages = $allResellerPackagesModel->where('user_id', $ruser->admin_id)->first();

    //             // log_message('info', 'allPackages Detail Found: ' . print_r($allPackages, true));

    //             if ($allPackages) {
    //                 //log_message('info', 'its here........' );

    //                 // Decode the package_details JSON
    //                 $packageDetails = json_decode($allPackages['package_details'], true);
    //                 log_message('info', 'Decoded package_name details: ' . print_r($package_name, true));
    //                 // Find the package name in the decoded package details
    //                 $package = null;
    //                 foreach ($packageDetails as $packageDetail) {
    //                     if (isset($packageDetail['package_name']) && $packageDetail['package_name'] == $package_name) {
    //                         $packageDetail['bandwidth'] = $packages['bandwidth'] ?? '--';
    //                         $packageDetail['pricing_type'] = $packages['pricing_type'] ?? '--';
    //                         $package = $packageDetail;
    //                         break;
    //                     }
    //                 }

    //                 log_message('info', 'Package Detail Found: ' . print_r($package, true));

    //                 // Return the package if found
    //                 return $package ?? null;
    //             }

    //             $package = $packages;
    //             return $package ?? null;
    //         }

    //     } else {
    //         if (!empty($user)) {

    //             // Load the Package model
    //             $packageModel = model('App\Models\Package');

    //             // Find the package by the user's package ID
    //             $package = $packageModel->find($user->package_id);
    //             // log_message('info', 'User package details: ' . print_r($package, true));


    //             return $package ?? null;
    //         }
    //     }

    //     // Log that no package was found
    //     log_message('error', 'No package found for user ID: ' . $id);

    //     return null;
    // }

    function getUserPackage($id, $packageId = null, $packageName = null)
    {
        // Phase-E2: per-request static caches to eliminate N+1 on the customer grid.
        // allResellerPackage rows are loaded once per reseller per request.
        // User rows are cached to avoid re-fetching the same user within one request.
        static $__pkgCache  = [];  // [admin_id => rawPackages[]]
        static $__userCache = [];  // [user_id  => user object]
        static $__reqStamp  = null;
        $stamp = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        if ($__reqStamp !== $stamp) {
            $__pkgCache = $__userCache = [];
            $__reqStamp = $stamp;
        }

        $userModel = model('App\Models\User');
        if (!isset($__userCache[$id])) {
            $__userCache[$id] = $userModel->find($id);
        }
        $user = $__userCache[$id];

        if (!$user) {
            log_message('error', 'User not found for ID: ' . $id);
            return null;
        }

        $created_by = $user->created_by;

        if ($created_by === 'resellerAdmin') {
            $searchPackageId = $packageId ?? $user->package_id;
            if (empty($searchPackageId)) {
                return null;
            }

            $adminId = (int) $user->admin_id;
            if (!isset($__pkgCache[$adminId])) {
                $allResellerPackagesModel = model('App\Models\allResellerPackage');
                $__pkgCache[$adminId] = $allResellerPackagesModel->where('user_id', $adminId)->findAll();
            }
            $rawPackages = $__pkgCache[$adminId];

            foreach ($rawPackages as $package) {
                $detailsArr = is_string($package['package_details'])
                    ? json_decode($package['package_details'], true)
                    : $package['package_details'];

                if (!is_array($detailsArr)) {
                    continue;
                }

                foreach ($detailsArr as $details) {
                    if (
                        ((string) ($details['id'] ?? '') === (string) $searchPackageId)
                        && ($packageName === null || trim((string) ($details['package_name'] ?? '')) === trim((string) $packageName))
                    ) {
                        return [
                            'id' => $details['id'],
                            'package_name' => $details['package_name'] ?? '--',
                            'package_type' => $details['package_type'] ?? '--',
                            'bandwidth' => $details['bandwidth'] ?? '--',
                            'pricing_type' => $details['pricing_type'] ?? '--',
                            'price' => $details['price'] ?? '--',
                            'selling_price' => $details['selling_price'] ?? '--',
                            'preview' => $details['preview'] ?? '--',
                        ];
                    }
                }
            }

            // Fallback to ResellerPackages model if not found in allResellerPackage
            $resellerPkgModel = model('App\Models\ResellerPackages');
            $fallbackPkg = $resellerPkgModel->where('user_id', $user->admin_id)->find($searchPackageId);
            if ($fallbackPkg) {
                $fallbackPkgObj = (object) $fallbackPkg;
                return [
                    'id' => $fallbackPkgObj->id ?? '--',
                    'package_name' => $fallbackPkgObj->package_name ?? '--',
                    'package_type' => $fallbackPkgObj->pricing_type ?? '--',
                    'bandwidth' => $fallbackPkgObj->bandwidth ?? '--',
                    'pricing_type' => $fallbackPkgObj->pricing_type ?? '--',
                    'price' => $fallbackPkgObj->price ?? '--',
                    'selling_price' => $fallbackPkgObj->selling_price ?? ($fallbackPkgObj->price ?? '--'),
                    'preview' => $fallbackPkgObj->preview ?? '--',
                ];
            }

            // Strict reseller package enforcement: do not fallback to admin or other reseller packages.
            return null;
        }

        if (!empty($packageId)) {
            $packageModel = model('App\Models\Package');
            return $packageModel->find($packageId) ?? null;
        }

        $adminPackageModel = model('App\Models\AdminPackage');
        $pkg = $adminPackageModel->find($user->package_id);
        if (!$pkg) {
            $packageModel = model('App\Models\Package');
            $pkg = $packageModel->find($user->package_id);
        }
        return $pkg ?? null;
    }
}

/**
 * Get user's package
 */
function getResellerPackage($id)
{
    $userModel = model('App\Models\User');
    $userId = session()->get('user_id');
    $user = $userModel->find($id);

    if (!empty($user)) {
        $packageModel = model('App\Models\allResellerPackage'); // Check the model name and namespace
        if ($packageModel !== null) {
            $package = $packageModel->find($user->package_id);

            // Log the package details
            log_message('info', 'Package details: ' . print_r($package, true));

            return $package ?? null;
        } else {
            // Handle the case where the model could not be loaded
            log_message('error', 'ResellerPackages model could not be loaded.');
            return null;
        }
    }

    return null;
}
function getfund()
{

    // use App\models\ResellerFundingModel;
    // $userModel = model('App\Models\ResellerFundingModel'); 
    $userId = getSession('user_id');

    $userModel = model('App\Models\User');
    $details = $userModel->where(['id' => $userId])->first();
    $fund = $details->fund ?? '0';

    // $user = $userModel->where('customer', $userId)->first();
    // $amount = $user['amount'] ?? null;

    return $fund ?? '0';
}


function checkPaymentStatus($id, $lastPaymentMonth = null)
{
    $payment_model = model('App\Models\Payment');

    // Get current month name
    $currentMonth = $lastPaymentMonth ?? date('F'); // e.g., "September"

    // Fetch payment for this user and current month
    $details = $payment_model->where([
        'user_id' => $id,
        'month' => $currentMonth
    ])->first();

    // Return the status if available
    $fund = $details->status ?? ($details['status'] ?? null);
    log_message('info', 'Payment status: ' . 'id :' . $id . '...' . print_r($fund, true));
    return $fund ?? null;
}









function ResellerPackagePrice($Id, $status = null, $userId = null, $userRole = null, $packageName = null)
{
    if (empty($Id)) {
        return null;
    }

    $userId = $userId ?? session()->get('user_id');
    $userRole = $userRole ?? session()->get('user_role');

    // If the active user is a customer ('user'), the package is actually owned by their reseller
    if ($userRole === 'user') {
        $userModel = model('App\Models\User');
        $customer = $userModel->find($userId);
        if ($customer && $customer->created_by === 'resellerAdmin') {
            $userId = $customer->admin_id;
        }
    }

    $packageModel = model('App\Models\allResellerPackage');
    $rawPackages = $packageModel->where('user_id', $userId)->findAll();

    $packages = [];
    foreach ($rawPackages as $package) {
        $detailsArr = is_string($package['package_details'])
            ? json_decode($package['package_details'], true)
            : $package['package_details'];

        if (is_array($detailsArr)) {
            foreach ($detailsArr as $details) {
                $packages[] = $details;
            }
        }
    }

    $price = null;
    foreach ($packages as $package) {
        if (
            ((string) ($package['id'] ?? '') === (string) $Id)
            && ($packageName === null || trim((string) ($package['package_name'] ?? '')) === trim((string) $packageName))
        ) {
            $selling_price = (isset($package['selling_price']) && is_numeric($package['selling_price']) && $package['selling_price'] !== '--')
                ? $package['selling_price']
                : null;
            $cost_price = $package['price'] ?? null;

            // Prioritize reseller selling_price if available.
            $price = $selling_price ?? $cost_price;
            break;
        }
    }

    // Fallback to ResellerPackages model if not found in allResellerPackage
    if ($price === null) {
        $resellerPkgModel = model('App\Models\ResellerPackages');
        $fallbackPkg = $resellerPkgModel->where('user_id', $userId)->find($Id);
        if ($fallbackPkg) {
            $fallbackPkgObj = (object) $fallbackPkg;
            $selling_price = (isset($fallbackPkgObj->selling_price) && is_numeric($fallbackPkgObj->selling_price) && $fallbackPkgObj->selling_price !== '--')
                ? $fallbackPkgObj->selling_price
                : null;
            $cost_price = $fallbackPkgObj->price ?? null;
            $price = $selling_price ?? $cost_price;
        }
    }

    // Strict reseller package enforcement: if not found in the reseller package list, do not return a fallback price.
    return $price;
}
function ResellerPackagePreview($Id)
{
    $packageModel = new allResellerPackage();
    $userId = session()->get('user_id');
    log_message('info', 'User ID: ' . $Id);
    // Fetch packages for the specific user
    $rawPackages = $packageModel->where('user_id', $userId)->findAll();

    // Decode the package_details JSON field
    $packages = [];
    foreach ($rawPackages as $package) {
        $package['package_details'] = json_decode($package['package_details'], true);
        foreach ($package['package_details'] as $details) {
            $packages[] = $details;
        }
    } // use App\models\ResellerFundingModel;
    log_message('debug', 'Row filteredData: ' . print_r($packages, true));

    $price = null;

    foreach ($packages as $package) {
        if ($package['id'] == $Id) {
            $price = $package['preview'] ?? $package['preview'];
            break; // Stop loop after finding the first match
        }
    }



    return $price;
}

function getAdminPackage($id)
{
    $userModel = model('App\Models\User');
    $user = $userModel->find($id);

    if (!empty($user)) {
        $packageModel = model('App\Models\AdminPackage'); // Check the model name and namespace
        if ($packageModel !== null) {
            $package = $packageModel->find($user->package_id);

            // Log the package details
            log_message('info', 'Package details: ' . print_r($package, true));

            return $package ?? null;
        } else {
            // Handle the case where the model could not be loaded
            log_message('error', 'ResellerPackages model could not be loaded.');
            return null;
        }
    }

    return null;
}

// function getPPPoEUserStatus($id,$pppoe_id)
// {
//     // $userModel = model('App\Models\User');
//     // $details = $userModel->where(['id' => $id, 'role' => 'user'])->first();


//         $router_client = routerClient($id);

//         if (!is_array($router_client)) {
//             $user_ppp = getPPPoEUser($router_client, $pppoe_id);
//             $ppoe = $user_ppp[0]['disabled'] ?? '--';
//             return $ppoe;

//         }
//     $ppoe='true';

//     return $ppoe;
// }
// static $routerCache = [];
function getPPPoEUserStatus($id, $pppoe_id)
{
    // Static cache to store router_client for each id
    static $routerCache = [];

    // Check if router_client is already cached for the given id
    if (isset($routerCache[$id])) {
        $router_client = $routerCache[$id];
    } else {
        $router_client = routerClient($id);
        // Cache the router_client result by id
        $routerCache[$id] = $router_client;
    }

    log_message('info', 'getPPPoEUser.............');

    // Process the PPPoE user based on the cached router_client
    if (!is_array($router_client)) {
        $user_ppp = getPPPoEUser($router_client, $pppoe_id);
        $ppoe = $user_ppp[0]['disabled'] ?? '--';
        return $ppoe;
    }

    return 'true';
}

function fetchDataByAreaId($areaId)
{
    $subarea_model = model('App\Models\AreaSub');
    $data = $subarea_model
        ->select('*')
        ->where('user_id', $areaId)
        ->orderBy('id', 'desc');

    log_message('info', 'fetchDataByAreaId data: ' . print_r($data, true));

    return $data;
}


if (!function_exists('getPackagePrice')) {

    function getPackagePrice()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $totalPrice = 0;
        $ConnectionDetails = model('App\Models\ConnectionData');
        $userModel = model('App\Models\User');

        if ($userRole === 'admin') {
            $userIds = $userModel->select('id, package_id')->where('admin_id', $userId)->where('role', 'user')->findAll();
            if (empty($userIds)) {
                return 0;
            }

            // Batch fetch connection details to avoid N+1 queries
            $uIds = array_column($userIds, 'id');
            $connections = $ConnectionDetails->whereIn('user_id', $uIds)->findAll();
            $freeUserIds = [];
            foreach ($connections as $conn) {
                $bStatus = strtolower($conn->billing_status ?? $conn['billing_status'] ?? '');
                if ($bStatus === 'free') {
                    $freeUserIds[] = $conn->user_id ?? $conn['user_id'];
                }
            }

            // Load package prices in memory
            $resellerPackageSimpleModel = new Package();
            $rawPkgs = $resellerPackageSimpleModel->findAll();
            $pkgPriceMap = [];
            foreach ($rawPkgs as $pkg) {
                $pkgId = is_array($pkg) ? ($pkg['id'] ?? null) : ($pkg->id ?? null);
                $price = is_array($pkg) ? ($pkg['price'] ?? 0) : ($pkg->price ?? 0);
                if ($pkgId !== null) {
                    $pkgPriceMap[$pkgId] = (float) $price;
                }
            }

            foreach ($userIds as $user) {
                if (in_array($user->id, $freeUserIds)) {
                    continue;
                }
                if (!empty($user->package_id) && isset($pkgPriceMap[$user->package_id])) {
                    $totalPrice += $pkgPriceMap[$user->package_id];
                }
            }
        }
        if ($userRole === 'resellerAdmin') {
            $userIds = $userModel->select('id, package_id')->where('admin_id', $userId)->where('role', 'user')->findAll();
            if (empty($userIds)) {
                return 0;
            }

            // Batch fetch connection details to avoid N+1 queries
            $uIds = array_column($userIds, 'id');
            $connections = $ConnectionDetails->whereIn('user_id', $uIds)->findAll();
            $freeUserIds = [];
            foreach ($connections as $conn) {
                $bStatus = strtolower($conn->billing_status ?? $conn['billing_status'] ?? '');
                if ($bStatus === 'free') {
                    $freeUserIds[] = $conn->user_id ?? $conn['user_id'];
                }
            }

            // Load reseller package details in memory
            $resellerPackageSimpleModel = new allResellerPackage();
            $packagePriceObj = $resellerPackageSimpleModel->where('user_id', $userId)->first();
            $resellerPkgPriceMap = [];
            if ($packagePriceObj) {
                $packageDetails = json_decode($packagePriceObj['package_details'], true);
                if (is_array($packageDetails)) {
                    foreach ($packageDetails as $detail) {
                        if (isset($detail['id'])) {
                            $detailprice = (isset($detail['selling_price']) && is_numeric($detail['selling_price']) && $detail['selling_price'] > 0)
                                ? $detail['selling_price']
                                : (is_numeric($detail['price'] ?? null) ? $detail['price'] : 0);
                            $resellerPkgPriceMap[$detail['id']] = (float) $detailprice;
                        }
                    }
                }
            }

            foreach ($userIds as $user) {
                if (in_array($user->id, $freeUserIds)) {
                    continue;
                }
                if (!empty($user->package_id) && isset($resellerPkgPriceMap[$user->package_id])) {
                    $totalPrice += $resellerPkgPriceMap[$user->package_id];
                }
            }
        }

        return $totalPrice;
    }
}


if (!function_exists('getExpiredPackagePrice')) {

    function getExpiredPackagePrice()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        $totalPrice = 0;
        if ($userRole === 'admin') {


            $totalPrice = 0;
            $userModel = model('App\Models\User');
            // $details = $userModel->where(['admin_id' => $userId])->first();
            $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->where('subscription_status', 'inactive')->findAll();

            // $userRegister = $this->reseller_model->select('discount')->where('userid', $userId)->first();
            // log_message('info', 'Payment $userRegister: ' . json_encode($userRegister));
            // $discount = $userRegister['discount'] ?? 0;

            $packageIds = [];
            foreach ($userIds as $user) {
                $packageId = $userModel->select('package_id')->where('id', $user->id)->first();
                if ($packageId) {
                    $packageIds[] = $packageId;
                }
            }
            $resellerPackageSimpleModel = new Package();
            // $packagePrices = [];

            foreach ($packageIds as $packageId) {
                // log_message('info', 'Package IDs $packageId->package_id: ' . json_encode($packageId->package_id));

                $packagePrice = $resellerPackageSimpleModel->where('id', $packageId->package_id)->first();
                // log_message('info', 'Package IDs: packagePrice' . json_encode($packagePrice));

                if ($packagePrice) {
                    // $packagePrices[] = $packagePrice;
                    if (is_array($packagePrice)) {
                        $totalPrice += $packagePrice['price'];
                    } elseif (is_object($packagePrice)) {
                        $totalPrice += $packagePrice->price;
                    }
                    // log_message('info', 'Package IDs: totalPrice' . json_encode($totalPrice));

                }

                // foreach ($packagePrices as $packagePrice) {
                //     $packageDetails = json_decode($packagePrice['package_details'], true);
                //     foreach ($packageDetails as $detail) {
                //         if ($detail['id'] == $packageId->package_id) {
                //             // log_message('info', 'Package Price: ' . $detail['price']);

                //         $totalPrice += $detail['price'];
                //         // log_message('info', 'Total Price: ' . $totalPrice);
                //         }
                //     }
                // }
            }
        }
        if ($userRole === 'resellerAdmin') {
            $totalPrice = 0;
            $userModel = model('App\Models\User');
            // $details = $userModel->where(['admin_id' => $userId])->first();
            $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->where('subscription_status', 'inactive')->findAll();
            $reseller_model = new Registration();
            $userRegister = $reseller_model->select('discount')->where('userid', $userId)->first();
            // log_message('info', 'Payment $userRegister: ' . json_encode($userRegister));
            $discount = $userRegister['discount'] ?? 0;

            $packageIds = [];
            foreach ($userIds as $user) {
                $packageId = $userModel->select('package_id')->where('id', $user->id)->first();
                if ($packageId) {
                    $packageIds[] = $packageId;
                }
            }
            $resellerPackageSimpleModel = new allResellerPackage();
            $packagePrices = [];


            // log_message('info', 'Package IDs $packageId->package_id: ' . json_encode($packageId->package_id));

            $packagePrice = $resellerPackageSimpleModel->where('user_id', $userId)->first();
            // log_message('info', 'Package IDs: packagePrice' . json_encode($packagePrice));

            if ($packagePrice) {
                $packagePrices[] = $packagePrice;
            }
            foreach ($packageIds as $packageId) {


                foreach ($packagePrices as $packagePrice) {
                    // log_message('info', 'Total Price: ' . $totalPrice);
                    $packageDetails = json_decode($packagePrice['package_details'], true);
                    foreach ($packageDetails as $detail) {
                        if ($detail['id'] == $packageId->package_id) {
                            // log_message('info', 'Package Price: ' . $detail['price']);
                            $detailprice = $detail['selling_price'] ?? $detail['price'] ?? 0;
                            $totalPrice += (int) $detailprice;
                        }
                    }
                }
            }
            // if ($discount > 0) {
            //     $totalPrice = $totalPrice - ($totalPrice * ($discount / 100));
            //     log_message('info', 'Total Price after discount: ' . $totalPrice);
            // }


        }

        return $totalPrice ?? null;
    }
}

/**
 * Get Reseller's package
 */
if (!function_exists('getResellersPackage')) {

    function getResellersPackage($id)
    {

        $userModel = model('App\Models\User');

        $user = $userModel->find($id);

        if (!empty($user)) {

            $packageModel = model('App\Models\ResellerPackages');

            $package = $packageModel->find($user->package_id);

            // if ($package) {
            // 	echo $package['package_name'];
            // } else {
            // 	echo 'Package not found';
            // }


            return $package ?? null;
        }

        return null;
    }
}

if (!function_exists('getAllCostomer')) {

    function getAllCostomer()
    {

        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');



        $details = $userModel->where(['id' => $userId])->first();

        $role = $details->role;
        if ($role === 'employee') {
            $created_by = $details->created_by;
            if ($created_by === 'resellerAdmin') {
                $userId = $details->admin_id;
                $detail = $userModel->where(['id' => $userId])->first();
                $admin_id = $detail->admin_id;

                $details = $userModel->where(['id' => $admin_id])->first();
                $userId = $admin_id;
            } else {
                // $detail = $userModel->where(['id' => $userId])->first();
                $admin_id = $details->admin_id;
                $details = $userModel->where(['id' => $admin_id])->first();
            }
        }
        if ($role === 'resellerAdmin') {
            $detail = $userModel->where(['id' => $userId])->first();
            $admin_id = $detail->admin_id;

            $details = $userModel->where(['id' => $admin_id])->first();
            $userId = $admin_id;
        }

        // log_message('info', 'Fetched count details: ' . json_encode($details));


        helper('subscription');
        $accountBlocked = ($details->status === 'inactive')
            || ($details->conn_status != 'conn' && !hasPendingPackageChange($details))
            || (isSAdminSubscriptionExpired($details) && ($details->role ?? '') === 'admin')
            || (($details->subscription_status === 'inactive')
                && ($details->role ?? '') !== 'admin'
                && !hasPendingPackageChange($details));

        if ($accountBlocked) {
            $data = [
                'msz' => 'Your account is not active.Update your account to create new customer',
            ];
        }

        $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', "resellerAdmin")->findAll();
        $Customers = [];
        foreach ($userIds as $user) {
            $Customer = $userModel->where('admin_id', $user->id)->countAllResults();
            if ($Customer) {
                $Customers[] = $Customer;
            }
        }

        // Add the customers count to the total count
        $totalCustomerCount = array_sum($Customers);

        $user_model = model('App\Models\User');
        $count = $user_model->builder()
            ->where('role', 'user')
            ->where('admin_id', $userId)
            ->countAllResults();

        // log_message('info', 'Fetched count data: ' . json_encode($count));

        $count += $totalCustomerCount;

        $AdminPackage = model('App\Models\AdminPackage');
        $package = $AdminPackage->select('duration')
            ->where('id', $details->package_id)
            ->first();

        // log_message('info', 'Fetched count userIds: ' . json_encode($userIds));

        // log_message('info', 'Fetched count Customers: ' . json_encode($Customers));

        // log_message('info', 'Fetched count data: ' . json_encode($count));
        // log_message('info', 'Fetched package data: ' . json_encode($package));

        // if ($count == $package['duration'] || $count > $package['duration']) {


        //     return $package ?? null;
        // }



        return ['count' => $count, 'package' => $package];
    }
}
/**
 * Get user's access permission
 */
// if (!function_exists('userHasPermission')) {
//     function userHasPermission($menu, $sub_menu = null, $role = null, $user_id = null, $default = null)
//     {

//         $role = $role ?? getSession('user_role');

//         $user_id = $user_id ?? getSession('user_id');

//         /**
//          * Admin has all permissions
//          */
//         if ($role === 'super_admin')return true;
//         // if ($role === 'super_admin') return false;


//         $model = model('App\Models\CustomAccess');

//         $permission = $model->where(['user_id' => $user_id, 'status' => 'active'])->first();

//         // $srole = getSession('user_role');

//         if ($default === 'special_default') {
//             if ($role === 'admin') {
//                 // log_message('debug', 'access permission sAdmin: ' );

//                 $model = model('App\Models\Permission');
//                 // Check if $role is 'resellerAdmin' and set it to 'reseller'

//                 $permission = $model->where([
//                     'user_id' => $user_id,
//                     'user_type' => $role
//                 ])->first();


//                 // log_message('debug', 'access permission Details: ' . print_r($permission, true));

//             }
//         }

//         /**
//          * If no specific permission then get the default one
//          */
//         if (empty($permission)) {
//             log_message('debug', 'access permission empty: ' );

//             $model = model('App\Models\Permission');
//             // Check if $role is 'resellerAdmin' and set it to 'reseller'


//             if ($role === 'admin') {
//                 log_message('debug', 'access permission sAdmin: ' );

//                 // $model = model('App\Models\Permission');
//                 // Check if $role is 'resellerAdmin' and set it to 'reseller'

//                 $permission = $model->where([
//                     'user_id' => 2,
//                     'user_type' => $role
//                 ])->first();


//                 // log_message('debug', 'access permission Details: ' . print_r($permission, true));

//             }
//             if ($role === 'resellerAdmin') {

//                 // $model = model('App\Models\Permission');
//                 // Check if $role is 'resellerAdmin' and set it to 'reseller'
//                 $userId = session()->get('user_id');

//                 $userModel = model('App\Models\User');
//                 $details = $userModel->where(['id' => $userId])->first();
//                 $admins_id = $details->admin_id ?? 3 ;
//                 log_message('debug', 'access permission rsllerAdmin: details '.print_r($details ,true));
//                 log_message('debug', 'user: resellerAdmin ' . print_r($admins_id, true));

//                 $permission = $model->where([
//                     'user_id' => $admins_id,
//                     'user_type' => $role
//                 ])->first();


//                 // log_message('debug', 'access permission rsllerAdmin: ' . print_r($permission, true));

//             }
//             if ($role === 'user') {

//                 $userModel = model('App\Models\User');
//                 $details = $userModel->where(['id' => $user_id])->first();
//                 $admin_id = $details->admin_id ?? 2;

//                 if ($details->role === 'resellerAdmin') {
//                     $details = $userModel->where(['id' => $admin_id])->first();
//                     $admin_id = $details->admin_id ?? '2';
//                     log_message('debug', 'user: user ' . print_r($admin_id, true));

//                     $permission = $model->where(['user_id' => $admin_id, 'user_type' => $role])->first();

//                 }
//                 log_message('debug', 'user: ' . print_r($permission, true));

//                 $permission = $model->where(['user_id' => $admin_id, 'user_type' => $role])->first();


//             }
//         }

//         if (!empty($permission->permissions)) {
//             log_message('debug', 'access permission not empty: ' );

//             $data = json_decode($permission->permissions, true);

//             if (empty($sub_menu)) {

//                 return array_key_exists($menu, $data);
//             }
//             // $result = array_key_exists($menu, $data) ? in_array($sub_menu, $data[$menu]) : false;

//             // log_message('debug', 'result: result ' . print_r($result, true));

//             return array_key_exists($menu, $data) ? in_array($sub_menu, $data[$menu]) : false;
//         }

//         return false;
//     }
// }

// if (!function_exists('userHasPermission')) {
// 	function userHasPermission($menu, $sub_menu = null, $role = null, $user_id = null)
// 	{

// 		$role = $role ?? getSession('user_role');

// 		$user_id = $user_id ?? getSession('user_id');

// 		/**
// 		 * Admin has all permissions
// 		 */
// 		if ($role === 'super_admin') return true;

// 		$model = model('App\Models\CustomAccess');

// 		$permission = $model->where(['user_id' => $user_id, 'status' => 'active'])->first();

// 		/**
// 		 * If no specific permission then get the default one
// 		 */
// 		if (empty($permission)) {

// 			$model = model('App\Models\Permission');

// 			$permission = $model->where(['user_type' => $role])->first();

// 		}

// 		if (!empty($permission->permissions)) {

// 			$data = json_decode($permission->permissions, true);
//             // log_message('debug', 'Permission data: ' . print_r($data, true));

// 			if (empty($sub_menu)) {

// 				return array_key_exists($menu, $data);
// 			}

// 			return array_key_exists($menu, $data) ? in_array($sub_menu, $data[$menu]) : false;
// 		}

// 		return false;
// 	}
// }


// function deleteWriteAbleLogs()
// {
//     $writableLogDir = WRITEPATH . 'logs';
//     $writableBackupsDir = WRITEPATH . 'backups';

//     $logKeepDays = 3;
//     $backupKeepDays = 30;

//     // --- Delete old log files ---
//     if (is_dir($writableLogDir)) {
//         $logFiles = glob($writableLogDir . '/log-*.log');
//         $logCutoffDate = new DateTime("-{$logKeepDays} days");

//         foreach ($logFiles as $file) {
//             if (is_file($file) && preg_match('/log-(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
//                 $fileDate = DateTime::createFromFormat('Y-m-d', $matches[1]);

//                 if ($fileDate && $fileDate < $logCutoffDate) {
//                     log_message('info', 'Deleting old log file: ' . $file);
//                     // unlink($file); // Uncomment to delete
//                 } else {
//                     log_message('info', 'Keeping recent log file: ' . $file);
//                 }
//             }
//         }

//         log_message('info', 'Log cleanup completed.');
//     } else {
//         log_message('error', 'Logs directory does not exist: ' . $writableLogDir);
//     }

//     // --- Delete old backup files ---
//     if (is_dir($writableBackupsDir)) {
//         $backupFiles = glob($writableBackupsDir . '/isppaybd_isp_*.sql');
//         $backupCutoffDate = new DateTime("-{$backupKeepDays} days");

//         foreach ($backupFiles as $file) {
//             if (is_file($file) && preg_match('/isppaybd_isp_(\d{8})_\d{6}\.sql$/', basename($file), $matches)) {
//                 $fileDate = DateTime::createFromFormat('Ymd', $matches[1]);

//                 if ($fileDate && $fileDate < $backupCutoffDate) {
//                     log_message('info', 'Deleting old backup file: ' . $file);
//                     // unlink($file); // Uncomment to delete
//                 } else {
//                     log_message('info', 'Keeping recent backup file: ' . $file);
//                 }
//             }
//         }

//         log_message('info', 'Backup cleanup completed.');
//     } else {
//         log_message('error', 'Backups directory does not exist: ' . $writableBackupsDir);
//     }
// }





if (!function_exists('userHasPermission')) {
    function userHasPermission($menu, $sub_menu = null, $role = null, $user_id = null, $admin_id = null)
    {

        $role = $role ?? getSession('user_role');

        $user_id = $user_id ?? getSession('user_id');

        $admin_id = $admin_id ?? getSession('admin_id');

        $status = getSession('status');

        // Per-request memoization: the sidebar invokes this ~25x per page render.
        // Cache the final decision and (below) the two identical permission-table
        // lookups, collapsing dozens of duplicate queries into one each per request.
        static $__permCache = [];
        $__k = $role . '|' . $menu . '|' . $sub_menu . '|' . $user_id . '|' . $admin_id . '|' . $status;
        if (array_key_exists($__k, $__permCache)) {
            return $__permCache[$__k];
        }

        if ($status === 'inactive') {
            return $__permCache[$__k] = false;
        }

        // dd($admin_id);


        /**
         * Admin has all permissions
         */
        if (strtolower($role) === 'super_admin')
            return $__permCache[$__k] = true;

        // L2 cross-request cache (Phase 2 / C1): cache the final decision keyed by
        // the same inputs as the per-request memo, mixed with a version stamp that
        // Permission/CustomAccess model writes bump (afterInsert/Update/Delete). A
        // 30s TTL bounds rarer factors (e.g. sAdmin reassignment). FAIL-SAFE: any
        // cache fault falls through to a fresh compute, so the cache can never
        // grant or deny access on its own.
        $__l2Key = 'perm_' . permissionCacheVersion() . '_' . md5($__k);
        try {
            $__l2Hit = cache($__l2Key);
            if ($__l2Hit !== null) {
                return $__permCache[$__k] = (bool) $__l2Hit;
            }
        } catch (\Throwable $e) {
            // fall through to a fresh compute
        }

        $hasPermission = false;

        // 1. First, check Default Access based on role
        $model = model('App\Models\Permission');
        $defaultPermission = null;

        // Memoized default-permission row: identical for every call sharing the
        // same role + resolved sAdmin id, so it is fetched at most once per request.
        static $__defCache = [];
        if ($role === 'admin') {
            // Tenants check default permissions from the platform owner (user_id = 2)
            $defaultPermission = $__defCache['admin|2'] ??= $model->where(['user_type' => 'admin', 'user_id' => 2])->first();
        } else {
            // resellers, employees, and users check their sAdmin's given permissions based on their role
            $sAdminId = getSAdminIdForUser($user_id);
            $__dk = $role . '|' . $sAdminId;
            $defaultPermission = $__defCache[$__dk] ??= $model->where(['user_type' => $role, 'user_id' => $sAdminId])->first();
        }

        if (!empty($defaultPermission->permissions)) {
            $data = json_decode($defaultPermission->permissions, true);
            if (empty($sub_menu)) {
                $hasPermission = array_key_exists($menu, $data);
            } else {
                $hasPermission = array_key_exists($menu, $data) ? in_array($sub_menu, $data[$menu]) : false;
            }
        }

        // 2. If they don't have access by default, then check Custom Access
        if (!$hasPermission) {
            $customModel = model('App\Models\CustomAccess');
            // Memoized custom-access row: identical for every call sharing the same user.
            static $__customCache = [];
            $__ck = (string) $user_id;
            $customPermission = $__customCache[$__ck] ??= $customModel->where(['user_id' => $user_id, 'status' => 'active'])->first();

            if (!empty($customPermission->permissions)) {
                $customData = json_decode($customPermission->permissions, true);
                if (empty($sub_menu)) {
                    $hasPermission = array_key_exists($menu, $customData);
                } else {
                    $hasPermission = array_key_exists($menu, $customData) ? in_array($sub_menu, $customData[$menu]) : false;
                }
            }
        }

        try {
            cache()->save($__l2Key, $hasPermission ? 1 : 0, 30);
        } catch (\Throwable $e) {
            // caching is best-effort
        }

        return $__permCache[$__k] = $hasPermission;
    }
}

/** Version stamp mixed into L2 permission cache keys; bumped on any perm write. */
if (!function_exists('permissionCacheVersion')) {
    function permissionCacheVersion(): int
    {
        try {
            $v = cache('perm_cache_version');

            return $v === null ? 0 : (int) $v;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

/** Invalidate every L2-cached permission decision by advancing the version stamp. */
if (!function_exists('bumpPermissionCacheVersion')) {
    function bumpPermissionCacheVersion(): void
    {
        try {
            cache()->save('perm_cache_version', permissionCacheVersion() + 1, 2592000);
        } catch (\Throwable $e) {
            // best-effort; the 30s TTL on cached decisions bounds any miss
        }
    }
}

/**
 * Calculate package expire date
 */

if (!function_exists('calPackageExpireDate')) {
    function calPackageExpireDate($id, $from, $userId = null, $duration = null)
    {
        log_message('info', "[calPackageExpireDate Debug] Initiated: Package ID = {$id}, From = {$from}, User ID = " . json_encode($userId) . ", Duration = " . json_encode($duration));
        if ($duration !== null && is_numeric($duration)) {
            $expiry = date('Y-m-d H:i:s', strtotime("+$duration days", strtotime($from)));
            log_message('info', "[calPackageExpireDate Debug] Custom duration {$duration} passed. Expiry resolved: {$expiry}");
            return $expiry;
        }
        if (empty($userId)) {
            $userId = session()->get('user_id');
            log_message('info', "[calPackageExpireDate Debug] User ID fallback to session: " . json_encode($userId));
        }

        $userModel = model('App\Models\User');
        $details = !empty($userId) ? $userModel->find($userId) : null;
        log_message('info', "[calPackageExpireDate Debug] Resolved user details: " . ($details ? 'Found' : 'Not Found'));

        $role = '';
        $createdBy = '';
        if ($details) {
            $role = is_object($details) ? ($details->role ?? '') : ($details['role'] ?? '');
            $createdBy = is_object($details) ? ($details->created_by ?? '') : ($details['created_by'] ?? '');
            log_message('info', "[calPackageExpireDate Debug] Role: {$role}, Created By: {$createdBy}");
        }

        if (getSession('user_role') === 'admin' || $role === 'admin') {
            // Respect the SaaS plan's billing period (weekly/monthly/yearly)
            // instead of the old unconditional +1 month.
            $interval = '+1 month';
            $sAdminPackageId = $details ? (is_object($details) ? ($details->package_id ?? null) : ($details['package_id'] ?? null)) : null;
            if (!empty($sAdminPackageId)) {
                $adminPackage = model('App\Models\AdminPackage')->find($sAdminPackageId);
                $adminPricing = is_object($adminPackage) ? ($adminPackage->pricing_type ?? '') : ($adminPackage['pricing_type'] ?? '');
                if ($adminPricing === 'weekly') {
                    $interval = '+7 days';
                } elseif ($adminPricing === 'yearly') {
                    $interval = '+1 year';
                }
            }
            $expiry = date('Y-m-d H:i:s', strtotime($interval, strtotime($from)));
            log_message('info', "[calPackageExpireDate Debug] User is sAdmin. Expiry resolved: {$expiry} ({$interval})");
            return $expiry;
        }

        $model = model('App\Models\Package');

        if (getSession('user_role') === 'resellerAdmin' || $role === 'resellerAdmin') {
            $model = model('App\Models\ResellerPackages');
            log_message('info', "[calPackageExpireDate Debug] Switching package model to ResellerPackages (based on session/user role).");
        }

        if ($createdBy === 'resellerAdmin') {
            $model = model('App\Models\ResellerPackages');
            log_message('info', "[calPackageExpireDate Debug] Switching package model to ResellerPackages (based on created_by resellerAdmin).");
        }

        $package = $model->find($id);

        if (empty($package)) {
            log_message('info', "[calPackageExpireDate Debug] Package ID {$id} not found in model " . get_class($model) . ". Trying fallback model.");
            // Check fallback model if package is not found
            if ($model instanceof \App\Models\ResellerPackages) {
                $fallbackModel = model('App\Models\Package');
            } else {
                $fallbackModel = model('App\Models\ResellerPackages');
            }
            $package = $fallbackModel->find($id);
        }

        if (empty($package)) {
            log_message('warning', "[calPackageExpireDate Debug] Package ID {$id} not found in either standard or reseller package models. Falling back to +1 month.");
            return date('Y-m-d H:i:s', strtotime('+1 month', strtotime($from)));
        }

        $expiry = null;

        $pricingType = is_object($package) ? ($package->pricing_type ?? '') : ($package['pricing_type'] ?? '');
        log_message('info', "[calPackageExpireDate Debug] Package found: " . (is_object($package) ? ($package->package_name ?? 'Unknown') : ($package['package_name'] ?? 'Unknown')) . ". Pricing Type: {$pricingType}");

        switch ($pricingType) {
            case 'weekly':
                $expiry = date('Y-m-d H:i:s', strtotime('+7 days', strtotime($from)));
                break;

            case 'monthly':
                $expiry = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($from)));
                break;

            case 'yearly':
                $expiry = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($from)));
                break;

            default:
                $expiry = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($from)));
                break;
        }

        log_message('info', "[calPackageExpireDate Debug] Calculated expiry: {$expiry}");
        return $expiry;
    }
}

// if (!function_exists('calResellerPackageExpireDate')) {
//     function calResellerPackageExpireDate($id, $from)
//     {
//         log_message('info', 'calResellerPackageExpireDate function called with ID: ' . $id . ' and Date: ' . $from);

//         $resellermodel = model('App\Models\ResellerPackages');
//         $resellerPackage = $resellermodel->find($id);

//         if (!$resellerPackage) {
//             log_message('error', 'No reseller package found with ID: ' . $id);
//             return null;
//         }

//         log_message('info', 'Reseller Package Retrieved: ' . print_r($resellerPackage, true));

//         $expiry = null;

//         // Assuming preview contains the number of days for expiry
//         $previewValue = is_array($resellerPackage) ? $resellerPackage['pricing_type'] : $resellerPackage->pricing_type;

//         // Calculate the expiry date based on the preview value (assuming it's in days)
//         $expiry = date('Y-m-d H:i:s', strtotime('+' . intval($previewValue) . ' days', strtotime($from)));

//         // Log the calculated expiry date
//         log_message('info', 'Calculated Expiry Date based on preview: ' . $expiry);

//         //return $expiry;
//     }
// }


if (!function_exists('calsAdminPackageExpireDate')) {
    function calsAdminPackageExpireDate($id, $from)
    {
        log_message('info', 'calResellerPackageExpireDate function called with ID: ' . $id . ' and Date: ' . $from);

        $resellermodel = model('App\Models\AdminPackage');
        $resellerPackage = $resellermodel->find($id);

        if (!$resellerPackage) {
            log_message('error', 'No reseller package found with ID: ' . $id);
            return null;
        }

        log_message('info', 'Reseller Package Retrieved: ' . print_r($resellerPackage, true));

        $expiry = null;

        // Assuming preview contains the number of days for expiry
        $previewValue = is_array($resellerPackage) ? $resellerPackage['preview'] : $resellerPackage->preview;

        // Calculate the expiry date based on the preview value (assuming it's in days)
        $expiry = date('Y-m-d H:i:s', strtotime('+' . intval($previewValue) . ' days', strtotime($from)));

        // Log the calculated expiry date
        log_message('info', 'Calculated Expiry Date based on preview: ' . $expiry);

        return $expiry;
    }
}

function Send_SMs($data, $id, $router_action = null, $Password = null, $msz = null, $insertedId = null)
{
    try {
        log_message('info', 'data message body: ' . print_r($data, true));

        $pppoeName = is_object($router_action) ? ($router_action->pppoe_name ?? '--') : ($router_action['pppoe_name'] ?? '--');
        $pppoePass = is_object($router_action) ? ($router_action->pppoe_password ?? '--') : ($router_action['pppoe_password'] ?? '--');
        $pass = $Password ?? (is_object($data) ? ($data->password ?? "--") : ($data['password'] ?? "--"));

        $AdminpackageModel = new AdminPackage();
        // Resolve the actual USER (customer) ID for tracking and settings
        $userId = is_object($data) ? ($data->id ?? $data->user_id ?? '--') : ($data['id'] ?? $data['user_id'] ?? $data[0]['id'] ?? $data[0]['user_id'] ?? '--');

        // Resolve the OWNER (admin) ID for the sms_messages table
        $smsOwnerId = session()->get('user_id');
        if (is_object($data) && !empty($data->admin_id)) {
            $smsOwnerId = $data->admin_id;
        } elseif (is_array($data) && !empty($data['admin_id'])) {
            $smsOwnerId = $data['admin_id'];
        }

        $package_id = is_object($data) ? ($data->package_id ?? '--') : ($data['package_id'] ?? $data[0]['package_id'] ?? '--');
        $Adminpackages = $AdminpackageModel->where(['id' => $package_id])->findAll();
        $will_expire = is_object($data) ? ($data->will_expire ?? '--') : ($data['will_expire'] ?? $data[0]['will_expire'] ?? '--');
        $PackageAmount = '--';
        $created_by = is_object($data) ? ($data->created_by ?? '--') : ($data['created_by'] ?? $data[0]['created_by'] ?? '--');
        $admin_id = is_object($data) ? ($data->admin_id ?? 0) : ($data['admin_id'] ?? $data[0]['admin_id'] ?? 0);

        // Access the data
        $name = is_object($data) ? ($data->name ?? '--') : ($data['name'] ?? $data[0]['name'] ?? '--');
        $mobile = is_object($data) ? ($data->mobile ?? '--') : ($data['mobile'] ?? $data[0]['mobile'] ?? '--');
        $email = is_object($data) ? ($data->email ?? '--') : ($data['email'] ?? $data[0]['email'] ?? '--');
        $amount = is_object($data) ? ($data->amount ?? '--') : ($data['amount'] ?? $data[0]['amount'] ?? '--');
        $month = is_object($data) ? ($data->month ?? '--') : ($data['month'] ?? $data[0]['month'] ?? '--');
        $user_id = is_object($data) ? ($data->user_id ?? '--') : ($data['user_id'] ?? $data[0]['user_id'] ?? '--');

        if ($created_by === 'resellerAdmin') {
            // Use ResellerPackagePrice to get selling_price (preferred) or price
            $PackageAmount = ResellerPackagePrice($package_id, null, (int) $admin_id, 'resellerAdmin') ?? '--';
        } else {
            if (!empty($Adminpackages)) {
                $firstPkg = $Adminpackages[0];
                $PackageAmount = is_object($firstPkg) ? ($firstPkg->price ?? '--') : ($firstPkg['price'] ?? '--');
            }

            // NEW FALLBACK: Try the regular Package model if AdminPackage didn't work
            if ($PackageAmount === '--' && !empty($package_id) && $package_id !== '--') {
                $packageModel = model('App\Models\Package');
                $regularPkg = $packageModel->find($package_id);
                if ($regularPkg) {
                    $PackageAmount = is_object($regularPkg) ? ($regularPkg->price ?? '--') : ($regularPkg['price'] ?? '--');
                }
            }
        }

        // FALLBACK: If amount is missing (manual send), use PackageAmount
        if ($amount === '--' || empty($amount) || (is_numeric($amount) && (float) $amount === 0.0)) {
            $amount = $PackageAmount;
        }

        log_message('info', "SMS DEBUG: PackageID: $package_id, CreatedBy: $created_by, AdminID: $admin_id, ResolvedPackageAmount: $PackageAmount, FinalAmount: $amount");

        $current_url = 'https://easybilpay.com/';
        // Fetch the template model
        // $payment_model = model('App\Models\Payment');
        // $payment_model_data = $payment_model->where('user_id', $user_id)->first();
        // $InvoiceID=$payment_model_data->invoice ??$payment_model_data['invoice'] ??'--';
        // $amount=$payment_model_data->amount ??$payment_model_data['amount'] ??'--';
        // Check if $id is not empty
        $user = null;

        if (!empty($id)) {
            $template_model = model('App\Models\SmsTemplateModel');
            $user = $template_model->where('id', $id)->first();

            // Check if the user exists
            if (!$user) {
                log_message('error', "No template found with ID: $id");
                // return false;
            }
        } else {
            log_message('error', "Template ID is empty.");
            // return false;
        }


        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $user_id])->first();

        log_message('info', 'details message body: ' . print_r($details, true));

        $dataFallbackName = is_object($data) ? ($data->name ?? '--') : (is_array($data) ? ($data[0]['name'] ?? '--') : '--');
        $dataFallbackMobile = is_object($data) ? ($data->mobile ?? '--') : (is_array($data) ? ($data[0]['mobile'] ?? '--') : '--');
        $empName = ($details && is_object($details) ? ($details->name ?? null) : (is_array($details) ? ($details['name'] ?? null) : null)) ?? $dataFallbackName;
        log_message('info', 'details message body: ' . print_r($empName, true));
        // $names=$empName;
        $mobilee = ($details && is_object($details) ? ($details->mobile ?? null) : (is_array($details) ? ($details['mobile'] ?? null) : null)) ?? $dataFallbackMobile;
        $CompanyName = getSetting('app_name', '', is_numeric($userId) ? $userId : null);
        // Check if the record exists

        // Extract the message body
        // Extract the message body safely
        $message_body = $msz;
        if (is_object($user) && isset($user->message_body)) {
            $message_body = $user->message_body;
        } elseif (is_array($user) && isset($user['message_body'])) {
            $message_body = $user['message_body'];
        }

        // Example data to replace placeholders
        $replacementData = [
            'EmployeeName' => ($name !== '--' ? $name : null) ?? $empName,
            'Mobile' => ($mobile !== '--' ? $mobile : null) ?? $mobilee,
            'Email' => $email,
            'Message' => $msz,
            'PackageAmount' => ($PackageAmount !== '--' ? $PackageAmount : null) ?? $amount,
            'will_expire' => $will_expire,
            'CustomerName' => ($name !== '--' ? $name : null) ?? $empName,
            'c_name' => ($name !== '--' ? $name : null) ?? $empName,
            'ClientCode' => $pppoeName,
            'UserName' => '--',
            'Password' => $pppoePass ?? $pass,
            'LoginUserName' => $email,
            'LoginPassword' => $pass,
            'BaseSiteURL' => $current_url,
            'PaidAmount' => $amount,
            'PaymentAmount' => $amount,
            'Amount' => $amount,
            'MonthName' => $month,
            'CompanyName' => $CompanyName,
            'company_name' => $CompanyName,
            'CompanyMobile' => getSetting('company_cell', getSetting('support_mobile', getSetting('company_phone', '01628856735', is_numeric($userId) ? $userId : null), is_numeric($userId) ? $userId : null), is_numeric($userId) ? $userId : null),
            'company_cell' => getSetting('company_cell', getSetting('support_mobile', getSetting('company_phone', '01628856735', is_numeric($userId) ? $userId : null), is_numeric($userId) ? $userId : null), is_numeric($userId) ? $userId : null),
            'CompanyEmail' => getSetting('webmaster_email', 'info@isppaybd.com', is_numeric($userId) ? $userId : null),
            'CompanyAddress' => getSetting('company_address', 'Bangladesh', is_numeric($userId) ? $userId : null),
        ];

        log_message('info', 'replacementData message body: ' . print_r($replacementData, true));

        $message_body_new = $message_body;

        // Replace placeholders in the message body
        foreach ($replacementData as $key => $val) {
            $message_body_new = str_ireplace(["{{{$key}}}", "{{$key}}"], $val ?? '', $message_body_new);
        }

        // Optionally decode the message body if needed
        // $message_body_new = urldecode($message_body_new);

        // Log the updated message body for verification
        log_message('info', 'Updated message body: ' . $message_body_new);

        // ── Auto-create sms_messages row if Send_SMs was called without a pre-inserted ID ──
        $sms_model = model('App\\Models\\Sms');

        if (empty($insertedId)) {
            // Determine the owner user_id: prefer explicit info, then data array, then session
            $smsOwnerId = null;
            if (is_numeric($data) && $data !== '--') {
                $smsOwnerId = (int) $data;
            } elseif (is_array($data)) {
                $smsOwnerId = $data['admin_id'] ?? $data['id'] ?? null;
            } elseif (is_object($data)) {
                $smsOwnerId = $data->admin_id ?? $data->id ?? null;
            }

            if (empty($smsOwnerId)) {
                $smsOwnerId = session()->get('user_id');
            }

            if (!empty($smsOwnerId)) {
                $insertedId = $sms_model->insert([
                    'user_id' => $smsOwnerId,
                    'datetime' => date('Y-m-d H:i:s'),
                    'content' => $message_body_new,
                    'send_to' => $mobilee ?? $mobile ?? '--',
                    'status' => 'pending',
                    'logs' => 'Sending…',
                    'gateway' => '',
                ]);
            }
        }

        // Send the SMS notification with the replaced message body
        try {
            $result = sendSms($mobilee ?? $mobile, $message_body_new, is_numeric($userId) ? $userId : null);

            if (!empty($insertedId)) {
                $updateData = [
                    'content' => $message_body_new,
                    'send_to' => $mobilee ?? $mobile ?? '--',
                    'status' => $result['status'] ?? 'failed',
                    'logs' => $result['logs'] ?? 'No response',
                    'gateway' => $result['gateway'] ?? '',
                    'sender_number' => $result['sender_number'] ?? '',
                    'message_id' => $result['message_id'] ?? '',
                ];
                $sms_model->update($insertedId, $updateData);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Exception caught while sending SMS: ' . $e->getMessage());
            if (!empty($insertedId)) {
                $sms_model->update($insertedId, [
                    'status' => 'failed',
                    'logs' => 'Exception: ' . $e->getMessage(),
                ]);
            }
            $result = false;
        }
        log_message('info', 'Updated message result: ' . print_r($result, true));

        // Optional: Send email notification if required
        try {
            $emailData = [
                'user' => $name ?? $empName ?? '--',
                'expire_date' => $will_expire ?? '--',
            ];
            sendMail(
                $email,
                getSetting('app_name', '', is_numeric($userId) ? $userId : null) . ' | Subscription Expired',
                view('emails/subscription-expired', $emailData),
            );
        } catch (\Throwable $e) {
            log_message('error', 'Email exception caught in Send_SMs: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }

        // $result = $sms->sendMessage($to, $message);

        // Return the result
        return $result;
    } catch (\Throwable $e) {
        log_message('error', "Send_SMs Critical Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        return false;
    }
}

// if (!function_exists('generateReceiptPDF')) {
// function generateReceiptPDF($userData, $orderData)
// {
//     // Create new PDF document
//     $pdf = new TCPDF('P', 'mm', [56.33, 200], true, 'UTF-8', false);

//     // Set document information
//     $pdf->SetCreator('Your Application');
//     $pdf->SetAuthor('Your Company');
//     $pdf->SetTitle('Receipt');
//     $pdf->SetMargins(2, 5, 2);
//     $pdf->SetAutoPageBreak(true, 5);

//     // Add a page
//     $pdf->AddPage();

//     // Set font
//     $pdf->SetFont('helvetica', '', 8);

//     // Receipt Header
//     $html = "<h3 style='text-align: center;'>Receipt</h3>";
//     $html .= "<p style='text-align: center;'>Thank you for your payment!</p>";
//     $html .= "<hr />";

//     // User Details
//     $html .= "<b>User Information:</b><br />";
//     $html .= "Name: {$userData->name}<br />";
//     $html .= "Mobile: {$userData->mobile}<br />";
//     $html .= "Email: {$userData->email}<br />";
//     $html .= "Address: {$userData->address}<br /><br />";

//     // Order Details
//     $html .= "<b>Order Details:</b><br />";
//     $html .= "Invoice: {$orderData->invoice}<br />";
//     $html .= "Amount Paid: {$orderData->amount} BDT<br />";
//     $html .= "Month: {$orderData->month}<br />";
//     $html .= "Paid On: {$orderData->paid_at}<br />";
//     $html .= "Payment Method: {$orderData->paid_via}<br /><br />";

//     $html .= "<hr />";
//     $html .= "<p style='text-align: center;'>Powered by Your Company</p>";

//     // Output content
//     $pdf->writeHTML($html, true, false, true, false, '');

//     // Save PDF to file or output directly
//     $outputPath = 'receipt.pdf';
//     $pdf->Output($outputPath, 'F'); // Save to file

//     return $outputPath;
// }
// }


/**
 * DEFINED EVENT KEYS AND THEIR DEFAULT TEMPLATE IDs
 * (matches the default templates in sms_templates table)
 *
 *  user_created    → template id 2  (Greetings To Client Template)
 *  payment_done    → template id 13 (customer Bill payment)
 *  user_expired    → template id 12 (customer payment due)
 *  expiry_notice   → template id 12 (customer payment due)
 *  employee_pay    → template id 1  (Employee Salary Payment Template)
 *  employee_create → template id 6  (add_employes)
 */

if (!function_exists('sendEventSms')) {
    /**
     * Send SMS for a named event.
     * Looks up the sAdmin's configured template; falls back to $defaultTemplateId.
     * Returns false (silently) if the event is disabled for that admin.
     *
     * @param string           $event             e.g. 'payment_done'
     * @param object|array     $userData          The customer data array/object
     * @param int|null         $adminId           The sAdmin (resolved from $userData if null)
     * @param int|null         $defaultTemplateId Fallback template ID (the hardcoded one)
     * @param mixed            $routerAction      Passed through to Send_SMs
     * @param string|null      $password          Passed through to Send_SMs
     */
    function sendEventSms(
        string $event,
        $userData,
        ?int $adminId = null,
        ?int $defaultTemplateId = null,
        $routerAction = null,
        ?string $password = null
    ) {
        // Resolve adminId from the user data if not provided
        if (empty($adminId)) {
            $adminId = is_object($userData)
                ? ($userData->admin_id ?? null)
                : ($userData['admin_id'] ?? $userData[0]['admin_id'] ?? null);
        }

        $templateId = $defaultTemplateId; // start with the hardcoded default

        if (!empty($adminId)) {
            // Resolve the parent sAdmin for configuration inheritance
            $configAdminId = getSAdminIdForUser($adminId);

            $configModel = model('App\Models\SmsEventConfig');
            $smsConfig = $configModel->where(['admin_id' => $configAdminId, 'event' => $event])->first();

            if ($smsConfig) {
                // If admin has explicitly enabled this SMS event
                if ((int) $smsConfig->is_enabled === 1) {
                    if (!empty($smsConfig->template_id)) {
                        $templateId = (int) $smsConfig->template_id;
                    }
                } else {
                    $templateId = null; // Explicitly disable SMS part
                    log_message('info', "sendEventSms: SMS part for event '{$event}' is disabled for sAdmin {$configAdminId} (resolved from admin {$adminId})");
                }
            }
        }

        if (empty($templateId)) {
            log_message('info', "sendEventSms: no template resolved for event '{$event}' / admin {$adminId}");
        } else {
            Send_SMs($userData, $templateId, $routerAction, $password);
        }

        // --- NEW: Trigger Voice Notification if enabled ---
        return sendEventVoiceSms($event, $userData, (int) $adminId);
    }
}

/**
 * Trigger Voice notification for a named event
 */
if (!function_exists('sendEventVoiceSms')) {
    function sendEventVoiceSms(string $event, $userData, int $adminId)
    {
        try {
            log_message('info', "sendEventVoiceSms: Checking for event '{$event}' for admin {$adminId}");

            $configModel = model('App\Models\VoiceEventConfig');
            $config = $configModel->where(['admin_id' => $adminId, 'event' => $event])->first();

            if ($config && (int) $config->is_enabled === 1 && !empty($config->voice_template_id)) {
                log_message('info', "sendEventVoiceSms: Found config for '{$event}'. Enabled: {$config->is_enabled}, Template ID: {$config->voice_template_id}");

                $voiceModel = model('App\Models\VoiceSmsModel');
                $voice = $voiceModel->find($config->voice_template_id);

                if ($voice) {
                    log_message('info', "sendEventVoiceSms: Found voice template: {$voice->name} (Message ID: {$voice->message_id})");

                    // 1. Try to get mobile from $userData
                    $to = is_object($userData) ? ($userData->mobile ?? null) : ($userData['mobile'] ?? $userData[0]['mobile'] ?? null);
                    if (empty($to)) {
                        $to = is_object($userData) ? ($userData->personal_mobile ?? null) : ($userData['personal_mobile'] ?? null);
                    }

                    // 2. If $to is still null, look up by user_id
                    if (empty($to)) {
                        $userIdKey = is_object($userData) ? ($userData->user_id ?? null) : ($userData['user_id'] ?? $userData[0]['user_id'] ?? null);
                        if ($userIdKey && $userIdKey !== '--') {
                            $userModel = model('App\Models\User');
                            $userRow = $userModel->find($userIdKey);
                            if ($userRow) {
                                log_message('info', "sendEventVoiceSms: Resolved mobile from DB for user ID: " . $userIdKey);
                                $to = is_object($userRow) ? ($userRow->mobile ?? $userRow->personal_mobile ?? null) : ($userRow['mobile'] ?? $userRow['personal_mobile'] ?? null);
                            }
                        }
                    }

                    if ($to) {
                        log_message('info', "sendEventVoiceSms: Triggering voice call to {$to} via sendVoiceSms");
                        return sendVoiceSms($to, $voice->message_id, $adminId);
                    } else {
                        log_message('error', "sendEventVoiceSms: No mobile number found for user. Data: " . json_encode($userData));
                        log_message('error', "sendEventVoiceSms: No mobile number found for user.");
                    }
                } else {
                    log_message('error', "sendEventVoiceSms: Voice template with ID {$config->voice_template_id} not found in library.");
                }
            } else {
                log_message('info', "sendEventVoiceSms: Event '{$event}' is not enabled or no template set for admin {$adminId}.");
            }
        } catch (\Throwable $e) {
            log_message('error', "sendEventVoiceSms exception: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send raw Voice SMS
 */
if (!function_exists('sendVoiceSms')) {
    function sendVoiceSms($to, $voiceTemplateId, $userId = null)
    {
        try {
            $gateway = getSetting('default_voice_sms_gateway', '', $userId);
            if ($gateway === 'awajdigital') {
                $awaj = new App\Libraries\AwajDigital($userId);
                $result = $awaj->sendBroadcast($to, $voiceTemplateId);
                log_message('info', "sendVoiceSms (AwajDigital) result: " . json_encode($result));
                return $result;
            }
            return ['status' => 'error', 'logs' => 'Voice gateway not configured or unsupported'];
        } catch (\Throwable $e) {
            log_message('error', 'sendVoiceSms error: ' . $e->getMessage());
            return ['status' => 'error', 'logs' => $e->getMessage()];
        }
    }
}

if (!function_exists('sendSms')) {

    function sendSms($to, $message, $userId = null)
    {
        try {
            $prefix = getSettingPrefixForUser($userId);
            if ($prefix === 'SKIP_SMS') {
                log_message('info', "SMS skipped for user ID {$userId} - Reseller lacks SMS permission.");
                return ['status' => 'skipped', 'logs' => 'Reseller does not have SMS permission'];
            }

            // Check the default SMS gateway and instantiate the correct class
            switch (getSetting('default_sms_gateway', '', $userId)) {
                case 'bulksmsbd':
                    $sms = new App\Libraries\BulkSmsBd($userId);
                    break;
                case 'bulksmsdhaka':
                    $sms = new App\Libraries\BulkSmsDhaka($userId);
                    break;

                case 'greenwebsms':
                    $sms = new App\Libraries\GreenWebSms($userId);
                    break;

                case 'smsq':
                    $sms = new App\Libraries\SmsQ($userId);
                    break;
                case 'telnet':
                    $sms = new App\Libraries\TelnetSms($userId);
                    break;

                case null:
                    // Handle the case when the default SMS gateway is null
                    throw new Exception('No default SMS gateway configured');
                    break;

                default:
                    throw new Exception('Unsupported SMS gateway');
            }

            // Log the message body being sent
            // log_message('info', 'Updated sendSms($to, $message) message body: ' . $message);

            // Send the message and capture the result
            $result = $sms->sendMessage($to, $message);

            // Return the result
            return $result;
        } catch (\Throwable $e) {
            log_message('error', 'Error in sendSms: ' . $e->getMessage());
            return ['status' => 'error', 'logs' => 'SMS Gateway Error: ' . $e->getMessage()];
        }
    }
}



/**
 * Calculate user subscription renewal date
 */
if (!function_exists('calcUserSubsRenewDate')) {
    function calcUserSubsRenewDate($id, $duration = null)
    {
        $user = getUserById($id);
        if (empty($user)) {
            $days = $duration ?? 30;
            return date('Y-m-d H:i:s', strtotime("+$days days"));
        }

        $willExpire = is_object($user) ? ($user->will_expire ?? null) : ($user['will_expire'] ?? null);
        $from = (!empty($willExpire) && $willExpire > date('Y-m-d H:i:s')) ? $willExpire : date('Y-m-d H:i:s');

        $packageId = is_object($user) ? ($user->package_id ?? null) : ($user['package_id'] ?? null);

        return calPackageExpireDate($packageId, $from, $id, $duration);
    }
}


if (!function_exists('activity')) {
    function activity()
    {
        $userId = session()->get('user_id');
        $userModel = model('App\Models\User');

        $users = $userModel->where(['id' => $userId, 'auto_disconnect' => 'yes', 'will_expire < ' => date('Y-m-d H:i:s'), 'subscription_status' => 'active',])->first();

        //  log_message('info', 'users users Data: ' . print_r($users, true));
        log_message('debug', 'Retrieved Credentials: ' . print_r($users, true));

        if (!empty($users)) {

            $router_client = routerClient($users->router_id);

            if (!is_array($router_client)) {
                log_message('info', 'users users Data here : ');

                $pppoe = getPPPoEUserUserId($router_client, $users->id);
                $pppoe_id = $pppoe[0]['.id'] ?? $users->pppoe_id ?? null;

                log_message('info', "PPPoE ID for User ID {$users->id}: {$pppoe_id}");



                if ($users->role === 'user') {
                    disablePPPoEUser($router_client, $pppoe_id);
                }
                $data = [
                    'user' => $users->name,
                    'expire_date' => date("d M Y, h:i a", strtotime($users->will_expire)),
                ];

                $userModel->update($users->id, ['subscription_status' => 'inactive']);


                //send email notification
                try {
                    // event: user_expired | default template: 12 (customer payment due)
                    sendEventSms('user_expired', $users, (int) ($users->admin_id ?? 0), 12);


                    sendMail(
                        $users->email,
                        getSetting('app_name', '', is_numeric($userId) ? $userId : null) . ' | Subscription Expired',
                        view('emails/subscription-expired', $data),
                    );
                } catch (\Throwable $e) {
                    // Handle the exception
                    log_message('error', 'Exception caught: ' . $e->getMessage());
                }
            }
            $userModel->update($users->id, ['subscription_status' => 'inactive']);
        }
    }



    function routerName($macAddress)
    {
        $start = microtime(true);

        // Convert MAC to OUI (first 6 hex digits, no colons)
        $oui = strtoupper(str_replace(':', '', substr($macAddress, 0, 8)));

        // Path to your HTML file (use FCPATH for local file access)
        $filePath = FCPATH . 'assets/standards-oui_ieee_default.html';

        if (!file_exists($filePath)) {
            return 'OUI file not found';
        }

        // Get file contents
        $htmlContent = file_get_contents($filePath);

        // Regex to find organization name after "(base 16)"
        $pattern = '/' . $oui . '\s+\(base 16\)\s+([^\r\n]+)/i';

        if (preg_match($pattern, $htmlContent, $matches)) {
            $end = microtime(true);
            log_message('info', 'Time taken to find OUI: ' . ($end - $start) . ' seconds');

            return trim($matches[1]);
        }
        // If not found in file, use API
        $apiUrl = 'https://api.macvendors.com/' . $macAddress;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $apiResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($apiResponse && !$curlError) {
            $end = microtime(true);
            log_message('info', 'Time taken to find OUI: ' . ($end - $start) . ' seconds');
            return trim($apiResponse);
        }

        $end = microtime(true);
        log_message('info', 'Time taken to find OUI: ' . ($end - $start) . ' seconds');
        return 'Organization not found';
    }

    function sendWhatsApp($phone, $message)
    {
        log_message('info', 'sendWhatsApp function called with phone: ' . $phone . ' and message: ' . $message);
        $url = "https://wa.me/{$phone}?text=" . urlencode($message);
        header("Location: $url"); // Redirects user to WhatsApp
        exit;
    }



    // function daily_payment_generate()
    // {
    //     $user_model = model('App\Models\User');
    //     $transationModel = model('App\Models\ResellerTransactions');

    //     $resellers = $user_model->where('role', 'resellerAdmin')->where('status', 'active')->findAll();

    //     foreach ($resellers as $reseller) {
    //         $fund = $reseller->fund ?? 0;
    //         log_message('info', 'Processing reseller: ' . print_r($reseller, true));
    //         if (userHasPermission('Resellers', 'daily_payment_generate', 'resellerAdmin', $reseller->id, $reseller->admin_id) || userHasPermission('reseller', 'daily_payment_generate', 'resellerAdmin', $reseller->id, $reseller->admin_id)) {
    //             log_message('info', 'Permission granted for reseller: ' . $reseller->id);

    //             $users = $user_model->where('role', 'user')->where('status', 'active')->where('admin_id', $reseller->id)->findAll();

    //             foreach ($users as $user) {
    //                 $package_id = $user->package_id;
    //                 $tprice = (int) ResellerPackagePrice($package_id);
    //                 $dailyPrice = $tprice / 30; // Assuming 30 days in a month
    //                 $price = round($dailyPrice, 2); // Round to 2 decimal places

    //                 if ($fund >= $price && $price > 0) {
    //                     $fund -= $price;
    //                     $user_model->update($reseller->id, ['fund' => $fund]);

    //                     $will_expire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($user->will_expire)));

    //                     $user_model->update($user->id, ['will_expire' => $will_expire]);

    //                     $transationdata = [
    //                         'customer' => $$user->id,
    //                         'admin_id' => $reseller->id,
    //                         'amount' => $price,
    //                         'package_price' => $tprice,
    //                         'active_for' => '1',
    //                         'comments' => 'Single Customer Created'
    //                     ];

    //                     $result = $transationModel->insert($transationdata);
    //                 } elseif ($user->will_expire > date('Y-m-d H:i:s')) {
    //                     $will_expire = date('Y-m-d H:i:s');

    //                     $user_model->update($user->id, ['will_expire' => $will_expire]);
    //                 }
    //             }
    //         } else {
    //             log_message('info', 'Permission denied for reseller: ' . $reseller->id);
    //             continue;
    //         }


    //         // $today = date('Y-m-d');
    //     }
    // }



    // function daily_payment_generate()
    // {
    //     $userModel = model('App\Models\User');
    //     $transactionModel = model('App\Models\ResellerTransactions');

    //     // Get all active resellers
    //     $resellers = $userModel->where('role', 'resellerAdmin')
    //         ->where('status', 'active')
    //         ->findAll();

    //     if (empty($resellers)) {
    //         log_message('info', 'No active resellers found.');
    //         return;
    //     }

    //     // Loop through each reseller once
    //     foreach ($resellers as $reseller) {
    //         $resellerId = $reseller->id;
    //         $adminId = $reseller->admin_id;
    //         $fund = (float) ($reseller->fund ?? 0);
    //         log_message('info', "Processing reseller ID: {$resellerId} with initial fund: {$fund}");

    //         if ($fund <= 0) continue;

    //         // Check permission once per reseller
    //         $hasPermission =
    //             userHasPermission('Resellers', 'daily_payment_generate', 'resellerAdmin', $resellerId, $adminId) ||
    //             userHasPermission('reseller', 'daily_payment_generate', 'resellerAdmin', $resellerId, $adminId);

    //         if (!$hasPermission) {
    //             log_message('info', "Permission denied for reseller: {$resellerId}");
    //             continue;
    //         }

    //         log_message('info', "Processing reseller: {$resellerId}");

    //         // Cache current reseller ID for ResellerPackagePrice()
    //         cache()->save('current_reseller_id', $resellerId, 3600);

    //         // Fetch all active customers of this reseller
    //         $users = $userModel->select('id, package_id, will_expire')
    //             ->where([
    //                 'role' => 'user',
    //                 'status' => 'active',
    //                 'admin_id' => $resellerId
    //             ])->findAll();

    //         if (empty($users)) continue;

    //         // We'll collect all updates and run batch operations later for performance
    //         $userUpdates = [];
    //         $transactionInserts = [];

    //         foreach ($users as $user) {
    //             $packageId = $user->package_id;
    //             log_message('info', "Processing user: {$user->id} with package: {$packageId}");
    //             $tprice = (int) ResellerPackagePrice($packageId, null, $resellerId, "resellerAdmin");
    //             $dailyPrice = round($tprice / 30, 2);

    //             if ($dailyPrice <= 0) continue;

    //             if ($fund >= $dailyPrice) {
    //                 // Deduct from fund
    //                 $fund -= $dailyPrice;
    //                 $today = date('Y-m-d H:i:s');

    //                 if ($user->will_expire < $today) {
    //                     $willExpire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($today)));

    //                 } else {
    //                     $willExpire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($user->will_expire)));
    //                     // Extend validity by 1 day

    //                 }

    //                 $userUpdates[] = [
    //                     'id' => $user->id,
    //                     'will_expire' => $willExpire,
    //                 ];

    //                 $transactionInserts[] = [
    //                     'customer' => $user->id,
    //                     'admin_id' => $resellerId,
    //                     'amount' => $dailyPrice,
    //                     'package_price' => $tprice,
    //                     'active_for' => '1',
    //                     'comments' => 'Daily payment auto-deducted'
    //                 ];
    //             } else {
    //                 log_message('info', "Insufficient fund for reseller: {$resellerId}, expiring subscription.");
    //                 // Insufficient fund — expire the user
    //                 $willExpire = date('Y-m-d H:i:s');
    //                 $userUpdates[] = [
    //                     'id' => $user->id,
    //                     'will_expire' => $willExpire,
    //                 ];
    //             }
    //         }

    //         // Batch update all users
    //         if (!empty($userUpdates)) {
    //             log_message('info', 'Updating users in bulk: ' . print_r($userUpdates, true));
    //             // $userModel->updateBatch($userUpdates, 'id');
    //         }

    //         // Insert all transactions in bulk
    //         if (!empty($transactionInserts)) {
    //             log_message('info', 'Inserting transactions in bulk: ' . print_r($transactionInserts, true));
    //             // $transactionModel->insertBatch($transactionInserts);
    //         }

    //         // Update reseller fund once
    //         // $userModel->update($resellerId, ['fund' => $fund]);

    //         log_message('info', "Reseller {$resellerId} updated successfully with remaining fund: {$fund}");
    //     }

    //     log_message('info', 'Daily payment generation process completed.');
    // }


    if (!function_exists('getPublicIP')) {
        /**
         * Get public IP of the user using ipify API
         * @return string
         */
        function getPublicIP()
        {
            try {
                // Use file_get_contents for simplicity
                $res = file_get_contents('https://api.ipify.org?format=json');
                if ($res) {
                    $data = json_decode($res);
                    return $data->ip ?? 'Unknown Public IP';
                }
            } catch (\Exception $e) {
                log_message('error', 'Public IP fetch error: ' . $e->getMessage());
            }
            return 'Unknown Public IP';
        }
    }

    if (!function_exists('getLocalIP')) {
        /**
         * Get local IP using $_SERVER variables (works only if server can detect)
         * ⚠️ This is NOT same as client LAN IP. Client LAN IP can only be fetched via JS.
         * @return string
         */
        function getLocalIP()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
            }
            return 'Unknown Local IP';
        }
    }

    if (!function_exists('countPendingFreeRequests')) {
        /**
         * Count pending free user requests for an sAdmin
         * @param int $adminId
         * @return int
         */
        function countPendingFreeRequests($adminId)
        {
            $model = model('App\Models\FreeUserRequest');
            return $model->where(['admin_id' => $adminId, 'status' => 'pending'])->countAllResults();
        }
    }

    if (!function_exists('getPendingFreeRequests')) {
        /**
         * Get pending free user requests for an sAdmin
         * @param int $adminId
         * @return array
         */
        function getPendingFreeRequests($adminId)
        {
            $model = model('App\Models\FreeUserRequest');
            $db = \Config\Database::connect();
            $builder = $db->table('free_user_requests r');
            $builder->select('r.*, c.name as customer_name, c.email as customer_email, res.name as reseller_name');
            $builder->join('users c', 'c.id = r.user_id', 'inner');
            $builder->join('users res', 'res.id = r.reseller_id', 'inner');
            $builder->where('r.admin_id', $adminId);
            $builder->where('r.status', 'pending');
            $builder->orderBy('r.id', 'DESC');
            return $builder->get()->getResultArray();
        }
    }
}

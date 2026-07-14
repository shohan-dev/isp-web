<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\allResellerPackage;
use App\Models\ResellerPackages;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\CLI\Console;
use CodeIgniter\Exceptions\PageNotFoundException;

class AllResellersPackage extends BaseController
{
    use ResponseTrait;

    public function index($userId)
    {
        if (!userHasPermission('packages', 'read')) {
            show_404();
        }
        $packageModel = new allResellerPackage();
        log_message('info', 'User ID: ' . $userId);
        $userRole = session()->get('user_role');

        if ($userRole === 'employee') {
            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();
            $userId = $details->admin_id;
        }
        // Fetch packages for the specific user
        $rawPackages = $packageModel->where('user_id', $userId)->findAll();

        // Decode the package_details JSON field
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

        // Fallback to reseller_packages table directly if empty
        if (empty($packages)) {
            $resellerPkgModel = model('App\Models\ResellerPackages');
            $fallbackPkgs = $resellerPkgModel->where('user_id', $userId)->where('status', 'active')->findAll();
            foreach ($fallbackPkgs as $pkg) {
                $packages[] = [
                    'id' => $pkg['id'] ?? ($pkg->id ?? null),
                    'package_name' => $pkg['package_name'] ?? ($pkg->package_name ?? '--'),
                    'price' => $pkg['price'] ?? ($pkg->price ?? 0),
                    'selling_price' => $pkg['selling_price'] ?? ($pkg['price'] ?? 0),
                    'bandwidth' => $pkg['bandwidth'] ?? ($pkg->bandwidth ?? 0),
                    'package_type' => $pkg['pricing_type'] ?? ($pkg->pricing_type ?? 'monthly'),
                    'preview' => $pkg['preview'] ?? ($pkg->preview ?? '--'),
                    'mikrotik_router_id' => $pkg['mikrotik_router_id'] ?? ($pkg->mikrotik_router_id ?? null),
                    'mikrotik_profile' => $pkg['mikrotik_profile'] ?? ($pkg->mikrotik_profile ?? null),
                ];
            }
        }

        // Fetch all routers to display names in the view
        $routerModel = model('App\Models\Router');
        $routers = $routerModel->where('status', 'active')->findAll();

        $data = [
            'packages' => $packages,
            'userId' => $userId,
            'routers' => $routers,
        ];

        return view('reseller/userpackages', $data);
    }


    public function updatePackage()
    {
        $packageModel = new allResellerPackage();
        log_message('info', 'User Data: Processing updatePackage method');

        // Get data from request
        $userId = $this->request->getPost('userId');
        $packageName = $this->request->getPost('packageName');
        $price = $this->request->getPost('price');
        $sellingPrice = $this->request->getPost('sellingPrice');
        $bandwidth = $this->request->getPost('bandwidth');
        $packageType = $this->request->getPost('packageType');
        $preview = $this->request->getPost('preview');

        // Validate input
        if (!$userId || !$packageName) {
            return $this->fail('Invalid input', 400);
        }

        // Fetch package data
        $packageData = $packageModel->where('user_id', $userId)->first();
        if ($packageData) {
            // Decode package details safely
            $packageDetails = is_string($packageData['package_details']) 
                ? json_decode($packageData['package_details'], true) 
                : $packageData['package_details'];
            if (!is_array($packageDetails)) {
                $packageDetails = []; // Ensure it's an array
            }

            $updated = false;
            foreach ($packageDetails as &$package) {
                if ($package['package_name'] === $packageName) {
                    if (getSession('user_role') === 'resellerAdmin') {
                        $package['price'] = $package['price'] ?? '--';
                        $package['selling_price'] = $sellingPrice ?? $package['selling_price']; // Added
                        $package['bandwidth'] = $package['bandwidth'] ?? '--';
                        $package['package_type'] = $package['package_type'] ?? '--';
                        $package['preview'] = $preview ?? $package['preview'] ?? '--';
                        $updated = true;
                        break;
                    } else {
                        $package['price'] = $price ?? $package['price'];
                        $package['selling_price'] = $sellingPrice ?? $package['selling_price']; // Added
                        $package['bandwidth'] = $bandwidth ?? $package['bandwidth'];
                        $package['package_type'] = $packageType ?? $package['package_type'];
                        $package['preview'] = $preview ?? $package['preview'];
                        $updated = true;
                        break;
                    }
                }
            }

            if ($updated) {
                $updatedData = ['package_details' => json_encode($packageDetails)];
                log_message('info', 'Updated Package Data: ' . json_encode($updatedData));

                if ($packageModel->where('id', $packageData['id'])->set($updatedData)->update()) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Packages updated successfully.'
                    ]);
                }
            }
        }

        // Fallback to update reseller_packages table directly
        $resellerPkgModel = model('App\Models\ResellerPackages');
        $fallbackPkg = $resellerPkgModel->where('user_id', $userId)->where('package_name', $packageName)->first();
        if ($fallbackPkg) {
            $updateData = [];
            if (getSession('user_role') === 'resellerAdmin') {
                $updateData['selling_price'] = $sellingPrice ?? $fallbackPkg['selling_price'];
                $updateData['preview'] = $preview ?? $fallbackPkg['preview'];
            } else {
                $updateData['price'] = $price ?? $fallbackPkg['price'];
                $updateData['selling_price'] = $sellingPrice ?? $fallbackPkg['selling_price'];
                $updateData['bandwidth'] = $bandwidth ?? $fallbackPkg['bandwidth'];
                $updateData['pricing_type'] = $packageType ?? $fallbackPkg['pricing_type'];
                $updateData['preview'] = $preview ?? $fallbackPkg['preview'];
            }

            if ($resellerPkgModel->update($fallbackPkg['id'], $updateData)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Packages updated successfully.'
                ]);
            }
        }

        return $this->failNotFound('Package not found in user data or database.');
    }



    public function packages($userId)
    {
        if (! $this->request->isAJAX()) {
            return $this->fail('Invalid request type', 400);
        }

        $packageModel = new allResellerPackage();
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

        // Fallback to reseller_packages table directly if empty
        if (empty($packages)) {
            $resellerPkgModel = model('App\Models\ResellerPackages');
            $fallbackPkgs = $resellerPkgModel->where('user_id', $userId)->where('status', 'active')->findAll();
            foreach ($fallbackPkgs as $pkg) {
                $packages[] = [
                    'id' => $pkg['id'] ?? ($pkg->id ?? null),
                    'package_name' => $pkg['package_name'] ?? ($pkg->package_name ?? '--'),
                    'price' => $pkg['price'] ?? ($pkg->price ?? 0),
                    'selling_price' => $pkg['selling_price'] ?? ($pkg['price'] ?? 0),
                    'bandwidth' => $pkg['bandwidth'] ?? ($pkg->bandwidth ?? 0),
                    'package_type' => $pkg['pricing_type'] ?? ($pkg->pricing_type ?? 'monthly'),
                    'preview' => $pkg['preview'] ?? ($pkg->preview ?? '--'),
                    'mikrotik_router_id' => $pkg['mikrotik_router_id'] ?? ($pkg->mikrotik_router_id ?? null),
                    'mikrotik_profile' => $pkg['mikrotik_profile'] ?? ($pkg->mikrotik_profile ?? null),
                ];
            }
        }

        return $this->respond(['status' => 'success', 'packages' => $packages]);
    }

    public function adminPackagesJson()
    {
        if (! $this->request->isAJAX()) {
            return $this->fail('Invalid request type', 400);
        }

        // Support both real admin and impersonating admin
        $session = session();
        if ($session->has('original_user')) {
            // Admin is impersonating a reseller — use the original admin's ID
            $original = $session->get('original_user');
            $adminId  = $original['user_id'];
        } else {
            $userRole = $session->get('user_role');
            $userModel = model('App\\Models\\User');
            $user = $userModel->find($session->get('user_id'));
            if ($userRole === 'resellerAdmin') {
                if ($user) {
                    $adminId = is_object($user) ? $user->admin_id : ($user['admin_id'] ?? $session->get('user_id'));
                } else {
                    $adminId = $session->get('user_id');
                }
            } elseif ($userRole === 'employee') {
                if ($user) {
                    $created_by = is_object($user) ? $user->created_by : ($user['created_by'] ?? '');
                    $userAdminId = is_object($user) ? $user->admin_id : ($user['admin_id'] ?? null);
                    if ($created_by === 'resellerAdmin') {
                        $reseller = $userModel->find($userAdminId);
                        if ($reseller) {
                            $adminId = is_object($reseller) ? $reseller->admin_id : ($reseller['admin_id'] ?? $userAdminId);
                        } else {
                            $adminId = $userAdminId;
                        }
                    } else {
                        $adminId = $userAdminId;
                    }
                } else {
                    $adminId = $session->get('user_id');
                }
            } else {
                $adminId = $session->get('user_id');
            }
        }

        $packageModel = model('App\\Models\\Package');
        $packages     = $packageModel->where('user_id', $adminId)->where('status', 'active')->findAll();

        $result = [];
        foreach ($packages as $pkg) {
            $result[] = [
                'id'           => is_object($pkg) ? $pkg->id           : $pkg['id'],
                'package_name' => is_object($pkg) ? $pkg->package_name : $pkg['package_name'],
                'price'        => is_object($pkg) ? $pkg->price        : $pkg['price'],
                'selling_price'=> is_object($pkg) ? $pkg->price        : $pkg['price'], // packages table has no selling_price
                'bandwidth'    => is_object($pkg) ? $pkg->bandwidth    : $pkg['bandwidth'],
                'package_type' => is_object($pkg) ? $pkg->pricing_type : $pkg['pricing_type'],
            ];
        }

        return $this->respond(['status' => 'success', 'packages' => $result, 'admin_id' => $adminId]);
    }

    public function syncPackages()
    {
        $packageModel = new allResellerPackage();
        // log_message('info', 'User Data: Processing syncPackages method');

        $userId = $this->request->getPost('userId');
        // log_message('info', 'User Data: ' . json_encode($userId));

        $packageModel = new ResellerPackages();
        $resellerPackageSimpleModel = new allResellerPackage();
        $userModel = model('App\Models\User');
        $details = $userModel->where(['id' => $userId])->first();

        $admin_id = $details->admin_id;
        $packages = $packageModel->where('user_id', $admin_id)->findAll();
        // log_message('info', 'Reselller packages: ' . json_encode($packages));

        $packageDetailsArray = [];
        foreach ($packages as $package) {
            $packageDetailsArray[] = [
                'id' => $package['id'],
                'package_name' => $package['package_name'],
                'price' => $package['price'],
                'selling_price' => $package['selling_price'] ?? '--',
                'bandwidth' => $package['bandwidth'],
                'package_type' => $package['pricing_type'],
                'preview' => $package['preview'],
                'mikrotik_router_id' => $package['mikrotik_router_id'] ?? null,
                'mikrotik_profile' => $package['mikrotik_profile'] ?? null,
            ];
        }

        // Check if the record exists for the given user_id (admin_id = user_id)
        $existingRecord = $resellerPackageSimpleModel->where('user_id', $userId)->first();
        // log_message('info', 'Fetched Packages: ' . json_encode($existingRecord));
        $combinedPackageData = [
            'user_id' => $userId,
            'package_details' => json_encode($packageDetailsArray)
        ];
        // log_message('info', 'Package Details Array Before Update: ' . json_encode($packageDetailsArray));

        if ($existingRecord) {
            // Update existing record
            $resellerPackageSimpleModel
                ->where('user_id', $userId)
                ->set(['package_details' => json_encode($packageDetailsArray)])
                ->update();
        } else {
            // Insert new record if no existing entry found
            $resellerPackageSimpleModel->insert($combinedPackageData);
        }
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Packages synced successfully.'
        ]);
    }
}

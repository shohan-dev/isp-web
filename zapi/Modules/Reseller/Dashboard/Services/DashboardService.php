<?php

namespace Zapi\Modules\Reseller\Dashboard\Services;

use Zapi\Core\Base\BaseApiController;
use App\Models\allResellerPackage;
use App\Models\Package;
use Zapi\Modules\Reseller\Dashboard\Services\DashboardService\DashboardServicePart01Segment;

class DashboardService extends BaseApiController
{

    use DashboardServicePart01Segment;

    protected $user_model;
    protected $payment_model;
    protected $package_model;
    protected $area_model;
    protected $router_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->payment_model = model('App\Models\Payment');
        $this->package_model = model('App\Models\Package');
        $this->area_model = model('App\Models\Area');
        $this->router_model = model('App\Models\Router');
        helper(['url', 'user', 'router']);
    }


    function getPackagePrice($userId, $userRole)
    {
        $totalPrice = 0;
        $ConnectionDetails = model('App\Models\ConnectionData');

        if ($userRole === 'admin') {


            $totalPrice = 0;
            $userModel = model('App\Models\User');
            // $details = $userModel->where(['admin_id' => $userId])->first();
            // $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->where('subscription_status', 'active')->findAll();
            $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->findAll();


            // $userRegister = $this->reseller_model->select('discount')->where('userid', $userId)->first();
            // log_message('info', 'Payment $userRegister: ' . json_encode($userRegister));
            // $discount = $userRegister['discount'] ?? 0;

            $packageIds = [];
            foreach ($userIds as $user) {
                $existingRecord = $ConnectionDetails->where('user_id', $user->id)->first();
                if (!empty($existingRecord)) {
                    // log_message('info', 'Existing Record: ' . print_r($existingRecord, true));
                    $billing_status = $existingRecord->billing_status ?? $existingRecord['billing_status'];
                    if ($billing_status === 'free' || $billing_status === 'Free') {
                        continue; // Skip this user and move to the next iteration
                    }
                }


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
                    }
                    elseif (is_object($packagePrice)) {
                        $totalPrice += $packagePrice->price;
                    }
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
            $userModel = model('App\Models\User');
            // $details = $userModel->where(['admin_id' => $userId])->first();
            // $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->where('subscription_status', 'active')->findAll();
            $userIds = $userModel->select('id')->where('admin_id', $userId)->where('role', 'user')->findAll();
            // $reseller_model = new Registration();
            // $userRegister = $reseller_model->select('discount')->where('userid', $userId)->first();
            log_message('info', 'Payment $userIds: ' . json_encode($userIds));
            // $discount = $userRegister['discount'] ?? 0;

            $packageIds = [];
            foreach ($userIds as $user) {
                $existingRecord = $ConnectionDetails->where('user_id', $user->id)->first();
                if (!empty($existingRecord)) {
                    // log_message('info', 'Existing Record: ' . print_r($existingRecord, true));
                    $billing_status = $existingRecord->billing_status ?? $existingRecord['billing_status'];
                    if ($billing_status === 'free' || $billing_status === 'Free') {
                        continue; // Skip this user and move to the next iteration
                    }
                }
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

            $i = 0;
            foreach ($packageIds as $packageId) {
                foreach ($packagePrices as $packagePrice) {
                    // log_message('info', 'Total Price: ' . $totalPrice);
                    $packageDetails = json_decode($packagePrice['package_details'], true);
                    foreach ($packageDetails as $detail) {
                        if ($detail['id'] == $packageId->package_id) {

                            log_message('info', 'Package Price: ' . print_r($detail, true));
                            $detailprice = (is_numeric($detail['selling_price']) && $detail['selling_price'] > 0)
                                ? $detail['selling_price']
                                : (is_numeric($detail['price']) ? $detail['price'] : 0);

                            $totalPrice += (int)$detailprice;


                            log_message('info', 'Package Price: ' . $i++ . ':' . $detailprice . ' => Total: ' . $totalPrice);
                        }
                    }
                }
            }

        // if ($discount > 0) {
        //     $totalPrice = $totalPrice - ($totalPrice * ($discount / 100));
        //     log_message('info', 'Total Price after discount: ' . $totalPrice);
        // }


        }
        log_message('info', 'Package IDs: totalPrice' . json_encode($totalPrice));

        return $totalPrice ?? null;
    }


}


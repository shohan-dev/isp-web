<?php

namespace Zapi\Modules\Reseller\Subscription\Services\SubscriptionService;

trait SubscriptionServicePart01Segment
{
        /**
         * GET /api/reseller/subscription/{resellerId}/{customerId}
         */
        public function info($resellerId = null, $customerId = null)
        {
            $resellerId = (int) $resellerId;
            $customerId = (int) $customerId;
    
            if (empty($resellerId) || empty($customerId)) {
                return $this->respondError((string) 'Missing reseller id or customer id', 400, 'REQUEST_FAILED');
            }
    
            $customer = $this->user_model->where(['id' => $customerId, 'admin_id' => $resellerId, 'role' => 'user'])->first();
            if (empty($customer)) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            $reseller = $this->user_model->find($resellerId);
            $packageDetails = $this->getResellerPackageDetails($resellerId);
            $profiles = $this->getFilteredProfilesForCustomer($customer, $packageDetails);
            $pppoeProfile = $this->getCurrentPppoeProfile($customer);
    
            $currentMonth = date('F');
            $paymentDetails = $this->payment_model
                ->where(['user_id' => $customerId, 'month' => $currentMonth])
                ->first();
    
            $paymentMonths = $this->payment_model
                ->where(['user_id' => $customerId])
                ->findAll();
    
            $data = [
                'customer' => [
                    'id' => (string) $customer->id,
                    'name' => $customer->name ?? '',
                    'created_at' => $customer->created_at ?? null,
                    'package_id' => (string) ($customer->package_id ?? ''),
                    'subscription_status' => $customer->subscription_status ?? 'inactive',
                    'last_renewed' => $customer->last_renewed ?? null,
                    'will_expire' => $customer->will_expire ?? null,
                ],
                'payment_details' => $paymentDetails,
                'payment_months' => $paymentMonths,
                'payment_months_status' => $this->buildPaymentMonthStatusMap($paymentMonths),
                'packages' => $packageDetails,
                'profiles' => $profiles,
                'pppoe_profile' => $pppoeProfile,
                'reseller_fund' => (float) ($reseller->fund ?? 0),
                'package_id' => (string) ($customer->package_id ?? ''),
                'subscription_status' => $customer->subscription_status ?? 'inactive',
                'last_renewed' => $customer->last_renewed ?? null,
                'will_expire' => $customer->will_expire ?? null,
            ];
    
            return $this->respondSuccess($data);
        }
    
        /**
         * POST /api/reseller/subscription/{resellerId}/renew
         */
        public function renew($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $rules = [
                'customer_id' => 'required|numeric',
                'will_expire' => 'required',
            ];
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $customerId = (int) $input['customer_id'];
            $willExpire = (string) $input['will_expire'];
            $selectedMonth = !empty($input['month']) ? (string) $input['month'] : date('F');
            $packageId = !empty($input['package_id']) ? (string) $input['package_id'] : '';
            $pppoeProfile = !empty($input['pppoe_profile']) ? (string) $input['pppoe_profile'] : '';
            $paidVia = !empty($input['paid_via']) ? (string) $input['paid_via'] : 'Cash';
            $methodTrx = !empty($input['method_trx']) ? (string) $input['method_trx'] : '';
            $statusRaw = isset($input['status']) ? strtolower(trim((string) $input['status'])) : '';
            $status = in_array($statusRaw, ['successful', 'pending'], true) ? $statusRaw : 'pending';
    
            $customer = $this->user_model->where(['id' => $customerId, 'admin_id' => $resellerId, 'role' => 'user'])->first();
            if (empty($customer)) {
                return $this->respondError((string) 'Customer not found', 404, 'REQUEST_FAILED');
            }
    
            if ($packageId === '') {
                $packageId = (string) ($customer->package_id ?? '');
            }
    
            if ($packageId === '') {
                return $this->respondError((string) 'Please select a valid package.', 400, 'REQUEST_FAILED');
            }
    

    
            $willExpire = $this->normalizeDateTime($willExpire);
            $dateValidation = $this->validateSubscriptionDates($willExpire, $selectedMonth);
            if (!$dateValidation['success']) {
                return $this->respondError((string) $dateValidation['message'], 400, 'REQUEST_FAILED');
            }
    
            $calc = $this->calculateSubscriptionData($customer, $willExpire, $packageId, $resellerId, $status);
            if (!$calc['success']) {
                return $this->respondError((string) $calc['message'], 400, 'REQUEST_FAILED');
            }
    
            $db = \Config\Database::connect();
            $db->transStart();
    
            try {
                $now = date('Y-m-d H:i:s');
                $isActive = strtotime($willExpire) > time();
    
                $updated = $this->user_model->update($customerId, [
                    'package_id' => $packageId,
                    'last_renewed' => $now,
                    'will_expire' => $willExpire,
                    'subscription_status' => $isActive ? 'active' : 'inactive',
                    'conn_status' => $isActive ? 'conn' : 'disconn',
                ]);
    
                if (!$updated) {
                    throw new \RuntimeException('Failed to update customer subscription');
                }
    
                $routerResult = $this->handleRouterProfileAndStatus($customer, $pppoeProfile, $isActive);
                if (!$routerResult['success']) {
                    throw new \RuntimeException($routerResult['message']);
                }
    
                $paymentResult = $this->processResellerPayment(
                    $customerId,
                    $customer,
                    $resellerId,
                    $selectedMonth,
                    $paidVia,
                    $methodTrx,
                    $status,
                    $calc
                );
                if (!$paymentResult['success']) {
                    throw new \RuntimeException($paymentResult['message']);
                }
    
                $db->transComplete();
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', 'API reseller subscription renew failed: ' . $e->getMessage());
                return $this->respondError((string) $e->getMessage(), 500, 'REQUEST_FAILED');
            }
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Subscription renewed successfully',
                'charged' => round((float) $calc['price'], 2),
                'remaining_fund' => round((float) $calc['remaining_fund'], 2),
                'active_days' => (int) $calc['difference'],
                'new_expiry' => $willExpire,
                'payment_id' => $paymentResult['payment_id'] ?? null,
                'payment_url' => $paymentResult['payment_url'] ?? null,
            ]);
        }
    
        /**
         * POST /api/reseller/subscription/{resellerId}/bulk-renew
         * Process full subscription renewal (with payment/proration) for multiple customers.
         *
         * Body: { ids: [id,...], will_expire: "YYYY-MM-DD", package_id?, pppoe_profile?, paid_via?, status? }
         */
        public function bulkRenew($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            if (empty($input)) {
                $body = $this->request->getBody();
                if (!empty($body)) {
                    $decoded = json_decode($body, true);
                    if (is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            }
    
            $ids = $input['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                return $this->respondError((string) 'Select at least one customer', 400, 'REQUEST_FAILED');
            }
    
            $willExpire = trim($input['will_expire'] ?? '');
            if (empty($willExpire)) {
                return $this->respondError((string) 'Select expire date', 400, 'REQUEST_FAILED');
            }
    
            $willExpire = $this->normalizeDateTime($willExpire);
            $packageId = !empty($input['package_id']) ? (string) $input['package_id'] : '';
            $pppoeProfile = !empty($input['pppoe_profile']) ? (string) $input['pppoe_profile'] : '';
            $paidVia = !empty($input['paid_via']) ? (string) $input['paid_via'] : 'Cash';
            $methodTrx = !empty($input['method_trx']) ? (string) $input['method_trx'] : '';
            $statusRaw = isset($input['status']) ? strtolower(trim((string) $input['status'])) : '';
            $status = in_array($statusRaw, ['successful', 'pending'], true) ? $statusRaw : 'pending';
            $selectedMonth = !empty($input['month']) ? (string) $input['month'] : date('F');
    
            $results = [];
            $successCount = 0;
            $failCount = 0;
            $totalCharged = 0.0;
    
            foreach ($ids as $cid) {
                $customerId = (int) $cid;
                if (empty($customerId)) {
                    $results[] = ['id' => $cid, 'status' => 'error', 'message' => 'Invalid customer id'];
                    $failCount++;
                    continue;
                }
    
                $customer = $this->user_model->where(['id' => $customerId, 'admin_id' => $resellerId, 'role' => 'user'])->first();
                if (empty($customer)) {
                    $results[] = ['id' => $cid, 'status' => 'error', 'message' => 'Customer not found'];
                    $failCount++;
                    continue;
                }
    
                $custPackageId = $packageId !== '' ? $packageId : (string) ($customer->package_id ?? '');
                if ($custPackageId === '') {
                    $results[] = ['id' => $cid, 'name' => $customer->name ?? '', 'status' => 'error', 'message' => 'No package assigned'];
                    $failCount++;
                    continue;
                }
    
                $existingPayment = $this->payment_model->where([
                    'user_id' => $customerId,
                    'paidby' => $resellerId,
                    'month' => $selectedMonth,
                ])->first();
                if ($existingPayment && (string) ($customer->package_id ?? '') === $custPackageId) {
                    $results[] = ['id' => $cid, 'name' => $customer->name ?? '', 'status' => 'skipped', 'message' => 'Already renewed this month'];
                    continue;
                }
    
                $dateValidation = $this->validateSubscriptionDates($willExpire, $selectedMonth);
                if (!$dateValidation['success']) {
                    $results[] = ['id' => $cid, 'name' => $customer->name ?? '', 'status' => 'error', 'message' => $dateValidation['message']];
                    $failCount++;
                    continue;
                }
    
                $calc = $this->calculateSubscriptionData($customer, $willExpire, $custPackageId, $resellerId, $status);
                if (!$calc['success']) {
                    $results[] = ['id' => $cid, 'name' => $customer->name ?? '', 'status' => 'error', 'message' => $calc['message']];
                    $failCount++;
                    continue;
                }
    
                $db = \Config\Database::connect();
                $db->transStart();
    
                try {
                    $now = date('Y-m-d H:i:s');
                    $isActive = strtotime($willExpire) > time();
    
                    $this->user_model->update($customerId, [
                        'package_id' => $custPackageId,
                        'last_renewed' => $now,
                        'will_expire' => $willExpire,
                        'subscription_status' => $isActive ? 'active' : 'inactive',
                        'conn_status' => $isActive ? 'conn' : 'disconn',
                    ]);
    
                    $this->handleRouterProfileAndStatus($customer, $pppoeProfile, $isActive);
    
                    $paymentResult = $this->processResellerPayment(
                        $customerId, $customer, $resellerId,
                        $selectedMonth, $paidVia, $methodTrx, $status, $calc
                    );
    
                    if (!$paymentResult['success']) {
                        throw new \RuntimeException($paymentResult['message']);
                    }
    
                    $db->transComplete();
    
                    $totalCharged += (float) $calc['price'];
                    $successCount++;
                    $results[] = [
                        'id' => $cid,
                        'name' => $customer->name ?? '',
                        'status' => 'success',
                        'charged' => round((float) $calc['price'], 2),
                    ];
                } catch (\Throwable $e) {
                    $db->transRollback();
                    $failCount++;
                    $results[] = ['id' => $cid, 'name' => $customer->name ?? '', 'status' => 'error', 'message' => $e->getMessage()];
                }
            }
    
            $reseller = $this->user_model->find($resellerId);
            $remainingFund = (float) ($reseller->fund ?? 0);
    
            $total = count($ids);
            $msg = $successCount === $total
                ? "All {$total} customer(s) renewed successfully"
                : "{$successCount}/{$total} customer(s) renewed. {$failCount} failed.";
    
            return $this->respondPayload([
                'status' => $successCount > 0 ? 'success' : 'error',
                'message' => $msg,
                'total_charged' => round($totalCharged, 2),
                'remaining_fund' => round($remainingFund, 2),
                'results' => $results,
            ]);
        }
    
        protected function getResellerPackageDetails(int $resellerId): array
        {
            $packageModel = model('App\Models\allResellerPackage');
            $row = $packageModel->where('user_id', $resellerId)->first();
            if (empty($row)) {
                return [];
            }
    
            $packageJson = is_array($row)
                ? ($row['package_details'] ?? '[]')
                : ($row->package_details ?? '[]');
            $details = json_decode((string) $packageJson, true);
            return is_array($details) ? array_values($details) : [];
        }
    
        protected function getFilteredProfilesForCustomer($customer, array $packageDetails): array
        {
            $routerClient = routerClient($customer->router_id ?? null);
            if (!($routerClient instanceof \RouterOS\Client)) {
                return [];
            }
    
            $profiles = getPPPoEProfiles($routerClient);
            if (!is_array($profiles)) {
                return [];
            }
    
            $packageNames = array_values(array_filter(array_map(function ($pkg) {
                return trim((string) ($pkg['package_name'] ?? ''));
            }, $packageDetails)));
    
            if (empty($packageNames)) {
                return array_values(array_unique($profiles));
            }
    
            // Match profile number with package number exactly (same as web JS behavior).
            $allowedNumbers = [];
            foreach ($packageNames as $pkgName) {
                if (preg_match('/(\d+)/', $pkgName, $m)) {
                    $allowedNumbers[] = (int) $m[1];
                }
            }
    
            if (empty($allowedNumbers)) {
                return array_values(array_unique($profiles));
            }
    
            $filtered = array_values(array_filter($profiles, function ($profile) use ($allowedNumbers) {
                if (!preg_match('/(\d+)/', (string) $profile, $m)) {
                    return false;
                }
                return in_array((int) $m[1], $allowedNumbers, true);
            }));
    
            return array_values(array_unique($filtered));
        }
    
        protected function getCurrentPppoeProfile($customer): string
        {
            $routerClient = routerClient($customer->router_id ?? null);
            if (!($routerClient instanceof \RouterOS\Client)) {
                return '';
            }
    
            $pppoe = getPPPoEUserUserId($routerClient, $customer->id);
            $pppoeId = $pppoe[0]['.id'] ?? ($customer->pppoe_id ?? null);
            if (empty($pppoeId)) {
                return '';
            }
    
            $userPpp = getPPPoEUser($routerClient, $pppoeId);
            return (string) ($userPpp[0]['profile'] ?? '');
        }
    
}

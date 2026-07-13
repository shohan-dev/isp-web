<?php

namespace Zapi\Modules\Reseller\CustomerPayment\Services\CustomerPaymentService;

trait CustomerPaymentServicePart01Segment
{
        private function resolveInput(): array
        {
            $jsonInput = $this->request->getJSON(true);
            if (is_array($jsonInput) && !empty($jsonInput)) {
                return $jsonInput;
            }
    
            $rawInput = $this->request->getRawInput();
            if (is_array($rawInput) && !empty($rawInput)) {
                return $rawInput;
            }
    
            $postInput = $this->request->getPost();
            return is_array($postInput) ? $postInput : [];
        }
    
        /**
         * GET /api/reseller/customer-payments/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $fromDate = $this->request->getGet('fromDate');
            $toDate = $this->request->getGet('toDate');
    
            $builder = $this->payment_model->where(['user_type' => 'user', 'admin_id' => $resellerId]);
    
            if (!empty($fromDate) && !empty($toDate)) {
                $builder->where('created_at >=', $fromDate)->where('created_at <=', $toDate);
            } elseif (!empty($fromDate)) {
                $builder->where('created_at >=', $fromDate);
            }
    
            $pager = $this->getPaginationParams();
            $totalFound = (int) $builder->countAllResults(false);
            $payments = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            $result = [];
            foreach ($payments as $p) {
                $row = is_object($p) ? (array) $p : $p;
                $row['customer_name'] = getUserById($row['user_id'])->name ?? '--';
                $row['paid_to_name'] = !empty($row['paid_to']) ? (getUserById($row['paid_to'])->name ?? '--') : '--';
                $result[] = $row;
            }
    
            return $this->respondPaginatedSuccess($result, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * GET /api/reseller/customer-payments/{resellerId}/user/{userId}
         */
        public function userPayments($resellerId = null, $userId = null)
        {
            if (empty($resellerId) || empty($userId)) {
                return $this->respondError((string) 'Missing reseller id or user id', 400, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->payment_model
                ->where('user_type', 'user')
                ->where('admin_id', $resellerId)
                ->groupStart()
                ->where('user_id', $userId)
                ->orWhere('paidby', $userId)
                ->groupEnd()
                ->orderBy('id', 'desc');
            $totalFound = (int) $builder->countAllResults(false);
            $payments = $builder
                ->findAll($pager['per_page'], $pager['offset']);
    
            $successfulAmount = 0;
            $pendingAmount = 0;
            $result = [];
    
            foreach ($payments as $p) {
                $row = is_object($p) ? (array) $p : $p;
                $row['customer_name'] = getUserById($row['user_id'])->name ?? '--';
                if (($row['status'] ?? '') === 'successful') {
                    $successfulAmount += (float) ($row['amount'] ?? 0);
                } elseif (($row['status'] ?? '') === 'pending') {
                    $pendingAmount += (float) ($row['amount'] ?? 0);
                }
                $result[] = $row;
            }
    
            return $this->response->setStatusCode(200)->setJSON([
                'statusCode' => 200,
                'success' => true,
                'data' => $result,
                'summary' => [
                    'successfulAmount' => round($successfulAmount, 2),
                    'pendingAmount' => round($pendingAmount, 2),
                ],
                'pagination' => $this->buildPaginationMeta(
                    $totalFound,
                    $pager['current_page'],
                    $pager['per_page'],
                    count($result)
                ),
                'error' => null,
            ]);
        }
    
        /**
         * POST /api/reseller/customer-payments/{resellerId}
         */
        public function create($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            /* {resellerId} comes from the URL and is used both to scope the target
               customer and as the account FundService::deduct() draws from, while
               RoleAuthFilter only verifies the JWT role is reselleradmin/admin/
               super_admin — never that this id is the caller's. So reseller A could
               POST to .../customer-payments/{resellerB_id} and drain B's fund while
               extending B's customer. canAccessReseller() allows self, the owning
               tenant admin, and the platform owner. */
            if (!$this->canAccessReseller((int) $resellerId)) {
                return $this->respondError((string) 'Access denied', 403, 'REQUEST_FAILED');
            }

            $input = $this->resolveInput();
    
            $rules = [
                'customer' => 'required|numeric',
                'amount' => 'required|numeric',
                'month' => 'required',
                'status' => 'required|in_list[successful,pending,failed]',
            ];
    
            if (($input['status'] ?? '') === 'successful') {
                $rules['paid_via'] = 'required';
            }
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $customerId = $input['customer'];
            $customer = $this->user_model->where(['id' => $customerId, 'admin_id' => $resellerId, 'role' => 'user'])->first();
            if (empty($customer)) {
                return $this->respondError((string) 'Customer not found for this reseller', 404, 'REQUEST_FAILED');
            }
    
            $data = [
                'user_id' => $customerId,
                'user_type' => 'user',
                'admin_id' => $resellerId,
                'paidby' => $resellerId,
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $input['amount'],
                'month' => $input['month'],
                'paid_via' => $input['paid_via'] ?? null,
                'paid_to' => $resellerId,
                'method_trx' => $input['method_trx'] ?? null,
                'status' => $input['status'],
                'created_at' => date('Y-m-d H:i:s'),
            ];
    
            if ($input['status'] === 'successful') {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
    
            $existing = $this->payment_model->where(['user_id' => $customerId, 'month' => $input['month']])->first();
    
            if ($existing && ($existing->status ?? ($existing['status'] ?? '')) !== 'successful') {
                $existingId = is_object($existing) ? $existing->id : $existing['id'];
                $result = $this->payment_model->update($existingId, $data);
            } else {
                $result = $this->payment_model->insert($data, false);
            }
    
            if (!$result) {
                return $this->respondError((string) 'Payment creation failed', 500, 'REQUEST_FAILED');
            }
    
            $willExpire = $input['will_expire'] ?? null;
            $renew = $input['renew'] ?? 'no';
    
            if ($renew === 'yes' && !empty($willExpire)) {
                $now = date('Y-m-d H:i:s');
                $resellerDetails = $this->user_model->find($resellerId);
                $fund = $resellerDetails->fund ?? 0;
    
                $tprice = function_exists('ResellerPackagePrice') ? ResellerPackagePrice($customer->package_id) : 0;
                $customerExpire = $customer->will_expire ?? $now;
    
                if ($customer->subscription_status === 'active') {
                    $base = strtotime($customerExpire);
                } else {
                    $base = time();
                }
                $target = strtotime($willExpire);
                $difference = max(0, floor(($target - $base) / 86400));
    
                $pricePerDay = $tprice / 30;
                $price = $pricePerDay * $difference;

                // BUG-09 fix: atomic deduct; rejects overdraw at the DB level.
                if (!(new \App\Services\FundService())->deduct((int) $resellerId, (float) $price)) {
                    return $this->respondError((string) 'Insufficient fund. Required: ' . round($price, 2) . ', Available: ' . round((float) ($fund), 2), 400, 'REQUEST_FAILED');
                }
                $this->user_model->update($customerId, [
                    'last_renewed' => date('Y-m-d H:i:s'),
                    'will_expire' => $willExpire,
                    'subscription_status' => (strtotime($willExpire) > time()) ? 'active' : 'inactive',
                ]);
    
                $transModel = model('App\Models\ResellerTransactions');
                $transModel->insert([
                    'customer' => $customerId,
                    'admin_id' => $resellerId,
                    'amount' => $price,
                    'package_price' => $tprice,
                    'active_for' => $difference,
                    'comments' => 'API Payment + Renewal',
                ]);
            }
    
            return $this->respondSuccess(['message' => 'Payment recorded successfully']);
        }
    
        /**
         * PUT /api/reseller/customer-payments/{resellerId}/{paymentId}
         */
        public function update($resellerId = null, $paymentId = null)
        {
            if (empty($resellerId) || empty($paymentId)) {
                return $this->respondError((string) 'Missing reseller id or payment id', 400, 'REQUEST_FAILED');
            }
    
            $payment = $this->payment_model->where(['id' => $paymentId, 'user_type' => 'user', 'admin_id' => $resellerId])->first();
            if (empty($payment)) {
                return $this->respondError((string) 'Payment not found', 404, 'REQUEST_FAILED');
            }
    
            $input = $this->resolveInput();
    
            $rules = [
                'amount' => 'required|numeric',
                'month' => 'required',
                'status' => 'required|in_list[successful,pending,failed]',
            ];
    
            if (($input['status'] ?? '') === 'successful') {
                $rules['paid_via'] = 'required';
            }
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'amount' => $input['amount'],
                'month' => $input['month'],
                'paid_via' => $input['paid_via'] ?? null,
                'method_trx' => $input['method_trx'] ?? null,
                'status' => $input['status'],
            ];
    
            if ($input['status'] === 'successful') {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
    
            $result = $this->payment_model->update($paymentId, $data);
    
            if (!$result) {
                return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
            }
    
            $willExpire = $input['will_expire'] ?? null;
            $renew = $input['renew'] ?? 'no';
            if ($renew === 'yes' && !empty($willExpire)) {
                $paymentUserId = is_object($payment) ? $payment->user_id : $payment['user_id'];
                $now = date('Y-m-d H:i:s');
                $this->user_model->update($paymentUserId, [
                    'last_renewed' => $now,
                    'will_expire' => $willExpire,
                    'subscription_status' => (strtotime($willExpire) > time()) ? 'active' : 'inactive',
                ]);
            }
    
            return $this->respondSuccess(['message' => 'Payment updated successfully']);
        }
    
        /**
         * DELETE /api/reseller/customer-payments/{resellerId}
         */
        public function delete($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->resolveInput();
            $ids = $input['ids'] ?? null;
    
            if (empty($ids) || !is_array($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $result = $this->payment_model->where('user_type', 'user')->where('admin_id', $resellerId)->whereIn('id', $ids)->delete();
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Deleted successfully']);
            }
    
            return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
        }
    
}

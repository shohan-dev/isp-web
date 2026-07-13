<?php

namespace Zapi\Modules\Reseller\Funding\Services\FundingService;

trait FundingServicePart02Segment
{
        private function createSelfRecharge($resellerId, $reseller, array $input)
        {
            $rules = [
                'amount' => 'required|numeric',
                'paid_via' => 'required',
            ];

            if (!$this->validate($rules)) {
                return $this->respondPayload([
                    'status' => 'validation-error',
                    'errors' => $this->validator->getErrors(),
                ], 400);
            }

            $amount = $this->asFloat($input['amount']);
            if ($amount <= 0 || $amount > 500000) {
                return $this->respondError(
                    (string) 'Amount must be greater than zero and not exceed 500000',
                    400,
                    'VALIDATION_ERROR'
                );
            }

            // Self-recharge is always pending until a verified gateway callback credits fund.
            $insertData = [
                'customer' => (int) $resellerId,
                'admin_id' => $this->getResellerAdminId($reseller, $resellerId),
                'amount' => $amount,
                'received_amount' => $this->asFloat($input['received_amount'] ?? $input['amount']),
                'invoice_number' => $input['invoice_number'] ?? ('FND-' . random_int(100000, 999999)),
                'paid_via' => $input['paid_via'],
                'received_date' => $input['received_date'] ?? date('Y-m-d'),
                'comments' => $input['comments'] ?? null,
                'status' => 'pending',
            ];

            $insertId = $this->funding_model->insert($insertData, true);
            if (!$insertId) {
                return $this->respondError((string) 'Insert failed', 500, 'REQUEST_FAILED');
            }

            // Create a payment invoice so this recharge appears in My Payments
            // and can be completed through /api/reseller/make-reseller-payment/{paymentId} (reseller app JWT).
            $paymentData = [
                'user_id' => (int) $resellerId,
                'user_type' => 'resellerAdmin',
                'admin_id' => $this->getResellerAdminId($reseller, $resellerId),
                'paidby' => (int) $resellerId,
                'invoice' => $insertData['invoice_number'],
                'amount' => $insertData['amount'],
                'pay_amount' => $insertData['received_amount'],
                'month' => $input['month'] ?? date('F'),
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'paid_via' => $insertData['paid_via'] ?? null,
                'paid_to' => (int) $resellerId,
                'method_trx' => $input['method_trx'] ?? null,
            ];

            $paymentId = $this->payment_model->insert($paymentData, true);
            if (!$paymentId) {
                // Keep funding + payment creation consistent for app consumers.
                $this->funding_model->delete($insertId);
                return $this->respondError((string) 'Failed to generate payment invoice', 500, 'REQUEST_FAILED');
            }

            $newFund = $this->getResellerFund($reseller);

            $created = $this->funding_model->find($insertId);

            $row = $this->mapSelfRechargeRow($this->toArray($created));
            // Legacy envelope unwrap keeps only `data`; embed payment fields for mobile clients.
            $row['payment_id'] = (int) $paymentId;
            $row['payment_url'] = base_url('api/reseller/make-reseller-payment/' . (int) $paymentId);

            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Self recharge funding record created successfully',
                'recharge_type' => 'self_recharge',
                'id' => $insertId,
                'payment_id' => (int) $paymentId,
                'payment_url' => $row['payment_url'],
                'current_fund' => round($newFund, 2),
                'data' => $row,
            ]);
        }
    
        private function createCustomerInvoice($resellerId, $reseller, array $input)
        {
            if (empty($input['customer_id']) && !empty($input['customer'])) {
                $input['customer_id'] = $input['customer'];
            }
    
            $rules = [
                'customer_id' => 'required|numeric',
                'amount' => 'required|numeric',
                'month' => 'required',
                'status' => 'required|in_list[successful,pending,failed]',
            ];
    
            if (($input['status'] ?? '') === 'successful') {
                $rules['paid_via'] = 'required';
                $rules['method_trx'] = 'required';
            }
    
            if (!$this->validate($rules)) {
                return $this->respondPayload([
                    'status' => 'validation-error',
                    'errors' => $this->validator->getErrors(),
                ], 400);
            }
    
            $customerId = $this->asInt($input['customer_id']);
            $customer = $this->user_model
                ->where('id', $customerId)
                ->where('admin_id', (int) $resellerId)
                ->where('role', 'user')
                ->first();
    
            if (empty($customer)) {
                return $this->respondError((string) 'Customer not found for this reseller', 404, 'REQUEST_FAILED');
            }
    
            $invoiceData = [
                'user_id' => $customerId,
                'user_type' => 'user',
                'admin_id' => (int) $resellerId,
                'paidby' => (int) $resellerId,
                'invoice' => $input['invoice_number'] ?? ('INV-' . random_int(100000, 999999)),
                'amount' => $this->asFloat($input['amount']),
                'pay_amount' => $this->asFloat($input['pay_amount'] ?? $input['amount']),
                'month' => $input['month'],
                'paid_via' => $input['paid_via'] ?? null,
                'paid_to' => (int) $resellerId,
                'method_trx' => $input['method_trx'] ?? null,
                'status' => $input['status'],
                'created_at' => date('Y-m-d H:i:s'),
            ];
    
            if (($input['status'] ?? '') === 'successful') {
                $invoiceData['paid_at'] = $input['paid_at'] ?? date('Y-m-d H:i:s');
            }
    
            $saved = null;
            $existing = $this->payment_model
                ->where('user_id', $customerId)
                ->where('month', $input['month'])
                ->first();
    
            if (!empty($existing)) {
                $existingRow = $this->toArray($existing);
                $existingStatus = strtolower((string) ($existingRow['status'] ?? ''));
    
                if ($existingStatus !== 'successful') {
                    $existingId = $this->asInt($existingRow['id'] ?? 0);
                    $updated = $this->payment_model->update($existingId, $invoiceData);
                    if (!$updated) {
                        return $this->respondError((string) 'Invoice update failed', 500, 'REQUEST_FAILED');
                    }
                    $saved = $this->payment_model->find($existingId);
                } else {
                    $saved = $existing;
                }
            } else {
                $insertId = $this->payment_model->insert($invoiceData, true);
                if (!$insertId) {
                    return $this->respondError((string) 'Invoice creation failed', 500, 'REQUEST_FAILED');
                }
                $saved = $this->payment_model->find($insertId);
            }
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Customer invoice recorded successfully',
                'recharge_type' => 'customer_invoice',
                'current_fund' => round($this->getResellerFund($reseller), 2),
                'data' => $this->mapCustomerInvoiceRow($this->toArray($saved)),
            ]);
        }
    
        /**
         * DELETE /api/reseller/funding/{resellerId}
         */
        public function delete($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            $denied = $this->assertOwnsReseller((int) $resellerId);
            if ($denied !== null) {
                return $denied;
            }

            $reseller = $this->user_model->find($resellerId);
            if (empty($reseller)) {
                return $this->respondError((string) 'Reseller not found', 404, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true);
            if (!is_array($input)) {
                $input = $this->request->getPost();
            }
            if (!is_array($input)) {
                $input = [];
            }
    
            $rechargeType = $this->normalizeType($input['recharge_type'] ?? 'self_recharge');
            $ids = $this->normalizeIds($input['ids'] ?? []);
            $selfIds = $this->normalizeIds($input['self_recharge_ids'] ?? []);
            $invoiceIds = $this->normalizeIds($input['invoice_ids'] ?? []);
    
            if ($rechargeType === 'self_recharge') {
                if (empty($selfIds)) {
                    $selfIds = $ids;
                }
                return $this->deleteSelfRecharge($resellerId, $selfIds);
            }
    
            if ($rechargeType === 'customer_invoice') {
                if (empty($invoiceIds)) {
                    $invoiceIds = $ids;
                }
                return $this->deleteCustomerInvoice($resellerId, $invoiceIds);
            }
    
            if (empty($selfIds)) {
                $selfIds = $ids;
            }
            if (empty($invoiceIds)) {
                $invoiceIds = $ids;
            }
    
            $deletedSelf = $this->deleteSelfRecharge($resellerId, $selfIds, true);
            $deletedInvoice = $this->deleteCustomerInvoice($resellerId, $invoiceIds, true);
    
            $newReseller = $this->user_model->find($resellerId);
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Selected records deleted successfully',
                'recharge_type' => 'all',
                'deleted' => [
                    'self_recharge' => $deletedSelf,
                    'customer_invoice' => $deletedInvoice,
                ],
                'current_fund' => round($this->getResellerFund($newReseller), 2),
            ]);
        }
    
        private function deleteSelfRecharge($resellerId, array $ids, $internal = false)
        {
            if (empty($ids)) {
                if ($internal) {
                    return ['requested_ids' => [], 'deleted_ids' => [], 'deleted_count' => 0, 'deducted_fund' => 0.0];
                }
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $rows = $this->funding_model
                ->where('customer', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
    
            $matched = [];
            $totalDeduct = 0.0;
    
            foreach ($rows as $row) {
                $item = $this->toArray($row);
                $id = $this->asInt($item['id'] ?? 0);
                if ($id > 0) {
                    $matched[] = $id;
                }

                if (strtolower((string) ($item['status'] ?? '')) === 'successful') {
                    $rowAmount = $this->asFloat($item['amount'] ?? 0);
                    $totalDeduct += $rowAmount;
                    if ($rowAmount > 0) {
                        if (! (new \App\Services\FundService())->deduct(
                            (int) $resellerId,
                            $rowAmount,
                            'resellerfund:delete:' . $id,
                            'Self recharge record deleted'
                        )) {
                            if ($internal) {
                                return ['requested_ids' => $ids, 'deleted_ids' => [], 'deleted_count' => 0, 'deducted_fund' => 0.0];
                            }
                            return $this->respondError((string) 'Insufficient fund to reverse recharge', 400, 'REQUEST_FAILED');
                        }
                    }
                }
            }

            if (empty($matched)) {
                if ($internal) {
                    return ['requested_ids' => $ids, 'deleted_ids' => [], 'deleted_count' => 0, 'deducted_fund' => 0.0];
                }
                return $this->respondError((string) 'No self recharge records found', 404, 'REQUEST_FAILED');
            }
    
            $result = $this->funding_model
                ->where('customer', (int) $resellerId)
                ->whereIn('id', $matched)
                ->delete();
    
            if (!$result) {
                if ($internal) {
                    return ['requested_ids' => $ids, 'deleted_ids' => [], 'deleted_count' => 0, 'deducted_fund' => 0.0];
                }
                return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
            }

            $payload = [
                'requested_ids' => $ids,
                'deleted_ids' => $matched,
                'deleted_count' => count($matched),
                'deducted_fund' => round($totalDeduct, 2),
            ];
    
            if ($internal) {
                return $payload;
            }
    
            $reseller = $this->user_model->find($resellerId);
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Self recharge records deleted successfully',
                'recharge_type' => 'self_recharge',
                'current_fund' => round($this->getResellerFund($reseller), 2),
                'deleted' => $payload,
            ]);
        }
    
        private function deleteCustomerInvoice($resellerId, array $ids, $internal = false)
        {
            if (empty($ids)) {
                if ($internal) {
                    return ['requested_ids' => [], 'deleted_ids' => [], 'deleted_count' => 0];
                }
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $rows = $this->payment_model
                ->where('user_type', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $ids)
                ->findAll();
    
            $matched = [];
            foreach ($rows as $row) {
                $item = $this->toArray($row);
                $id = $this->asInt($item['id'] ?? 0);
                if ($id > 0) {
                    $matched[] = $id;
                }
            }
    
            if (empty($matched)) {
                if ($internal) {
                    return ['requested_ids' => $ids, 'deleted_ids' => [], 'deleted_count' => 0];
                }
                return $this->respondError((string) 'No customer invoice records found', 404, 'REQUEST_FAILED');
            }
    
            $result = $this->payment_model
                ->where('user_type', 'user')
                ->where('admin_id', (int) $resellerId)
                ->whereIn('id', $matched)
                ->delete();
    
            if (!$result) {
                if ($internal) {
                    return ['requested_ids' => $ids, 'deleted_ids' => [], 'deleted_count' => 0];
                }
                return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
            }
    
            $payload = [
                'requested_ids' => $ids,
                'deleted_ids' => $matched,
                'deleted_count' => count($matched),
            ];
    
            if ($internal) {
                return $payload;
            }
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Customer invoice records deleted successfully',
                'recharge_type' => 'customer_invoice',
                'deleted' => $payload,
            ]);
        }
    
}

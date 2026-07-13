<?php

namespace Zapi\Modules\Reseller\EmployeePayment\Services\EmployeePaymentService;

trait EmployeePaymentServicePart01Segment
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
         * GET /api/reseller/employee-payments/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->payment_model->where(['user_type' => 'employee', 'admin_id' => $resellerId]);
            $totalFound = (int) $builder->countAllResults(false);
            $payments = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            $result = [];
            foreach ($payments as $p) {
                $row = is_object($p) ? (array) $p : $p;
                $row['employee_name'] = getUserById($row['user_id'])->name ?? '--';
                $result[] = $row;
            }
    
            return $this->respondPaginatedSuccess($result, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * POST /api/reseller/employee-payments/{resellerId}
         */
        public function create($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->resolveInput();
    
            $rules = [
                'employee' => 'required|numeric',
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
    
            $employeeId = $input['employee'] ?? $input['employee_id'] ?? null;
            if (empty($employeeId)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
            $employee = $this->user_model->where(['id' => $employeeId, 'role' => 'employee', 'admin_id' => $resellerId])->first();
            if (empty($employee)) {
                return $this->respondError((string) 'Employee not found for this reseller', 404, 'REQUEST_FAILED');
            }
    
            $data = [
                'user_id' => $employeeId,
                'user_type' => 'employee',
                'admin_id' => $resellerId,
                'paidby' => $resellerId,
                'invoice' => 'INV-' . date('ymdHis') . '-' . random_int(100, 999),
                'amount' => $input['amount'],
                'month' => $input['month'],
                'created_at' => date('Y-m-d H:i:s'),
                'paid_via' => $input['paid_via'] ?? null,
                'method_trx' => $input['method_trx'] ?? null,
                'status' => $input['status'],
            ];
    
            if ($input['status'] === 'successful') {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
    
            $result = $this->payment_model->insert($data, false);
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Employee payment created successfully']);
            }
    
            return $this->respondPayload([
                'status' => 'error',
                'message' => 'Insert failed',
                'errors' => $this->payment_model->errors(),
            ], 500);
        }
    
        /**
         * PUT /api/reseller/employee-payments/{resellerId}/{paymentId}
         */
        public function update($resellerId = null, $paymentId = null)
        {
            if (empty($resellerId) || empty($paymentId)) {
                return $this->respondError((string) 'Missing reseller id or payment id', 400, 'REQUEST_FAILED');
            }
    
            $payment = $this->payment_model->where(['id' => $paymentId, 'user_type' => 'employee', 'admin_id' => $resellerId])->first();
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
                'status' => $input['status'],
            ];
    
            if ($input['status'] === 'successful') {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
    
            $result = $this->payment_model->update($paymentId, $data);
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Payment updated successfully']);
            }
    
            return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
        }
    
        /**
         * DELETE /api/reseller/employee-payments/{resellerId}
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
    
            $result = $this->payment_model->where('user_type', 'employee')->where('admin_id', $resellerId)->whereIn('id', $ids)->delete();
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Deleted successfully']);
            }
    
            return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
        }
    
}

<?php

namespace Zapi\Modules\Reseller\Profile\Services\ProfileService;

trait ProfileServicePart01Segment
{
        private function asArray($value): array
        {
            if (is_array($value)) {
                return $value;
            }
            if (is_object($value)) {
                return (array) $value;
            }
            return [];
        }

        private function firstNonEmptyString(array $values, string $default = ''): string
        {
            foreach ($values as $value) {
                if ($value === null) {
                    continue;
                }
                $text = trim((string) $value);
                if ($text !== '' && strtolower($text) !== 'null') {
                    return $text;
                }
            }
            return $default;
        }

        private function parseCustomerTypes($raw): array
        {
            if (is_array($raw)) {
                return array_values(array_filter(array_map(
                    static fn($v) => trim((string) $v),
                    $raw
                ), static fn($v) => $v !== ''));
            }

            if ($raw === null) {
                return [];
            }

            $text = trim((string) $raw);
            if ($text === '' || strtolower($text) === 'null') {
                return [];
            }

            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter(array_map(
                    static fn($v) => trim((string) $v),
                    $decoded
                ), static fn($v) => $v !== ''));
            }

            $parts = explode(',', $text);
            return array_values(array_filter(array_map(
                static fn($v) => trim(str_replace(['[', ']', '"'], '', (string) $v)),
                $parts
            ), static fn($v) => $v !== ''));
        }

        /**
         * GET /api/reseller/profile/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $user = $this->user_model->find($resellerId);
            if (empty($user)) {
                return $this->respondError((string) 'User not found', 404, 'REQUEST_FAILED');
            }
    
            $registration = $this->registration_model->where('userid', $resellerId)->first();
    
            $userData = $this->asArray($user);
            $registrationData = $this->asArray($registration);
            unset($userData['password'], $userData['code']);

            $status = strtolower($this->firstNonEmptyString([
                $userData['subscription_status'] ?? null,
                $userData['account_status'] ?? null,
                $userData['status'] ?? null,
            ], 'active'));
            $status = in_array($status, ['active', 'inactive', 'pending'], true) ? $status : 'active';

            $customerTypes = $this->parseCustomerTypes(
                $registrationData['customer_type'] ?? ($registrationData['customer_types'] ?? null)
            );

            $userData['status'] = $status;
            $userData['account_status'] = $status;
            $userData['subscription_status'] = $status;
            $userData['conn_status'] = $this->firstNonEmptyString([
                $userData['conn_status'] ?? null,
                $userData['connection_status'] ?? null,
            ], $status === 'active' ? 'conn' : 'disconn');
            $userData['activity'] = $this->firstNonEmptyString([
                $userData['activity'] ?? null,
            ], $status === 'active' ? 'active' : 'inactive');
            $userData['balance'] = $this->firstNonEmptyString([
                $userData['fund'] ?? null,
                $userData['balance'] ?? null,
            ], '0');

            // Keep organization values available in both profile and organization payloads
            // for app clients that read either side.
            $userData['organization_name'] = $this->firstNonEmptyString([
                $registrationData['organization_name'] ?? null,
                $userData['organization_name'] ?? null,
            ]);
            $userData['division'] = $this->firstNonEmptyString([
                $registrationData['division'] ?? null,
                $userData['division'] ?? null,
            ]);
            $userData['nationalid'] = $this->firstNonEmptyString([
                $registrationData['nationalid'] ?? null,
                $registrationData['national_id'] ?? null,
                $userData['nationalid'] ?? null,
                $userData['national_id'] ?? null,
            ]);
            $userData['customer_types'] = $customerTypes;
            $userData['customer_type'] = $customerTypes;

            if (!empty($registrationData)) {
                $registrationData['customer_types'] = $customerTypes;
                $registrationData['customer_type'] = $customerTypes;
            }
    
            return $this->respondSuccess([
                'profile' => $userData,
                'organization' => $registrationData,
            ]);
        }
    
        /**
         * PUT /api/reseller/profile/{resellerId}
         */
        public function update($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $user = $this->user_model->find($resellerId);
            if (empty($user)) {
                return $this->respondError((string) 'User not found', 404, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $allowed = ['name', 'mobile', 'address', 'email'];
            $data = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $input)) {
                    $data[$f] = $input[$f];
                }
            }
    
            if (empty($data)) {
                return $this->respondError((string) 'No updatable fields provided', 400, 'REQUEST_FAILED');
            }
    
            if (!empty($data['email'])) {
                $exists = $this->user_model->where('email', $data['email'])->where('id !=', $resellerId)->first();
                if ($exists) {
                    return $this->respondError((string) 'Email already in use', 400, 'REQUEST_FAILED');
                }
            }
    
            if (!empty($data['mobile'])) {
                $exists = $this->user_model->where('mobile', $data['mobile'])->where('id !=', $resellerId)->first();
                if ($exists) {
                    return $this->respondError((string) 'Mobile already in use', 400, 'REQUEST_FAILED');
                }
            }
    
            $result = $this->user_model->update($resellerId, $data);
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Profile updated successfully']);
            }
    
            return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
        }
    
        /**
         * PUT /api/reseller/profile/{resellerId}/organization
         */
        public function updateOrganization($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $allowed = ['organization_name', 'admin_name', 'mobile', 'email', 'division', 'district', 'upazilla', 'address', 'nationalid', 'reference_name', 'reference_mobile'];
            $data = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $input)) {
                    $data[$f] = $input[$f];
                }
            }
    
            if (array_key_exists('customer_type', $input)) {
                $data['customer_type'] = is_array($input['customer_type']) ? json_encode($input['customer_type']) : $input['customer_type'];
            }
    
            if (empty($data)) {
                return $this->respondError((string) 'No updatable fields provided', 400, 'REQUEST_FAILED');
            }
    
            $registration = $this->registration_model->where('userid', $resellerId)->first();
    
            if ($registration) {
                $regId = is_object($registration) ? $registration->id : ($registration['id'] ?? null);
                $result = $this->registration_model->update($regId, $data);
            } else {
                $data['userid'] = $resellerId;
                $result = $this->registration_model->insert($data);
            }
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Organization data updated successfully']);
            }
    
            return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
        }
    
        /**
         * POST /api/reseller/profile/{resellerId}/change-password
         */
        public function changePassword($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $rules = [
                'old_password' => 'required',
                'new_password' => 'required|min_length[4]',
                'confirm_password' => 'required|matches[new_password]',
            ];
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $user = $this->user_model->find($resellerId);
            if (empty($user)) {
                return $this->respondError((string) 'User not found', 404, 'REQUEST_FAILED');
            }
    
            $userPassword = is_object($user) ? $user->password : ($user['password'] ?? '');
    
            if (!password_verify($input['old_password'], $userPassword)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $newPass = $input['new_password'];
            $result = $this->user_model->update($resellerId, [
                'code' => $newPass,
                'password' => password_hash($newPass, PASSWORD_DEFAULT),
            ]);
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Password changed successfully']);
            }
    
            return $this->respondError((string) 'Password change failed', 500, 'REQUEST_FAILED');
        }
    
}

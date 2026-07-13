<?php

namespace Zapi\Modules\Reseller\Employee\Services\EmployeeService;

trait EmployeeServicePart01Segment
{
        /**
         * GET /api/reseller/employees/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            $pager = $this->getPaginationParams();
            $builder = $this->user_model->where(['role' => 'employee', 'admin_id' => $resellerId]);
            $totalFound = (int) $builder->countAllResults(false);
            $employees = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);

            return $this->respondPaginatedSuccess($employees, $totalFound, $pager['current_page'], $pager['per_page']);
        }

        /**
         * GET /api/reseller/employees/{resellerId}/{employeeId}
         */
        public function details($resellerId = null, $employeeId = null)
        {
            if (empty($resellerId) || empty($employeeId)) {
                return $this->respondError((string) 'Missing reseller id or employee id', 400, 'REQUEST_FAILED');
            }

            $employee = $this->user_model->where(['id' => $employeeId, 'role' => 'employee', 'admin_id' => $resellerId])->first();

            if (empty($employee)) {
                return $this->respondError((string) 'Employee not found', 404, 'REQUEST_FAILED');
            }

            return $this->respondSuccess($employee);
        }

        /**
         * POST /api/reseller/employees/{resellerId}
         * Mirrors web App\Controllers\Employee::create (new.php fields).
         */
        public function create($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            $input = $this->request->getJSON(true) ?: $this->request->getPost();

            $rules = [
                'name' => 'required',
                'designation' => 'required|in_list[billman,lineman,manager,noc,marketing]',
                'mobile' => 'required|is_unique[users.mobile]',
                'address' => 'required',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required',
                're_password' => 'required|matches[password]',
                'status' => 'required|in_list[active,inactive]',
            ];

            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }

            $areaId = $input['area_id'] ?? null;
            if (is_array($areaId)) {
                $areaId = implode(',', array_filter(array_map('trim', $areaId)));
            }

            $data = [
                'name' => $input['name'],
                'designation' => $input['designation'],
                'area_id' => $areaId,
                'mobile' => $input['mobile'],
                'address' => $input['address'],
                'email' => $input['email'],
                'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                'role' => 'employee',
                'status' => $input['status'],
                'admin_id' => $resellerId,
                'created_by' => 'resellerAdmin',
            ];

            $result = $this->user_model->insert($data, false);

            if ($result) {
                return $this->respondSuccess(['id' => $result, 'message' => 'Employee created successfully']);
            }

            return $this->respondError((string) 'Insert failed', 500, 'REQUEST_FAILED');
        }

        /**
         * PUT /api/reseller/employees/{resellerId}/{employeeId}
         * Mirrors web App\Controllers\Employee::update (edit.php fields).
         */
        public function update($resellerId = null, $employeeId = null)
        {
            if (empty($resellerId) || empty($employeeId)) {
                return $this->respondError((string) 'Missing reseller id or employee id', 400, 'REQUEST_FAILED');
            }

            $employee = $this->user_model->where(['id' => $employeeId, 'role' => 'employee', 'admin_id' => $resellerId])->first();
            if (empty($employee)) {
                return $this->respondError((string) 'Employee not found', 404, 'REQUEST_FAILED');
            }

            $input = $this->request->getJSON(true) ?: $this->request->getPost();

            $areaId = $input['area_id'] ?? null;
            if (is_array($areaId)) {
                $areaId = implode(',', array_filter(array_map('trim', $areaId)));
            } else {
                $areaId = is_string($areaId) ? trim($areaId) : '';
            }

            if ($areaId === '') {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', [
                    'area_id' => 'Select employee\'s service area',
                ]);
            }

            $rules = [
                'name' => 'required',
                'designation' => 'required|in_list[billman,lineman,manager,noc,marketing]',
                'mobile' => 'required|is_unique[users.mobile, id, ' . $employeeId . ']|regex_match[/^880\d{10}$/]',
                'address' => 'required',
                'email' => 'required|is_unique[users.email, id, ' . $employeeId . ']',
                'status' => 'required|in_list[active,inactive]',
            ];

            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }

            $pwd = trim((string) ($input['password'] ?? ''));
            $repwd = trim((string) ($input['re_password'] ?? ''));
            if ($pwd !== '' || $repwd !== '') {
                if ($pwd === '' || $repwd === '') {
                    return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', [
                        're_password' => 'Rewrite the password',
                    ]);
                }
                if ($pwd !== $repwd) {
                    return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', [
                        're_password' => 'Passwords doesn\'t matched',
                    ]);
                }
            }

            $data = [
                'name' => $input['name'],
                'designation' => $input['designation'],
                'area_id' => $areaId,
                'mobile' => $input['mobile'],
                'address' => $input['address'],
                'email' => $input['email'],
                'status' => $input['status'],
            ];

            if ($pwd !== '') {
                $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);
            }

            $result = $this->user_model->where(['id' => $employeeId, 'role' => 'employee'])->set($data)->update();

            if ($result) {
                return $this->respondSuccess(['message' => 'Employee updated successfully']);
            }

            return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
        }

        /**
         * DELETE /api/reseller/employees/{resellerId}
         */
        public function delete($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            $ids = $input['ids'] ?? null;

            if (empty($ids) || !is_array($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }

            $result = $this->user_model->where('admin_id', $resellerId)->where('role', 'employee')->whereIn('id', $ids)->delete();

            if ($result) {
                return $this->respondSuccess(['message' => 'Deleted successfully']);
            }

            return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
        }
}

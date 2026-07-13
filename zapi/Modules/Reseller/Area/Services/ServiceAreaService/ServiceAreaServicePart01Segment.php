<?php

namespace Zapi\Modules\Reseller\Area\Services\ServiceAreaService;

use App\Models\Area as AreaModel;
use App\Models\AreaSub as AreaSubModel;

trait ServiceAreaServicePart01Segment
{
        /**
         * @return array<string, mixed>
         */
        protected function getJsonInput(): array
        {
            $json = $this->request->getJSON(true);
            if (is_array($json) && !empty($json)) {
                return $json;
            }
            $post = $this->request->getPost();
            if (is_array($post) && !empty($post)) {
                return $post;
            }
            $raw = $this->request->getRawInput();
            if (is_array($raw) && !empty($raw)) {
                return $raw;
            }
            if (is_array($json)) {
                return $json;
            }
    
            return [];
        }
    
        /**
         * @param array<string, mixed> $input
         * @return array<string, string>|null
         */
        protected function validationErrors(array $input, array $rules): ?array
        {
            $validation = \Config\Services::validation();
            $validation->setRules($rules);
            if ($validation->run($input)) {
                return null;
            }
    
            return $validation->getErrors();
        }
    
        /**
         * @param array<string, mixed> $input
         * @return int[]
         */
        protected function normalizeIds(array $input): array
        {
            if (isset($input['ids']) && is_array($input['ids'])) {
                return array_map('intval', $input['ids']);
            }
    
            return [];
        }
    
        /**
         * GET /api/reseller/areas/{resellerId}
         */
        public function index($resellerId = null)
        {
            $resellerId = (int) $resellerId;
            if ($resellerId <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->area_model->where('user_id', $resellerId);
            $totalFound = (int) $builder->countAllResults(false);
            $areas = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            return $this->respondPaginatedSuccess($areas, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * GET /api/reseller/areas/{resellerId}/sub/{parentAreaId}
         * Sub-rows use user_id = parent area id (same as Area::fetchsub / subcreate).
         */
        public function subindex($resellerId = null, $parentAreaId = null)
        {
            $resellerId = (int) $resellerId;
            $parentAreaId = (int) $parentAreaId;
            if ($resellerId <= 0 || $parentAreaId <= 0) {
                return $this->respondError((string) 'Missing reseller id or parent area id', 400, 'REQUEST_FAILED');
            }
    
            $parent = $this->area_model->find($parentAreaId);
            if (empty($parent) || (string) $parent->user_id !== (string) $resellerId) {
                return $this->respondError((string) 'Area not found', 404, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->subarea_model->where('user_id', $parentAreaId);
            $totalFound = (int) $builder->countAllResults(false);
            $subareas = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            return $this->respondPaginatedSuccess($subareas, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * POST /api/reseller/areas/{resellerId?}
         * Body: area_name, area_code, status (active|inactive)
         */
        public function create($resellerId = null)
        {
            $input = $this->getJsonInput();
            $rid = $resellerId !== null && $resellerId !== ''
                ? (int) $resellerId
                : (int) ($input['user_id'] ?? $input['reseller_id'] ?? 0);
    
            if ($rid <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $rules = [
                'area_name' => 'required',
                'area_code' => 'required',
                'status' => 'required|in_list[active,inactive]',
            ];
    
            $vErr = $this->validationErrors($input, $rules);
            if ($vErr !== null) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'user_id' => $rid,
                'area_name' => $input['area_name'],
                'area_code' => $input['area_code'],
                'status' => $input['status'],
            ];
    
            $id = $this->area_model->insert($data, false);
    
            if ($id) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => 'Service area added successfully',
                    'id' => $id,
                    'data' => $this->area_model->find($id),
                ], 201);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
        /**
         * POST /api/reseller/subareas
         * Body: reseller_id, user_id (parent area id), area_name, area_code, status
         */
        public function subcreate()
        {
            $input = $this->getJsonInput();
            $parentAreaId = (int) ($input['user_id'] ?? $input['parent_area_id'] ?? $input['id'] ?? 0);
            $resellerId = (int) ($input['reseller_id'] ?? 0);
    
            if ($parentAreaId <= 0) {
                return $this->respondError((string) 'Missing parent area id', 400, 'REQUEST_FAILED');
            }
    
            $parent = $this->area_model->find($parentAreaId);
            if (empty($parent)) {
                return $this->respondError((string) 'Parent area not found', 404, 'REQUEST_FAILED');
            }
    
            if ($resellerId > 0 && (string) $parent->user_id !== (string) $resellerId) {
                return $this->respondError((string) 'Parent area does not belong to this reseller', 403, 'REQUEST_FAILED');
            }
    
            $rules = [
                'area_name' => 'required|is_unique[sub_areas.area_name]',
                'area_code' => 'required',
                'status' => 'required|in_list[active,inactive]',
            ];
    
            $vErr = $this->validationErrors($input, $rules);
            if ($vErr !== null) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'user_id' => $parentAreaId,
                'area_name' => $input['area_name'],
                'area_code' => $input['area_code'],
                'status' => $input['status'],
            ];
    
            $id = $this->subarea_model->insert($data, false);
    
            if ($id) {
                return $this->respondPayload([
                    'status' => 'success',
                    'message' => 'Service area added successfully',
                    'id' => $id,
                    'data' => $this->subarea_model->find($id),
                ], 201);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
        /**
         * GET /api/reseller/areas/edit/{id}
         */
        public function edit($id = null)
        {
            $id = (int) $id;
            if ($id <= 0) {
                return $this->respondError((string) 'Missing id', 400, 'REQUEST_FAILED');
            }
    
            $details = $this->area_model->find($id);
            if (empty($details)) {
                return $this->respondError((string) 'Not found', 404, 'REQUEST_FAILED');
            }
    
            return $this->respondSuccess($details);
        }
    
        /**
         * GET /api/reseller/subareas/edit/{id}
         */
        public function editsub($id = null)
        {
            $id = (int) $id;
            if ($id <= 0) {
                return $this->respondError((string) 'Missing id', 400, 'REQUEST_FAILED');
            }
    
            $details = $this->subarea_model->find($id);
            if (empty($details)) {
                return $this->respondError((string) 'Not found', 404, 'REQUEST_FAILED');
            }
    
            return $this->respondSuccess($details);
        }
    
        /**
         * PUT /api/reseller/areas/update/{id}
         * Body: reseller_id (recommended), area_name, area_code, status
         */
        public function update($id = null)
        {
            $id = (int) $id;
            if ($id <= 0) {
                return $this->respondError((string) 'Missing id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getJsonInput();
            $rid = (int) ($input['reseller_id'] ?? 0);
            if ($rid <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $area = $this->area_model->find($id);
            if (empty($area)) {
                return $this->respondError((string) 'Not found', 404, 'REQUEST_FAILED');
            }
    
            if ((string) $area->user_id !== (string) $rid) {
                return $this->respondError((string) 'Area does not belong to this reseller', 403, 'REQUEST_FAILED');
            }
    
            $rules = [
                'area_name' => 'required',
                'area_code' => 'required',
                'status' => 'required|in_list[active,inactive]',
            ];
    
            $vErr = $this->validationErrors($input, $rules);
            if ($vErr !== null) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'area_name' => $input['area_name'],
                'area_code' => $input['area_code'],
                'status' => $input['status'],
            ];
    
            $res = $this->area_model->update($id, $data);
    
            if ($res !== false) {
                return $this->respondSuccess(['message' => 'Service area updated successfully', 'payload' => $this->area_model->find($id),]);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
        /**
         * PUT /api/reseller/subareas/update/{id}
         * Body: reseller_id (recommended), area_name, area_code, status
         */
        public function updatesub($id = null)
        {
            $id = (int) $id;
            if ($id <= 0) {
                return $this->respondError((string) 'Missing id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->getJsonInput();
            $rid = (int) ($input['reseller_id'] ?? 0);
            if ($rid <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $sub = $this->subarea_model->find($id);
            if (empty($sub)) {
                return $this->respondError((string) 'Not found', 404, 'REQUEST_FAILED');
            }
    
            $parent = $this->area_model->find($sub->user_id);
            if (empty($parent) || (string) $parent->user_id !== (string) $rid) {
                return $this->respondError((string) 'Sub area does not belong to this reseller', 403, 'REQUEST_FAILED');
            }
    
            $rules = [
                'area_name' => 'required',
                'area_code' => 'required',
                'status' => 'required|in_list[active,inactive]',
            ];
    
            $vErr = $this->validationErrors($input, $rules);
            if ($vErr !== null) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'area_name' => $input['area_name'],
                'area_code' => $input['area_code'],
                'status' => $input['status'],
            ];
    
            $res = $this->subarea_model->update($id, $data);
    
            if ($res !== false) {
                return $this->respondSuccess(['message' => 'Service area updated successfully', 'payload' => $this->subarea_model->find($id),]);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
        /**
         * DELETE /api/reseller/areas/{resellerId}/delete
         * DELETE /api/reseller/areas/delete  (body: reseller_id + ids)
         * Body: { "ids": [1,2,3] }
         */
        public function delete($resellerId = null)
        {
            $input = $this->getJsonInput();
            $ids = $this->normalizeIds($input);
    
            if (empty($ids)) {
                return $this->respondError((string) 'Nothing is selected', 400, 'REQUEST_FAILED');
            }
    
            $rid = (int) ($resellerId ?? $input['reseller_id'] ?? 0);
            if ($rid <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            foreach ($ids as $aid) {
                $a = $this->area_model->find($aid);
                if (empty($a)) {
                    return $this->respondError((string) 'Area not found', 404, 'REQUEST_FAILED');
                }
                if ((string) $a->user_id !== (string) $rid) {
                    return $this->respondError((string) 'Invalid area selection', 403, 'REQUEST_FAILED');
                }
            }
    
            $res = $this->area_model->whereIn('id', $ids)->delete();
    
            if ($res) {
                return $this->respondSuccess(['message' => 'Selected records deleted successfully']);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
}

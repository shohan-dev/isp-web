<?php

namespace Zapi\Modules\Reseller\Package\Services\PackageService;

trait PackageServicePart01Segment
{
        /**
         * GET /api/reseller/packages/(:num?)
         * Returns packages list scoped to the route id or session user
         */
        public function fetch($adminId = null)
        {
    
    
            if (empty($adminId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $adminId])->first();
            $admin_id = $details->admin_id ?? $adminId;
            $packageModel = model('App\Models\ResellerPackages');
    
            $pager = $this->getPaginationParams();
            $builder = $packageModel->where('status', 'active')->where('user_id', $admin_id);
            $totalFound = (int) $builder->countAllResults(false);
            $result = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            return $this->respondPaginatedSuccess($result, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * DELETE /api/reseller/packages
         * body: { ids: [1,2,3] }
         * Deletes packages according to current role scope
         */
    
        public function delete($adminId = null, $packageId = null)
        {
            if (empty($adminId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            if (!empty($packageId)) {
                $ids = [(int)$packageId];
            }
            else {
                $input = $this->request->getJSON(true) ?: $this->request->getPost();
                $ids = $input['ids'] ?? null;
    
                if (empty($ids)) {
                    return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
                }
    
                if (is_string($ids)) {
                    $decoded = json_decode($ids, true);
                    if (is_array($decoded)) {
                        $ids = $decoded;
                    }
                    else {
                        $ids = array_filter(array_map('trim', explode(',', $ids)), 'strlen');
                    }
                }
    
                if (!is_array($ids)) {
                    return $this->respondError((string) 'Invalid ids format', 400, 'REQUEST_FAILED');
                }
            }
    
            $packageModel = model('App\Models\ResellerPackages');
    
            // ensure only packages for this reseller are affected
            $existing = $packageModel->where('user_id', $adminId)->whereIn('id', $ids)->findAll();
            $toDelete = array_map(function ($r) {
                return (int)$r['id']; }, $existing);
    
            if (empty($toDelete)) {
                return $this->respondError((string) 'No matching packages found', 404, 'REQUEST_FAILED');
            }
    
            $deleted = $packageModel->whereIn('id', $toDelete)->delete();
    
            if ($deleted) {
                return $this->respondSuccess(['deleted' => $toDelete]);
            }
    
            return $this->respondError('Request failed.', 500, 'REQUEST_FAILED');
        }
    
        /**
         * GET /api/reseller/packages/details/(:adminId)/(:packageId)/(:role)
         * Return single package details owned by reseller (no session)
         */
        public function details($adminId = null, $packageId = null)
        {
            if (empty($adminId) || empty($packageId)) {
                return $this->respondError((string) 'Missing reseller id or package id', 400, 'REQUEST_FAILED');
            }
    
            $packageModel = model('App\Models\ResellerPackages');
            $pkg = $packageModel->where(['id' => (int)$packageId, 'user_id' => $adminId])->first();
    
            if (empty($pkg)) {
                return $this->respondError((string) 'Package not found', 404, 'REQUEST_FAILED');
            }
    
            return $this->respondSuccess($pkg);
        }
    
}

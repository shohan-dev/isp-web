<?php

namespace Zapi\Modules\Reseller\Area\Services\ServiceAreaService;

use App\Models\Area as AreaModel;
use App\Models\AreaSub as AreaSubModel;

trait ServiceAreaServicePart02Segment
{
        /**
         * DELETE /api/reseller/subareas/delete
         * Body: { "reseller_id": N, "ids": [1,2,3] }
         */
        public function deletesub()
        {
            $input = $this->getJsonInput();
            $ids = $this->normalizeIds($input);
    
            if (empty($ids)) {
                return $this->respondError((string) 'Nothing is selected', 400, 'REQUEST_FAILED');
            }
    
            $rid = (int) ($input['reseller_id'] ?? 0);
            if ($rid <= 0) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            foreach ($ids as $sid) {
                $sub = $this->subarea_model->find($sid);
                if (empty($sub)) {
                    return $this->respondError((string) 'Sub area not found', 404, 'REQUEST_FAILED');
                }
                $parent = $this->area_model->find($sub->user_id);
                if (empty($parent) || (string) $parent->user_id !== (string) $rid) {
                    return $this->respondError((string) 'Invalid sub area selection', 403, 'REQUEST_FAILED');
                }
            }
    
            $res = $this->subarea_model->whereIn('id', $ids)->delete();
    
            if ($res) {
                return $this->respondSuccess(['message' => 'Selected records deleted successfully']);
            }
    
            return $this->respondError((string) 'Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }
    
}

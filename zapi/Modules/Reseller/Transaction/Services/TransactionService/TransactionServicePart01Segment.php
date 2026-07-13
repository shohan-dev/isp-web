<?php

namespace Zapi\Modules\Reseller\Transaction\Services\TransactionService;

trait TransactionServicePart01Segment
{
        /**
         * GET /api/reseller/transactions/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $fromDate = $this->request->getGet('fromDate');
            $toDate = $this->request->getGet('toDate');
    
            $builder = $this->transaction_model->where('admin_id', $resellerId);
    
            if (!empty($fromDate) && !empty($toDate)) {
                $builder->where('created_at >=', $fromDate)->where('created_at <=', $toDate);
            } elseif (!empty($fromDate)) {
                $builder->where('created_at >=', $fromDate);
            }
    
            $pager = $this->getPaginationParams();
            $totalFound = (int) $builder->countAllResults(false);
            $transactions = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            $result = [];
            foreach ($transactions as $t) {
                $row = is_object($t) ? (array) $t : $t;
                $row['customer_name'] = getUserById($row['customer'])->name ?? '--';
                $result[] = $row;
            }
    
            return $this->respondPaginatedSuccess($result, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * DELETE /api/reseller/transactions/{resellerId}
         */
        public function delete($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }

            /* The delete below is scoped only by the URL-supplied {resellerId}, so
               any reseller could DELETE /api/reseller/transactions/{victim_id} and
               destroy another reseller's financial history. Verify ownership. */
            if (!$this->canAccessReseller((int) $resellerId)) {
                return $this->respondError((string) 'Access denied', 403, 'REQUEST_FAILED');
            }

            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            $ids = $input['ids'] ?? null;
    
            if (empty($ids) || !is_array($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $result = $this->transaction_model->where('admin_id', $resellerId)->whereIn('id', $ids)->delete();
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Deleted successfully']);
            }
    
            return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
        }
    
}

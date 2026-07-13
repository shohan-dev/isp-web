<?php

namespace Zapi\Modules\Reseller\Payment\Services\PaymentService;

trait PaymentServicePart01Segment
{
        /**
         * GET /api/reseller/payments/{resellerId}
         * Reseller's own payments (POP payments where user_type = 'reseller' or paidby = reseller)
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $fromDate = $this->request->getGet('fromDate');
            $toDate = $this->request->getGet('toDate');
            $status = $this->request->getGet('status');
    
            $builder = $this->payment_model->builder()->select('*');
    
            $builder->groupStart()
                ->where('user_id', $resellerId)
                ->orWhere('paidby', $resellerId)
                ->orWhere('admin_id', $resellerId)
                ->orWhere('paid_to', $resellerId)
                ->groupEnd();
    
            if (!empty($status)) {
                $builder->where('status', $status);
            }
    
            if (!empty($fromDate) && !empty($toDate)) {
                $builder->where('DATE(created_at) >=', $fromDate)
                    ->where('DATE(created_at) <=', $toDate);
            }
    
            $pager = $this->getPaginationParams();
            $totalFound = (int) $builder->countAllResults(false);
            $payments = $builder
                ->orderBy('id', 'desc')
                ->limit($pager['per_page'], $pager['offset'])
                ->get()
                ->getResultArray();
    
            $result = [];
            foreach ($payments as $row) {
                $row['customer_name'] = getUserById($row['user_id'])->name ?? '--';
                $row['paid_to_name'] = !empty($row['paid_to']) ? (getUserById($row['paid_to'])->name ?? '--') : '--';
                $result[] = $row;
            }
    
            return $this->respondPaginatedSuccess($result, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
}

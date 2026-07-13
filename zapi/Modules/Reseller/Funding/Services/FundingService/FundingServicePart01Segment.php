<?php

namespace Zapi\Modules\Reseller\Funding\Services\FundingService;

trait FundingServicePart01Segment
{
        private function toArray($row)
        {
            if (is_array($row)) {
                return $row;
            }
    
            return is_object($row) ? (array) $row : [];
        }
    
        private function asFloat($value)
        {
            return (float) (is_numeric($value) ? $value : 0);
        }
    
        private function asInt($value)
        {
            return (int) (is_numeric($value) ? $value : 0);
        }
    
        private function normalizeType($type)
        {
            $value = strtolower(trim((string) $type));
    
            if (in_array($value, ['all', 'both'], true)) {
                return 'all';
            }
    
            if (in_array($value, ['invoice', 'customer_invoice', 'customer-payment', 'customer_payment'], true)) {
                return 'customer_invoice';
            }
    
            return 'self_recharge';
        }
    
        private function normalizeIds($ids)
        {
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)), 'strlen');
                }
            }
    
            if (!is_array($ids)) {
                return [];
            }
    
            $result = [];
            foreach ($ids as $id) {
                if (is_numeric($id) && (int) $id > 0) {
                    $result[] = (int) $id;
                }
            }
    
            return array_values(array_unique($result));
        }
    
        private function normalizeDateForFilter($date, $isEnd = false)
        {
            $raw = trim((string) $date);
            if ($raw === '') {
                return null;
            }
    
            $raw = str_replace('T', ' ', $raw);
            $parsed = strtotime($raw);
            if ($parsed === false) {
                return null;
            }
    
            return $isEnd ? date('Y-m-d 23:59:59', $parsed) : date('Y-m-d 00:00:00', $parsed);
        }
    
        private function getUserName($id)
        {
            $uid = $this->asInt($id);
            if ($uid <= 0) {
                return '--';
            }
    
            $user = getUserById($uid);
            if (is_object($user)) {
                return $user->name ?? '--';
            }
            if (is_array($user)) {
                return $user['name'] ?? '--';
            }
    
            return '--';
        }
    
        private function getResellerFund($reseller)
        {
            $row = $this->toArray($reseller);
            if (array_key_exists('fund', $row)) {
                return $this->asFloat($row['fund']);
            }
    
            return is_object($reseller) ? $this->asFloat($reseller->fund ?? 0) : 0.0;
        }
    
        private function getResellerAdminId($reseller, $resellerId)
        {
            $row = $this->toArray($reseller);
            if (array_key_exists('admin_id', $row)) {
                return $this->asInt($row['admin_id']);
            }

            if (is_object($reseller) && isset($reseller->admin_id)) {
                return $this->asInt($reseller->admin_id);
            }

            return $this->asInt($resellerId);
        }

        /**
         * Resolves the authenticated actor's user id by re-decoding the Bearer
         * token. `Request::$globals` (what JwtAuthFilter writes `jwt_user_id`
         * into via setGlobal()) is a protected property with no public getter
         * counterpart in CI4 — `getGlobal()` does not exist, so reading it back
         * from outside RequestTrait's own class always silently returns 0.
         * Mirrors the working Zapi\Modules\Reseller\Core\Services\ResellerBaseService::jwtPayload().
         */
        private function actorIdFromToken(): int
        {
            $authHeader = (string) $this->request->getHeaderLine('Authorization');
            if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
                return 0;
            }
            $token = trim(substr($authHeader, 7));
            if (stripos($token, 'bearer ') === 0) {
                $token = trim(substr($token, 7));
            }
            $payload = \Zapi\Core\Support\Auth\JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
            if (!is_array($payload)) {
                return 0;
            }
            $uid = $payload['sub'] ?? $payload['id'] ?? $payload['user_id'] ?? null;
            return ($uid !== null && $uid !== '') ? $this->asInt($uid) : 0;
        }

        /**
         * BOLA guard: JWT subject must own the reseller row or be its parent admin.
         */
        private function assertOwnsReseller(int $resellerId)
        {
            $jwtUserId = $this->actorIdFromToken();

            if ($jwtUserId <= 0) {
                return $this->respondError((string) 'Unauthorized', 401, 'UNAUTHORIZED');
            }

            if ($jwtUserId === $resellerId) {
                return null;
            }

            $reseller = $this->user_model->find($resellerId);
            if (! empty($reseller)) {
                $parentId = $this->asInt(is_array($reseller) ? ($reseller['admin_id'] ?? 0) : ($reseller->admin_id ?? 0));
                if ($parentId > 0 && $parentId === $jwtUserId) {
                    return null;
                }
            }

            return $this->respondError((string) 'Forbidden', 403, 'FORBIDDEN');
        }
    
        private function mapSelfRechargeRow(array $row)
        {
            return [
                'id' => $this->asInt($row['id'] ?? 0),
                'recharge_type' => 'self_recharge',
                'customer' => $this->asInt($row['customer'] ?? 0),
                'customer_name' => $this->getUserName($row['customer'] ?? 0),
                'admin_id' => $this->asInt($row['admin_id'] ?? 0),
                'invoice_number' => (string) ($row['invoice_number'] ?? ''),
                'amount' => $this->asFloat($row['amount'] ?? 0),
                'received_amount' => $this->asFloat($row['received_amount'] ?? 0),
                'paid_via' => (string) ($row['paid_via'] ?? ''),
                'received_date' => (string) ($row['received_date'] ?? ''),
                'comments' => (string) ($row['comments'] ?? ''),
                'status' => strtolower((string) ($row['status'] ?? 'pending')),
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }
    
        private function mapCustomerInvoiceRow(array $row)
        {
            return [
                'id' => $this->asInt($row['id'] ?? 0),
                'recharge_type' => 'customer_invoice',
                'customer' => $this->asInt($row['user_id'] ?? 0),
                'customer_name' => $this->getUserName($row['user_id'] ?? 0),
                'admin_id' => $this->asInt($row['admin_id'] ?? 0),
                'invoice_number' => (string) ($row['invoice'] ?? ''),
                'amount' => $this->asFloat($row['amount'] ?? 0),
                'received_amount' => $this->asFloat($row['pay_amount'] ?? $row['amount'] ?? 0),
                'paid_via' => (string) ($row['paid_via'] ?? ''),
                'method_trx' => (string) ($row['method_trx'] ?? ''),
                'month' => (string) ($row['month'] ?? ''),
                'paid_to' => $this->asInt($row['paid_to'] ?? 0),
                'paid_to_name' => $this->getUserName($row['paid_to'] ?? 0),
                'paidby' => $this->asInt($row['paidby'] ?? 0),
                'status' => strtolower((string) ($row['status'] ?? 'pending')),
                'received_date' => $row['paid_at'] ?? null,
                'comments' => '',
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['paid_at'] ?? null,
            ];
        }
    
        private function sortByCreatedAtDesc(array $records)
        {
            usort($records, static function ($a, $b) {
                $aTime = strtotime((string) ($a['created_at'] ?? '1970-01-01 00:00:00'));
                $bTime = strtotime((string) ($b['created_at'] ?? '1970-01-01 00:00:00'));
                return $bTime <=> $aTime;
            });
    
            return $records;
        }
    
        private function summarize(array $records)
        {
            $summary = [
                'count' => count($records),
                'total_amount' => 0.0,
                'total_received_amount' => 0.0,
                'successful_count' => 0,
                'pending_count' => 0,
                'failed_count' => 0,
            ];
    
            foreach ($records as $row) {
                $summary['total_amount'] += $this->asFloat($row['amount'] ?? 0);
                $summary['total_received_amount'] += $this->asFloat($row['received_amount'] ?? 0);
    
                $status = strtolower((string) ($row['status'] ?? 'pending'));
                if ($status === 'successful') {
                    $summary['successful_count']++;
                } elseif ($status === 'pending') {
                    $summary['pending_count']++;
                } else {
                    $summary['failed_count']++;
                }
            }
    
            $summary['total_amount'] = round($summary['total_amount'], 2);
            $summary['total_received_amount'] = round($summary['total_received_amount'], 2);
    
            return $summary;
        }
    
        /**
         * GET /api/reseller/funding/{resellerId}
         */
        public function fetch($resellerId = null)
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
    
            $fromDate = $this->request->getGet('fromDate');
            $toDate = $this->request->getGet('toDate');
            $status = strtolower(trim((string) $this->request->getGet('status')));
            $customerId = $this->asInt($this->request->getGet('customer_id'));
            $rechargeType = $this->normalizeType($this->request->getGet('recharge_type') ?? 'all');
    
            $fromFilter = $this->normalizeDateForFilter($fromDate, false);
            $toFilter = $this->normalizeDateForFilter($toDate, true);
    
            $selfRecords = [];
            if ($rechargeType === 'all' || $rechargeType === 'self_recharge') {
                $builder = $this->funding_model->where('customer', (int) $resellerId);
    
                if ($status !== '') {
                    $builder->where('status', $status);
                }
                if (!empty($fromFilter)) {
                    $builder->where('created_at >=', $fromFilter);
                }
                if (!empty($toFilter)) {
                    $builder->where('created_at <=', $toFilter);
                }
    
                $rows = $builder->orderBy('id', 'desc')->findAll();
                foreach ($rows as $row) {
                    $selfRecords[] = $this->mapSelfRechargeRow($this->toArray($row));
                }
            }
    
            $invoiceRecords = [];
            if ($rechargeType === 'all' || $rechargeType === 'customer_invoice') {
                $builder = $this->payment_model
                    ->where('user_type', 'user')
                    ->where('admin_id', (int) $resellerId);
    
                if ($status !== '') {
                    $builder->where('status', $status);
                }
                if ($customerId > 0) {
                    $builder->where('user_id', $customerId);
                }
                if (!empty($fromFilter)) {
                    $builder->where('created_at >=', $fromFilter);
                }
                if (!empty($toFilter)) {
                    $builder->where('created_at <=', $toFilter);
                }
    
                $rows = $builder->orderBy('id', 'desc')->findAll();
                foreach ($rows as $row) {
                    $invoiceRecords[] = $this->mapCustomerInvoiceRow($this->toArray($row));
                }
            }
    
            $allRecords = $this->sortByCreatedAtDesc(array_merge($selfRecords, $invoiceRecords));
    
            $data = $allRecords;
            if ($rechargeType === 'self_recharge') {
                $data = $selfRecords;
            } elseif ($rechargeType === 'customer_invoice') {
                $data = $invoiceRecords;
            }
            $pager = $this->getPaginationParams();
            $totalFound = count($data);
            $pagedData = array_slice($data, $pager['offset'], $pager['limit']);
    
            return $this->respondPayload([
                'status' => 'success',
                'message' => 'Funding history fetched successfully',
                'recharge_type' => $rechargeType,
                'current_fund' => round($this->getResellerFund($reseller), 2),
                'summary' => [
                    'total_records' => $totalFound,
                    'self_recharge' => $this->summarize($selfRecords),
                    'customer_invoice' => $this->summarize($invoiceRecords),
                ],
                'count' => count($pagedData),
                'data' => $pagedData,
                'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedData)),
            ]);
        }
    
        /**
         * POST /api/reseller/funding/{resellerId}
         */
        public function create($resellerId = null)
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
            if ($rechargeType === 'customer_invoice') {
                return $this->createCustomerInvoice($resellerId, $reseller, $input);
            }
    
            return $this->createSelfRecharge($resellerId, $reseller, $input);
        }
    
}

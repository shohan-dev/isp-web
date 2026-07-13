<?php

namespace Zapi\Modules\Reseller\Subscription\Services\SubscriptionService;

trait SubscriptionServicePart02Segment
{
        protected function buildPaymentMonthStatusMap(array $payments): array
        {
            $currentYear = date('Y');
            $monthStatus = [];
    
            foreach ($payments as $payment) {
                $createdAt = $payment->created_at ?? null;
                if (empty($createdAt)) {
                    continue;
                }
                $year = date('Y', strtotime($createdAt));
                if ($year !== $currentYear) {
                    continue;
                }
    
                $month = (string) ($payment->month ?? '');
                if ($month === '') {
                    continue;
                }
                $status = (($payment->status ?? '') === 'successful') ? 'Paid' : 'Due';
                $monthStatus[$month] = $status;
            }
    
            return $monthStatus;
        }
    
        protected function normalizeDateTime(string $dateTime): string
        {
            $normalized = str_replace('T', ' ', trim($dateTime));
            if (strlen($normalized) === 16) {
                $normalized .= ':00';
            }
            return $normalized;
        }
    
        protected function validateSubscriptionDates(string $willExpire, string $selectedMonth): array
        {
            $now = date('Y-m-d H:i:s');
            $willExpireTs = strtotime($willExpire);
            $nowTs = strtotime($now);
    
            if ($willExpireTs === false) {
                return ['success' => false, 'message' => 'Invalid expiration date format'];
            }
    
            $monthNow = date('F', $nowTs);
            $monthWillExpire = date('F', $willExpireTs);
            $nextMonth = date('F', strtotime('+1 month', $nowTs));
            $allowedMonths = [$monthNow, $monthWillExpire, $nextMonth];
    
            if (!in_array($selectedMonth, $allowedMonths, true)) {
                return ['success' => false, 'message' => 'Selected month is not allowed based on current or expiration date.'];
            }
    
            return ['success' => true];
        }
    
        /**
         * Reseller selling price for a package id, matching Subscription::renew / ResellerPackagePrice()
         * when session user is the reseller (explicit id for API).
         */
        protected function resellerPackagePriceForApi(int $resellerId, string $packageId): float
        {
            if ($packageId === '') {
                return 0.0;
            }
            $p = ResellerPackagePrice($packageId, null, $resellerId, 'resellerAdmin');
            if ($p !== null && $p !== '--' && is_numeric($p)) {
                return (float) $p;
            }
            $info = $this->getResellerPackagePriceInfo($resellerId, $packageId);
    
            return (float) $info['selling_price'];
        }
    
        /**
         * Mirrors isp-core Subscription::renew() reseller pricing: fund check on current package,
         * full month when package unchanged, and |prorated old credit − new month| when package changes.
         */
        protected function calculateSubscriptionData($customer, string $willExpire, string $packageId, int $resellerId, string $paymentStatus = 'pending'): array
        {
            $now = time();
            $willExpireTs = strtotime($willExpire);
            if ($willExpireTs === false) {
                return ['success' => false, 'message' => 'Invalid expiration date format'];
            }
    
            $prevExpireTs = !empty($customer->will_expire) ? strtotime($customer->will_expire) : $now;
            if ($prevExpireTs === false) {
                $prevExpireTs = $now;
            }
            $previousPackageId = (string) ($customer->package_id ?? '');
    
            $enforceFund = strtolower(trim($paymentStatus)) !== 'pending';
    
            // Subscription.php ~195–204: fund must cover current (old) package price before proceeding.
            if ($enforceFund) {
                $fundCheckPkg = $previousPackageId !== '' ? $previousPackageId : $packageId;
                $fundCheckPrice = $this->resellerPackagePriceForApi($resellerId, $fundCheckPkg);
                if ($fundCheckPrice <= 0) {
                    return ['success' => false, 'message' => 'Please select a valid package.'];
                }
    
                $reseller = $this->user_model->find($resellerId);
                $fund = (float) ($reseller->fund ?? 0);
                if ($fund < $fundCheckPrice) {
                    return ['success' => false, 'message' => "Your Reseller doesn't have enough fund. Please contact with him."];
                }
            }
    
            $priceInfo = $this->getResellerPackagePriceInfo($resellerId, $packageId);
            $tprice = $this->resellerPackagePriceForApi($resellerId, $packageId);
            if ($tprice <= 0) {
                return ['success' => false, 'message' => 'Please select a valid package.'];
            }
    
            // Phase 5 unification (2026-06-21): a package CHANGE is priced by the ONE
            // canonical rule App\Services\BillingService::quote() = (newMonthly/30)*days,
            // over the SAME window the web upgrade (Subscription.php:211,335) and the
            // customer self-serve path (Customer/.../SubscriptionService.php:309) use —
            // days from now to the requested expiry — so every channel reconciles.
            // Was |int(oldFull/30*remaining) - int(newFull)| (450 for 300->600/15d) -> now 300.
            // Using the now->willExpire window (not old remaining days) also removes the
            // 0-charge / under-charge holes: a real future expiry always bills > 0 over the
            // period the customer actually receives. Price is rounded to 2dp before it
            // flows to the fund/ledger/payment (payments.amount is a varchar, gateway-read).
            // Same-package renewal keeps the existing full-month behavior, which matches the
            // web renew path; prorating it is a separate web-wide decision.
            if ($previousPackageId !== '' && $previousPackageId !== $packageId) {
                $changeDays = 0;
                if ($willExpireTs > $now) {
                    $changeDays = (int) floor(($willExpireTs - $now) / (60 * 60 * 24));
                }
                $oldFull = $this->resellerPackagePriceForApi($resellerId, $previousPackageId);
                $newFull = $this->resellerPackagePriceForApi($resellerId, $packageId);
                if ($oldFull <= 0 || $newFull <= 0) {
                    return ['success' => false, 'message' => 'Please select a valid package.'];
                }
                $difference = $changeDays;
                // Degenerate input guard: a package change with a non-future expiry
                // (changeDays <= 0) must NOT bill quote(newFull, 0) = 0 (a free upgrade).
                // Fall back to a full new month — the same outcome the customer self-serve
                // path produces (it skips the proration when difference <= 0).
                $price = $changeDays > 0
                    ? round((new \App\Services\BillingService())->quote((float) $newFull, $changeDays), 2)
                    : round((float) $newFull, 2);
            } else {
                // Same package or first assignment: full monthly (reseller) price, like web renew.
                $price = round((float) $tprice, 2);
                if (($customer->subscription_status ?? '') === 'active' && $prevExpireTs > $now) {
                    $difference = $this->calendarDaysBetweenTimestamps($prevExpireTs, $willExpireTs);
                } else {
                    $difference = $this->calendarDaysBetweenTimestamps($now, $willExpireTs);
                }
            }
    
            if ($difference < 0) {
                return ['success' => false, 'message' => 'Select the expiration date correctly.'];
            }
    
            $reseller = $this->user_model->find($resellerId);
            $fund = (float) ($reseller->fund ?? 0);
    
            if ($enforceFund && $fund < $price) {
                return ['success' => false, 'message' => "Don't have enough fund. Please recharge."];
            }
    
            return [
                'success' => true,
                'difference' => (int) $difference,
                'price' => (float) $price,
                'tprice' => (float) $tprice,
                'originalPrice' => (float) $priceInfo['price'],
                'remaining_fund' => (float) ($fund - ($enforceFund ? $price : 0)),
            ];
        }
    
        /**
         * Whole calendar days between two Unix timestamps (local date; matches Flutter billing days).
         */
        protected function calendarDaysBetweenTimestamps(int $fromTs, int $toTs): int
        {
            $from = new \DateTime('@' . $fromTs);
            $to = new \DateTime('@' . $toTs);
            $from->setTime(0, 0, 0);
            $to->setTime(0, 0, 0);
            if ($to < $from) {
                return -1;
            }
    
            return (int) $from->diff($to)->days;
        }
    
        protected function getResellerPackagePriceInfo(int $resellerId, string $packageId): array
        {
            $details = $this->getResellerPackageDetails($resellerId);
            foreach ($details as $pkg) {
                if ((string) ($pkg['id'] ?? '') !== (string) $packageId) {
                    continue;
                }
    
                $basePrice = is_numeric($pkg['price'] ?? null) ? (float) $pkg['price'] : 0.0;
                $sellingPrice = is_numeric($pkg['selling_price'] ?? null) && (float) $pkg['selling_price'] > 0
                    ? (float) $pkg['selling_price']
                    : $basePrice;
    
                return [
                    'price' => $basePrice,
                    'selling_price' => $sellingPrice,
                ];
            }
    
            return ['price' => 0.0, 'selling_price' => 0.0];
        }
    
        protected function handleRouterProfileAndStatus($customer, string $pppoeProfile, bool $isActive): array
        {
            try {
                $routerClient = routerClient($customer->router_id ?? null);
                if (!($routerClient instanceof \RouterOS\Client)) {
                    return ['success' => true];
                }
    
                $pppoe = getPPPoEUserUserId($routerClient, $customer->id);
                $pppoeId = $pppoe[0]['.id'] ?? ($customer->pppoe_id ?? null);
                if (empty($pppoeId)) {
                    return ['success' => true];
                }
    
                $userPpp = getPPPoEUser($routerClient, $pppoeId);
                $pppoeName = $userPpp[0]['name'] ?? '--';
                $pppoePassword = $userPpp[0]['password'] ?? '--';
                $pppoeService = $userPpp[0]['service'] ?? '--';
    
                if ($pppoeProfile !== '') {
                    updatePPPoEUser($routerClient, [
                        'pppoe_name' => $pppoeName,
                        'pppoe_password' => $pppoePassword,
                        'pppoe_service' => $pppoeService,
                        'pppoe_profile' => $pppoeProfile,
                        'pppoe_id' => $pppoeId,
                    ]);
                }
    
                if ($isActive) {
                    enablePPPoEUser($routerClient, $pppoeId);
                } else {
                    disablePPPoEUser($routerClient, $pppoeId);
                }
    
                return ['success' => true];
            } catch (\Throwable $e) {
                log_message('error', 'Router operation failed: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Router operation failed'];
            }
        }
    
        protected function processResellerPayment(
            int $customerId,
            $customer,
            int $resellerId,
            string $selectedMonth,
            string $paidVia,
            string $methodTrx,
            string $status,
            array $calc
        ): array {
            $paymentModel = model('App\Models\Payment');
            $existing = $paymentModel->where([
                'user_id' => $customerId,
                'paidby' => $resellerId,
                'month' => $selectedMonth,
            ])->first();
    
            $statusNorm = strtolower(trim($status));
            $isPaid = ($statusNorm === 'successful');
    
            if ($isPaid) {
                // BUG-09 fix: atomic deduct replaces the unsafe RMW pattern.
                if (!(new \App\Services\FundService())->deduct((int) $resellerId, (float) $calc['price'])) {
                    return ['success' => false, 'message' => 'Insufficient reseller fund for this renewal'];
                }
    
                $transactionModel = model('App\Models\ResellerTransactions');
                $txnInserted = $transactionModel->insert([
                    'customer' => $customerId,
                    'admin_id' => $resellerId,
                    'amount' => $calc['price'],
                    'package_price' => $calc['tprice'],
                    'active_for' => $calc['difference'],
                    'comments' => 'Customer Subscription Updated',
                ]);
                if (!$txnInserted) {
                    return ['success' => false, 'message' => 'Failed to create reseller transaction'];
                }
            }
    
            $payData = [
                'user_id' => $customerId,
                'user_type' => 'user',
                'admin_id' => $customer->admin_id,
                'paidby' => $resellerId,
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $calc['price'],
                'pay_amount' => $calc['tprice'],
                'month' => $selectedMonth,
                'paid_via' => $paidVia,
                'paid_to' => $resellerId,
                'method_trx' => $methodTrx,
                'status' => $isPaid ? 'successful' : 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ];
    
            if ($isPaid) {
                $payData['paid_at'] = date('Y-m-d H:i:s');
            }
    
            $paymentId = null;
            if (!empty($existing)) {
                $payData['amount'] = (float) ($existing->amount ?? 0) + (float) $calc['price'];
                if (!$paymentModel->update($existing->id, $payData)) {
                    return ['success' => false, 'message' => 'Failed to update payment'];
                }
                $paymentId = (int) $existing->id;
            } else {
                $inserted = $paymentModel->insert($payData);
                if (!$inserted) {
                    return ['success' => false, 'message' => 'Failed to create payment'];
                }
                $paymentId = (int) $paymentModel->getInsertID();
            }
    
            $paymentUrl = !empty($paymentId) ? route_to('Reseller.payment.pay', $paymentId) : null;
    
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_url' => $paymentUrl,
            ];
        }
    
}

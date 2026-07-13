<?php

namespace Zapi\Modules\Customer\Billing\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class BillingService extends CustomerBaseService
{
    public function getPaymentInfo()
    {
        $userId = $this->request->getGet('user_id');
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $paidAmount   = $this->sumUserSelfPayments((int) $userId, 'successful');
        $pendingAmount = $this->sumUserSelfPayments((int) $userId, 'pending');

        $packageName  = $this->resolvePackageName($user);
        $monthlyPrice = $this->resolveMonthlyPrice($user);

        return $this->respondSuccess([
            'user_id'           => (int) $userId,
            'user_name'         => $user->name ?? 'Unknown',
            'current_balance'   => (float) ($user->fund ?? 0),
            'pending_amount'    => $pendingAmount,
            'paid_amount'       => $paidAmount,
            'last_payment_date' => $user->last_renewed ?? null,
            'next_due_date'     => $user->will_expire ?? null,
            'package_name'      => $packageName,
            'monthly_price'     => $monthlyPrice,
            'currency'          => 'BDT',
        ]);
    }

    public function getInvoice($invoiceId)
    {
        $userId = $this->request->getGet('user_id');
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $invoice = $this->getInvoiceFromDB($invoiceId, (int) $userId);

        if (!$invoice) {
            return $this->respondError('Invoice not found', 404);
        }

        return $this->respondSuccess($invoice);
    }

    public function initiatePayment($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $monthlyPrice = $this->resolveMonthlyPrice($user);
        $amount       = $this->request->getGet('amount') ?? $monthlyPrice;

        return $this->respondSuccess([
            'user_id'         => (int) $userId,
            'amount'          => (float) $amount,
            'payment_url'     => base_url('payment/' . $userId),
            'payment_methods' => $this->getAvailablePaymentMethods((int) $userId),
            'expires_at'      => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ]);
    }

    public function getPaymentHistory($userId, $limit = 10)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $history = $this->getPaymentsFromDB((int) $userId, (int) $limit);

        return $this->respondSuccess([
            'user_id'      => (int) $userId,
            'total_count'  => count($history),
            'total_amount' => array_sum(array_column($history, 'amount')),
            'payments'     => $history,
        ]);
    }

    public function getDueReminders($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $dueDate      = $user->will_expire ?? null;
        $daysUntilDue = $dueDate ? (int) round((strtotime($dueDate) - time()) / 86400) : 0;

        // BUG-08 fix: use the real outstanding pending balance, not the flat
        // monthly price — a user 3 months overdue should see the full amount owed,
        // not just one month's cost. Fall back to the monthly price when no
        // pending sum exists (e.g. brand-new account with no payment rows yet).
        $pendingBalance = $this->sumUserSelfPayments((int) $userId, 'pending');
        $dueAmount = $pendingBalance > 0 ? $pendingBalance : $this->resolveMonthlyPrice($user);

        return $this->respondSuccess([
            'user_id'      => (int) $userId,
            'due_amount'   => $dueAmount,
            'due_date'     => $dueDate,
            'days_until_due' => $daysUntilDue,
            'reminder_sent'  => $daysUntilDue <= 7,
            'auto_suspend'   => $daysUntilDue <= 0,
            'messages'       => $this->getReminderMessages($daysUntilDue),
        ]);
    }

    public function getAutoSuspendStatus($userId)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $user = $this->getUser($userId);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $isSuspended = strtolower((string) ($user->subscription_status ?? '')) !== 'active';
        $willExpire  = $user->will_expire ?? null;

        return $this->respondSuccess([
            'user_id'                => (int) $userId,
            'is_suspended'           => $isSuspended,
            'suspension_date'        => $isSuspended ? $willExpire : null,
            'auto_suspend_enabled'   => true,
            'suspend_threshold_days' => 0,
            'grace_period_hours'     => 24,
        ]);
    }

    public function toggleAutopay($userId, $enabled)
    {
        if (!$userId) {
            return $this->respondError('User ID is required', 400);
        }

        $enabled = (bool) $enabled;
        $key     = "autopay_user_{$userId}";

        try {
            \Config\Services::cache()->save($key, $enabled ? 1 : 0, 86400 * 365);
        } catch (\Throwable $e) {
            log_message('warning', 'toggleAutopay cache write failed: ' . $e->getMessage());
        }

        return $this->respondSuccess([
            'user_id'          => (int) $userId,
            'autopay_enabled'  => $enabled,
            'message'          => $enabled ? 'Auto-pay enabled successfully' : 'Auto-pay disabled',
        ]);
    }

    // ── private helpers ────────────────────────────────────────────────────────

    private function getInvoiceFromDB(string $invoiceId, int $userId): ?array
    {
        $db  = \Config\Database::connect();
        $row = $db->table('payments')
            ->where('id', $invoiceId)
            ->where('user_id', $userId)
            ->get()
            ->getRow();

        if (!$row) {
            return null;
        }

        $user        = $this->user_model->find($userId);
        $packageName = $user ? $this->resolvePackageName($user) : 'Unknown';

        return [
            'invoice_id'    => (string) $row->id,
            'user_id'       => $userId,
            'user_name'     => $user->name ?? 'Unknown',
            'package_name'  => $packageName,
            'billing_month' => $row->month ?? date('F Y'),
            'period'        => $row->period ?? null,
            'amount'        => (float) ($row->amount ?? 0),
            'pay_amount'    => (float) ($row->pay_amount ?? $row->amount ?? 0),
            'status'        => $row->status ?? 'unknown',
            'method'        => $row->paid_via ?? null,
            'transaction_id'=> $row->method_trx ?? null,
            'created_at'    => $row->created_at ?? null,
            'paid_at'       => $row->paid_at ?? null,
            'comment'       => $row->comment ?? null,
        ];
    }

    private function getPaymentsFromDB(int $userId, int $limit): array
    {
        $rows = $this->payment_model
            ->where('user_id', $userId)
            ->where('user_type', 'user')
            ->orderBy('created_at', 'DESC')
            ->limit(max(1, min(100, $limit)))
            ->findAll();

        return array_map(static function ($row) {
            $r = is_object($row) ? $row : (object) $row;
            return [
                'id'             => (string) $r->id,
                'date'           => $r->created_at ?? null,
                'paid_at'        => $r->paid_at ?? null,
                'amount'         => (float) ($r->amount ?? 0),
                'pay_amount'     => (float) ($r->pay_amount ?? $r->amount ?? 0),
                'month'          => $r->month ?? null,
                'period'         => $r->period ?? null,
                'method'         => $r->paid_via ?? null,
                'transaction_id' => $r->method_trx ?? null,
                'status'         => $r->status ?? 'unknown',
                'comment'        => $r->comment ?? null,
            ];
        }, $rows);
    }

    private function getAvailablePaymentMethods(int $userId): array
    {
        $user    = $this->user_model->find($userId);
        $adminId = $user ? (int) ($user->admin_id ?? 0) : 0;
        $methods = [];

        try {
            $gateways = \Config\Services::settings()->get('payment_gateways', null, "user_{$adminId}");
            if (is_string($gateways)) {
                $gateways = json_decode($gateways, true);
            }
            if (is_array($gateways)) {
                foreach ($gateways as $gw) {
                    $methods[] = ['name' => $gw['name'] ?? 'Unknown', 'type' => $gw['type'] ?? 'online'];
                }
            }
        } catch (\Throwable $e) {
            // fall back to a default list
        }

        if (empty($methods)) {
            $methods = [
                ['name' => 'bKash', 'type' => 'mobile'],
                ['name' => 'Nagad', 'type' => 'mobile'],
                ['name' => 'Bank Transfer', 'type' => 'bank'],
            ];
        }

        return $methods;
    }

    private function resolvePackageName(object $user): string
    {
        $pkgId = (int) ($user->package_id ?? 0);
        if ($pkgId <= 0) {
            return 'Unknown';
        }
        $pkg = $this->package_model->find($pkgId);
        if (!$pkg) {
            return 'Unknown';
        }
        return is_object($pkg) ? ($pkg->package_name ?? 'Unknown') : ($pkg['package_name'] ?? 'Unknown');
    }

    private function resolveMonthlyPrice(object $user): float
    {
        $pkgId = (int) ($user->package_id ?? 0);
        if ($pkgId <= 0) {
            return 0.0;
        }
        $pkg = $this->package_model->find($pkgId);
        if (!$pkg) {
            return 0.0;
        }
        return (float) (is_object($pkg) ? ($pkg->price ?? 0) : ($pkg['price'] ?? 0));
    }

    private function getReminderMessages(int $days): array
    {
        if ($days > 7) {
            return ['Your payment is due on ' . date('Y-m-d', strtotime("+{$days} days"))];
        }
        if ($days > 0) {
            return ["Reminder: Your payment is due in {$days} day(s)"];
        }
        if ($days === 0) {
            return ['Payment is due today!'];
        }
        return ['Your service may be suspended. Please pay immediately.'];
    }
}

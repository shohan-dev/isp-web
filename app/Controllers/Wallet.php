<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminPackage;
use App\Models\TenantWallet;
use App\Models\WalletTransaction;
use App\Services\PaygBillingService;
use App\Services\WalletService;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

/**
 * Tenant (sAdmin) platform wallet — Pay-As-You-Go balance, top-ups, add-ons
 * and the transaction ledger. Suspended tenants can still reach these routes
 * (whitelisted in AuthCheck) so they can top up and reactivate.
 */
class Wallet extends BaseController
{
    protected WalletService $walletService;
    protected PaygBillingService $billing;

    public function __construct()
    {
        $this->walletService = new WalletService();
        $this->billing = new PaygBillingService($this->walletService);
    }

    protected function currentSAdmin()
    {
        if (getSession('user_role') !== 'admin') {
            return null;
        }

        return model('App\Models\User')->find(getSession('user_id'));
    }

    public function index()
    {
        $user = $this->currentSAdmin();
        if (!$user) {
            return redirect()->route('route.dashboard');
        }

        $wallet = $this->walletService->ensureWallet((int) $user->id);
        $plan = $this->billing->paygPlan();
        $estimate = $this->billing->estimate((int) $user->id);
        $isPayg = $this->billing->isPaygUser($user);

        $data = [
            'title' => 'My Wallet',
            'user' => $user,
            'wallet' => $wallet,
            'plan' => $plan,
            'estimate' => $estimate,
            'isPayg' => $isPayg,
            'trialUser' => $user,
            'addonCatalog' => AdminPackage::addonCatalog($plan),
            'chosenAddons' => TenantWallet::chosenAddons($wallet),
        ];

        return view('wallet/index', $data);
    }

    /**
     * Create a pending top-up invoice and hand off to the gateway page.
     */
    public function topup()
    {
        $user = $this->currentSAdmin();
        if (!$user) {
            return requestResponse('error', 'Only ISP admin accounts have a platform wallet.', 403);
        }

        $amount = (float) $this->request->getPost('amount');
        if ($amount < 100) {
            return requestResponse('error', 'Minimum top-up amount is ৳100.', 400);
        }
        if ($amount > 500000) {
            return requestResponse('error', 'Maximum top-up amount is ৳500,000.', 400);
        }

        $paymentModel = model('App\Models\Payment');
        $paymentModel->insert([
            'user_id' => $user->id,
            'admin_id' => null, // platform-bound payment
            'paidby' => $user->id,
            'user_type' => 'admin',
            'invoice' => 'INV-' . random_int(100000, 999999),
            'amount' => $amount,
            'month' => date('F'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'custom_data' => json_encode(['purpose' => 'wallet_topup']),
        ]);
        $paymentId = $paymentModel->getInsertID();

        return requestResponse('success', [
            'msg' => 'Top-up invoice created. Complete the payment to add the balance to your wallet.',
            'payment_url' => route_to('route.payment.pay', $paymentId),
        ], 200);
    }

    /**
     * Save the tenant's PAYG add-on choices (affects the next cycle charge).
     */
    public function updateAddons()
    {
        $user = $this->currentSAdmin();
        if (!$user) {
            return requestResponse('error', 'Only ISP admin accounts have a platform wallet.', 403);
        }

        $wallet = $this->walletService->ensureWallet((int) $user->id);
        $catalog = AdminPackage::addonCatalog($this->billing->paygPlan());

        $chosen = array_values(array_intersect(
            array_map('strval', (array) ($this->request->getPost('addons') ?? [])),
            array_keys($catalog)
        ));

        model('App\Models\TenantWallet')->update($wallet->id, ['addons' => json_encode($chosen)]);

        return requestResponse('success', ['msg' => 'Add-ons updated. They apply from your next monthly charge.'], 200);
    }

    /**
     * Switch this tenant to the Pay-As-You-Go plan. Remaining paid days carry
     * over: PAYG billing takes over when the current expiry passes.
     */
    public function switchToPayg()
    {
        $user = $this->currentSAdmin();
        if (!$user) {
            return requestResponse('error', 'Only ISP admin accounts can switch plans.', 403);
        }

        if ($this->billing->isPaygUser($user)) {
            return requestResponse('error', 'You are already on the Pay-As-You-Go plan.', 400);
        }

        $plan = $this->billing->paygPlan();
        if (empty($plan)) {
            return requestResponse('error', 'Pay-As-You-Go is not available right now.', 400);
        }
        $planId = (int) (is_object($plan) ? $plan->id : $plan['id']);
        $minTopup = (float) (is_object($plan) ? ($plan->min_topup ?? 0) : ($plan['min_topup'] ?? 0));

        $wallet = $this->walletService->ensureWallet((int) $user->id);
        $hasRemainingDays = !empty($user->will_expire) && strtotime($user->will_expire) > time();

        if (!$hasRemainingDays && (float) $wallet->balance < $minTopup) {
            return requestResponse(
                'error',
                'Please add at least ৳' . number_format($minTopup) . ' to your wallet before switching to Pay-As-You-Go.',
                400
            );
        }

        model('App\Models\User')->update($user->id, [
            'package_id' => $planId,
            'pre_package' => $user->package_id,
            'billing_type' => 'prepaid',
            'pending_package_id' => null,
        ]);

        // Expired already? Bill the first cycle right now from the wallet.
        if (!$hasRemainingDays) {
            $fresh = model('App\Models\User')->find($user->id);
            $result = $this->billing->runCycle($fresh, true);
            if (($result['status'] ?? '') !== 'charged') {
                return requestResponse('success', [
                    'msg' => 'Switched to Pay-As-You-Go, but your wallet could not cover the first monthly charge yet. Please top up.',
                ], 200);
            }
        }

        return requestResponse('success', [
            'msg' => 'You are now on Pay-As-You-Go. '
                . ($hasRemainingDays
                    ? 'Your remaining subscription days carry over; wallet billing starts when they end.'
                    : 'Your first monthly charge has been deducted from your wallet.'),
        ], 200);
    }

    /**
     * Wallet ledger for DataTables.
     */
    public function transactions()
    {
        $user = $this->currentSAdmin();
        if (!$user) {
            return requestResponse('error', 'Unauthorized', 403);
        }

        $builder = model(WalletTransaction::class)->builder()
            ->select('*')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc');

        $datatables = new DataTablesCodeIgniter4($builder);
        $datatables->addSequenceNumber('serial');

        $datatables->format('created_at', static function ($value) {
            return !empty($value) ? date('d.m.Y h:i A', strtotime($value)) : '--';
        });

        $datatables->format('type', static function ($value) {
            if ($value === WalletTransaction::TYPE_CREDIT) {
                return '<span class="ipb-pay-badge is-success">Top Up</span>';
            }
            if ($value === WalletTransaction::TYPE_DEBIT) {
                return '<span class="ipb-pay-badge is-warning">Charge</span>';
            }

            return '<span class="ipb-pay-badge is-info">Adjustment</span>';
        });

        $datatables->format('amount', static function ($value) {
            $amount = (float) $value;
            $sign = $amount >= 0 ? '+' : '−';
            $class = $amount >= 0 ? 'txn-credit' : 'txn-debit';

            return '<span class="' . $class . '">' . $sign . '৳' . number_format(abs($amount), 2) . '</span>';
        });

        $datatables->format('balance_after', static function ($value) {
            return '৳' . number_format((float) $value, 2);
        });

        $datatables->format('description', static function ($value) {
            return $value !== null && $value !== '' ? esc($value) : '--';
        });

        $datatables->except(['id', 'wallet_id', 'user_id', 'reference', 'created_by']);
        $datatables->asObject();
        $datatables->generate();
    }
}

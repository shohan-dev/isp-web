<?php

namespace Zapi\Modules\Customer\Payment\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class PaymentService extends CustomerBaseService
{
    public function makePayment($id)
    {
        $details = $this->payment_model->where(['id' => $id, 'status' => 'pending', 'user_type' => 'user'])->first();
        if (empty($details)) {
            show_404();
            return null;
        }

        $amount = floatval($details->amount ?? 0);
        $data = [
            'title' => 'Payment Gateway',
            'details' => $details,
            'bkash_charge' => ($amount * (floatval(getSetting('bkashpg_charge') ?? 0) / 100)),
            'nagad_charge' => ($amount * (floatval(getSetting('nagadpg_charge') ?? 0) / 100)),
            'sslcommerz_charge' => ($amount * (floatval(getSetting('sslcommerz_charge') ?? 0) / 100)),
            'eps_charge' => ($amount * (floatval(getSetting('eps_charge') ?? 0) / 100)),
            'shurjopay_charge' => ($amount * (floatval(getSetting('shurjopay_charge') ?? 0) / 100)),
            'paystation_charge' => ($amount * (floatval(getSetting('paystation_charge') ?? 0) / 100)),
        ];

        return view('payments/api_gateway', $data);
    }

    public function makeResellerPayment($id)
    {
        $details = $this->payment_model
            ->where('id', $id)
            ->where('status', 'pending')
            ->groupStart()
            ->where('user_type', 'reseller')
            ->orWhere('user_type', 'resellerAdmin')
            ->groupEnd()
            ->first();

        if (empty($details)) {
            show_404();
            return null;
        }

        $actorId = $this->resolveAccessTokenUserId();
        if ($actorId !== null && !$this->actorOwnsResellerPayment($details, $actorId)) {
            show_404();
            return null;
        }

        $amount = floatval($details->amount ?? 0);
        $data = [
            'title' => 'Payment Gateway',
            'details' => $details,
            'bkash_charge' => ($amount * (floatval(getSetting('bkashpg_charge') ?? 0) / 100)),
            'nagad_charge' => ($amount * (floatval(getSetting('nagadpg_charge') ?? 0) / 100)),
            'sslcommerz_charge' => ($amount * (floatval(getSetting('sslcommerz_charge') ?? 0) / 100)),
            'eps_charge' => ($amount * (floatval(getSetting('eps_charge') ?? 0) / 100)),
            'shurjopay_charge' => ($amount * (floatval(getSetting('shurjopay_charge') ?? 0) / 100)),
            'paystation_charge' => ($amount * (floatval(getSetting('paystation_charge') ?? 0) / 100)),
        ];

        return view('payments/api_gateway', $data);
    }
}


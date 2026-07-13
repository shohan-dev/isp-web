<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class Payment extends BaseController
{

    protected $payment_model;

    public function __construct()
    {
        /**
         * Payment Model
         */
        $this->payment_model = model('App\Models\Payment');
    }

    /**
     * Payment
     * @action: Default Payment View
     */
    public function index($user_type = null)
    {
        // $dt=getUserById(getSession('user_id'));

        $data = [
            'title' => 'My Payment',
        ];

        return view('payments/default', $data);
    }


    /**
     * Payment
     * @action: Fetch Payments
     */
    public function fetch()
    {
        // $userType = getSession('user_role') === 'admin' ? 'admin' : getSession('user_role');
        $role = session()->get('user_role');
        $userId = getSession('user_id');
        if ($role === 'user') {
            $data = $this->payment_model->builder()
                ->select('*')
                ->where([
                    'user_id' => getSession('user_id'),
                    'paidby' => getSession('user_id'),
                ])
                ->orderBy('id', 'desc');
        } elseif ($role === 'admin') {
            $data = $this->payment_model->builder()
                ->select('*')
                ->groupStart()
                    ->where('user_id', $userId)
                    ->orWhere('paidby', $userId)
                    ->orWhere('admin_id', $userId)
                    ->orWhere('paid_to', $userId)
                ->groupEnd()
                ->orderBy('id', 'desc');
        } else {
            $data = $this->payment_model->builder()
                ->select('*')
                ->groupStart() // first OR group
                ->orWhere('paidby', $userId)
                ->groupEnd()
                ->orGroupStart() // second OR group
                ->where('admin_id', $userId)
                ->orWhere('paid_to', $userId)
                ->groupEnd()
                ->orderBy('id', 'desc');
        }
        //'user_type' => $userType
        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        $datatables->format('paid_via', function ($value) {

            return $value ?? '--';
        });

        $datatables->format('created_at', function ($value) {

            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        $datatables->format('paid_at', function ($value) {

            return !empty($value) ? date('d.m.Y', strtotime($value)) : '--';
        });

        $datatables->format('paid_to', function ($value) {
            if (!empty($value)) {
                $user = getUserById($value);
                return $user ? $user->name . ' (' . ucwords($user->role ?? '') . ')' : '--';
            }
            return '--';
        });


        $datatables->format('method_trx', function ($value) {

            return $value ?? '--';
        });

        helper('subscription');
        $datatables->addColumn('purpose', function ($row) {
            return esc(paymentPurposeLabel($row));
        });

        $datatables->format('status', function ($value) {

            if ($value == 'successful') {

                return '<span class="ipb-pay-badge is-success">Successful</span>';
            } elseif ($value == 'pending') {

                return '<span class="ipb-pay-badge is-warning">Pending</span>';
            } else {

                return '<span class="ipb-pay-badge is-danger">Failed</span>';
            }
        });

        if (userHasPermission('payment', 'invoice') || userHasPermission('payment', 'payment') || getSession('status') === 'inactive') {

            $datatables->addColumn('action', function ($row) {

                $html = '--';

                if (userHasPermission('payment', 'invoice') && ($row->status === 'successful')) {

                    $html = '<div class="ipb-row-actions"><a href="' . route_to('route.payment.invoice', $row->id) . '" class="ipb-row-btn tone-info" title="Invoice"><i class="fa fa-download"></i> Invoice</a></div>';
                }

                if ((getSession('user_role') === 'user')  && ($row->status === 'pending') && userHasPermission('payment', 'payment') && userHasPermission('subscription', 'renew')) {

                    $html = '<div class="ipb-row-actions"><a href="' . route_to('route.payment.pay', $row->id) . '" class="ipb-row-btn tone-brand" title="Payment"><i class="fa fa-money-bill-transfer"></i> Payment</a></div>';
                }
                if (((getSession('user_role') === 'admin') || getSession('user_role') === 'resellerAdmin') && ($row->status === 'pending')) {

                    $html = '<div class="ipb-row-actions"><a href="' . route_to('route.payment.pay', $row->id) . '" class="ipb-row-btn tone-brand" title="Payment"><i class="fa fa-money-bill-transfer"></i> Payment</a></div>';
                }

                return $html;
            });
        }

        $datatables->except(['id', 'user_id', 'user_type']);

        $datatables->asObject();

        $datatables->generate();
    }

    /**
     * Payment
     * @action: Make Payment
     */
    public function makePayment($id)
    {
        log_message('info', 'Make Payment called with ID: ' . $id);
        $details = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

        if (!empty($details)) {

            $amount = floatval($details->amount ?? 0);
            $isPublic = !getSession('user_id');
            $userIdContext = $details->admin_id;

            // Strictly restrict Super Admin's (ID 2) gateways to ONLY sAdmin self-recharge
            if (empty($userIdContext)) {
                if (getSession('user_role') === 'admin') {
                    $userIdContext = 2; // Allow sAdmin to pay Super Admin
                } else {
                    show_404(); // No regular user should have an empty admin_id invoice
                }
            }

            $data = [
                'title' => 'Payment Gateway',
                'details' => $details,
                'bkash_charge' => ($amount * (floatval(getSetting('bkashpg_charge', 0, $userIdContext) ?? 0) / 100)),
                'nagad_charge' => ($amount * (floatval(getSetting('nagadpg_charge', 0, $userIdContext) ?? 0) / 100)),
                'sslcommerz_charge' => ($amount * (floatval(getSetting('sslcommerz_charge', 0, $userIdContext) ?? 0) / 100)),
                'eps_charge' => ($amount * (floatval(getSetting('eps_charge', 0, $userIdContext) ?? 0) / 100)),
                'shurjopay_charge' => ($amount * (floatval(getSetting('shurjopay_charge', 0, $userIdContext) ?? 0) / 100)),
                'paystation_charge' => ($amount * (floatval(getSetting('paystation_charge', 0, $userIdContext) ?? 0) / 100)),
                'isPublic' => $isPublic,
                'userIdContext' => $userIdContext
            ];

            // log_message('info', 'Successfully called the URL: ' . print_r($data,true));


            return view('payments/gateway', $data);
        }

        show_404();
    }
    public function makeResellerPayment($id)
    {
        $details = $this->payment_model->where(['id' => $id, 'status' => 'pending'])->first();

        if (!empty($details)) {

            $amount = floatval($details->amount ?? 0);
            $userIdContext = $details->admin_id;

            // Strictly restrict Super Admin's (ID 2) gateways to ONLY sAdmin self-recharge
            if (empty($userIdContext)) {
                if (getSession('user_role') === 'admin') {
                    $userIdContext = 2; // Allow sAdmin to pay Super Admin
                } else {
                    show_404(); // No regular user should have an empty admin_id invoice
                }
            }
            
            $data = [
                'title' => 'Payment Gateway',
                'details' => $details,
                'bkash_charge' => ($amount * (floatval(getSetting('bkashpg_charge', 0, $userIdContext) ?? 0) / 100)),
                'nagad_charge' => ($amount * (floatval(getSetting('nagadpg_charge', 0, $userIdContext) ?? 0) / 100)),
                'sslcommerz_charge' => ($amount * (floatval(getSetting('sslcommerz_charge', 0, $userIdContext) ?? 0) / 100)),
                'eps_charge' => ($amount * (floatval(getSetting('eps_charge', 0, $userIdContext) ?? 0) / 100)),
                'shurjopay_charge' => ($amount * (floatval(getSetting('shurjopay_charge', 0, $userIdContext) ?? 0) / 100)),
                'paystation_charge' => ($amount * (floatval(getSetting('paystation_charge', 0, $userIdContext) ?? 0) / 100)),
                'userIdContext' => $userIdContext
            ];

            return view('payments/gateway', $data);
        }

        show_404();
    }

    /**
     * Payment
     * @action: Print Payment Invoice
     */

    //   public function invoicePrint($id)
    //   {
    //       log_message('info', 'User Data: herere' );
    //   }
    public function invoicePrint($id)
    {


        $details = $this->payment_model
            ->where([
                'id' => $id,
                'user_id' => getSession('user_id'),
                'status' => 'successful'
            ])
            ->first();


        if (!empty($details)) {
            // log_message('info', 'User Data: ' . json_encode($details));


            $data = [
                'details' => $details,
                'user' => getUserById(getSession('user_id'))
            ];

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'orientation' => 'P',
                'default_font' => 'hindsiliguri',
            ]);

            $mpdf->SetWatermarkImage(base_url('assets/img/logo/' . getSetting('app_logo')));
            $mpdf->showWatermarkImage = true;
            $mpdf->watermarkImageAlpha = 0.25;
            $mpdf->watermark_size = 60;
            $mpdf->watermarkAngle = 33;

            $mpdf->WriteHTML(view('payments/invoice', $data));

            $this->response->setHeader('Content-Type', 'application/pdf');

            return $mpdf->Output("payment-invoice-" . strtolower($details->month) . "-" . date("d-m-Y", strtotime($details->paid_at)) . "-" . date("h-i-s") . ".pdf", "I");
        }

        show_404();
    }
}

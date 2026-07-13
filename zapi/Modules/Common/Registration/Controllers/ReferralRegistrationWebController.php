<?php

namespace Zapi\Modules\Common\Registration\Controllers;

use CodeIgniter\Controller;
use Zapi\Modules\Shared\Rewards\Models\ReferralCodeModel;

/**
 * Public web page for customer referral self-registration.
 * GET  /register?ref=CODE
 * POST /register/submit
 */
class ReferralRegistrationWebController extends Controller
{
    public function index()
    {
        $ref = strtoupper(trim((string) ($this->request->getGet('ref') ?? '')));
        $referrerName = '';
        $codeValid = false;
        $packages = [];

        if ($ref !== '') {
            $codeRow = (new ReferralCodeModel())->findActiveByCode($ref);
            if ($codeRow) {
                $codeValid = true;
                $referrer = model('App\Models\User')->find((int) $codeRow->user_id);
                $referrerName = (string) ($referrer->name ?? '');
                $ownerId = (int) ($referrer->admin_id ?? $codeRow->owner_id ?? 0);
                if ($ownerId > 0) {
                    $packages = model('App\Models\Package')
                        ->where(['user_id' => $ownerId, 'status' => 'active'])
                        ->findAll();
                }
            }
        }

        return view('Zapi\registration\referral_register', [
            'title'          => 'Register with Referral',
            'referral_code'  => $ref,
            'code_valid'     => $codeValid,
            'referrer_name'  => $referrerName,
            'packages'       => $packages,
            'login_url'      => route_to('route.auth.login'),
        ]);
    }

    public function submit()
    {
        $service = new RegistrationService();
        $service->initController($this->request, $this->response, service('logger'));
        return $service->register();
    }
}

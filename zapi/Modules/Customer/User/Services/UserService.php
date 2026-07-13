<?php

namespace Zapi\Modules\Customer\User\Services;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class UserService extends CustomerBaseService
{
    public function index($id = null)
    {
        if ($id === null || $id === '' || !is_numeric($id)) {
            return $this->respondError('Valid user id is required', 400, 'REQUEST_FAILED');
        }

        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $uid = (string) $details->id;
        $ppoe = '';
        $router_client = routerClient($details->router_id);
        if (!is_array($router_client)) {
            $pppoeUserLookupFn = 'getPPPoEUserUserId';
            $pppoe = function_exists($pppoeUserLookupFn) ? $pppoeUserLookupFn($router_client, $details->id) : [];
            $pppoe_id = $pppoe[0]['.id'] ?? $details->pppoe_id ?? null;
            $user_ppp = getPPPoEUser($router_client, $pppoe_id);
            $ppoe = $user_ppp[0]['name'] ?? '--';
        }

        $paymentSuccessful = $this->sumUserSelfPayments($uid, 'successful');
        $paymentPending = $this->sumUserSelfPayments($uid, 'pending');
        $paymentFailed = $this->sumUserSelfPayments($uid, 'failed');
        $paymentTotal = $paymentSuccessful + $paymentPending + $paymentFailed;
        $package = $this->package_model->find($details->package_id);

        $adminInfo = $this->resolveSupportContactForUser((int) $id);

        $newsModel = model('App\Models\News_notice');
        $notices = $newsModel->asObject()->where('admin_id', $details->admin_id)->orderBy('id', 'DESC')->limit(10)->findAll();
        $noticeList = [];
        foreach (($notices ?? []) as $notice) {
            $noticeList[] = [
                'id' => $notice->id ?? '',
                'name' => $notice->name ?? '',
                'details' => $notice->details ?? '',
                'url' => $notice->url ?? '',
                'image' => $notice->image ?? '',
                'created_at' => $notice->created_at ?? '',
            ];
        }

        return $this->respondSuccess([
            'pppoe' => $ppoe,
            'details' => $details,
            'package' => $package,
            'payment_received' => $paymentSuccessful,
            'payment_pending' => $paymentPending,
            'payment_failed' => $paymentFailed,
            'payment_total' => $paymentTotal,
            'payment_successful' => $paymentSuccessful,
            'total_support_ticket' => (int) $this->ticket_model->where(['user_id' => $uid])->countAllResults(),
            'statistics' => $this->statistics('user', $uid, null),
            'admin_details' => $adminInfo,
            'notices' => $noticeList,
        ]);
    }

    public function fetch()
    {
        $user_id = $this->request->getGet('user_id') ?? null;
        if (empty($user_id)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        $pager = $this->getPaginationParams();
        $builder = $this->payment_model
            ->select('*')
            ->groupStart()
            ->where('user_id', $user_id)
            ->where('paidby', $user_id)
            ->groupEnd()
            ->orderBy('id', 'desc');
        $totalFound = (int) $builder->countAllResults(false);
        $data = $builder->limit($pager['limit'], $pager['offset'])->findAll();

        return $this->respondPaginatedSuccess($data, $totalFound, $pager['page'], $pager['limit']);
    }

    public function packages()
    {
        $userId = $this->request->getGet('user_id');
        if (empty($userId)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }

        $details = $this->user_model->where(['id' => $userId])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        $packages = $this->resolvePackagesForUser($details);
        $pager = $this->getPaginationParams();
        $totalFound = count($packages);
        $pagedPackages = array_slice($packages, $pager['offset'], $pager['limit']);

        return $this->respondSuccess([
            'package_id' => $details->package_id,
            'pre_package' => $details->pre_package,
            'pending_package_id' => $details->pending_package_id ?? null,
            'trial_ends_at' => $details->trial_ends_at ?? null,
            'subscription_status' => $details->subscription_status,
            'last_renewed' => $details->last_renewed,
            'will_expire' => $details->will_expire,
            'packages' => $pagedPackages,
            'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($pagedPackages)),
        ]);
    }

    public function pingUserApi()
    {
        $router_id = $this->request->getGet('router_id');
        $name = $this->request->getGet('name');
        if (empty($router_id)) {
            return $this->respondError('router_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        if (empty($name)) {
            return $this->respondError('name query parameter is required', 400, 'REQUEST_FAILED');
        }

        $result = pingUser($router_id, $name, 1);
        if (!is_array($result)) {
            return $this->respondError('Ping failed', 500, 'REQUEST_FAILED');
        }

        if (($result['status'] ?? '') === 'error') {
            return $this->respondError(
                (string) ($result['message'] ?? 'Ping failed'),
                400,
                'REQUEST_FAILED'
            );
        }

        return $this->respondSuccess([
            'status' => (string) ($result['status'] ?? 'success'),
            'data' => $result['data'] ?? [],
            'average_latency' => $result['average_latency'] ?? 'N/A',
            'packets' => $result['packets'] ?? [
                'sent' => 0,
                'received' => 0,
                'loss' => '0%',
            ],
        ]);
    }
}


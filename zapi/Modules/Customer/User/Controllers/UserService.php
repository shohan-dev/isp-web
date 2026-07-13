<?php

namespace Zapi\Modules\Customer\User\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class UserService extends CustomerBaseService
{
    public function index($id = null)
    {
        if ($id === null || $id === '' || !is_numeric($id)) {
            return $this->respondError('Valid user id is required', 400, 'REQUEST_FAILED');
        }

        /* This looked the user up by id alone, with no comparison to the caller's
           JWT subject, and returned the whole row (below) under 'details' — so any
           authenticated customer could walk GET /api/customer/users/{id} and read
           every other customer's profile, including the `password` hash column.
           (zapi responses do not pass through app/Libraries/SanitizedResponse,
           which is what strips `password` on the web side.) actorCanAccessUser()
           permits self, the owning tenant admin, and super_admin. */
        if (!$this->actorCanAccessUser($id)) {
            return $this->respondError('Access denied', 403, 'REQUEST_FAILED');
        }

        $details = $this->user_model->where(['id' => $id, 'role' => 'user'])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }

        // Never ship the password hash to a client.
        if (is_object($details)) {
            unset($details->password);
        } elseif (is_array($details)) {
            unset($details['password']);
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
        $totalPayment = $this->sumUserSelfPayments($uid, null);
        $paymentTotal = $paymentSuccessful + $paymentPending + $paymentFailed;
        $package = function_exists('getUserPackage')
            ? getUserPackage($details->id, $details->package_id ?? null)
            : $this->package_model->find($details->package_id);
        $packageName = '';
        if (is_object($package)) {
            $packageName = (string) ($package->package_name ?? $package->name ?? '');
        } elseif (is_array($package)) {
            $packageName = (string) ($package['package_name'] ?? $package['name'] ?? '');
        }

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
            'package' => (string) ($details->package_id ?? ''),
            'package_name' => $packageName,
            'payment_received' => $paymentSuccessful,
            'payment_pending' => $paymentPending,
            'payment_failed' => $paymentFailed,
            'total_payment' => $totalPayment,
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

        // Keep zapi payload aligned with web payment table semantics.
        // Web uses `amount` for display and shows readable paid_to user names.
        $normalized = [];
        foreach (($data ?? []) as $row) {
            $item = (array) $row;
            $amount = (string) ($item['amount'] ?? '0');
            $item['pay_amount'] = (string) ($item['pay_amount'] ?? $amount);

            $paidToId = $item['paid_to'] ?? '';
            $paidToName = '--';
            if (!empty($paidToId) && function_exists('getUserById')) {
                $user = getUserById($paidToId);
                if (!empty($user)) {
                    $role = strtolower((string) ($user->role ?? ''));
                    $roleLabel = $role !== '' ? ucfirst($role) : '';
                    $paidToName = trim((string) (($user->name ?? '--') . ($roleLabel !== '' ? " ({$roleLabel})" : '')));
                }
            }

            $item['paid_to_name'] = $paidToName;
            if (empty($item['paid_via'])) {
                $item['paid_via'] = '--';
            }
            if (empty($item['method_trx'])) {
                $item['method_trx'] = '--';
            }
            $normalized[] = $item;
        }

        return $this->respondPaginatedSuccess($normalized, $totalFound, $pager['page'], $pager['limit']);
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


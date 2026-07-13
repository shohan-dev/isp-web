<?php

namespace Zapi\Modules\Customer\Permission\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class PermissionService extends CustomerBaseService
{
    public function index()
    {
        // Resolve the target strictly from the verified JWT subject. The
        // client-supplied user_id must never be trusted on its own: doing so
        // let any reseller/admin/employee token read an arbitrary user's
        // permission map cross-tenant (IDOR).
        $actorId = $this->resolveAccessTokenUserId();
        if ($actorId === null) {
            return $this->respondError('Unauthorized.', 401, 'UNAUTHORIZED');
        }

        $requested = $this->request->getGet('user_id');
        $user_id = $actorId;

        // A supervisory lookup of another user's permissions is only allowed
        // when the existing tenant/ownership guard authorises the actor.
        if ($requested !== null && $requested !== '' && (int) $requested !== $actorId) {
            if (!$this->actorCanAccessUser((int) $requested)) {
                return $this->respondError('You do not have access to this resource.', 403, 'FORBIDDEN');
            }
            $user_id = (int) $requested;
        }

        $details = $this->user_model->where(['id' => $user_id])->first();
        $role = $details->role ?? '--';
        $admin_id = $details->admin_id ?? '--';
        $created_by = $details->created_by ?? '--';

        if ($created_by === 'resellerAdmin') {
            $details = $this->user_model->where(['id' => $admin_id])->first();
            $admin_id = $details->admin_id ?? '--';
        }

        $model = model('App\Models\CustomAccess');
        $permission = $model->where(['user_id' => $user_id, 'status' => 'active'])->first();

        if (empty($permission)) {
            $model = model('App\Models\Permission');
            if ($role == 'resellerAdmin' || $role == 'user' || $role == 'employee') {
                $permission = $model->where(['user_type' => $role, 'user_id' => $admin_id])->first();
            } elseif ($role == 'admin') {
                $permission = $model->where(['user_type' => $role, 'user_id' => 2])->first();
            }
        }

        if (!empty($permission->permissions)) {
            $permission = json_decode($permission->permissions, true);
        }

        return $this->respondSuccess(['permission' => $permission]);
    }
}


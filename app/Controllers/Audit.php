<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

class Audit extends BaseController
{
    protected $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditLogModel();
    }

    public function index()
    {
        $id = $this->request->getGet('id');
        $pppoe_name = $this->request->getGet('pppoe_name');
        $userModel = new \App\Models\User();
        $user = $id !== null ? $userModel->find($id) : null;

        if ($id !== null) {
            helper('user');
            $callerRole = getSession('user_role');
            if ($callerRole !== 'super_admin') {
                $callerTenant = getSAdminIdForUser((int) getSession('user_id'));
                $targetTenant = $user !== null ? getSAdminIdForUser((int) $id) : null;
                if ($targetTenant === null || $targetTenant !== $callerTenant) {
                    show_404();
                }
            }
        }

        $router = isset($user->router_id) ? $user->router_id : null;

        $from     = $this->request->getGet('from');
        $to       = $this->request->getGet('to');
        $perPage  = $this->request->getGet('per_page') ?? 25;

        $data = [
            'title' => 'Audit Logs',
            'router'  => $router,
            'pppoe_name' => $pppoe_name,
            'logs'     => $this->auditModel->getFiltered($from, $to, $perPage, $id),
            'pager'    => $this->auditModel->pager,
            'from'     => $from,
            'to'       => $to,
            'perPage'  => $perPage,
        ];

        return view('customers/audit_index', $data);
    }
}

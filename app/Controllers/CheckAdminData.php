<?php

namespace App\Controllers;

use App\Models\Payment;

class CheckAdminData extends BaseController
{
    public function index()
    {
        $model = new Payment();
        $results = $model->select('users.admin_id, payments.paid_via, COUNT(*) as count')
                         ->join('users', 'users.id = payments.user_id')
                         ->groupBy('users.admin_id, payments.paid_via')
                         ->limit(100)
                         ->findAll();
        
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
        exit;
    }
}

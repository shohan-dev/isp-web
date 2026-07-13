<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\News_notice;
use App\Models\User;

class NewsController extends BaseController
{
    protected $news_model, $user_model;

    public function __construct()
    {
        $this->news_model = new News_notice();
        $this->user_model = new User();
    }

    public function index()
    {
        $id = getSession('user_id');
        $role = getSession('user_role');

        if ($role !== 'user') {
            return redirect()->to(route_to('route.dashboard'));
        }

        $userDetails = $this->user_model->asObject()->find($id);
        log_message('debug', 'NewsController session: id=' . $id . ', role=' . $role);
        
        if (!$userDetails) {
            log_message('error', 'NewsController: User details not found for ID: ' . $id);
            return redirect()->to(route_to('route.logout'));
        }

        $data = [
            'notices' => $this->news_model->asObject()->where('admin_id', $userDetails->admin_id)->orderBy('id', 'DESC')->findAll(),
            'admin_details' => $this->user_model->asObject()->find($userDetails->admin_id)
        ];

        return view('news/index', $data);
    }
}

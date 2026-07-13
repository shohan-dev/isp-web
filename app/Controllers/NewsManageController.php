<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\News_notice;

class NewsManageController extends BaseController
{
    protected $news_model;

    public function __construct()
    {
        $this->news_model = new News_notice();
    }

    public function index()
    {
        $id = getSession('user_id');
        $role = getSession('user_role');

        if (!in_array($role, ['admin', 'resellerAdmin'])) {
            return redirect()->to(route_to('route.dashboard'));
        }

        $data = [
            'notices' => $this->news_model->where('admin_id', $id)->orderBy('id', 'DESC')->findAll(),
        ];

        return view('news/manage', $data);
    }

    public function save()
    {
        $id = getSession('user_id');
        $notice_id = $this->request->getPost('id');

        $data = [
            'name' => $this->request->getPost('name'),
            'details' => $this->request->getPost('details'),
            'url' => $this->request->getPost('url'),
            'admin_id' => $id,
        ];

        if ($notice_id) {
            $this->news_model->update($notice_id, $data);
            $msg = 'Notice updated successfully';
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->news_model->insert($data);
            $msg = 'Notice added successfully';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function delete($id)
    {
        $admin_id = getSession('user_id');
        $notice = $this->news_model->find($id);

        if ($notice && $notice->admin_id == $admin_id) {
            $this->news_model->delete($id);
            return redirect()->back()->with('success', 'Notice deleted successfully');
        }

        return redirect()->back()->with('error', 'Unauthorized or notice not found');
    }
}

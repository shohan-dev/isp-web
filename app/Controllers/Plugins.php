<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PluginModel;

class Plugins extends BaseController
{
    protected $plugin_model;

    public function __construct()
    {
        $this->plugin_model = new PluginModel();
    }

    public function index()
    {
        // Public access allowed, but let's check for admin status for UI purposes
        $isAdmin = (getSession('user_role') === 'super_admin');
        $category = $this->request->getGet('category') ?? 'All addons';
        
        $builder = $this->plugin_model->builder();
        if ($category !== 'All addons') {
            $builder->where('category', $category);
        }
        
        $plugins = $builder->get()->getResultArray();
        
        // Get counts for sidebar
        $categories = [
            'All addons' => $this->plugin_model->countAllResults(),
            "Mobile Application's" => $this->plugin_model->where('category', "Mobile Application's")->countAllResults(),
            'VAS' => $this->plugin_model->where('category', 'VAS')->countAllResults(),
            'Network & Monitoring' => $this->plugin_model->where('category', 'Network & Monitoring')->countAllResults(),
            'SMS Gateway API' => $this->plugin_model->where('category', 'SMS Gateway API')->countAllResults(),
            'Payment Gateway (API)' => $this->plugin_model->where('category', 'Payment Gateway (API)')->countAllResults(),
            'Hardware Integration' => $this->plugin_model->where('category', 'Hardware Integration')->countAllResults(),
        ];

        $data = [
            'title' => 'Plugins & Addons',
            'plugins' => $plugins,
            'active_category' => $category,
            'category_counts' => $categories,
            'isPublic' => !getSession('user_id') // Set to true if not logged in
        ];

        return view('plugins/index', $data);
    }

    public function store()
    {
        if (getSession('user_role') !== 'super_admin') {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Access denied');
        }
        $this->validate([
            'image' => [
                'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|ext_in[image,png,jpg,jpeg,gif]',
                'errors' => [
                    'uploaded' => 'Please upload an image',
                    'max_size' => 'Image size is too large (max 2MB)',
                    'is_image' => 'The file must be an image',
                    'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif'
                ]
            ]
        ]);

        if (!$this->validation->run()) {
            return redirect()->back()->withInput()->with('error', implode(', ', $this->validation->getErrors()));
        }

        $file = $this->request->getFile('image');
        $imageName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(FCPATH . 'assets/img/plugins_images', $imageName);
        }

        $data = [
            'title'         => $this->request->getPost('title'),
            'category'      => $this->request->getPost('category'),
            'description'   => $this->request->getPost('description'),
            'price_type'    => $this->request->getPost('price_type'),
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'image'         => $imageName ? 'assets/img/plugins_images/' . $imageName : null,
            'status'        => 1
        ];

        if ($this->plugin_model->insert($data)) {
            return redirect()->to(route_to('route.plugins.index'))->with('success', 'Plugin added successfully');
        }

        return redirect()->back()->with('error', 'Failed to add plugin');
    }

    public function update($id)
    {
        if (getSession('user_role') !== 'super_admin') {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Access denied');
        }
        $plugin = $this->plugin_model->find($id);
        if (!$plugin) {
            return redirect()->back()->with('error', 'Plugin not found');
        }

        if (!empty($_FILES['image']['name'])) {
            $this->validate([
                'image' => [
                    'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|ext_in[image,png,jpg,jpeg,gif]',
                    'errors' => [
                        'uploaded' => 'Please upload an image',
                        'max_size' => 'Image size is too large (max 2MB)',
                        'is_image' => 'The file must be an image',
                        'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif'
                    ]
                ]
            ]);

            if (!$this->validation->run()) {
                return redirect()->back()->withInput()->with('error', implode(', ', $this->validation->getErrors()));
            }
        }

        $file = $this->request->getFile('image');
        $imageName = $plugin['image'];

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Delete old image if exists
            if ($plugin['image']) {
                $oldImagePath = FCPATH . ltrim($plugin['image'], '/');
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'assets/img/plugins_images', $newName);
            $imageName = 'assets/img/plugins_images/' . $newName;
        }

        $data = [
            'title'         => $this->request->getPost('title'),
            'category'      => $this->request->getPost('category'),
            'description'   => $this->request->getPost('description'),
            'price_type'    => $this->request->getPost('price_type'),
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'image'         => $imageName,
        ];

        if ($this->plugin_model->update($id, $data)) {
            return redirect()->to(route_to('route.plugins.index'))->with('success', 'Plugin updated successfully');
        }

        return redirect()->back()->with('error', 'Failed to update plugin');
    }

    public function delete($id)
    {
        if (getSession('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }
        $plugin = $this->plugin_model->find($id);
        if ($plugin) {
            if ($plugin['image'] && file_exists(FCPATH . $plugin['image'])) {
                @unlink(FCPATH . $plugin['image']);
            }
            if ($this->plugin_model->delete($id)) {
                return $this->response->setJSON(['success' => true, 'message' => 'Plugin deleted successfully']);
            }
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete plugin']);
    }
}

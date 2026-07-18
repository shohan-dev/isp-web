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

    /**
     * Public marketing catalogue at GET /plugins — the same landing.css/landing.js
     * shell as the homepage (nav, footer, hero), for everyone including guests.
     * Super-admins manage plugins from the admin console at admin/plugins.
     */
    public function index()
    {
        $category = $this->request->getGet('category') ?? 'All addons';
        [$plugins, $categories] = $this->buildCatalog($category);

        helper(['utility', 'tenant']);
        $brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
        $tenant = function_exists('currentTenant') ? currentTenant() : null;

        return view('plugins/landing', [
            'title'           => 'Plugins & Addons',
            'plugins'         => $plugins,
            'active_category' => $category,
            'category_counts' => $categories,
            'brandUserId'     => $brandUserId,
            'tenant'          => $tenant,
            'appName'         => resolveBrandTitle($tenant, $brandUserId),
            'logoUrl'         => resolvePublicBrandLogoUrl($tenant, $brandUserId),
        ]);
    }

    /**
     * Super-admin management console at GET admin/plugins — the dashboard shell
     * (sidebar + header) with the add/edit/delete CRUD modals. Guarded fail-closed:
     * authcheck (route group) proves a session, this proves the super_admin role.
     */
    public function admin()
    {
        if (getSession('user_role') !== 'super_admin') {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Access denied');
        }

        $category = $this->request->getGet('category') ?? 'All addons';
        [$plugins, $categories] = $this->buildCatalog($category);

        return view('plugins/index', [
            'title'           => 'Plugins & Addons',
            'plugins'         => $plugins,
            'active_category' => $category,
            'category_counts' => $categories,
        ]);
    }

    /**
     * Shared catalogue query for both the public and admin views: the filtered
     * plugin rows plus the per-category counts for the sidebar.
     *
     * @return array{0: array, 1: array} [$plugins, $categoryCounts]
     */
    private function buildCatalog(string $category): array
    {
        $builder = $this->plugin_model->builder();
        if ($category !== 'All addons') {
            $builder->where('category', $category);
        }

        $plugins = $builder->get()->getResultArray();

        $categories = [
            'All addons' => $this->plugin_model->countAllResults(),
            "Mobile Application's" => $this->plugin_model->where('category', "Mobile Application's")->countAllResults(),
            'VAS' => $this->plugin_model->where('category', 'VAS')->countAllResults(),
            'Network & Monitoring' => $this->plugin_model->where('category', 'Network & Monitoring')->countAllResults(),
            'SMS Gateway API' => $this->plugin_model->where('category', 'SMS Gateway API')->countAllResults(),
            'Payment Gateway (API)' => $this->plugin_model->where('category', 'Payment Gateway (API)')->countAllResults(),
            'Hardware Integration' => $this->plugin_model->where('category', 'Hardware Integration')->countAllResults(),
        ];

        // Hide empty categories from the sidebar — except "All addons" (always
        // shown) and the currently active filter (so selecting a category that
        // just emptied out doesn't also make its own nav link disappear).
        $categories = array_filter(
            $categories,
            static fn ($count, $name) => $name === 'All addons' || $count > 0 || $name === $category,
            ARRAY_FILTER_USE_BOTH
        );

        return [$plugins, $categories];
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
            ],
            'price' => [
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'errors' => [
                    'decimal' => 'Price must be a number',
                    'greater_than_equal_to' => 'Price cannot be negative',
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

        $priceRaw = trim((string) $this->request->getPost('price'));

        $data = [
            'title'         => $this->request->getPost('title'),
            'category'      => $this->request->getPost('category'),
            'description'   => $this->request->getPost('description'),
            'price_type'    => $this->request->getPost('price_type'),
            'price'         => $priceRaw === '' ? null : (float) $priceRaw,
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'image'         => $imageName ? 'assets/img/plugins_images/' . $imageName : null,
            'status'        => 1
        ];

        if ($this->plugin_model->insert($data)) {
            return redirect()->to(route_to('route.plugins.admin'))->with('success', 'Plugin added successfully');
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

        $priceRules = [
            'price' => [
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'errors' => [
                    'decimal' => 'Price must be a number',
                    'greater_than_equal_to' => 'Price cannot be negative',
                ]
            ]
        ];

        if (!empty($_FILES['image']['name'])) {
            $this->validate(array_merge($priceRules, [
                'image' => [
                    'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|ext_in[image,png,jpg,jpeg,gif]',
                    'errors' => [
                        'uploaded' => 'Please upload an image',
                        'max_size' => 'Image size is too large (max 2MB)',
                        'is_image' => 'The file must be an image',
                        'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif'
                    ]
                ]
            ]));
        } else {
            $this->validate($priceRules);
        }

        if (!$this->validation->run()) {
            return redirect()->back()->withInput()->with('error', implode(', ', $this->validation->getErrors()));
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

        $priceRaw = trim((string) $this->request->getPost('price'));

        $data = [
            'title'         => $this->request->getPost('title'),
            'category'      => $this->request->getPost('category'),
            'description'   => $this->request->getPost('description'),
            'price_type'    => $this->request->getPost('price_type'),
            'price'         => $priceRaw === '' ? null : (float) $priceRaw,
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'image'         => $imageName,
        ];

        if ($this->plugin_model->update($id, $data)) {
            return redirect()->to(route_to('route.plugins.admin'))->with('success', 'Plugin updated successfully');
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

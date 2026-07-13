<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\allResellerPackage;
use App\Models\ResellerPackages;
use App\Libraries\DataTables;
use App\Services\TrashService;

class Packages extends BaseController
{

    protected $package_model;
    protected $AdminPackage;


    public function __construct()
    {
        /**
         * Package Model
         */
        $this->package_model = model('App\Models\Package');
        $this->AdminPackage = model('App\Models\AdminPackage');
    }

    /**
     * Packages
     * @action: All Packages View
     */
    public function index()
    {
        $userRole = session()->get('user_role');

        if ($userRole === 'resellerAdmin') {
            $data = [
                'title' => 'Packages',
                'role' => 'reseller'
            ];
        } elseif ($userRole === 'employee') {
            $data = [
                'title' => 'Packages',
                'role' => 'employee'
            ];
        } else {
            $data = [
                'title' => 'Packages',
                'role' => 'admin'
            ];
        }

        return view('packages/all', $data);
    }


    /**
     * Packages
     * @action: Fetch Packages
     */
    /**
     * Packages
     * @action: Fetch Packages
     */
    public function fetch()
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $usesAdminPackageFilters = true;

        if ($userRole === 'resellerAdmin') {

            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();

            $admin_id = $details->admin_id;
            $packageModel = model('App\Models\ResellerPackages');

            $data = $packageModel->builder()
                ->where('status', 'active')
                ->where('user_id', $admin_id)
                ->orderBy('id', 'desc');
            $usesAdminPackageFilters = false;
        } elseif ($userRole === 'employee') {
            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();

            $created_by = $details->created_by;

            if ($created_by === 'resellerAdmin') {
                $admin_id = $details->admin_id;
                $packageModel = model('App\Models\ResellerPackages');

                $data = $packageModel->builder()
                    ->where('status', 'active')
                    ->where('user_id', $admin_id)
                    ->orderBy('id', 'desc');
                $usesAdminPackageFilters = false;
            } else {
                $userId = $details->admin_id;
                $data = $this->package_model->builder()
                    ->select('*')
                    ->where('user_id', $userId)
                    ->orderBy('id', 'desc');
            }
        } else {
            $data = $this->package_model->builder()
                ->select('*')
                ->where('user_id', $userId)
                ->orderBy('id', 'desc');
        }

        if ($usesAdminPackageFilters) {
            $statusFilter = $this->request->getPost('status_filter');
            $visibilityFilter = $this->request->getPost('visibility_filter');
            if ($statusFilter === 'active' || $statusFilter === 'inactive') {
                $data->where('status', $statusFilter);
            }
            if ($visibilityFilter === 'active' || $visibilityFilter === 'inactive') {
                $data->where('visibility', $visibilityFilter);
            }
        }

        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('packages', 'delete')) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }


        $datatables->addColumn('pricing', function ($row) use ($userRole) {
            $price = ($userRole === 'resellerAdmin') ? ResellerPackagePrice($row->id) : $row->price;
            return '<span style="color: var(--success-500, #28a745); font-weight: bold;">' . $price . ' - ' . ucwords($row->pricing_type) . '</span>';
        });
        $datatables->format('bandwidth', function ($value) {
            return '<span style="color: var(--info-500, #3f51b5);">' . $value . '</span>';
        });

        if ($userRole === 'resellerAdmin') {
            $datatables->format('status', function ($value) {
                return ($value === 'Active')
                    ? '<span class="ipb-pay-badge is-success">Active</span>'
                    : '<span class="ipb-pay-badge is-danger">Inactive</span>';
            });
        } else {
            $datatables->format('status', function ($value) {
                return '<span class="ipb-pay-badge is-success">Active</span>';
            });

            $datatables->format('visibility', function ($value) {
                return '<span class="ipb-pay-badge is-info">Visible</span>';
            });
        }

        if (userHasPermission('packages', 'update')) {
            $datatables->addColumn('action', function ($row) {
                return '<div class="ipb-row-actions"><a href="' . route_to('route.packages.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update"><i class="far fa-pen-to-square"></i> Update</a></div>';
            });
        }

        $datatables->except(['id', 'price', 'pricing_type']);

        $datatables->asObject();
        $datatables->generate();
    }

    /**
     * Packages
     * @action: New Package View
     */
    public function new()
    {
        $data = [
            'title' => 'New Package',
        ];

        return view('packages/new', $data);
    }


    /**
     * Packages
     * @action: New Package Create
     */
    public function create()
    {
        $this->validate([
            'package_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package name',
                ]
            ],
            'bandwidth' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package bandwidth',
                ]
            ],
            'price' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package price',
                ]
            ],
            'pricing_type' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select pricing type',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select package status',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $userId = session()->get('user_id');
            log_message('info', 'Fetched userId data: ' . json_encode($userId));

            $userRole = session()->get('user_role');
            $userModel = model('App\Models\User');
            $details = $userModel->where(['id' => $userId])->first();


            if ($userRole === 'employee') {

                $userId = $details->admin_id;
            }
            $data = [
                'user_id' => $userId,
                'package_name' => getPostInput('package_name'),
                'bandwidth' => getPostInput('bandwidth'),
                'price' => getPostInput('price'),
                'pricing_type' => getPostInput('pricing_type'),
                'status' => getPostInput('status'),
                'visibility' => getPostInput('visibility'),
            ];
            log_message('info', 'Data being saved: ' . json_encode($data));

            $result = $this->package_model->insert($data, false);

            if ($result) {
                bumpLookupCacheVersion((int) $userId);

                return requestResponse('success', "New package added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Packages
     * @action: Delete Package
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {
            $packages = $this->package_model->whereIn('id', $ids)->findAll();

            $result = (new TrashService())->trash('package', $packages);

            if ($result > 0) {
                $ownerIds = array_unique(array_map(static fn ($row) => (int) $row->user_id, $packages));
                foreach ($ownerIds as $ownerId) {
                    bumpLookupCacheVersion($ownerId);
                }

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Packages
     * @action: Edit Package View
     */
    public function edit($id)
    {
        $details = $this->package_model->find($id);

        if (!empty($details)) {

            $data = [
                'title' => 'Update Package',
                'details' => $details,
            ];

            return view('packages/edit', $data);
        }

        show_404();
    }

    /**
     * Packages
     * @action: Update Package
     */
    public function update($id)
    {
        $this->validate([
            'package_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package name',
                ]
            ],
            'bandwidth' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package bandwidth',
                ]
            ],
            'price' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter package price',
                ]
            ],
            'pricing_type' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select pricing type',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select package status',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $package = $this->package_model->find($id);

            $data = [
                'package_name' => getPostInput('package_name'),
                'bandwidth' => getPostInput('bandwidth'),
                'price' => getPostInput('price'),
                'pricing_type' => getPostInput('pricing_type'),
                'status' => getPostInput('status'),
                'visibility' => getPostInput('visibility'),
            ];

            $result = $this->package_model->update($id, $data);

            if ($result) {
                if ($package !== null) {
                    bumpLookupCacheVersion((int) $package->user_id);
                }

                return requestResponse('success', "Package updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }
}

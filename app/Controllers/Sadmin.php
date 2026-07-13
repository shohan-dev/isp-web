<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\allResellerPackage;
use App\Models\ResellerPackages;
use App\Models\NetworkModel;


use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class Sadmin extends BaseController
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

    public function diagram()
    {
        if (!userHasPermission('network', 'read'))
            show_404();
        $data['title'] = 'Network Diagram';

        return view('network/diagram', $data);
    }
    public function map()
    {
        if (!userHasPermission('network', 'read'))
            show_404();
        $data['title'] = 'Network Map';

        $model = new \App\Models\NetworkModel();
        $model->ensureTableExists();

        $adminId = getSession('user_id'); // Get the current admin ID
        $nodes = $model->where('admin_id', $adminId)->findAll();

        // Format nodes for the map
        $locations = [];
        foreach ($nodes as $node) {
            $locations[] = [
                'id' => $node['id'],
                'name' => $node['label'],
                'lat' => $node['latitude'],
                'lng' => $node['longitude'],
                'parent_id' => $node['parent_id'] ?? null
            ];
        }

        $data['locations'] = $locations;


        return view('network/map', $data);
    }


    // public function diagram()
    // {
    //     $model = new NetworkModel();
    //     $model->ensureTableExists();

    //     $nodes = $model->findAll();
    //     log_message('debug', 'Network nodes fetched: ' . print_r($nodes, true));

    //     return view('network/diagram', ['nodes' => $nodes]);
    // }

    public function index()
    {
        $model = new \App\Models\NetworkModel();
        $model->ensureTableExists();

        $adminId = getSession('user_id'); // Get the current admin ID

        $nodes = $model->where('admin_id', $adminId)->findAll();

        return $this->response->setJSON($nodes); // ✅ This returns JSON
    }

    public function addNode()
    {
        log_message('debug', 'Add Node called with POST data: ');
        $model = new \App\Models\NetworkModel();
        $model->ensureTableExists();

        $label = $this->request->getPost('label');
        $parent = $this->request->getPost('parent_id');
        $color = $this->request->getPost('color');
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');

        $adminId = getSession('user_id'); // Get the current admin ID

        $data = [
            'label'     => $label,
            'color'     => $color,
            'admin_id'  => $adminId,
            'latitude'  => $latitude,
            'longitude' => $longitude
        ];

        if (!empty($parent)) {
            $data['parent_id'] = $parent;
        }

        $model->insert($data);
        $id = $model->getInsertID();
        log_message('debug', "Node added with ID: {$id} = {$parent}");
        // ✅ Prevent self-referencing edge
        if ($id == $parent) {
            $model->update($id, ['parent_id' => null]);
            $parent = null;
        }

        return $this->response->setJSON([
            'status'    => 'success',
            'id'        => $id,
            'label'     => $label,
            'color'     => $color,
            'parent_id' => $parent,
            'latitude'  => $latitude,
            'longitude' => $longitude
        ]);
    }

    public function editNode()
    {
        $id = $this->request->getPost('id');
        $label = $this->request->getPost('label');
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $parent = $this->request->getPost('parent_id');

        $model = new \App\Models\NetworkModel();

        log_message('debug', "Node added with ID: {$id} = {$parent}");

        $updateData = [
            'label'     => $label,
            'latitude'  => $latitude,
            'longitude' => $longitude
        ];

        $model->update($id, $updateData);

        return $this->response->setJSON(['status' => 'success']);
    }



    public function deleteNode()
    {
        log_message('debug', 'Delete Node called with ID: ' . $this->request->getPost('id'));
        $id = $this->request->getPost('id');

        $model = new \App\Models\NetworkModel();
        $this->recursiveDelete($id, $model);

        return $this->response->setJSON(['status' => 'success']);
    }

    private function recursiveDelete($id, $model)
    {
        $children = $model->where('parent_id', $id)->findAll();
        foreach ($children as $child) {
            $this->recursiveDelete($child['id'], $model);
        }

        $model->delete($id);
    }

    /**
     * Toggle platform-wide maintenance mode (platform admin only).
     */
    public function toggleMaintenance()
    {
        if (getSession('user_role') !== \Config\Roles::PLATFORM) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        helper('flag');
        $on = filter_var($this->request->getPost('on'), FILTER_VALIDATE_BOOLEAN);

        if ($on) {
            setFlag('maintenance_mode', true);
            $message = 'Maintenance mode enabled — non-admin traffic will receive 503.';
        } else {
            clearFlag('maintenance_mode');
            $message = 'Maintenance mode disabled.';
        }

        return redirect()->back()->with('success', $message);
    }
}

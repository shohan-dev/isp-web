<?php
namespace App\Controllers;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;
use CodeIgniter\Controller;

use App\Controllers\BaseController;
use App\Models\StoreLocationModel;

class StoreLocationController extends BaseController
{
    protected $storeLocationModel;

    public function __construct()
    {
        $this->storeLocationModel = new StoreLocationModel();
    }

    public function index()
    {
        $admin_id = session()->get('user_id');
         $data = [
            'title' => 'Store Locations',
        ];
        $data['locations'] = $this->storeLocationModel->where('admin_id', $admin_id)->findAll();
        return view('inventory/store_location', $data);
    }

    public function create()
    {
        $this->storeLocationModel->save([
            'location_name' => $this->request->getPost('location_name'),
            'short_value'   => $this->request->getPost('short_value'),
            'admin_id'      => session()->get('user_id') 
        ]);
        return redirect()->back()->with('message', 'Location added!');
    }

    public function update()
    {
        $id = $this->request->getPost('id');
        $this->storeLocationModel->update($id, [
            'location_name' => $this->request->getPost('location_name'),
            'short_value'   => $this->request->getPost('short_value'),
        ]);
        return redirect()->back()->with('message', 'Location updated!');
    }

    public function delete($id)
    {
        $this->storeLocationModel->delete($id);
        return redirect()->back()->with('message', 'Location deleted!');
    }
}

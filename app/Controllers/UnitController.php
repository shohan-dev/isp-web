<?php namespace App\Controllers;

use App\Models\UnitModel;

class UnitController extends BaseController
{
    public function index()
    {
        $unitModel = new UnitModel();
        $admin_id = session()->get('user_id');
        $data = [
            'title' => 'Units',
        ];
        $data['units'] = $unitModel->where('admin_id',$admin_id)->findAll();
        return view('inventory/unit', $data);
    }

    public function create()
    {
        $unitModel = new UnitModel();
        $admin_id = session()->get('user_id'); // Get the admin ID from session
        $unitModel->save([
            'name' => $this->request->getPost('name'),
            'admin_id' => $admin_id // Save the admin ID
        ]);
        return redirect()->to('/units')->with('message', 'Unit added!');
    }

    public function delete($id)
    {
        $unitModel = new UnitModel();
        $unitModel->delete($id);
        return redirect()->to('/units')->with('message', 'Unit deleted!');
    }

    public function update()
    {
        $unitModel = new UnitModel();
        $unitModel->update($this->request->getPost('id'), [
            'name' => $this->request->getPost('name')
        ]);
        return redirect()->to('/units')->with('message', 'Unit updated!');
    }
}

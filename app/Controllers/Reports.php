<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;
use App\Models\User;
use App\Models\Area;
use App\Models\Package;
use App\Models\Router;
use App\Models\ConnectionData;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Reports extends BaseController
{
    protected $user_model;
    protected $area_model;
    protected $package_model;
    protected $router_model;
    protected $connection_details_model;

    public function __construct()
    {
        $this->user_model = model('App\Models\User');
        $this->area_model = model('App\Models\Area');
        $this->package_model = model('App\Models\Package');
        $this->router_model = model('App\Models\Router');
        $this->connection_details_model = model('App\Models\ConnectionData');
    }

    public function btrc()
    {
        $role = session()->get('user_role');
        if (strtolower($role) !== 'super_admin' && strtolower($role) !== 'admin' && !userHasPermission('reports', 'read')) {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Unauthorized access.');
        }

        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        // Handle staff/employee admin_id
        if ($userRole === 'employee') {
            $user = $this->user_model->find($userId);
            $userId = $user->admin_id;
        }

        // Fetch areas belonging to the admin and their resellers
        $areas = $this->area_model->builder()
            ->where('user_id', $userId)
            ->orWhereIn('user_id', function($subquery) use ($userId) {
                return $subquery->select('id')->from('users')->where('admin_id', $userId)->where('role', 'resellerAdmin');
            })
            ->get()
            ->getResult();
        $resellers = $this->user_model->where('role', 'resellerAdmin')->where('admin_id', $userId)->findAll();

        $data = [
            'title' => 'BTRC Report',
            'areas' => $areas,
            'resellers' => $resellers,
        ];

        return view('reports/btrc', $data);
    }

    private function getBtrcQuery($filters)
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        if ($userRole === 'employee') {
            $user = $this->user_model->find($userId);
            $userId = $user->admin_id;
        }

        $builder = $this->user_model->builder()
            ->select('users.id, users.name, users.mobile, users.email, users.address, users.created_at, areas.area_name, connection_details.connection_type, connection_details.client_type, connection_details.billing_status')
            ->select('COALESCE(p_admin.package_name, p_reseller.package_name) as package_name')
            ->select('COALESCE(p_admin.bandwidth, p_reseller.bandwidth) as bandwidth')
            ->select('COALESCE(p_admin.price, p_reseller.price) as price')
            ->join('areas', 'areas.id = users.area_id', 'left')
            ->join('packages as p_admin', 'p_admin.id = users.package_id', 'left')
            ->join('reseller_packages as p_reseller', 'p_reseller.id = users.package_id', 'left')
            ->join('connection_details', 'connection_details.user_id = users.id', 'left')
            ->where('users.role', 'user');

        if ($userRole === 'super_admin' || $userRole === 'admin') {
            $builder->groupStart()
                ->where('users.admin_id', $userId)
                ->orWhereIn('users.admin_id', function ($subquery) use ($userId) {
                    return $subquery->select('id')->from('users')->where('admin_id', $userId)->where('role', 'resellerAdmin');
                })
                ->groupEnd();
        } else {
            $builder->where('users.admin_id', $userId);
        }

        if (!empty($filters['area_id'])) {
            $builder->where('users.area_id', $filters['area_id']);
        }

        if (!empty($filters['reseller_id'])) {
            $builder->where('users.admin_id', $filters['reseller_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('users.status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $builder->where('users.created_at >=', $filters['from_date'] . ' 00:00:00');
        }

        if (!empty($filters['to_date'])) {
            $builder->where('users.created_at <=', $filters['to_date'] . ' 23:59:59');
        }

        return $builder;
    }

    public function fetchBtrc()
    {
        $role = session()->get('user_role');
        if (strtolower($role) !== 'super_admin' && strtolower($role) !== 'admin' && !userHasPermission('reports', 'read')) {
            return requestResponse('error', 'Unauthorized access.', 403);
        }

        $filters = $this->request->getPost();
        $builder = $this->getBtrcQuery($filters);

        $datatables = new DataTablesCodeIgniter4($builder);
        $datatables->asObject();
        $datatables->addSequenceNumber('serial');

        $datatables->addColumn('activation_date', function ($row) {
            return date('d/m/Y', strtotime($row->created_at));
        });

        $datatables->generate();
    }

    public function exportBtrcPdf()
    {
        $role = session()->get('user_role');
        if (strtolower($role) !== 'super_admin' && strtolower($role) !== 'admin' && !userHasPermission('reports', 'read')) {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Unauthorized access.');
        }

        $filters = $this->request->getGet();
        $query = $this->getBtrcQuery($filters);
        $data = $query->get()->getResult();

        $html = view('reports/btrc_pdf', ['data' => $data, 'title' => 'BTRC Report']);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->WriteHTML($html);
        return $this->response->setHeader('Content-Type', 'application/pdf')->setBody($mpdf->Output('BTRC_Report.pdf', 'S'));
    }

    public function exportBtrcExcel()
    {
        $role = session()->get('user_role');
        if (strtolower($role) !== 'super_admin' && strtolower($role) !== 'admin' && !userHasPermission('reports', 'read')) {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Unauthorized access.');
        }

        $filters = $this->request->getGet();
        $query = $this->getBtrcQuery($filters);
        $data = $query->get()->getResult();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['SL', 'Client Name', 'Mobile', 'Email', 'Package', 'Bandwidth', 'Price', 'Area', 'Conn. Type', 'Client Type', 'Address', 'Activation Date'];
        $sheet->fromArray($headers, NULL, 'A1');

        $rows = [];
        foreach ($data as $index => $row) {
            $rows[] = [
                $index + 1,
                $row->name,
                $row->mobile,
                $row->email,
                $row->package_name,
                $row->bandwidth,
                $row->price,
                $row->area_name,
                $row->connection_type,
                $row->client_type,
                $row->address,
                date('d/m/Y', strtotime($row->created_at))
            ];
        }
        $sheet->fromArray($rows, NULL, 'A2');

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="BTRC_Report.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

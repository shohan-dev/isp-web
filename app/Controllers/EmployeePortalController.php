<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdvanceSalary;
use App\Models\Attendance;
use App\Models\EmployeeLocation;
use App\Models\User;
use App\Libraries\DataTables;

class EmployeePortalController extends BaseController
{
    protected $advance_salary_model;
    protected $attendance_model;
    protected $location_model;
    protected $user_model;

    public function __construct()
    {
        $this->advance_salary_model = model('App\Models\AdvanceSalary');
        $this->attendance_model     = model('App\Models\Attendance');
        $this->location_model       = model('App\Models\EmployeeLocation');
        $this->user_model           = model('App\Models\User');
    }

    /**
     * Check-in action for employee
     */
    public function checkIn()
    {
        $userId = session()->get('user_id');
        $today = date('Y-m-d');

        // Check if already checked in today
        $existing = $this->attendance_model->where(['employee_id' => $userId, 'date' => $today])->first();
        if ($existing) {
            requestResponse('error', 'You have already checked in today!', 400);
            exit;
        }

        $result = $this->attendance_model->insert([
            'employee_id' => $userId,
            'date'        => $today,
            'check_in'    => date('Y-m-d H:i:s'),
            'status'      => 'present',
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            requestResponse('success', 'Checked in successfully!', 200);
            exit;
        }

        requestResponse('error', 'Something went wrong. Please try again.', 500);
        exit;
    }

    /**
     * Check-out action for employee
     */
    public function checkOut()
    {
        $userId = session()->get('user_id');
        $today = date('Y-m-d');

        // Find today's check-in
        $existing = $this->attendance_model->where(['employee_id' => $userId, 'date' => $today])->first();
        if (!$existing) {
            requestResponse('error', 'You must check in first before checking out!', 400);
            exit;
        }

        if (!empty($existing->check_out)) {
            requestResponse('error', 'You have already checked out today!', 400);
            exit;
        }

        $result = $this->attendance_model->update($existing->id, [
            'check_out' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            requestResponse('success', 'Checked out successfully!', 200);
            exit;
        }

        requestResponse('error', 'Something went wrong. Please try again.', 500);
        exit;
    }

    /**
     * Location update action for employee
     */
    public function updateLocation()
    {
        $userId = session()->get('user_id');
        
        // Try reading JSON input body first
        $json = $this->request->getJSON(true);
        $lat  = $json['latitude'] ?? getPostInput('latitude');
        $lng  = $json['longitude'] ?? getPostInput('longitude');
        $addr = $json['address'] ?? getPostInput('address') ?? '';

        if (empty($lat) || empty($lng)) {
            requestResponse('error', 'Latitude and longitude are required!', 400);
            exit;
        }

        // Insert location log
        $this->location_model->insert([
            'employee_id' => $userId,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'address'     => $addr,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        // Update user's last_location_update
        $this->user_model->update($userId, [
            'last_location_update' => date('Y-m-d H:i:s')
        ]);

        requestResponse('success', 'Location updated successfully!', 200);
        exit;
    }

    /**
     * Submit an advance salary request
     */
    public function applyAdvanceSalary()
    {
        $userId = session()->get('user_id');
        $amount = getPostInput('amount');
        $reason = getPostInput('reason');

        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            requestResponse('error', 'Please enter a valid amount!', 400);
            exit;
        }

        $result = $this->advance_salary_model->insert([
            'employee_id' => $userId,
            'amount'      => $amount,
            'reason'      => $reason,
            'status'      => 'pending'
        ]);

        if ($result) {
            requestResponse('success', 'Advance salary request submitted successfully!', 200);
            exit;
        }

        requestResponse('error', 'Something went wrong. Please try again.', 500);
        exit;
    }

    /**
     * View/List page for advance salaries (Employee or Admin view)
     */
    public function listAdvanceSalary()
    {
        $userRole = session()->get('user_role');
        $userId   = session()->get('user_id');

        $data = [
            'title' => 'Advance Salary Requests',
            'role'  => $userRole
        ];

        return view('employee/advance_salary_list', $data);
    }

    /**
     * Fetch Advance Salary request list (DataTables)
     */
    public function fetchAdvanceSalary()
    {
        $userRole = session()->get('user_role');
        $userId   = session()->get('user_id');

        if ($userRole === 'employee') {
            $builder = $this->advance_salary_model->builder()
                ->select('advance_salary.*')
                ->where('employee_id', $userId)
                ->orderBy('id', 'desc');
        } else {
            // Admin sees all employees' requests under them
            $builder = $this->advance_salary_model->builder()
                ->select('advance_salary.*, users.name as employee_name')
                ->join('users', 'users.id = advance_salary.employee_id')
                ->where('users.admin_id', $userId)
                ->orderBy('advance_salary.id', 'desc');
        }

        $datatables = new DataTables($builder);
        $datatables->addSequenceNumber('serial');

        if ($userRole !== 'employee') {
            $datatables->addColumn('employee', function ($row) {
                return $row->employee_name;
            });
        }

        $datatables->format('amount', function ($value) {
            return '৳' . number_format($value, 2);
        });

        $datatables->format('created_at', function ($value) {
            return date("d-m-Y, h:i a", strtotime($value));
        });

        $datatables->format('status', function ($value) {
            if ($value === 'pending') {
                return '<span class="badge label-warning">Pending</span>';
            } elseif ($value === 'approved') {
                return '<span class="badge label-success">Approved</span>';
            } else {
                return '<span class="badge label-danger">Rejected</span>';
            }
        });

        $datatables->addColumn('action', function ($row) use ($userRole) {
            if ($userRole !== 'employee' && $row->status === 'pending') {
                return '<button class="btn btn-success btn-xs btn-approve" data-id="' . $row->id . '">Approve</button> ' .
                       '<button class="btn btn-danger btn-xs btn-reject" data-id="' . $row->id . '">Reject</button>';
            }
            return '--';
        });

        $datatables->except(['employee_id', 'updated_at']);
        $datatables->asObject();
        $datatables->generate();
    }

    /**
     * Approve or reject advance salary (Admin action)
     */
    public function updateAdvanceSalaryStatus($id)
    {
        $status = getPostInput('status');
        if (!in_array($status, ['approved', 'rejected'])) {
            return requestResponse('error', 'Invalid status!', 400);
        }

        $userId = session()->get('user_id');
        // Verify this request is for an employee belonging to this admin
        $request = $this->advance_salary_model->builder()
            ->select('advance_salary.*')
            ->join('users', 'users.id = advance_salary.employee_id')
            ->where('advance_salary.id', $id)
            ->where('users.admin_id', $userId)
            ->get()->getRow();

        if (!$request) {
            return requestResponse('error', 'Request not found or unauthorized!', 404);
        }

        $result = $this->advance_salary_model->update($id, [
            'status' => $status
        ]);

        if ($result) {
            requestResponse('success', 'Request status updated successfully to ' . $status . '!', 200);
            exit;
        }

        requestResponse('error', 'Failed to update request.', 500);
        exit;
    }

    /**
     * View specific employee details, attendance, location history, and advance salary requests (Admin view)
     */
    public function viewEmployeeActivity($employeeId)
    {
        $adminId = session()->get('user_id');
        
        // Fetch employee and verify they belong to this admin
        $employee = $this->user_model->where(['id' => $employeeId, 'admin_id' => $adminId])->first();
        if (!$employee) {
            show_404();
        }

        // Get attendance logs
        $attendance = $this->attendance_model
            ->where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->findAll();

        // Get location logs (limit to last 100 entries)
        $locations = $this->location_model
            ->where('employee_id', $employeeId)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->findAll();

        // Get advance salary history
        $advanceSalaries = $this->advance_salary_model
            ->where('employee_id', $employeeId)
            ->orderBy('id', 'desc')
            ->findAll();

        $data = [
            'title'            => 'Employee Details & Activity',
            'employee'         => $employee,
            'attendance'       => $attendance,
            'locations'        => $locations,
            'advance_salaries' => $advanceSalaries
        ];

        return view('employee/view_details', $data);
    }
}

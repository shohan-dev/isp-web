<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class ExpenseController extends BaseController
{
    protected $user_model;
    protected $connectionModel;
    protected $popModel;


    public function __construct()
    {
        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
        $this->connectionModel =  model('App\Models\ConnectionData');
        // $this->popModel = new PopModel();
    }

    public function index()
    {
        $user_id = session()->get('user_id');

        $expenseTypeModel = model('App\Models\ExpenseTypeModel');
        $expenseModel     = model('App\Models\ExpenseModel');

        $data['employees'] = $this->user_model
            ->where('admin_id', $user_id)
            ->where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['expense_types'] = $expenseTypeModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['expenses'] = $expenseModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('accounts/expenses', $data);
    }


    public function saveType()
    {
        log_message('debug', '=== saveType method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        $model = model('App\Models\ExpenseTypeModel');

        $name = $this->request->getPost('name');
        log_message('debug', 'Name received: ' . $name);

        // Check if name is empty
        if (empty($name)) {
            log_message('error', 'Name is empty');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Name is required'
            ]);
        }

        // Prevent duplicate per user
        $exists = $model->where('user_id', session()->get('user_id'))
            ->where('name', $name)
            ->first();

        if ($exists) {
            log_message('debug', 'Duplicate found');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense Type Already Exists'
            ]);
        }

        $insertData = [
            'user_id' => session()->get('user_id'),
            'name'    => $name,
            'status'  => 'active'
        ];

        log_message('debug', 'Insert data: ' . json_encode($insertData));

        $result = $model->insert($insertData);

        log_message('debug', 'Insert result: ' . $result);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Expense Type Created Successfully'
        ]);
    }


    public function save()
    {
        $model = model('App\Models\ExpenseModel');

        $file = $this->request->getFile('document');
        $fileName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            /* This moved ANY uploaded file straight into FCPATH.'assets/expenses/',
               which the web server serves directly — with no extension check at
               all, unlike the sibling IncomeController::save(). Combined with the
               route having had no auth filter, that was an unauthenticated write
               of an arbitrary file into a public web directory. Mirror the
               allowlist and size cap IncomeController already enforces. */
            $validTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $fileType   = strtolower((string) $file->getExtension());

            if (!in_array($fileType, $validTypes, true)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid file type. Allowed: jpg, jpeg, png, pdf, doc, docx'
                ]);
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'File too large. Maximum size is 5MB.'
                ]);
            }

            $fileName = $file->getRandomName();

            // Upload new image
            $uploadPath = FCPATH . 'assets/expenses/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file->move($uploadPath, $fileName);
        }

        $model->insert([
            'name'         => $this->request->getPost('name'),
            'expense_head' => $this->request->getPost('expense_head'),
            'employee'     => $this->request->getPost('employee'),
            'invoice_no'   => $this->request->getPost('invoice_no'),
            'date'         => $this->request->getPost('date'),
            'amount'       => $this->request->getPost('amount'),
            'bank_account' => $this->request->getPost('bank_account'),
            'description'  => $this->request->getPost('description'),
            'document'     => $fileName,
            'created_by'   => session()->get('user_id'),
            'user_id'      => session()->get('user_id'),
            'status'       => 'pending'
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Expense Saved Successfully'
        ]);
    }


    public function updateType()
    {
        log_message('debug', '=== updateType method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        $model = model('App\Models\ExpenseTypeModel');

        $typeId = $this->request->getPost('type_id');
        $name = $this->request->getPost('name');

        log_message('debug', 'Type ID: ' . $typeId);
        log_message('debug', 'Name: ' . $name);

        // Validate input
        if (empty($typeId) || empty($name)) {
            log_message('error', 'Type ID or Name is empty');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Type ID and Name are required'
            ]);
        }

        // Check if the type exists and belongs to the user
        $existingType = $model->where('id', $typeId)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$existingType) {
            log_message('error', 'Expense type not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense type not found'
            ]);
        }

        // Check for duplicate name (excluding current record)
        $duplicate = $model->where('user_id', session()->get('user_id'))
            ->where('name', $name)
            ->where('id !=', $typeId)
            ->first();

        if ($duplicate) {
            log_message('debug', 'Duplicate name found');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense Type with this name already exists'
            ]);
        }

        // Update the expense type
        $updateData = [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Update data: ' . json_encode($updateData));

        $result = $model->update($typeId, $updateData);

        log_message('debug', 'Update result: ' . ($result ? 'true' : 'false'));

        if ($result) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Expense Type Updated Successfully'
            ]);
        } else {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update expense type'
            ]);
        }
    }

    /**
     * Delete Expense Type
     */
    public function deleteType()
    {
        log_message('debug', '=== deleteType method called ===');

        $typeId = $this->request->getPost('id');

        log_message('debug', 'Type ID to delete: ' . $typeId);

        if (empty($typeId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Type ID is required'
            ]);
        }

        $model = model('App\Models\ExpenseTypeModel');

        // Check if the type exists and belongs to the user
        $existingType = $model->where('id', $typeId)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$existingType) {
            log_message('error', 'Expense type not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense type not found'
            ]);
        }

        // Check if this expense type is being used in any expenses
        $expenseModel = model('App\Models\ExpenseModel');
        $usedInExpenses = $expenseModel->where('user_id', session()->get('user_id'))
            ->where('expense_head', $existingType['name'])
            ->countAllResults();

        if ($usedInExpenses > 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot delete: This expense type is used in ' . $usedInExpenses . ' expense(s)'
            ]);
        }

        // Delete the expense type
        $result = $model->delete($typeId);

        log_message('debug', 'Delete result: ' . ($result ? 'true' : 'false'));

        if ($result) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Expense Type Deleted Successfully'
            ]);
        } else {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to delete expense type'
            ]);
        }
    }

    /**
     * Update Expense
     */
    /**
     * Update Expense
     */
    public function update()
    {
        log_message('debug', '=== update expense method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        $model = model('App\Models\ExpenseModel');
        $expenseId = $this->request->getPost('expense_id');

        log_message('debug', 'Expense ID: ' . $expenseId);

        if (empty($expenseId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense ID is required'
            ]);
        }

        // Check if expense exists and belongs to user
        $existingExpense = $model->where('id', $expenseId)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$existingExpense) {
            log_message('error', 'Expense not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Expense not found'
            ]);
        }

        // Handle file upload
        $file = $this->request->getFile('document');
        $fileName = $existingExpense['document']; // Keep existing file by default

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Validate file type and size
            $validTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $fileType = $file->getExtension();

            if (!in_array(strtolower($fileType), $validTypes)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid file type. Allowed: jpg, jpeg, png, pdf, doc, docx'
                ]);
            }

            if ($file->getSize() > 5 * 1024 * 1024) { // 5MB max
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'File size too large. Maximum 5MB allowed.'
                ]);
            }

            // Generate new random name for the file
            $newFileName = $file->getRandomName();
            $uploadPath = FCPATH . 'assets/expenses/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old file if exists
            if (!empty($existingExpense['document'])) {
                $oldFilePath = $uploadPath . $existingExpense['document'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                    log_message('debug', 'Deleted old file: ' . $oldFilePath);
                }
            }

            // Upload new file
            $file->move($uploadPath, $newFileName);
            $fileName = $newFileName; // Set the new filename
            log_message('debug', 'New file uploaded: ' . $newFileName);
        }

        // Prepare update data
        $updateData = [
            'name'         => $this->request->getPost('name'),
            'expense_head' => $this->request->getPost('expense_head'),
            'employee'     => $this->request->getPost('employee'),
            'invoice_no'   => $this->request->getPost('invoice_no'),
            'date'         => $this->request->getPost('date'),
            'amount'       => $this->request->getPost('amount'),
            'bank_account' => $this->request->getPost('bank_account'),
            'description'  => $this->request->getPost('description'),
            'document'     => $fileName, // This will be either old file or new file name
            'updated_at'   => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Update data: ' . json_encode($updateData));

        $result = $model->update($expenseId, $updateData);

        if ($result) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Expense Updated Successfully'
            ]);
        } else {
            log_message('error', 'Failed to update expense. DB Error: ' . json_encode($model->errors()));
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update expense'
            ]);
        }
    }

    /**
     * Delete Expense
     */
    /**
     * Delete Expense
     */
    public function delete($id = null)
    {
        log_message('debug', '=== delete expense method called ===');
        log_message('debug', 'Expense ID: ' . $id);

        if (empty($id)) {
            return redirect()->back()->with('error', 'Expense ID is required');
        }

        $model = model('App\Models\ExpenseModel');

        // Check if expense exists and belongs to user
        $expense = $model->where('id', $id)
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (!$expense) {
            log_message('error', 'Expense not found or does not belong to user');
            return redirect()->back()->with('error', 'Expense not found');
        }

        // Delete associated document file if exists - FIXED PATH
        if (!empty($expense['document'])) {
            $filePath = FCPATH . 'assets/expenses/' . $expense['document'];
            if (file_exists($filePath)) {
                unlink($filePath);
                log_message('debug', 'Deleted file: ' . $filePath);
            }
        }

        // Delete the expense
        $result = $model->delete($id);

        if ($result) {
            return redirect()->to(route_to('route.expense.list'))->with('success', 'Expense Deleted Successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to delete expense');
        }
    }
    public function get($id)
    {
        $expenseModel     = model('App\Models\ExpenseModel');
        $expense = $expenseModel->find($id);
        if ($expense) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $expense
            ]);
        }
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Expense not found'
        ]);
    }


    // In your Accounts controller or Expense controller

    public function approve()
    {
        if ($this->request->isAJAX()) {
            $expenseModel     = model('App\Models\ExpenseModel');
            $id = $this->request->getPost('id');

            // Update the expense status
            $data = [
                'status' => 'approved',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($expenseModel->update($id, $data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Expense approved successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to approve expense'
                ]);
            }
        }
    }

    public function reject()
    {
        if ($this->request->isAJAX()) {
            $expenseModel     = model('App\Models\ExpenseModel');
            $id = $this->request->getPost('id');
            $reason = $this->request->getPost('reason');

            // Update the expense status
            $data = [
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($expenseModel->update($id, $data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Expense rejected successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to reject expense'
                ]);
            }
        }
    }


    public function qSelectCriteria()
    {
        // Get date parameters from request (GET method)
        $request = service('request');
        $from_date = $request->getGet('from_date');
        $to_date = $request->getGet('to_date');

        log_message('debug', 'Received from_date: ' . $from_date);
        log_message('debug', 'Received to_date: ' . $to_date);

        // Validate and set default dates if not provided
        if (empty($from_date)) {
            // Default to first day of current month
            $from_date = date('Y-m-01'); // 2026-02-01 format
        }

        if (empty($to_date)) {
            // Default to today
            $to_date = date('Y-m-d'); // 2026-02-22 format
        }

        // Validate date range
        if (strtotime($from_date) > strtotime($to_date)) {
            // If from_date is greater than to_date, swap them
            $temp = $from_date;
            $from_date = $to_date;
            $to_date = $temp;
        }

        // Prevent future dates
        $today = date('Y-m-d');
        if (strtotime($to_date) > strtotime($today)) {
            $to_date = $today;
        }

        if (strtotime($from_date) > strtotime($today)) {
            $from_date = date('Y-m-01'); // Reset to first day of month
        }

        // Format dates for database queries
        $from_date_db = $from_date . ' 00:00:00';
        $to_date_db = $to_date . ' 23:59:59';

        $userId = session()->get('user_id');
        $currentMonth = date('F');
        $payment_model = model('App\Models\Payment');
        $expenseModel     = model('App\Models\ExpenseModel');
        $incomeModel     = model('App\Models\IncomeModel');

        // Customer payments received within date range
        $customers_payment_received = (int) $payment_model
            ->selectSum('amount')
            ->where('admin_id', $userId)
            ->where('user_type', 'user')
            ->where('status', 'successful')
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->first()
            ->amount;

        // Employee payments within date range
        $EmployeePayment = (int) $payment_model
            ->selectSum('amount')
            ->where('admin_id', $userId)
            ->where('user_type', 'employee')
            ->where('status', 'successful')
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->first()
            ->amount;

        // Expenses within date range
        $expense = $expenseModel
            ->selectSum('amount')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->orderBy('id', 'DESC')
            ->first();

        $expense = $expense['amount'] ?? 0; // use array syntax with default

        // Other income within date range
        $income = $incomeModel
            ->selectSum('amount')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->orderBy('id', 'DESC')
            ->first();

        $income_amount = $income['amount'] ?? 0;

        // Bandwidth buy (purchase bills) within date range
        $Band_buy_Model = model('App\Models\PurchaseBillModel');
        $result = $Band_buy_Model
            ->select('SUM(paid_number) as total_paid')
            ->where('admin_id', $userId)
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->first();

        $totalBand_buy = $result['total_paid'] ?? 0;

        // Bandwidth sell within date range
        $Band_sell_Model = model('App\Models\BandwidthSellInvoices');

        // Get all requisitions for this user within date range
        $allRequisitions = $Band_sell_Model
            ->where('admin_id', $userId)
            ->where('created_at >=', $from_date_db)
            ->where('created_at <=', $to_date_db)
            ->findAll();

        // Group by requisition_id
        $grouped = [];
        foreach ($allRequisitions as $row) {
            $requisitionId = $row['requisition_id'];

            if (!isset($grouped[$requisitionId])) {
                // Initialize with the first item's data
                $grouped[$requisitionId] = [
                    'id' => $row['id'],
                    'item_count' => 1,
                    'requisition_id' => $requisitionId,
                    'requisition_date' => $row['requisition_date'],
                    'requisition_by' => $row['requisition_by'],
                    'deadline' => $row['deadline'],
                    'approved_by' => $row['approved_by'],
                    'approved_date' => $row['approved_date'],
                    'item_names' => [$row['item_name']],
                    'total_amount' => (float) ($row['total_amount'] ?? 0),
                    'received_amount' => $row['received_amount'] ?? 0,
                    'due' => $row['due'] ?? 0,
                    'remarks' => $row['remarks'] ?? '',
                    'status' => $row['status'] ?? 'pending',
                    'payment_date' => $row['payment_date'] ?? null,
                    'payment_method' => $row['payment_method'] ?? null,
                    'paid_by' => $row['paid_by'] ?? null,
                    'received_by' => $row['received_by'] ?? null,
                ];
            } else {
                // For subsequent items, only increment count and add item name
                $grouped[$requisitionId]['item_count']++;
                $grouped[$requisitionId]['item_names'][] = $row['item_name'];
            }
        }

        // Re-index grouped values
        $requisitions = array_values($grouped);

        $totalBand_sell = 0;
        foreach ($requisitions as $row) {
            $totalBand_sell += (float) $row['received_amount'];
        }

        // OTC payments within date range
        $db = \Config\Database::connect();
        $result = $db->table('connection_details')
            ->select('SUM(connection_details.otc) as total_otc')
            ->join('users', 'users.id = connection_details.user_id')
            ->where('users.admin_id', $userId)
            ->where('connection_details.otc_status', 'paid')
            ->where('connection_details.created_at >=', $from_date_db)
            ->where('connection_details.created_at <=', $to_date_db)
            ->get()
            ->getRowArray();

        $totalOtc = $result['total_otc'] ?? 0;

        // Calculate totals
        $TotalExpense = $expense + $EmployeePayment + $totalBand_buy;
        $TotalIncome = $customers_payment_received + $income_amount + $totalBand_sell + $totalOtc;

        // For monthly bill (keeping as is since it's hardcoded)
        $monthly_bill = 12514.00;

        // Prepare data for view
        $data = [
            'page_title' => 'Accounts Report',
            'breadcrumb_active' => 'Accounts Report',
            'from_date' => $from_date,
            'to_date' => $to_date,

            // Income Details
            'customers_payment_received' => $customers_payment_received,
            'monthly_bill' => $monthly_bill,
            'total_income' => $TotalIncome,
            'Band_sell' => $totalBand_sell,
            'totalOtc' => $totalOtc,
            'other_income' => $income_amount,

            // Expense Details
            'EmployeePayment' => $EmployeePayment,
            'total_expense' => $TotalExpense,
            'other_expenses' => $expense,
            'Band_buy' => $totalBand_buy,

            // Current Summary
            'current_total_income' => $TotalIncome,
            'current_total_expenses' => $TotalExpense,
            'current_amount' => $TotalIncome - $TotalExpense,

            // Period Summary (based on selected date range)
            'period_total_income' => $TotalIncome,
            'period_total_expenses' => $TotalExpense,
            'period_current_amount' => $TotalIncome - $TotalExpense
        ];

        log_message('debug', 'Date Range: ' . $from_date . ' to ' . $to_date);
        log_message('debug', 'Total Income: ' . $TotalIncome);
        log_message('debug', 'Total Expense: ' . $TotalExpense);
        log_message('debug', 'Net Amount: ' . ($TotalIncome - $TotalExpense));

        return view('accounts/report', $data);
    }




    public function otcReport()
    {
        try {
            $user_id = session()->get('user_id');

            if (!$user_id) {
                return redirect()->to('login')->with('error', 'Please login first');
            }

            // Get all users/POPs for dropdown
            $pops = $this->user_model->where('admin_id', $user_id)->findAll();

            // Prepare data for view
            $data = [
                'title' => 'OTC Report',
                'pops' => $pops,
                'user_id' => $user_id
            ];

            return view('accounts/otc_report', $data);
        } catch (\Exception $e) {
            log_message('error', 'OTC Report Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load OTC report. Please try again.');
        }
    }

    /**
     * AJAX endpoint to fetch OTC data
     */
    public function ajaxGetOtcData()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON(['error' => 'Invalid request'])->setStatusCode(400);
            }

            $logged_in_user_id = session()->get('user_id');

            if (!$logged_in_user_id) {
                return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
            }

            $db = \Config\Database::connect();

            // Base query builder for all operations
            $baseBuilder = $db->table('connection_details')
                ->select('connection_details.*, users.name as user_name')
                ->join('users', 'users.id = connection_details.user_id')
                ->where('users.admin_id', $logged_in_user_id);

            // Get parameters
            $draw = $this->request->getPost('draw');
            $start = $this->request->getPost('start') ?? 0;
            $length = $this->request->getPost('length') ?? 10;

            // Get search value from DataTables
            $searchValue = $this->request->getPost('search')['value'] ?? '';

            // Apply filters
            $macPop = $this->request->getPost('mac_pop');
            $status = $this->request->getPost('status');
            $fromDate = $this->request->getPost('from_date');
            $toDate = $this->request->getPost('to_date');

            // Apply custom filters to base builder
            if (!empty($macPop)) {
                $baseBuilder->where('connection_details.user_id', $macPop);
            }
            if (!empty($status)) {
                $baseBuilder->where('connection_details.otc_status', $status);
            }
            if (!empty($fromDate) && $this->validateDate($fromDate)) {
                $baseBuilder->where('connection_details.created_at >=', $this->formatDateForDb($fromDate));
            }
            if (!empty($toDate) && $this->validateDate($toDate)) {
                $baseBuilder->where('connection_details.created_at <=', $this->formatDateForDb($toDate, true));
            }

            // Clone the builder for summary calculations (paid and due totals)
            $summaryBuilder = clone $baseBuilder;
            $summary = $summaryBuilder
                ->select('
                SUM(connection_details.otc) as total_otc,
                SUM(CASE WHEN LOWER(connection_details.otc_status) = "paid" THEN connection_details.otc ELSE 0 END) as paid_otc,
                SUM(CASE WHEN LOWER(connection_details.otc_status) = "due" THEN connection_details.otc ELSE 0 END) as due_otc,
                COUNT(CASE WHEN LOWER(connection_details.otc_status) = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN LOWER(connection_details.otc_status) = "due" THEN 1 END) as due_count,
                COUNT(CASE WHEN LOWER(connection_details.otc_status) = "pending" THEN 1 END) as pending_count
            ')
                ->get()
                ->getRowArray();

            // Clone the builder for total count (without search)
            $totalBuilder = clone $baseBuilder;
            $totalRecords = $totalBuilder->countAllResults();

            // Apply search filter if provided to the main builder
            if (!empty($searchValue)) {
                $baseBuilder->groupStart()
                    ->like('connection_details.id', $searchValue)
                    ->orLike('connection_details.fiber_code', $searchValue)
                    ->orLike('connection_details.user_id', $searchValue)
                    ->orLike('connection_details.connection_type', $searchValue)
                    ->orLike('connection_details.client_type', $searchValue)
                    ->orLike('connection_details.core_color', $searchValue)
                    ->orLike('connection_details.otc_status', $searchValue)
                    ->orLike('connection_details.billing_status', $searchValue)
                    ->orLike('connection_details.otc', $searchValue)
                    ->orLike('users.name', $searchValue) // Enable user name search
                    ->groupEnd();
            }

            // Get filtered count (with all filters including search)
            $filteredBuilder = clone $baseBuilder;
            $filteredRecords = $filteredBuilder->countAllResults();

            // Get data (with all filters including search)
            $data = $baseBuilder
                ->orderBy('connection_details.created_at', 'DESC')
                ->limit($length, $start)
                ->get()
                ->getResultArray();

            // Add status badges to the data
            foreach ($data as &$row) {
                $row['otc_status_badge'] = $this->getStatusBadge($row['otc_status'] ?? '');
                $row['billing_status_badge'] = $this->getBillingStatusBadge($row['billing_status'] ?? '');

                // Make sure we have connection id for the update modal
                // If your primary key is 'id', it should already be there
            }

            // Format response with summary data
            $response = [
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
                'totalOtc' => number_format($summary['total_otc'] ?? 0, 2),
                'paidOtc' => number_format($summary['paid_otc'] ?? 0, 2),
                'dueOtc' => number_format($summary['due_otc'] ?? 0, 2),
                'paidCount' => intval($summary['paid_count'] ?? 0),
                'dueCount' => intval($summary['due_count'] ?? 0),
                'pendingCount' => intval($summary['pending_count'] ?? 0)
            ];

            log_message('debug', 'Search Value: ' . $searchValue);
            log_message('debug', 'Filtered Records: ' . $filteredRecords);
            log_message('debug', 'SQL: ' . $db->getLastQuery());

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            log_message('error', 'AJAX Error: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    /**
     * Update OTC status and amount
     */
    public function updateStatus()
    {
        try {
            // Check if it's AJAX request
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            // Validate input
            $rules = [
                'user_id' => 'required|numeric',
                'connection_id' => 'required|numeric',
                'otc_amount' => 'required|numeric',
                'otc_status' => 'required'
            ];

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ]);
            }

            // Get connection model
            $connectionModel = model('App\Models\ConnectionData');

            // Update connection data
            $data = [
                'otc' => $this->request->getPost('otc_amount'),
                'otc_status' => $this->request->getPost('otc_status'),
                'remarks' => $this->request->getPost('remarks')
            ];


            // You might want to add logging or additional fields
            // 'updated_at' => date('Y-m-d H:i:s'),
            // 'updated_by' => session()->get('user_id')

            $updated = $connectionModel->update($this->request->getPost('connection_id'), $data);

            if ($updated) {
                // Log the activity if you have logging
                // log_message('info', "OTC status updated for connection ID: " . $this->request->getPost('connection_id'));

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'OTC status updated successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update OTC status'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', '[OtcReport::updateStatus] Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function to get status badge HTML
     */
    private function getStatusBadge($status)
    {
        $status = strtolower($status ?? '');

        switch ($status) {
            case 'completed':
            case 'paid':
                return '<span class="label label-success">' . ucfirst($status) . '</span>';
            case 'pending':
                return '<span class="label label-warning">Pending</span>';
            case 'due':
                return '<span class="label label-danger">Due</span>';
            case 'failed':
            case 'cancelled':
                return '<span class="label label-danger">' . ucfirst($status) . '</span>';
            case 'na':
                return '<span class="label label-default">N/A</span>';
            default:
                return '<span class="label label-default">' . ($status ? ucfirst($status) : 'N/A') . '</span>';
        }
    }

    /**
     * Helper function to get billing status badge
     */
    private function getBillingStatusBadge($status)
    {
        $status = strtolower($status ?? '');

        switch ($status) {
            case 'paid':
                return '<span class="label label-success">Paid</span>';
            case 'pending':
                return '<span class="label label-warning">Pending</span>';
            case 'due':
                return '<span class="label label-danger">Due</span>';
            default:
                return '<span class="label label-default">' . ($status ? ucfirst($status) : 'N/A') . '</span>';
        }
    }

    /**
     * Validate date format
     */
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Format date for database queries
     */
    private function formatDateForDb($date, $endOfDay = false)
    {
        $formatted = date('Y-m-d', strtotime($date));
        if ($endOfDay) {
            $formatted .= ' 23:59:59';
        }
        return $formatted;
    }


    public function exportCsv()
    {
        try {
            $logged_in_user_id = session()->get('user_id');

            if (!$logged_in_user_id) {
                return redirect()->to('auth/login')->with('error', 'Unauthorized');
            }

            $db = \Config\Database::connect();

            // Get filter parameters from query string
            $macPop = $this->request->getGet('mac_pop');
            $status = $this->request->getGet('status');
            $fromDate = $this->request->getGet('from_date');
            $toDate = $this->request->getGet('to_date');
            $search = $this->request->getGet('search');

            // Build query for export (same filters as the DataTable)
            $builder = $db->table('connection_details')
                ->select('
                connection_details.id,
                connection_details.user_id,
                users.name as user_name,
                connection_details.created_at,
                connection_details.connection_type,
                connection_details.fiber_code,
                connection_details.core_color,
                connection_details.client_type,
                connection_details.otc,
                connection_details.otc_status,
                connection_details.billing_status
            ')
                ->join('users', 'users.id = connection_details.user_id')
                ->where('users.admin_id', $logged_in_user_id);

            // Apply same filters as in ajaxGetOtcData
            if (!empty($macPop)) {
                $builder->where('connection_details.user_id', $macPop);
            }
            if (!empty($status)) {
                $builder->where('connection_details.otc_status', $status);
            }
            if (!empty($fromDate) && $this->validateDate($fromDate)) {
                $builder->where('connection_details.created_at >=', $this->formatDateForDb($fromDate));
            }
            if (!empty($toDate) && $this->validateDate($toDate)) {
                $builder->where('connection_details.created_at <=', $this->formatDateForDb($toDate, true));
            }

            // Apply search filter if provided
            if (!empty($search)) {
                $builder->groupStart()
                    ->like('connection_details.id', $search)
                    ->orLike('connection_details.fiber_code', $search)
                    ->orLike('connection_details.user_id', $search)
                    ->orLike('connection_details.connection_type', $search)
                    ->orLike('connection_details.client_type', $search)
                    ->orLike('connection_details.core_color', $search)
                    ->orLike('connection_details.otc_status', $search)
                    ->orLike('connection_details.billing_status', $search)
                    ->orLike('connection_details.otc', $search)
                    ->orLike('users.name', $search)
                    ->groupEnd();
            }

            // Get the data (no pagination - export ALL filtered records)
            $data = $builder
                ->orderBy('connection_details.created_at', 'DESC')
                ->get()
                ->getResultArray();

            // Set filename with timestamp and filter info
            $filename = 'otc_report_' . date('Y-m-d_His');

            // Add filter info to filename if filters are applied
            $filterParts = [];
            if (!empty($fromDate)) $filterParts[] = 'from_' . $fromDate;
            if (!empty($toDate)) $filterParts[] = 'to_' . $toDate;
            if (!empty($status)) $filterParts[] = 'status_' . $status;
            if (!empty($search)) $filterParts[] = 'search';

            if (!empty($filterParts)) {
                $filename .= '_' . implode('_', $filterParts);
            }

            $filename .= '.csv';

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Create output stream
            $output = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add CSV headers
            fputcsv($output, [
                'ID',
                'User ID',
                'User Name',
                'Date',
                'Connection Type',
                'Fiber Code',
                'Core Color',
                'Client Type',
                'OTC (৳)',
                'OTC Status',
                'Billing Status'
            ]);

            // Add data rows
            $totalOtc = 0;
            foreach ($data as $row) {
                // Format date
                $date = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '';

                // Format OTC amount
                $otc = !empty($row['otc']) ? number_format($row['otc'], 2) : '0.00';
                $totalOtc += floatval($row['otc'] ?? 0);

                // Format statuses
                $otcStatus = !empty($row['otc_status']) ? ucfirst($row['otc_status']) : 'N/A';
                $billingStatus = !empty($row['billing_status']) ? ucfirst($row['billing_status']) : 'N/A';

                fputcsv($output, [
                    $row['id'] ?? '',
                    $row['user_id'] ?? '',
                    $row['user_name'] ?? '',
                    $date,
                    $row['connection_type'] ?? '',
                    $row['fiber_code'] ?? '',
                    $row['core_color'] ?? '',
                    $row['client_type'] ?? '',
                    $otc,
                    $otcStatus,
                    $billingStatus
                ]);
            }

            // Add summary row
            fputcsv($output, []); // Empty row for spacing
            fputcsv($output, [
                'SUMMARY',
                '',
                '',
                '',
                '',
                '',
                '',
                'Total Records:',
                count($data),
                'Total OTC:',
                '৳ ' . number_format($totalOtc, 2)
            ]);

            // Add filter information
            if (!empty($fromDate) || !empty($toDate) || !empty($status) || !empty($search)) {
                fputcsv($output, []); // Empty row
                fputcsv($output, ['FILTERS APPLIED:']);

                if (!empty($fromDate)) fputcsv($output, ['From Date:', $fromDate]);
                if (!empty($toDate)) fputcsv($output, ['To Date:', $toDate]);
                if (!empty($status)) fputcsv($output, ['OTC Status:', ucfirst($status)]);
                if (!empty($search)) fputcsv($output, ['Search:', $search]);
            }

            // Add generation info
            fputcsv($output, []); // Empty row
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, ['Generated by:', session()->get('username') ?? '']);

            fclose($output);
            exit;
        } catch (\Exception $e) {
            log_message('error', 'Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }
}

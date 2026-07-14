<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class IncomeController extends BaseController
{
    protected $user_model;
    protected $incomeCategoryModel;
    protected $incomeModel;

    public function __construct()
    {
        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');
        $this->incomeCategoryModel = model('App\Models\IncomeTypeModel');
        $this->incomeModel = model('App\Models\IncomeModel');
    }

    public function index()
    {
        $user_id = session()->get('user_id');

        $data['employees'] = $this->user_model
            ->where('admin_id', $user_id)
            ->where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['income_categories'] = $this->incomeCategoryModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['incomes'] = $this->incomeModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        log_message('debug', 'Incomes fetched: ' . json_encode($data));

        return view('accounts/incomes', $data);
    }

    /**
     * Save Income Category
     */
    public function saveCategory()
    {
        log_message('debug', '=== saveCategory method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        // Check if it's an AJAX request
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request method'
            ]);
        }

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

        $user_id = session()->get('user_id');

        // Check if model is loaded properly
        if (!$this->incomeCategoryModel) {
            log_message('error', 'IncomeCategoryModel not loaded');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Model not loaded properly'
            ]);
        }

        // Prevent duplicate per user
        $exists = $this->incomeCategoryModel
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->first();

        if ($exists) {
            log_message('debug', 'Duplicate found');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income Category Already Exists'
            ]);
        }

        $insertData = [
            'user_id' => $user_id,
            'name'    => $name,
            'status'  => 'active'
        ];

        log_message('debug', 'Insert data: ' . json_encode($insertData));

        try {
            $result = $this->incomeCategoryModel->insert($insertData);

            if ($result) {
                log_message('debug', 'Insert successful');
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Created Successfully'
                ]);
            } else {
                log_message('error', 'Insert failed: ' . json_encode($this->incomeCategoryModel->errors()));
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to create category: ' . implode(', ', $this->incomeCategoryModel->errors())
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save Income
     */
    /**
     * Save Income
     */
    public function save()
    {
        log_message('debug', '=== save income method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        // Get all post data
        $postData = [
            'name'            => $this->request->getPost('name'),
            'income_category' => $this->request->getPost('income_category'),
            'employee'        => $this->request->getPost('employee'),
            'invoice_no'      => $this->request->getPost('invoice_no'),
            'date'            => $this->request->getPost('date'),
            'amount'          => $this->request->getPost('amount'),
            'bank_account'    => $this->request->getPost('bank_account'),
            'description'     => $this->request->getPost('description')
        ];

        log_message('debug', 'Processed post data: ' . json_encode($postData));

        // Validate required fields
        $required = ['name', 'income_category', 'employee', 'date', 'amount', 'bank_account'];
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ]);
            }
        }

        $file = $this->request->getFile('document');
        $fileName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Validate file type
            $validTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $fileType = $file->getExtension();

            if (!in_array(strtolower($fileType), $validTypes)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid file type. Allowed: jpg, jpeg, png, pdf, doc, docx'
                ]);
            }

            // Validate file size (max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'File size too large. Maximum 5MB allowed.'
                ]);
            }

            $fileName = $file->getRandomName();
            $uploadPath = FCPATH . 'assets/incomes/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move the file
            $file->move($uploadPath, $fileName);
            log_message('debug', 'File uploaded: ' . $fileName);
        }

        // Prepare insert data
        $insertData = [
            'name'            => $postData['name'],
            'income_category' => $postData['income_category'],
            'employee'        => $postData['employee'],
            'invoice_no'      => $postData['invoice_no'],
            'date'            => $postData['date'],
            'amount'          => $postData['amount'],
            'bank_account'    => $postData['bank_account'],
            'description'     => $postData['description'],
            'document'        => $fileName,
            'created_by'      => session()->get('user_id'),
            'user_id'         => session()->get('user_id'),
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Insert data: ' . json_encode($insertData));

        try {
            $result = $this->incomeModel->insert($insertData);

            if ($result) {
                log_message('debug', 'Income saved successfully with ID: ' . $result);
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Saved Successfully'
                ]);
            } else {
                $errors = $this->incomeModel->errors();
                log_message('error', 'Failed to save income. Errors: ' . json_encode($errors));
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to save income: ' . implode(', ', $errors)
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception saving income: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update Income Category
     */
    public function updateCategory()
    {
        log_message('debug', '=== updateCategory method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $categoryId = $this->request->getPost('category_id');
        $name = $this->request->getPost('name');

        log_message('debug', 'Category ID: ' . $categoryId);
        log_message('debug', 'Name: ' . $name);

        // Validate input
        if (empty($categoryId) || empty($name)) {
            log_message('error', 'Category ID or Name is empty');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Category ID and Name are required'
            ]);
        }

        $user_id = session()->get('user_id');

        // Check if the category exists and belongs to the user
        $existingCategory = $this->incomeCategoryModel
            ->where('id', $categoryId)
            ->where('user_id', $user_id)
            ->first();

        if (!$existingCategory) {
            log_message('error', 'Income category not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income category not found'
            ]);
        }

        // Check for duplicate name (excluding current record)
        $duplicate = $this->incomeCategoryModel
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->where('id !=', $categoryId)
            ->first();

        if ($duplicate) {
            log_message('debug', 'Duplicate name found');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income Category with this name already exists'
            ]);
        }

        // Update the income category
        $updateData = [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Update data: ' . json_encode($updateData));

        try {
            $result = $this->incomeCategoryModel->update($categoryId, $updateData);

            log_message('debug', 'Update result: ' . ($result ? 'true' : 'false'));

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Updated Successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to update income category'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete Income Category
     */
    public function deleteCategory()
    {
        log_message('debug', '=== deleteCategory method called ===');

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $categoryId = $this->request->getPost('id');

        log_message('debug', 'Category ID to delete: ' . $categoryId);

        if (empty($categoryId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Category ID is required'
            ]);
        }

        $user_id = session()->get('user_id');

        // Check if the category exists and belongs to the user
        $existingCategory = $this->incomeCategoryModel
            ->where('id', $categoryId)
            ->where('user_id', $user_id)
            ->first();

        if (!$existingCategory) {
            log_message('error', 'Income category not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income category not found'
            ]);
        }

        // Check if this income category is being used in any incomes
        $usedInIncomes = $this->incomeModel
            ->where('user_id', $user_id)
            ->where('income_category', $existingCategory['name'])
            ->countAllResults();

        if ($usedInIncomes > 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot delete: This income category is used in ' . $usedInIncomes . ' income(s)'
            ]);
        }

        // Delete the income category
        try {
            $result = $this->incomeCategoryModel->delete($categoryId);

            log_message('debug', 'Delete result: ' . ($result ? 'true' : 'false'));

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Deleted Successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete income category'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update Income
     */
    public function update()
    {
        log_message('debug', '=== update income method called ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $incomeId = $this->request->getPost('income_id');

        log_message('debug', 'Income ID: ' . $incomeId);

        if (empty($incomeId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income ID is required'
            ]);
        }

        $user_id = session()->get('user_id');

        // Check if income exists and belongs to user
        $existingIncome = $this->incomeModel
            ->where('id', $incomeId)
            ->where('user_id', $user_id)
            ->first();

        if (!$existingIncome) {
            log_message('error', 'Income not found or does not belong to user');
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income not found'
            ]);
        }

        // Handle file upload
        $file = $this->request->getFile('document');
        $fileName = $existingIncome['document']; // Keep existing file by default

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
            $uploadPath = FCPATH . 'assets/incomes/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old file if exists
            if (!empty($existingIncome['document'])) {
                $oldFilePath = $uploadPath . $existingIncome['document'];
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
            'name'            => $this->request->getPost('name'),
            'income_category' => $this->request->getPost('income_category'),
            'employee'        => $this->request->getPost('employee'),
            'invoice_no'      => $this->request->getPost('invoice_no'),
            'date'            => $this->request->getPost('date'),
            'amount'          => $this->request->getPost('amount'),
            'bank_account'    => $this->request->getPost('bank_account'),
            'description'     => $this->request->getPost('description'),
            'document'        => $fileName,
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Update data: ' . json_encode($updateData));

        try {
            $result = $this->incomeModel->update($incomeId, $updateData);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Updated Successfully'
                ]);
            } else {
                log_message('error', 'Failed to update income. DB Error: ' . json_encode($this->incomeModel->errors()));
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to update income'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get Income by ID
     */
    public function get($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $income = $this->incomeModel->find($id);
        if ($income) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $income
            ]);
        }
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Income not found'
        ]);
    }

    /**
     * Delete Income
     */
    public function delete($id = null)
    {
        log_message('debug', '=== delete income method called ===');
        log_message('debug', 'Income ID: ' . $id);

        if (empty($id)) {
            return redirect()->back()->with('error', 'Income ID is required');
        }

        $user_id = session()->get('user_id');

        // Check if income exists and belongs to user
        $income = $this->incomeModel
            ->where('id', $id)
            ->where('user_id', $user_id)
            ->first();

        if (!$income) {
            log_message('error', 'Income not found or does not belong to user');
            return redirect()->back()->with('error', 'Income not found');
        }

        // Delete associated document file if exists
        if (!empty($income['document'])) {
            $filePath = FCPATH . 'assets/incomes/' . $income['document'];
            if (file_exists($filePath)) {
                unlink($filePath);
                log_message('debug', 'Deleted file: ' . $filePath);
            }
        }

        // Delete the income
        try {
            $result = $this->incomeModel->delete($id);

            if ($result) {
                return redirect()->to(route_to('route.income.list'))->with('success', 'Income Deleted Successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to delete income');
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Approve Income
     */
    public function approve()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $id = $this->request->getPost('id');

        // Update the income status
        $data = [
            'status' => 'approved',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            if ($this->incomeModel->update($id, $data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Income approved successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to approve income'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reject Income
     */
    public function reject()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $id = $this->request->getPost('id');
        $reason = $this->request->getPost('reason');

        // Update the income status
        $data = [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            if ($this->incomeModel->update($id, $data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Income rejected successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to reject income'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

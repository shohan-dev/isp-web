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

        $employees = $this->user_model
            ->where('admin_id', $user_id)
            ->where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->findAll();

        // Always offer the logged-in user so income can be recorded without employees.
        $currentUser = $this->user_model->find($user_id);
        if ($currentUser) {
            $alreadyListed = false;
            foreach ($employees as $employee) {
                if ((int) ($employee->id ?? 0) === (int) $user_id) {
                    $alreadyListed = true;
                    break;
                }
            }
            if (!$alreadyListed) {
                array_unshift($employees, $currentUser);
            }
        }

        $data['employees'] = $employees;

        $data['income_categories'] = $this->incomeCategoryModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['incomes'] = $this->incomeModel
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('accounts/incomes', $data);
    }

    /**
     * Save Income Category
     */
    public function saveCategory()
    {
        $name = trim((string) $this->request->getPost('name'));

        if ($name === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Name is required'
            ]);
        }

        $user_id = session()->get('user_id');
        if (empty($user_id)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Session expired. Please log in again.'
            ]);
        }

        // Prevent duplicate per user
        $exists = $this->incomeCategoryModel
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->first();

        if ($exists) {
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

        try {
            $result = $this->incomeCategoryModel->insert($insertData);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Created Successfully'
                ]);
            }

            $errors = $this->incomeCategoryModel->errors();
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create category' . (!empty($errors) ? ': ' . implode(', ', $errors) : '')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Income category save failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save Income
     */
    public function save()
    {
        $user_id = session()->get('user_id');
        if (empty($user_id)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Session expired. Please log in again.'
            ]);
        }

        // Get all post data
        $postData = [
            'name'            => trim((string) $this->request->getPost('name')),
            'income_category' => $this->request->getPost('income_category'),
            'employee'        => $this->request->getPost('employee'),
            'invoice_no'      => $this->request->getPost('invoice_no'),
            'date'            => $this->request->getPost('date'),
            'amount'          => $this->request->getPost('amount'),
            'bank_account'    => $this->request->getPost('bank_account'),
            'description'     => $this->request->getPost('description')
        ];

        // Employee is optional (admin may have none); other fields are required.
        $required = ['name', 'income_category', 'date', 'amount', 'bank_account'];
        foreach ($required as $field) {
            if ($postData[$field] === null || $postData[$field] === '') {
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
            'employee'        => $postData['employee'] !== null && $postData['employee'] !== ''
                ? $postData['employee']
                : $user_id,
            'invoice_no'      => $postData['invoice_no'],
            'date'            => $postData['date'],
            'amount'          => $postData['amount'],
            'bank_account'    => $postData['bank_account'],
            'description'     => $postData['description'],
            'document'        => $fileName,
            'created_by'      => $user_id,
            'user_id'         => $user_id,
            'status'          => 'pending',
        ];

        try {
            $result = $this->incomeModel->insert($insertData);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Saved Successfully'
                ]);
            }

            $errors = $this->incomeModel->errors();
            log_message('error', 'Failed to save income. Errors: ' . json_encode($errors));
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to save income' . (!empty($errors) ? ': ' . implode(', ', $errors) : '')
            ]);
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
        $categoryId = $this->request->getPost('category_id');
        $name = trim((string) $this->request->getPost('name'));

        if (empty($categoryId) || $name === '') {
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
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income Category with this name already exists'
            ]);
        }

        $updateData = [
            'name' => $name,
        ];

        try {
            $result = $this->incomeCategoryModel->update($categoryId, $updateData);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Updated Successfully'
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update income category'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Income category update failed: ' . $e->getMessage());
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
        $categoryId = $this->request->getPost('id');

        if (empty($categoryId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Category ID is required'
            ]);
        }

        $user_id = session()->get('user_id');

        $existingCategory = $this->incomeCategoryModel
            ->where('id', $categoryId)
            ->where('user_id', $user_id)
            ->first();

        if (!$existingCategory) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income category not found'
            ]);
        }

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

        try {
            $result = $this->incomeCategoryModel->delete($categoryId);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Category Deleted Successfully'
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to delete income category'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Income category delete failed: ' . $e->getMessage());
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
        $incomeId = $this->request->getPost('income_id');

        if (empty($incomeId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Income ID is required'
            ]);
        }

        $user_id = session()->get('user_id');

        $existingIncome = $this->incomeModel
            ->where('id', $incomeId)
            ->where('user_id', $user_id)
            ->first();

        if (!$existingIncome) {
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
        $employee = $this->request->getPost('employee');
        $updateData = [
            'name'            => $this->request->getPost('name'),
            'income_category' => $this->request->getPost('income_category'),
            'employee'        => ($employee !== null && $employee !== '') ? $employee : ($existingIncome['employee'] ?? $user_id),
            'invoice_no'      => $this->request->getPost('invoice_no'),
            'date'            => $this->request->getPost('date'),
            'amount'          => $this->request->getPost('amount'),
            'bank_account'    => $this->request->getPost('bank_account'),
            'description'     => $this->request->getPost('description'),
            'document'        => $fileName,
        ];

        try {
            $result = $this->incomeModel->update($incomeId, $updateData);

            if ($result) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Income Updated Successfully'
                ]);
            }

            log_message('error', 'Failed to update income. DB Error: ' . json_encode($this->incomeModel->errors()));
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update income'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Exception updating income: ' . $e->getMessage());
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
        $user_id = session()->get('user_id');
        $income = $this->incomeModel
            ->where('id', $id)
            ->where('user_id', $user_id)
            ->first();

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
        $id = $this->request->getPost('id');
        $user_id = session()->get('user_id');

        if (empty($id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Income ID is required'
            ]);
        }

        $income = $this->incomeModel
            ->where('id', $id)
            ->where('user_id', $user_id)
            ->first();

        if (!$income) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Income not found'
            ]);
        }

        try {
            if ($this->incomeModel->update($id, ['status' => 'approved'])) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Income approved successfully'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to approve income'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Income approve failed: ' . $e->getMessage());
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
        $id = $this->request->getPost('id');
        $user_id = session()->get('user_id');

        if (empty($id)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Income ID is required'
            ]);
        }

        $income = $this->incomeModel
            ->where('id', $id)
            ->where('user_id', $user_id)
            ->first();

        if (!$income) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Income not found'
            ]);
        }

        try {
            if ($this->incomeModel->update($id, ['status' => 'rejected'])) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Income rejected successfully'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to reject income'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Income reject failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

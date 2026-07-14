<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InventoryPurchess;
use App\Models\VendorModel;
use App\Models\UnitModel;
use App\Models\InventoryCategory;


class InventoryPurchessController extends BaseController
{
    public function purchase_list()
     {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();
        $model = new InventoryPurchess();
        $userId = session()->get('user_id');
        $vendorModel = new VendorModel();
        // $companyName = $vendorModel->getCompanyNameById(5);


        // Step 1: Fetch all requisitions for this user
        $allRequisitions = $model->where(['admin_id' => $userId])->findAll();

        // Step 2: Group by requisition_id
        $grouped = [];
        foreach ($allRequisitions as $row) {
            // log_message('info', 'Processing requisition row: ' . json_encode($row));
            $id = $row['requisition_id'];
            $vendorName = $vendorModel->getCompanyNameById($row['vendor_suggestion']);

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['id'],
                    'item_count' => 0, // initialize item count manually
                    'requisition_id' => $id,
                    'vendor_suggestions' => [],
                    'requisition_date' => $row['requisition_date'],
                    'requisition_by' => $row['requisition_by'],
                    'deadline' => $row['deadline'],
                    'approved_by' => $row['approved_by'],
                    'approved_date' => $row['approved_date'],
                    'item_names' => [],
                    'total' => 0,
                ];
            }

            // Increment item count for each item
            $grouped[$id]['item_count']++;

            // Add vendor name if not already present
            if (!in_array($vendorName, $grouped[$id]['vendor_suggestions']) && $vendorName !== null) {
                $grouped[$id]['vendor_suggestions'][] = $vendorName;
            }

            // Add item name and total
            $grouped[$id]['item_names'][] = $row['item_name'];
            $grouped[$id]['total'] += (float) ($row['total'] ?? 0);
        }

        // Step 3: Re-index grouped values
        $requisitions = array_values($grouped);

        // Step 4: Load other models
        $VendorModel = new VendorModel();
        $unitController = new UnitModel();
        $providers = $VendorModel->where(['admin_id' => $userId])->findAll();
        // log_message('info', 'Providers fetched: ' . json_encode($providers));
        $unit = $unitController->where(['admin_id' => $userId])->findAll();

        $inventoryCategoryModel = new InventoryCategory();
        $categories = $inventoryCategoryModel
            ->select('*')
            ->where('admin_id', $userId)
            ->orderBy('id', 'asc')
            ->findAll();

        log_message('info', 'Categories fetched: ' . json_encode($categories));
        $allItems = [];
        foreach ($categories as $category) {
            log_message('info', 'Processing category: ' . json_encode($category));
            $categoryId = $category['id'];
            $categoryItems = json_decode($category['items'], true) ?: [];
            foreach ($categoryItems as $item) {
                $item['subcategory_id'] = null;
                $item['category_id'] = $categoryId;
                $allItems[] = $item;
            }

            $subcategoryItems = json_decode($category['subcategory_items'] ?? '', true) ?: [];

            foreach ($subcategoryItems as $index => $subcategory) {
                if (isset($subcategory['items'])) {
                    foreach ($subcategory['items'] as $item) {
                        $item['subcategory_id'] = (string) $index;
                        $item['category_id'] = $categoryId;
                        $allItems[] = $item;
                    }
                }
            }
        }
        // log_message('info', 'All items processed: ' . json_encode($allItems));
        // log_message('info', 'All items requisitions......: ' . json_encode($requisitions));

        return view('purchase/purchase_list', [
            'title' => 'Purchess Bill',
            'units' => $unit,
            'items' => $allItems,
            'requisitions' => $requisitions, // ✅ Grouped & processed
            'vendors' => $providers
        ]);
    }



    public function create()
    {
        $model = new InventoryPurchess();

        $items = $this->request->getPost('items');
        log_message('info', 'Creating requisition with items: ' . json_encode($items));
        $requisitionId = strtoupper(secure_random_string(10)); // Use your custom ID logic

        if (!$items || !is_array($items)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid items data']);
        }

        foreach ($items as $item) {
            $data = [
                'admin_id' => session()->get('user_id'),
                'requisition_id' => $requisitionId,
                'item_count' => count($items),
                'item_name' => $item['item'] ?? null,
                'category' => $item['category_id'] ?? null,
                'subcategory' => $item['subcategory_id'] ?? null,
                'description' => $item['description'] ?? null,
                'qty' => $item['qty'] ?? 0,
                'rate' => $item['rate'] ?? 0,
                'total' => floatval($item['qty']) * floatval($item['rate']),
                'vendor_suggestion' => $item['vendor_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'total_amount' => $this->calculateTotalAmount($items),
                'requisition_date' => $this->request->getPost('requisition_date'),
                'requisition_by' => $this->request->getPost('requisition_by'),
                'deadline' => $this->request->getPost('deadline'),
                'approved_by' => $this->request->getPost('approved_by'),
                'approved_date' => $this->request->getPost('approved_date'),
                'remarks' => $this->request->getPost('remarks'),

            ];

            $model->save($data);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Requisition saved!']);
    }

    private function calculateTotalAmount($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $total += $qty * $rate;
        }
        return $total;
    }

    public function getRequisition()
    {
        $id = $this->request->getGet('id');
        log_message('info', 'Requisition ID received: ' . $id);

        if (!$id) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing requisition ID'
            ]);
        }

        $InventoryPurchess = new InventoryPurchess();

        // Fetch all rows related to this requisition_id (one row per item)
        $requisitionRows = $InventoryPurchess->where('requisition_id', $id)->findAll();
        log_message('info', 'Requisition fetched: ' . json_encode($requisitionRows));

        if (empty($requisitionRows)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Requisition not found'
            ]);
        }

        // Use the first row to get common data
        $firstRow = $requisitionRows[0];

        // Prepare response structure
        $response = [
            'id' => $firstRow['id'],
            'requisition_id' => $firstRow['requisition_id'],
            'requisition_date' => $firstRow['requisition_date'],
            'deadline' => $firstRow['deadline'],
            'remarks' => $firstRow['remarks'], // Adjust if remarks stored elsewhere
            'total' => array_sum(array_column($requisitionRows, 'total')), // or use 'total_amount' if it's stored once
            'items' => [],
        ];

        foreach ($requisitionRows as $row) {
            log_message('info', 'Processing row: ' . json_encode($row));
            $response['items'][] = [
                'id' => $row['id'],
                'item' => $row['item_name'], // or 'item' depending on DB column
                'description' => $row['description'],
                'quantity' => $row['qty'],
                'rate' => $row['rate'],
                'unit_id' => $row['unit_id'] ?? null,
                'vendor_id' => $row['vendor_suggestion'] ?? null,
            ];
        }
        // log_message('info', 'Requisition response structure: ' . json_encode($response));

        log_message('info', 'Requisition response prepared: ' . json_encode($response));
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $response
        ]);
    }



    public function edit($id)
    {
        $model = new InventoryPurchess();
        $data['requisition'] = $model->find($id);

        return view('purchase/requisition_form', $data);
    }

    public function update()
    {
        log_message('info', 'Updating requisition...');
        $model = new InventoryPurchess();
        $id = $this->request->getPost('id');
        $requisitionId = $this->request->getPost('requisition_id');
        $items = $this->request->getPost('items');
        log_message('info', 'Updating requisition with ID: ' . $id . ' and requisition_id: ' . $requisitionId);
        log_message('info', 'Items data: ' . json_encode($items)); 
        if (!$id || !$items || !is_array($items)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid data for update']);
        }

        // Delete old requisition entries by requisition_id
        // $model->where('requisition_id', $requisitionId)->delete();

        // $updatedItems = $this->request->getPost('items'); // user-submitted items
        $existingItems = $model->select('id')->where('requisition_id', $requisitionId)->findAll();

        $existingItemNames = array_column($existingItems, 'id');
        $updatedItemNames = array_column($items, 'id');

        log_message('info', 'Item names fetched from DB: ' . json_encode($existingItemNames));
        log_message('info', 'Item names from POST: ' . json_encode($updatedItemNames));

        // Delete removed items
        $itemsToDelete = array_diff($existingItemNames, $updatedItemNames);
        foreach ($itemsToDelete as $itemNameToDelete) {
            $model->where('requisition_id', $requisitionId)
                ->where('id', $itemNameToDelete)
                ->delete();
            log_message('info', 'Deleted item: ' . $itemNameToDelete);
        }



        // Insert new updated data
        foreach ($items as $item) {
            $qty = isset($item['qty']) ? floatval($item['qty']) : 0;
            $rate = isset($item['rate']) ? floatval($item['rate']) : 0;
            $total = $qty * $rate;
            $data = [
                'admin_id' => session()->get('user_id'),
                'requisition_id' => $requisitionId,
                'item_count' => count($items),
                'item_name' => $item['item'] ?? null,
                'category' => $item['category_id'] ?? null,
                'subcategory' => $item['subcategory_id'] ?? null,
                'description' => $item['description'] ?? null,
                'qty' => $qty ?? 0,
                'rate' => $rate ?? 0,
                'total' => $total ?? 0,
                'vendor_suggestion' => $item['vendor_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'total_amount' => $this->calculateTotalAmount($items),
                'requisition_date' => $this->request->getPost('requisition_date'),
                'requisition_by' => $this->request->getPost('requisition_by'),
                'deadline' => $this->request->getPost('deadline'),
                'approved_by' => $this->request->getPost('approved_by'),
                'approved_date' => $this->request->getPost('approved_date'),
                'remarks' => $this->request->getPost('remarks'),

            ];
            log_message('info', 'Updating requisition with data: ' . json_encode($data));
            $existing = $model->where('requisition_id', $requisitionId)
                ->where('id', $item['id'])
                ->first();

            log_message('info', 'Existing item found: ' . json_encode($existing));
            if ($existing) {
                // Update the existing row
                $model->where('requisition_id', $requisitionId)
                    ->where('id', $item['id'])
                    ->set($data)
                    ->update();
                log_message('info', 'Item updated: ' . json_encode($data));
            } else {
                // Insert if not found
                $data['requisition_id'] = $requisitionId;
                $data['item_name'] = $item['item'];
                $model->insert($data);
                log_message('info', 'Item inserted: ' . json_encode($data));
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Requisition updated successfully!']);
    }

    // private function calculateTotalAmount($items)
    // {
    //     $total = 0;
    //     foreach ($items as $item) {
    //         $qty = floatval($item['qty'] ?? 0);
    //         $rate = floatval($item['rate'] ?? 0);
    //         $total += $qty * $rate;
    //     }
    //     return $total;
    // }


    public function delete($id)
    {
        log_message('info', 'Deleting requisition with ID: ' . $id);
        $model = new InventoryPurchess();
        $model->delete($id);

        return redirect()->route('purchase.requisition_lists');
    }
}

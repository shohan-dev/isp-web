<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InventoryPurchess;
use App\Models\VendorModel;
use App\Models\UnitModel;
use App\Models\InventoryCategory;
use App\Models\BandwidthSellClient;
use App\Models\BandwidthBuyCategory;
use App\Models\BandwidthSellInvoices;


class bandwidth_sell_controller extends BaseController
{
    public function index()
    {
        $model = new BandwidthSellClient();
        $userId = session()->get('user_id');
        $allClients = $model->where(['admin_id' => $userId])->findAll();

        log_message('info', 'All clients fetched: ' . json_encode($allClients));

        $data = [
            'title' => 'Bandwidth Sell',
            'clients' => $allClients,
        ];

        return view('bandwidth_sell/client', $data);
    }


    public function save()
    {
        $validationRules = [
            // Step 1: Customer Info
            'customer_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Customer\'s name',
                ]
            ],
            'contact_person' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter Contact Person\'s name',
                ]
            ],
            'mobile_number' => [
                'rules' => 'required|numeric',
                'errors' => [
                    'required' => 'Enter Mobile Number',
                    'numeric'  => 'Mobile Number must be numeric',
                ]
            ],
            'pop_status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select POP Status',
                ]
            ],

            // Step 2: Transmission Info
            'activation_date' => [
                'rules' => 'required|valid_date',
                'errors' => [
                    'required'    => 'Enter Activation Date',
                    'valid_date'  => 'Enter a valid date',
                ]
            ],

            // Step 3: Login Info
            'username' => [
                'rules' => 'required|min_length[4]',
                'errors' => [
                    'required'    => 'Enter a Username',
                    'min_length'  => 'Username must be at least 4 characters',
                ]
            ],
            'password' => [
                'rules' => 'required|min_length[4]',
                'errors' => [
                    'required'    => 'Enter a Password',
                    'min_length'  => 'Password must be at least 4 characters',
                ]
            ],
            'confirm_password' => [
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Confirm the password',
                    'matches'  => 'Password and Confirm Password do not match',
                ]
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validator->getErrors()
            ]);
        }



        $request = $this->request;

        $customerModel = new BandwidthSellClient();

        // Process multiple VLANs and IPs
        $vlanNames = $request->getPost('vlan_name') ?? [];
        $vlanIps   = $request->getPost('vlan_ip') ?? [];
        $ipList    = $request->getPost('ip_address') ?? [];

        // Combine VLANs as JSON array
        $vlanInfo = [];
        for ($i = 0; $i < count($vlanNames); $i++) {
            $vlanInfo[] = [
                'name' => $vlanNames[$i] ?? '',
                'ip'   => $vlanIps[$i] ?? ''
            ];
        }
        $userId = session()->get('user_id');
        $data = [
            'customer_name'   => $request->getPost('customer_name'),
            'admin_id'        => $userId,
            'customer_code'   => $request->getPost('customer_code'),
            'contact_person'  => $request->getPost('contact_person'),
            'email'           => $request->getPost('email'),
            'mobile_number'   => $request->getPost('mobile_number'),
            'phone_number'    => $request->getPost('phone_number'),
            'pop_status'      => $request->getPost('pop_status'),
            'reference_by'    => $request->getPost('reference_by'),
            'address'         => $request->getPost('address'),

            'nttn_info'       => $request->getPost('nttn_info'),
            'vlan_info'       => json_encode($vlanInfo), // store as JSON text
            'scr_id'          => $request->getPost('scr_id'),
            'activation_date' => $request->getPost('activation_date'),
            'ip_addresses'    => json_encode($ipList),
            'pop_name'        => $request->getPost('pop_name'),

            'username'        => $request->getPost('username'),
            'password'        => $request->getPost('password'),

            // 'password'        => password_hash($request->getPost('password'), PASSWORD_DEFAULT),
            'activity_status' => $request->getPost('activity_status'),
        ];

        if (!$customerModel->insert($data)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $customerModel->errors()
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Customer saved successfully.'
        ]);
    }

    public function edit($id)
    {
        $model = new BandwidthSellClient();
        $customer = $model->find($id);

        if (!$customer) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Customer not found']);
        }

        return $this->response->setJSON(['status' => 'success', 'customer' => $customer]);
    }

    public function update($id)
    {
        $validationRules = [
            'customer_name' => [
                'rules' => 'required',
                'errors' => ['required' => 'Enter Customer\'s name']
            ],
            'contact_person' => [
                'rules' => 'required',
                'errors' => ['required' => 'Enter Contact Person']
            ],
            'mobile_number' => [
                'rules' => 'required',
                'errors' => ['required' => 'Enter Mobile Number']
            ],
            'pop_status' => [
                'rules' => 'required',
                'errors' => ['required' => 'Select POP Status']
            ],
            'username' => [
                'rules' => 'required',
                'errors' => ['required' => 'Enter Username']
            ],
            'password' => [
                'rules' => 'permit_empty|min_length[4]',
                'errors' => [
                    'min_length' => 'Password must be at least 6 characters'
                ]
            ]
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validator->getErrors()
            ]);
        }
        log_message('info', 'Updating customer with ID: ' . $id);
        $model = new BandwidthSellClient();

        $data = $this->request->getPost();

        // If password is not empty, hash it
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']); // Don't update password if not provided
        }

        try {
            $model->update($id, $data);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Customer updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to update customer: ' . $e->getMessage()
            ]);
        }
    }

    public function purchase_list()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();

        $model = new BandwidthSellInvoices();
        $userId = session()->get('user_id');
        $vendorModel = new BandwidthSellClient();

        // Step 1: Fetch all requisitions for this user
        $allRequisitions = $model->where(['admin_id' => $userId])->findAll();

        // Step 2: Group by requisition_id
        $grouped = [];
        foreach ($allRequisitions as $row) {
            $requisitionId = $row['requisition_id'];
            $vendorName = $vendorModel->where(['id' => $row['vendor_suggestion']])->first()['customer_name'] ?? null;

            if (!isset($grouped[$requisitionId])) {
                // Initialize with the first item's data
                $grouped[$requisitionId] = [
                    'id' => $row['id'],
                    'item_count' => 1,
                    'requisition_id' => $requisitionId,
                    'vendor_suggestion' => $vendorName,
                    'requisition_date' => $row['requisition_date'],
                    'requisition_by' => $row['requisition_by'],
                    'deadline' => $row['deadline'],
                    'approved_by' => $row['approved_by'],
                    'approved_date' => $row['approved_date'],
                    'item_names' => [$row['item_name']],
                    // IMPORTANT: Take the total_amount from the first item only
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
                // DO NOT add total_amount again - it's the same for all items
            }
        }

        // Step 3: Re-index grouped values
        $requisitions = array_values($grouped);

        // Step 4: Load other models
        $VendorModel = new BandwidthSellClient();
        $unitController = new UnitModel();
        $providers = $VendorModel->where(['admin_id' => $userId])->findAll();
        // log_message('info', 'Providers fetched: ' . json_encode($providers));
        $unit = $unitController->where(['admin_id' => $userId])->findAll();

        $inventoryCategoryModel = new BandwidthBuyCategory();
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
        log_message('info', 'All items processed: ' . json_encode($allItems));
        log_message('info', 'All items requisitions......: ' . json_encode($requisitions));

        return view('bandwidth_sell/purchase_list', [
            'title' => 'Sells invoices',
            'units' => $unit,
            'items' => $allItems,
            'requisitions' => $requisitions, // ✅ Grouped & processed
            'vendors' => $providers
        ]);
    }

    public function create()
    {
        $model = new BandwidthSellInvoices();

        $items = $this->request->getPost('items');
        log_message('info', 'Creating requisition with items: ' . json_encode($items));
        $requisitionId = strtoupper(secure_random_string(10)); // Use your custom ID logic

        if (!$items || !is_array($items)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid items data']);
        }

        // log_message('info', 'Requisition vendor_id generated: ' . $this->request->getPost('vendor_id'));

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
                'vendor_suggestion' =>  $this->request->getPost('vendor_id') ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'total_amount' => $this->calculateTotalAmount($items),
                'received_amount' => $this->request->getPost('received_amount') ?? 0,
                'due' => $this->request->getPost('due') ?? 0,
                'status' => $this->request->getPost('status') ?? 'due',
                'requisition_date' => $this->request->getPost('requisition_date'),
                'requisition_by' => $this->request->getPost('requisition_by'),
                'deadline' => $this->request->getPost('deadline'),
                'approved_by' => $this->request->getPost('approved_by'),
                'from_date' => $item['from_date'] ?? null,
                'to_date' => $item['to_date'] ?? null,
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

        $model = new BandwidthSellInvoices();

        // Fetch all rows related to this requisition_id
        $requisitionRows = $model->where('requisition_id', $id)->findAll();
        log_message('info', 'Requisition fetched: ' . json_encode($requisitionRows));

        if (empty($requisitionRows)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Requisition not found'
            ]);
        }

        // Use the first row to get common data
        $firstRow = $requisitionRows[0];

        // Calculate total from individual item totals (qty * rate)
        $totalFromItems = 0;
        foreach ($requisitionRows as $row) {
            $totalFromItems += floatval($row['total'] ?? 0);
        }

        log_message('info', 'Total from items (qty * rate): ' . $totalFromItems);
        log_message('info', 'Stored total_amount from DB: ' . ($firstRow['total_amount'] ?? 0));

        $response = [
            'id' => $firstRow['id'],
            'vendor_id' => $firstRow['vendor_suggestion'],
            'requisition_id' => $firstRow['requisition_id'],
            'requisition_date' => $firstRow['requisition_date'],
            'deadline' => $firstRow['deadline'],
            'remarks' => $firstRow['remarks'],
            'total' => $totalFromItems, // Use calculated total from items
            'received_amount' => $firstRow['received_amount'] ?? 0,
            'due' => $firstRow['due'] ?? 0,
            'status' => $firstRow['status'] ?? 'pending',
            'payment_date' => $firstRow['payment_date'] ?? null,
            'payment_method' => $firstRow['payment_method'] ?? null,
            'paid_by' => $firstRow['paid_by'] ?? null,
            'received_by' => $firstRow['received_by'] ?? null,
            'items' => [],
        ];

        foreach ($requisitionRows as $row) {
            $response['items'][] = [
                'id' => $row['id'],
                'item' => $row['item_name'],
                'description' => $row['description'],
                'quantity' => $row['qty'],
                'rate' => $row['rate'],
                'unit' => $row['unit_id'] ?? '',
                'from_date' => $row['from_date'] ?? null,
                'to_date' => $row['to_date'] ?? null,
            ];
        }

        log_message('info', 'Requisition response prepared with total: ' . $response['total']);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $response
        ]);
    }



    public function bandwidth_selledit($id)
    {
        $model = new BandwidthSellInvoices();
        $data['requisition'] = $model->find($id);

        return view('purchase/requisition_form', $data);
    }

    public function bandwidth_sellupdate()
    {
        log_message('info', 'Updating requisition...');
        $model = new BandwidthSellInvoices();

        $requisitionId = $this->request->getPost('requisition_id');
        $items = $this->request->getPost('items');
        $vendorId = $this->request->getPost('vendor_id');

        log_message('info', 'Updating requisition with requisition_id: ' . $requisitionId);
        log_message('info', 'Vendor ID: ' . $vendorId);
        log_message('info', 'Items data: ' . json_encode($items));

        if (!$requisitionId || !$items || !is_array($items)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid data for update']);
        }

        // Get the first existing item to preserve payment data
        $existingRequisition = $model->where('requisition_id', $requisitionId)->first();

        // Calculate the NEW total amount from submitted items
        $newTotalAmount = $this->calculateTotalAmount($items);
        log_message('info', 'New calculated total amount: ' . $newTotalAmount);

        // Preserve existing payment data
        $received_amount = $existingRequisition['received_amount'] ?? 0;

        // Calculate new due amount
        $newDue = $newTotalAmount - $received_amount;
        log_message('info', 'New due amount: ' . $newDue);

        // Get existing items for this requisition
        $existingItems = $model->where('requisition_id', $requisitionId)->findAll();
        $existingItemIds = array_column($existingItems, 'id');

        // Get IDs from submitted items (only those that have an ID - existing items)
        $submittedItemIds = [];
        foreach ($items as $item) {
            if (!empty($item['id'])) {
                $submittedItemIds[] = $item['id'];
            }
        }

        log_message('info', 'Existing item IDs: ' . json_encode($existingItemIds));
        log_message('info', 'Submitted item IDs: ' . json_encode($submittedItemIds));

        // Delete items that are in existing but not in submitted
        $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
        if (!empty($itemsToDelete)) {
            $model->where('requisition_id', $requisitionId)
                ->whereIn('id', $itemsToDelete)
                ->delete();
            log_message('info', 'Deleted items: ' . json_encode($itemsToDelete));
        }

        // Process each submitted item
        foreach ($items as $item) {
            $qty = isset($item['qty']) ? floatval($item['qty']) : 0;
            $rate = isset($item['rate']) ? floatval($item['rate']) : 0;
            $total = $qty * $rate;

            // Prepare data for this item
            $data = [
                'admin_id' => session()->get('user_id'),
                'requisition_id' => $requisitionId,
                'item_name' => $item['item'] ?? null,
                'category' => $item['category_id'] ?? null,
                'subcategory' => $item['subcategory_id'] ?? null,
                'description' => $item['description'] ?? null,
                'qty' => $qty,
                'rate' => $rate,
                'total' => $total,
                'vendor_suggestion' => $vendorId,
                'unit_id' => $item['unit_id'] ?? null,
                // Use the NEW total amount for ALL items
                'total_amount' => $newTotalAmount,
                'requisition_date' => $this->request->getPost('requisition_date'),
                'deadline' => $this->request->getPost('deadline'),
                'from_date' => $item['from_date'] ?? null,
                'to_date' => $item['to_date'] ?? null,
                'remarks' => $this->request->getPost('remarks'),
                // Preserve payment data with new due calculation
                'received_amount' => $received_amount,
                'due' => $newDue,
            ];

            // Check if this is an existing item (has ID) or new item (no ID)
            if (!empty($item['id'])) {
                // Update existing item
                $model->where('requisition_id', $requisitionId)
                    ->where('id', $item['id'])
                    ->set($data)
                    ->update();
                log_message('info', 'Updated item ID: ' . $item['id'] . ' with total_amount: ' . $newTotalAmount);
            } else {
                // Insert new item
                $data['item_count'] = count($items); // This will be updated later
                $model->insert($data);
                log_message('info', 'Inserted new item with total_amount: ' . $newTotalAmount);
            }
        }

        // Update ALL items with the correct item_count and ensure total_amount is consistent
        $updatedItems = $model->where('requisition_id', $requisitionId)->findAll();
        $itemCount = count($updatedItems);

        // Final update to ensure all rows have consistent data
        $model->where('requisition_id', $requisitionId)
            ->set([
                'item_count' => $itemCount,
                'total_amount' => $newTotalAmount,
                'due' => $newDue,
                'received_amount' => $received_amount
            ])
            ->update();

        log_message('info', 'Final update - All items set with total_amount: ' . $newTotalAmount . ', item_count: ' . $itemCount . ', due: ' . $newDue);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Requisition updated successfully!',
            'data' => [
                'total_amount' => $newTotalAmount,
                'received_amount' => $received_amount,
                'due' => $newDue,
                'item_count' => $itemCount
            ]
        ]);
    }

    public function bandwidth_sell_payment_update()
    {
        log_message('info', 'Updating payment for requisition...');
        $model = new BandwidthSellInvoices();

        $requisitionId = $this->request->getPost('requisition_id');

        log_message('info', 'Updating payment for requisition_id: ' . $requisitionId);

        if (!$requisitionId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing requisition ID'
            ]);
        }

        // Get current requisition data to calculate proper due
        $existingItems = $model->where('requisition_id', $requisitionId)->findAll();

        if (empty($existingItems)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Requisition not found'
            ]);
        }

        // Calculate total amount from all items
        $totalAmount = array_sum(array_column($existingItems, 'total'));

        // Get payment data
        $receivedAmount = $this->request->getPost('received_amount') ?? 0;
        $discount = $this->request->getPost('discount') ?? 0;
        $vat = $this->request->getPost('vat_amount') ?? 0;

        // Calculate due: total - received + vat - discount
        $due = $totalAmount - $receivedAmount + $vat - $discount;

        $data = [
            'received_amount' => $receivedAmount,
            'due' => $due,
            'payment_date' => $this->request->getPost('payment_date') ?? date('Y-m-d'),
            'payment_method' => $this->request->getPost('payment_method') ?? null,
            'remarks' => $this->request->getPost('description') ?? '',
            'discount' => $discount,
            'paid_by' => $this->request->getPost('paid_by') ?? null,
            'received_by' => $this->request->getPost('received_by') ?? null,
        ];

        log_message('info', 'Updating payment with data: ' . json_encode($data));
        log_message('info', 'Total amount from items: ' . $totalAmount);

        // Update ALL rows with this requisition_id
        $updated = $model->where('requisition_id', $requisitionId)
            ->set($data)
            ->update();

        log_message('info', 'Number of rows updated: ' . $updated);

        if ($updated) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Payment updated successfully!',
                'data' => [
                    'total_amount' => $totalAmount,
                    'received_amount' => $receivedAmount,
                    'due' => $due
                ]
            ]);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No records were updated. Please check if the requisition ID exists.'
            ]);
        }
    }

    // }
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

    public function purchase_list_invoice()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();

        $model = new BandwidthSellInvoices();
        $userId = session()->get('user_id');
        $vendorModel = new BandwidthSellClient();

        // Step 1: Fetch all requisitions for this user
        $allRequisitions = $model->where(['admin_id' => $userId])->findAll();

        // Step 2: Group by requisition_id
        $grouped = [];
        foreach ($allRequisitions as $row) {
            $requisitionId = $row['requisition_id'];
            $vendorName = $vendorModel->where(['id' => $row['vendor_suggestion']])->first()['customer_name'] ?? null;

            if (!isset($grouped[$requisitionId])) {
                // Initialize with the first item's data
                $grouped[$requisitionId] = [
                    'id' => $row['id'],
                    'item_count' => 1,
                    'requisition_id' => $requisitionId,
                    'vendor_suggestion' => $vendorName,
                    'requisition_date' => $row['requisition_date'],
                    'requisition_by' => $row['requisition_by'],
                    'deadline' => $row['deadline'],
                    'approved_by' => $row['approved_by'],
                    'approved_date' => $row['approved_date'],
                    'item_names' => [$row['item_name']],
                    // IMPORTANT: Take the total_amount from the first item only
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
                // DO NOT add total_amount again - it's the same for all items
            }
        }

        // Step 3: Re-index grouped values
        $requisitions = array_values($grouped);

        // Step 4: Calculate totals from individual items for verification
        foreach ($requisitions as &$req) {
            // Get all items for this requisition to calculate correct total from individual items
            $items = $model->where('requisition_id', $req['requisition_id'])->findAll();
            $calculatedTotal = 0;
            foreach ($items as $item) {
                $calculatedTotal += (float)($item['total'] ?? 0);
            }

            // Log for debugging
            log_message('info', 'Requisition ' . $req['requisition_id'] .
                ' - Stored total: ' . $req['total_amount'] .
                ', Calculated total: ' . $calculatedTotal);

            // Use the calculated total if it's different (optional)
            // $req['total_amount'] = $calculatedTotal;
        }

        // Step 5: Load other models for form data
        $VendorModel = new BandwidthSellClient();
        $unitController = new UnitModel();
        $providers = $VendorModel->where(['admin_id' => $userId])->findAll();
        $unit = $unitController->where(['admin_id' => $userId])->findAll();

        $inventoryCategoryModel = new BandwidthBuyCategory();
        $categories = $inventoryCategoryModel
            ->select('*')
            ->where('admin_id', $userId)
            ->orderBy('id', 'asc')
            ->findAll();

        $allItems = [];
        foreach ($categories as $category) {
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

        // Add this line before return view
        $data['allInvoiceItems'] = $allRequisitions;

        return view('bandwidth_sell/invoice', [
            'title' => 'Sells invoices',
            'units' => $unit,
            'items' => $allItems,
            'requisitions' => $requisitions,
            'vendors' => $providers,
            'allInvoiceItems' => $allRequisitions  // Add this line
        ]);
    }
    public function filterInvoices()
    {
        // Check if user has the required permission
        if (!userHasPermission('inventory_purchess', 'read')) {
            show_404();
        }

        // Instantiate the model
        $model = new BandwidthSellInvoices();

        // Log the filtering operation
        log_message('info', 'Filtering invoices by date range');

        // Retrieve date range from POST request
        $fromDate = $this->request->getPost('fromDate');
        $toDate = $this->request->getPost('toDate');

        // Build the query to filter by date range
        // $builder = $model->builder()
        //     ->select('*')
        //     ->where('requisition_date >=', $fromDate)
        //     ->where('requisition_date <=', $toDate);
        $builder = $model->builder()
            ->select('bandwidth_sell_invoices.*, bandwidth_sell_client.customer_name as vendor_suggestion_name')
            ->join('bandwidth_sell_client', 'bandwidth_sell_client.id = bandwidth_sell_invoices.vendor_suggestion', 'left')
            ->where('bandwidth_sell_invoices.requisition_date >=', $fromDate)
            ->where('bandwidth_sell_invoices.requisition_date <=', $toDate);



        // Execute the query and get results
        $requisitions = $builder->get()->getResultArray();
        log_message('info', 'Filtered requisitions: ' . json_encode($requisitions));
        // Calculate the total amount from all results
        $totalAmount = array_sum(array_column($requisitions, 'total_amount'));

        // Return JSON response
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $requisitions,
            'totalAmount' => number_format($totalAmount, 2)
        ]);
    }
    public function dailyindex()
    {
        if (!userHasPermission('inventory_purchess', 'read')) {
            show_404();
        }
        $model = new BandwidthSellClient();
        $userId = session()->get('user_id');
        $allClients = $model->where(['admin_id' => $userId])->findAll();


        $data = [
            'clients' => $allClients,
            'title' => 'Bandwidth Sell',
        ];

        return view('bandwidth_sell/bandwidth_daily_bill', $data);
    }

    public function getDailyBillData()
    {
        $model = new BandwidthSellInvoices();
        $filters = $this->request->getGet();
        $userId  = session()->get('user_id');

        $builder = $model->builder()
            ->select("
                bandwidth_sell_invoices.*, 
                bandwidth_sell_client.customer_name AS vendor_suggestion_name,
                bandwidth_sell_client.mobile_number AS vendor_suggestion_mobile,
                bandwidth_sell_client.contact_person AS vendor_suggestion_contact_person,
                bandwidth_sell_client.phone_number AS vendor_suggestion_phone_number,
            ")
            ->join('bandwidth_sell_client', 'bandwidth_sell_client.id = bandwidth_sell_invoices.vendor_suggestion', 'left')
            ->where('bandwidth_sell_invoices.admin_id', $userId);
        // ->where('bandwidth_sell_invoices.daily_bill', 1);

        // Apply filters
        if (!empty($filters['pop'])) {
            $builder->where('bandwidth_sell_invoices.vendor_suggestion', $filters['pop']);
        }
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            log_message('info', 'Filtering by date range: ' . $filters['from_date'] . ' to ' . $filters['to_date']);
            $builder->where("bandwidth_sell_invoices.requisition_date >=", $filters['from_date']);
            $builder->where("bandwidth_sell_invoices.requisition_date <=", $filters['to_date']);
        }
        if (!empty($filters['received_by'])) {
            $builder->where('bandwidth_sell_invoices.received_by', $filters['received_by']);
        }
        if (!empty($filters['created_by'])) {
            $builder->where('bandwidth_sell_invoices.created_by', $filters['created_by']);
        }
        if (!empty($filters['status'])) {
            $builder->where('bandwidth_sell_invoices.status', $filters['status']);
        }
        $allRows = $builder->get()->getResultArray();

        $grouped = [];
        foreach ($allRows as $row) {
            $id = $row['requisition_id'];

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['id'],
                    'requisition_id' => $id,
                    'vendor_suggestion_contact_person' => $row['vendor_suggestion_contact_person'] ?? null,
                    'vendor_suggestion_phone_number' => $row['vendor_suggestion_phone_number'] ?? null,
                    'vendor_suggestion_mobile' => $row['vendor_suggestion_mobile'] ?? null,
                    'vendor_suggestion_name' => $row['vendor_suggestion_name'] ?? null,
                    'requisition_date' => $row['requisition_date'],
                    'requisition_by' => $row['requisition_by'] ?? null,
                    'deadline' => $row['deadline'] ?? null,
                    'approved_by' => $row['approved_by'] ?? null,
                    'approved_date' => $row['approved_date'] ?? null,
                    'item_names' => [],
                    'item_count' => 0,
                    'total_amount' => $row['total_amount'] ?? 0,
                    'received_amount' => $row['received_amount'] ?? 0,
                    'due' => $row['due'] ?? 0,
                    'remarks' => $row['remarks'] ?? '',
                    'status' => $row['status'] ?? 'pending',
                    'payment_date' => $row['payment_date'] ?? null,
                    'payment_method' => $row['payment_method'] ?? null,
                    'paid_by' => $row['paid_by'] ?? null,
                    'received_by' => $row['received_by'] ?? null,
                ];
            }

            $grouped[$id]['item_count']++;
            $grouped[$id]['item_names'][] = $row['item_name'] ?? '';
            // $grouped[$id]['total_amount'] += (float) ($row['total_amount'] ?? 0);
        }

        // Convert grouped to indexed array for JSON response
        $groupedData = array_values($grouped);

        log_message('info', 'Daily bill data fetched: ' . json_encode($groupedData));
        return $this->response->setJSON($groupedData);
    }

    public function bandwidth_selldelete($id)
    {
        log_message('info', 'Deleting requisition with ID: ' . $id);

        $model = new BandwidthSellInvoices();

        // Delete ALL rows with this requisition_id
        $deleted = $model->where('requisition_id', $id)->delete();

        return redirect()->route('bandwidth.sell.purchase_list');
    }
}

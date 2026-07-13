<?php

namespace App\Controllers;


use App\Models\ProviderModel;
use App\Models\PurchaseBillModel;
use App\Models\InventoryModel;
use App\Models\InventoryCategory;
use App\Models\UnitModel;


use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;
use CodeIgniter\Controller;

class InventoryController extends Controller
{
    protected $bandwidthModel, $BandwidthBuyCategory, $providerModel, $puchessModel;

    public function __construct()
    {
        // Load the BandwidthModel
        $this->bandwidthModel = new InventoryModel();
        $this->BandwidthBuyCategory = new InventoryCategory();
        $this->providerModel = new ProviderModel();
        $this->puchessModel = new PurchaseBillModel();
        $request = \Config\Services::request();
    }

    // Method to list all inventory items
    public function index()
    {
        /* Same defect as BandwidthController::index(): inventory/index.php is an
           unmodified copy of the reseller list (its DataTable loads from
           route.Reseller.fetch and "Delete Item" posts to route.reseller.delete)
           and it ignores the $bandwidths passed here. This method is currently
           unrouted, but leave no landmine for whoever routes it next — send it
           to the real inventory item list, which enforces its own permission. */
        return redirect()->route('inventory.item_index');
    }
    public function category_index()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();
        $data = [
            'title' => 'Inventory Categories',
        ];
        $data['parent_categories'] = $this->BandwidthBuyCategory->getAllBandwidthPackages();
        return view('inventory/item_category', $data); // Create a corresponding view
    }
    public function item_index()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();
        $data = [
            'title' => 'Inventory Items',
        ];
        $data['parent_categories'] = $this->BandwidthBuyCategory->getAllBandwidthPackages();
        return view('inventory/item', $data); // Create a corresponding view
    }
    public function purchess_stock()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();
        $data = [
            'title' => 'Purchess stock',
        ];
        $data['parent_categories'] = $this->BandwidthBuyCategory->getAllBandwidthPackages();

        $model = new \App\Models\InventoryPurchess();
        $unitsModel = new UnitModel();
        $adminId = session('user_id'); // Get current admin ID from session

        $results = $model
            ->where('admin_id', $adminId) // Filter by current admin

            ->findAll();
        $units= $unitsModel->where('admin_id', $adminId)->findAll();
        log_message('info', 'Units fetched: ' . json_encode($units));
        // log_message('info', 'Purchess stock results: ' . json_encode($results));
        $itemSummary = [];

        foreach ($results as $row) {
            $itemName = trim($row['item_name']);
            $category = trim($row['category']);
            $subcategory = trim($row['subcategory']);

            // Skip if explicitly "null"
            if ($category === 'null' || $subcategory === 'null') {
                continue;
            }

            // Treat empty as 0
            $category = $category === '' ? '0' : $category;
            $subcategory = $subcategory === '' ? '0' : $subcategory;

            // Create a composite key
            $key = "{$itemName}|{$category}|{$subcategory}";

            // Accumulate totals
            if (!isset($itemSummary[$key])) {
                $itemSummary[$key] = [
                    'item_name' => $itemName,
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'unit' => $row['unit_id'] ?? null, // Use unit_id if available
                    'total_qty' => (int)$row['qty']
                ];
            } else {
                $itemSummary[$key]['total_qty'] += (int)$row['qty'];
            }
        }

        // Reindex array (optional)
        $itemSummary = array_values($itemSummary);

        // Example: log or return
        log_message('info', 'Item summary: ' . json_encode($itemSummary));
        $data['units'] = $units; // Add units to the data array
        $data['purchess_stock'] = $itemSummary; // Pass the summary

        return view('inventory/purchess_stock', $data); // Create a corresponding view
    }
    public function purchess()
    {
        if (!userHasPermission('inventory_purchess', 'read'))
            show_404();
        $userId = session()->get('user_id');

        // Fetch reseller data

        $providers = $this->providerModel->where(['admin_id' => $userId])->findAll();
        // log_message('info', 'Providers fetched: ' . json_encode($providers));
        $purchess = $this->puchessModel->where(['admin_id' => $userId])->findAll();


        $categories = $this->BandwidthBuyCategory
            ->select('*')
            ->where('admin_id', $userId)
            ->orderBy('id', 'asc')
            ->findAll();

        $allItems = [];

        foreach ($categories as $category) {
            // 1. Add category-level items first
            $categoryItems = json_decode($category['items'], true) ?: [];
            foreach ($categoryItems as $item) {
                // Keep the item as-is (assumes it already has 'category_id')
                $item['subcategory_id'] = null; // Explicitly mark as no subcategory
                $allItems[] = $item;
            }

            // 2. Add subcategory-level items
            $subcategoryItems = json_decode($category['subcategory_items'], true) ?: [];
            foreach ($subcategoryItems as $index => $subcategory) {
                if (isset($subcategory['items']) && is_array($subcategory['items'])) {
                    foreach ($subcategory['items'] as $item) {
                        // Append subcategory index as subcategory_id
                        $item['subcategory_id'] = (string) $index;
                        $allItems[] = $item;
                    }
                }
            }
        }

        // log_message('info', 'All items fetched: ' . json_encode($allItems));


        // Prepare the data array for the view
        $data = [
            'title' => 'Purchess Bill',
            'purchess' => $purchess,
            'items' => $allItems,
            'providers' => $providers // Add reseller data to the array
        ];
        return view('inventory/purchess', $data); // Create a corresponding view
    }

    public function getSubcategories()
    {
        $request = \Config\Services::request();
        // Only allow AJAX requests
        if (!$request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Forbidden'
            ]);
        }

        $categoryId = $request->getGet('category_id');
        if (empty($categoryId)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Category ID is required'
            ]);
        }

        try {
            // Fetch the category by ID
            $category = $this->BandwidthBuyCategory->where(['id' => $categoryId])->first();

            if (empty($category)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Category not found'
                ]);
            }

            // Decode subcategory_items JSON
            $subcategories = json_decode($category['subcategory_items'], true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($subcategories)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid subcategory JSON format'
                ]);
            }

            // log_message('info', 'Subcategories fetched: ' . json_encode($subcategories));

            return $this->response->setJSON([
                'status' => 'success',
                'subcategories' => $subcategories
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch subcategories: ' . $e->getMessage()
            ]);
        }
    }


    // Method to show a specific Inventory package by ID
    public function show($id)
    {
        // $BandwidthBuyCategory = new $this->BandwidthBuyCategory();

        $data['bandwidth'] = $this->bandwidthModel->getBandwidthPackageById($id);
        return view('bandwidth/show', $data); // Create a corresponding view
    }

    // Method to create a new bandwidth package
    public function create()
    {
        return view('bandwidth/create'); // Create a corresponding view with a form
    }

    public function item_update()
    {
        $request = \Config\Services::request();

        // Get all parameters with proper null checks
        $itemIndex = $request->getPost('item_index');
        $subcategoryIndex = $request->getPost('subcategory_index');
        $subcategoryName = $request->getPost('subcategory_name');
        $categoryId = $request->getPost('category');
        $subcategoryId = $request->getPost('subcategory') ?: null;

        // Debug logging
        log_message('info', 'Update request received:', [
            'item_index' => $itemIndex,
            'subcategory_index' => $subcategoryIndex,
            'subcategory_name' => $subcategoryName,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId
        ]);

        // Prepare update data
        $updateData = [
            'item_name' => $request->getPost('item_name'),
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'unit' => $request->getPost('unit'),
            'status' => $request->getPost('status'),
            'vat' => $request->getPost('vat'),
            'income_account' => $request->getPost('income_account'),
            'expense_account' => $request->getPost('expense_account'),
            'description' => $request->getPost('description'),
            'area' => $request->getPost('area'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Get the category
        $category = $this->BandwidthBuyCategory->find($categoryId);
        if (!$category) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Category not found'
            ]);
        }

        // Handle subcategory items
        if ($subcategoryName !== null && $subcategoryIndex !== null) {
            $subcategoryItems = json_decode($category['subcategory_items'], true) ?: [];

            // Validate subcategory index
            if (!isset($subcategoryItems[$subcategoryIndex])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid subcategory index'
                ]);
            }

            // Validate subcategory name match
            if ($subcategoryItems[$subcategoryIndex]['subcategory_name'] !== $subcategoryName) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Subcategory name mismatch'
                ]);
            }
            // log_message('info', 'Subcategory items before update: ' . json_encode($subcategoryItems));

            // Handle item update
            if ($itemIndex !== null) {
                // Update existing item
                if (!isset($subcategoryItems[$subcategoryIndex]['items'][$itemIndex])) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Item not found in subcategory'
                    ]);
                }

                $subcategoryItems[$subcategoryIndex]['items'][$itemIndex] = array_merge(
                    $subcategoryItems[$subcategoryIndex]['items'][$itemIndex],
                    $updateData
                );
            } else {
                // Add new item
                $subcategoryItems[$subcategoryIndex]['items'][] = $updateData;
            }
            // log_message('info', 'Subcategory items after update: ' . json_encode($subcategoryItems));
            // Save updates
            $this->BandwidthBuyCategory->update($categoryId, [
                'subcategory_items' => json_encode($subcategoryItems)
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Subcategory item updated successfully'
            ]);
        }
        // Handle main category items
        else {
            $categoryItems = json_decode($category['items'], true) ?: [];
            log_message('info', 'Subcategory items before update: ' . json_encode($categoryItems));

            if ($itemIndex !== null) {
                // Update existing item
                if (!isset($categoryItems[$itemIndex])) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Item not found in category'
                    ]);
                }

                $categoryItems[$itemIndex] = array_merge(
                    $categoryItems[$itemIndex],
                    $updateData
                );
            } else {
                // Add new item
                $categoryItems[] = $updateData;
            }
            log_message('info', 'Subcategory items before update: ' . json_encode($categoryItems));

            // Save updates
            $this->BandwidthBuyCategory->update($categoryId, [
                'items' => json_encode($categoryItems)
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Category item updated successfully'
            ]);
        }
    }


    public function item_delete()
    {

        $request = service('request');

        // log_message('info', 'Delete request received.');
        // log_message('info', 'Raw POST Data: ' . json_encode($request->getPost()));

        // Only allow AJAX requests
        if (!$request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request type'
            ]);
        }

        // Get POST data as array
        $data = $request->getPost();

        // Log the full array
        log_message('info', 'Parsed POST Data Array: ' . print_r($data, true));

        // Access values as array
        $categoryId = $data['category_id'] ?? null;
        $itemIndex = $data['item_index'] ?? null;
        $subcategoryIndex = $data['subcategory_index'] ?? null;
        $subcategoryName = $data['subcategory_name'] ?? null;
        $itemType = $data['item_type'] ?? null;

        if (!$categoryId || $itemIndex === null) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing category or item index'
            ]);
        }

        // Debug log all received data
        log_message('info', 'Delete request: ' . json_encode([
            'type' => $itemType,
            'category_id' => $categoryId,
            'item_index' => $itemIndex,
            'subcategory_index' => $subcategoryIndex,
            'subcategory_name' => $subcategoryName
        ]));


        // Get the category
        $category = $this->BandwidthBuyCategory->find($categoryId);
        if (!$category) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Category not found'
            ]);
        }

        // Handle subcategory items deletion
        if ($subcategoryName !== null && $subcategoryIndex !== null) {
            $subcategoryItems = json_decode($category['subcategory_items'], true) ?: [];

            // Validate subcategory index
            if (!isset($subcategoryItems[$subcategoryIndex])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid subcategory index'
                ]);
            }

            // Validate subcategory name match
            if ($subcategoryItems[$subcategoryIndex]['subcategory_name'] !== $subcategoryName) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Subcategory name mismatch'
                ]);
            }

            // Handle item deletion
            if ($itemIndex !== null && isset($subcategoryItems[$subcategoryIndex]['items'][$itemIndex])) {
                // Remove the item from the array
                array_splice($subcategoryItems[$subcategoryIndex]['items'], $itemIndex, 1);

                // Save updates
                $this->BandwidthBuyCategory->update($categoryId, [
                    'subcategory_items' => json_encode($subcategoryItems)
                ]);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Subcategory item deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Item not found in subcategory'
            ]);
        }
        // Handle main category items deletion
        else if ($itemIndex !== null) {
            $categoryItems = json_decode($category['items'], true) ?: [];

            if (isset($categoryItems[$itemIndex])) {
                // Remove the item from the array
                array_splice($categoryItems, $itemIndex, 1);

                // Save updates
                $this->BandwidthBuyCategory->update($categoryId, [
                    'items' => json_encode($categoryItems)
                ]);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Category item deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Item not found in category'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Invalid deletion request'
        ]);
    }


    // Method to store the new bandwidth package
    public function item_category_store()
    {
        $request = \Config\Services::request(); // Use this to avoid CLIRequest type issue

        $model = new $this->BandwidthBuyCategory();

        $sub_category_of = $request->getPost('sub_category_of') ?: null;
        $admin_id = session()->get('user_id');

        $data = [
            'admin_id' => $admin_id,
            'item_category_name' => $request->getPost('item_category_name'),
            'item_category_status' => $request->getPost('item_category_status'),
            'short_description' => $request->getPost('short_description'),
            'sub_category_of' => $sub_category_of ?: null,
        ];

        // Common item handling
        $items = $request->getPost('items');
        if (is_string($items)) {
            $items = json_decode($items, true) ?? [];
        } else {
            $items = $items ?: [];
        }
        // if (!is_array($items)) {
        //     $items = json_decode($items, true) ?: [];
        // }

        if (!empty($sub_category_of)) {
            // log_message('info', 'Sub-category detected: ' . $sub_category_of);

            // This is a subcategory, so prepare subcategory item
            $subcategory_item = [
                'subcategory_name' => $data['item_category_name'],
                'item_category_status' => $data['item_category_status'],
                'short_description' => $data['short_description'],
                'items' => array_values($items)
            ];

            // Fetch the existing main category
            $existing = $model->find($sub_category_of);
            // log_message('info', 'Existing main category: ' . json_encode($existing));
            $existing_subcategories = [];

            if (!empty($existing['subcategory_items'])) {
                $decoded = json_decode($existing['subcategory_items'], true);
                // Make sure it's an array of subcategories
                if (isset($decoded[0])) {
                    $existing_subcategories = $decoded;
                } else {
                    $existing_subcategories[] = $decoded;
                }
            }

            // Append new subcategory
            $existing_subcategories[] = $subcategory_item;

            $sub_data = [
                'sub_category_of' => $sub_category_of,
                'subcategory_items' => json_encode($existing_subcategories)
            ];

            // log_message('info', 'Updated subcategory_items array: ' . json_encode($sub_data));

            if ($model->update($sub_category_of, $sub_data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Sub-category added under main category.',
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to add sub-category.'
                ]);
            }
        } else {
            // This is a main category, store items directly
            $data['items'] = json_encode(array_values($items));
        }


        if ($model->insert($data)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Category added successfully.',
            ]);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to add category.'
            ]);
        }
    }



    /**
     * Save new item
     */
    public function item_store()
    {
        $request = \Config\Services::request();
        // Validate the request
        if (
            !$this->validate([
                'item_name' => 'required|min_length[3]|max_length[255]',
                'category' => 'required|numeric',
                'status' => 'required|in_list[active,inactive]',
                // 'vat' => 'permit_empty|decimal',
                'unit' => 'permit_empty|max_length[50]',
                // 'income_account' => 'permit_empty|max_length[255]',
                // 'expense_account' => 'permit_empty|max_length[255]',
                // 'description' => 'permit_empty|max_length[500]'
            ])
        ) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ]);
        }

        try {
            $categoryId = $request->getPost('category');
            $subcategoryId = $request->getPost('subcategory') ?: null;
            // Prepare item data
            $itemData = [
                'item_name' => $request->getPost('item_name'),
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId ?: null,
                'unit' => $request->getPost('unit'),
                'status' => $request->getPost('status'),
                'vat' => $request->getPost('vat'),
                'income_account' => $request->getPost('income_account'),
                'expense_account' => $request->getPost('expense_account'),
                'description' => $request->getPost('description'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            log_message('info', 'Item data: ' . json_encode($itemData));

            $category = $this->BandwidthBuyCategory->where(['id' => $categoryId])->first();

            log_message('info', 'Existing main category: ' . json_encode($category));
            if (!empty($subcategoryId)) {
                // Fetch the main category
                $category = $this->BandwidthBuyCategory->where(['id' => $categoryId])->first();

                log_message('info', 'Existing main category: ' . json_encode($category));

                // Decode subcategory_items JSON
                $subcategoryItems = [];
                if (!empty($category['subcategory_items'])) {
                    $subcategoryItems = json_decode($category['subcategory_items'], true);

                    if (!is_array($subcategoryItems)) {
                        log_message('error', 'Subcategory items not in valid JSON format.');
                        $subcategoryItems = [];
                    }
                }

                // // Prepare new item
                // $newItem = [
                //     'name' => $itemData['item_name'],
                //     'price' => $request->getPost('price'),
                //     'area' => $request->getPost('area')
                // ];

                // Find the matching subcategory by `subcategory_name`
                foreach ($subcategoryItems as &$subcat) {
                    if ($subcat['subcategory_name'] === $subcategoryId) {
                        // Append to items
                        if (!isset($subcat['items']) || !is_array($subcat['items'])) {
                            $subcat['items'] = [];
                        }

                        $subcat['items'][] = $itemData;
                        break;
                    }
                }

                // Save the updated subcategory_items back
                $result = $this->BandwidthBuyCategory->update($categoryId, [
                    'subcategory_items' => json_encode($subcategoryItems)
                ]);

                log_message('info', 'Updated subcategory items: ' . json_encode($subcategoryItems));
            } else {
                // Main category - Add item to `items` field


                // Decode existing items
                $existingItems = [];
                if (!empty($category['items'])) {
                    $existingItems = json_decode($category['items'], true);

                    if (!is_array($existingItems)) {
                        log_message('error', 'Existing items not in valid JSON format.');
                        $existingItems = [];
                    }
                }

                // Append the new item
                // $newItem = [
                //     'name' => $itemData['item_name'],
                //     'price' => $request->getPost('price'),  // Add this field if you have it in your form
                //     'area' => $request->getPost('area')    // Add this field if required
                // ];

                $existingItems[] = $itemData;

                // Save back to database
                $result = $this->BandwidthBuyCategory->update($categoryId, [
                    'items' => json_encode($existingItems)
                ]);

                log_message('info', 'Updated category items: ' . json_encode($existingItems));
            }
            // Insert the item
            // $itemId = $this->itemModel->insert($itemData);

            if (!$result) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to add item.'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Item saved successfully',
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to save item: ' . $e->getMessage()
            ]);
        }
    }

    // Method to edit an existing bandwidth package
    public function edit($id)
    {
        $data['bandwidth'] = $this->bandwidthModel->getBandwidthPackageById($id);
        return view('bandwidth/edit', $data); // Create a corresponding view
    }

    // Method to update the bandwidth package
    public function update($id)
    {
        // $data = $this->request->getPost();
        // $this->InventoryModel->updateBandwidthPackage($id, $data);
        return redirect()->to('/bandwidth');
    }

    // Method to delete a bandwidth package
    public function delete($id)
    {
        $this->bandwidthModel->deleteBandwidthPackage($id);
        return redirect()->to('/bandwidth');
    }


    //proider
    public function provider_index()
    {
        $data['providers'] = $this->providerModel->getAllBandwidthPackages();
        return view('bandwidth/provider', $data); // Create a corresponding view
    }
    public function add()
    {
        return view('provider_form');
    }

    // public function save()
    // {
    //     $request = \Config\Services::request();

    //     // Enable detailed logging
    //     log_message('info', 'Starting save process');
    //     log_message('debug', 'POST data: ' . print_r($request->getPost(), true));
    //     log_message('debug', 'FILES data: ' . print_r($_FILES, true));

    //     $admin_id = session()->get('user_id');
    //     if ($request->isAJAX()) {
    //         $data = [
    //             'admin_id' => $admin_id,
    //             'company_name' => $request->getPost('company_name'),
    //             'contact_person' => $request->getPost('contact_person'),
    //             'email' => $request->getPost('email'),
    //             'phone_number' => $request->getPost('phone_number'),
    //             'mobile_number' => $request->getPost('mobile_number'),
    //             'facebook_url' => $request->getPost('facebook_url'),
    //             'skype_id' => $request->getPost('skype_id'),
    //             'website' => $request->getPost('website'),
    //             'address' => $request->getPost('address'),
    //         ];


    //         // File upload handling with detailed debugging
    //         $logo = $request->getFile('logo') ?? null;
    //         log_message('debug', 'File object: ' . print_r($logo, true));

    //         if ($logo && $logo->getError() !== 4) { // 4 means no file was uploaded
    //             log_message('debug', 'File received: ' . $logo->getName());

    //             if (!$logo->isValid()) {
    //                 $error = $logo->getErrorString();
    //                 log_message('error', 'File validation error: ' . $error);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File error: ' . $error
    //                 ]);
    //             }

    //             if ($logo->hasMoved()) {
    //                 log_message('error', 'File has already been moved');
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File has already been processed'
    //                 ]);
    //             }

    //             $uploadPath = FCPATH . 'assets/img/company_logo';

    //             if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
    //                 log_message('error', 'Failed to create directory: ' . $uploadPath);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'Could not create upload directory'
    //                 ]);
    //             }

    //             if (!is_writable($uploadPath)) {
    //                 log_message('error', 'Directory not writable: ' . $uploadPath);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'Upload directory is not writable'
    //                 ]);
    //             }

    //             $logoName = $logo->getRandomName();

    //             try {
    //                 $logo->move($uploadPath, $logoName);
    //                 $destination = $uploadPath . DIRECTORY_SEPARATOR . $logoName;

    //                 if (!file_exists($destination)) {
    //                     log_message('error', 'File move reported success but file not found');
    //                     return $this->response->setJSON([
    //                         'status' => 'error',
    //                         'message' => 'File upload failed'
    //                     ]);
    //                 }

    //                 log_message('debug', 'File successfully moved to: ' . $destination);
    //                 $data['image'] = $logoName; // ✅ Only set if successfully uploaded

    //             } catch (\Exception $e) {
    //                 log_message('error', 'File move exception: ' . $e->getMessage());
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File upload failed: ' . $e->getMessage()
    //                 ]);
    //             }
    //         } else {
    //             log_message('debug', 'No file was uploaded or upload skipped');
    //         }


    //         // Uncomment when ready to save to DB

    //         $providerModel = new ProviderModel();
    //         try {
    //             if ($providerModel->save($data)) {
    //                 log_message('info', 'Provider saved successfully');
    //                 return $this->response->setJSON([
    //                     'status' => 'success',
    //                     'message' => 'Provider added successfully'
    //                 ]);
    //             } else {
    //                 log_message('error', 'Validation errors: ' . print_r($providerModel->errors(), true));
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'Validation failed',
    //                     'errors' => $providerModel->errors()
    //                 ]);
    //             }
    //         } catch (\Exception $e) {
    //             log_message('error', 'Database error: ' . $e->getMessage());
    //             return $this->response->setJSON([
    //                 'status' => 'error',
    //                 'message' => 'Database error occurred'
    //             ]);
    //         }

    //     }

    //     return $this->response->setStatusCode(405)->setJSON([
    //         'message' => 'Invalid request method'
    //     ]);
    // }

    public function save()
    {
        $request = \Config\Services::request();
        log_message('info', 'Starting provider save process');
        log_message('debug', 'POST data: ' . print_r($request->getPost(), true));
        log_message('debug', 'FILES data: ' . print_r($_FILES, true));

        $admin_id = session()->get('user_id');

        if (!$request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON([
                'message' => 'Invalid request method'
            ]);
        }

        $providerModel = new ProviderModel();
        $providerId = $request->getPost('id'); // Will be null on insert
        $isUpdate = !empty($providerId);

        $data = [
            'admin_id' => $admin_id,
            'company_name' => $request->getPost('company_name'),
            'contact_person' => $request->getPost('contact_person'),
            'email' => $request->getPost('email'),
            'phone_number' => $request->getPost('phone_number'),
            'mobile_number' => $request->getPost('mobile_number'),
            'facebook_url' => $request->getPost('facebook_url'),
            'skype_id' => $request->getPost('skype_id'),
            'website' => $request->getPost('website'),
            'address' => $request->getPost('address'),
        ];

        if ($isUpdate) {
            $data['id'] = $providerId;
            $existingProvider = $providerModel->find($providerId);
        }

        $this->validate([
            'logo' => [
                'rules' => 'max_size[logo,1024]|is_image[logo]|ext_in[logo,png,jpg,jpeg,gif]',
                'errors' => [
                    'max_size' => 'Logo size is too large (max 1MB)',
                    'is_image' => 'The file must be an image',
                    'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif'
                ]
            ]
        ]);

        if (!$this->validation->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Validation failed: ' . implode(', ', $this->validation->getErrors())
            ]);
        }

        $logo = $request->getFile('logo');
        if ($logo && $logo->getError() !== 4) {
            log_message('debug', 'File received: ' . $logo->getName());

            if (!$logo->isValid()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'File error: ' . $logo->getErrorString()
                ]);
            }

            if ($logo->hasMoved()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'File has already been processed'
                ]);
            }

            $uploadPath = FCPATH . 'assets/img/company_logo';
            if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Upload directory could not be created'
                ]);
            }

            if (!is_writable($uploadPath)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Upload directory not writable'
                ]);
            }

            $logoName = $logo->getRandomName();

            try {
                // 🔥 Delete old image if updating
                if ($isUpdate && !empty($existingProvider['image'])) {
                    $oldImagePath = $uploadPath . DIRECTORY_SEPARATOR . $existingProvider['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                        log_message('info', 'Old image deleted: ' . $oldImagePath);
                    }
                }

                $logo->move($uploadPath, $logoName);
                $data['image'] = $logoName;
                log_message('info', 'New logo saved: ' . $logoName);
            } catch (\Exception $e) {
                log_message('error', 'File upload exception: ' . $e->getMessage());
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'File upload failed: ' . $e->getMessage()
                ]);
            }
        } else {
            log_message('debug', 'No file uploaded or skipped');
        }

        try {
            if ($providerModel->save($data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => $isUpdate ? 'Provider updated successfully' : 'Provider added successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $providerModel->errors()
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Database error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Database error occurred'
            ]);
        }
    }


    public function item_category_delete()
    {
        $request = \Config\Services::request();
        $items = $request->getPost('items');
        log_message('info', 'Delete request received:' . json_encode($items));
        if (empty($items)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No items selected for deletion'
            ]);
        }

        foreach ($items as $item) {
            if ($item['isSubcategory'] === "true") {
                // Handle subcategory deletion
                $categoryId = $item['categoryId'];
                $subcategoryIndex = $item['subcategoryIndex'];

                $category = $this->BandwidthBuyCategory->find($categoryId);
                if (!$category)
                    continue;

                $subcategories = json_decode($category['subcategory_items'], true) ?? [];

                if (isset($subcategories[$subcategoryIndex])) {
                    // Remove the subcategory
                    array_splice($subcategories, $subcategoryIndex, 1);

                    // Update the category
                    $this->BandwidthBuyCategory->update($categoryId, [
                        'subcategory_items' => json_encode($subcategories)
                    ]);
                }
            } else {
                // Handle category deletion
                $id = $item['id'];
                log_message('info', 'Delete request received:' . json_encode($id));

                // First check if this category has any subcategories
                $category = $this->BandwidthBuyCategory->find($id);
                if ($category) {
                    $hasSubcategories = !empty(json_decode($category['subcategory_items'], true));

                    if ($hasSubcategories) {
                        return $this->response->setJSON([
                            'status' => 'error',
                            'message' => 'Cannot delete category that has subcategories'
                        ]);
                    }

                    $this->BandwidthBuyCategory->delete($id);
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Selected items deleted successfully'
        ]);
    }
    // In your controller
    public function catagory_update()
    {
        $request = \Config\Services::request();

        $rules = [
            'item_category_name' => 'required|min_length[3]',
            'item_category_status' => 'required|in_list[active,inactive]',
            'type' => 'required|in_list[category,subcategory]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode('<br>', $this->validator->getErrors())
            ]);
        }

        $type = $request->getPost('type');
        $data = [
            'item_category_name' => $request->getPost('item_category_name'),
            'item_category_status' => $request->getPost('item_category_status'),
            'short_description' => $request->getPost('short_description')
        ];

        if ($type === 'category') {

            // Update main category
            $id = $request->getPost('id');
            $data['sub_category_of'] = $request->getPost('sub_category_of') ?: null;

            log_message('info', 'Subcategory items before update: ' . json_encode($data));

            if ($this->BandwidthBuyCategory->update($id, $data)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Category updated successfully'
                ]);
            }
        } else {
            // Update subcategory
            $categoryId = $request->getPost('category_id');
            $subcategoryIndex = $request->getPost('subcategory_index');

            $category = $this->BandwidthBuyCategory->find($categoryId);
            if (!$category) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Parent category not found'
                ]);
            }

            $subcategories = json_decode($category['subcategory_items'], true) ?? [];

            log_message('info', 'Subcategory items before update: ' . json_encode($subcategories));

            if (!isset($subcategories[$subcategoryIndex])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Subcategory not found'
                ]);
            }

            // Update subcategory data
            $subcategories[$subcategoryIndex]['subcategory_name'] = $data['item_category_name'];
            $subcategories[$subcategoryIndex]['item_category_status'] = $data['item_category_status'];
            $subcategories[$subcategoryIndex]['short_description'] = $data['short_description'];

            log_message('info', 'Subcategory items after update: ' . json_encode($subcategories));
            if ($this->BandwidthBuyCategory->update($categoryId, ['subcategory_items' => json_encode($subcategories)])) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Subcategory updated successfully'
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Failed to update'
        ]);
    }
    public function provider_delete()
    {
        $request = \Config\Services::request();
        $ids = $request->getPost('selected_ids'); // Can also be $request->getGet('ids');

        if (empty($ids)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No IDs provided or invalid format.'
            ]);
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids); // Convert comma-separated string to array
        }

        $ids = array_filter($ids, 'is_numeric'); // Keep only numeric values
        log_message('debug', 'Filtered IDs: ' . print_r($ids, true));

        if (empty($ids)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No valid numeric IDs.'
            ]);
        }

        $providerModel = new ProviderModel();
        $imagePathBase = FCPATH . 'assets/img/company_logo/';

        foreach ($ids as $id) {
            // Fetch provider record
            $provider = $providerModel->getBandwidthPackageById($id);

            if ($provider && !empty($provider['image'])) {
                $imagePath = $imagePathBase . $provider['image'];

                // Delete image file if it exists
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete the provider record
            $providerModel->delete($id);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Selected providers and their images were deleted successfully.'
        ]);
    }






    // Save new bill

    // public function purchess_save()
    // {
    //     $request = \Config\Services::request();
    //     $db = \Config\Database::connect();

    //     $billModel = new PurchaseBillModel();
    //     $response = ['success' => false, 'message' => ''];

    //     try {
    //         $validation = $this->validate([
    //             'provider_id' => 'required|numeric',
    //             'payment_status' => 'required|in_list[paid,due]',
    //             'items' => 'required'
    //         ]);

    //         if (!$validation) {
    //             throw new \RuntimeException(implode(' ', $this->validator->getErrors()));
    //         }

    //         // Process items
    //         $items = $request->getPost('items');
    //         log_message('info', 'Items received: ' . json_encode($items));
    //         $grandTotal = 0;

    //         foreach ($items as $item) {
    //             $qty = (float) ($item['qty'] ?? 0);
    //             $rate = (float) ($item['rate'] ?? 0);
    //             $vat = (float) ($item['vat'] ?? 0);

    //             $subtotal = $qty * $rate;
    //             $total = $subtotal + ($subtotal * ($vat / 100));
    //             $grandTotal += $total;
    //         }

    //         $billData = [
    //             'admin_id' => session()->get('user_id'),
    //             'provider' => $request->getPost('provider_id'),
    //             'invoice_number' => $request->getPost('invoice_number') ?? $this->generateInvoiceNumber(),
    //             'payment_status' => $request->getPost('payment_status'),
    //             'billing_date' => $request->getPost('billing_date'),
    //             'remarks' => $request->getPost('remarks'),
    //             'items' => json_encode($items),
    //             'total' => $grandTotal
    //         ];
    //         log_message('info', 'Bill data: ' . json_encode($billData));
    //         $db->transBegin();

    //         // Save bill

    //         $logo = $request->getFile('image') ?? null;

    //         if ($logo && $logo->getError() !== 4) { // 4 means no file was uploaded
    //             log_message('debug', 'File received: ' . $logo->getName());

    //             if (!$logo->isValid()) {
    //                 $error = $logo->getErrorString();
    //                 log_message('error', 'File validation error: ' . $error);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File error: ' . $error
    //                 ]);
    //             }

    //             if ($logo->hasMoved()) {
    //                 log_message('error', 'File has already been moved');
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File has already been processed'
    //                 ]);
    //             }

    //             $uploadPath = FCPATH . 'assets/img/purchase_bill';

    //             if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true)) {
    //                 log_message('error', 'Failed to create directory: ' . $uploadPath);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'Could not create upload directory'
    //                 ]);
    //             }

    //             if (!is_writable($uploadPath)) {
    //                 log_message('error', 'Directory not writable: ' . $uploadPath);
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'Upload directory is not writable'
    //                 ]);
    //             }

    //             $logoName = $logo->getRandomName();

    //             try {
    //                 $logo->move($uploadPath, $logoName);
    //                 $destination = $uploadPath . DIRECTORY_SEPARATOR . $logoName;

    //                 if (!file_exists($destination)) {
    //                     log_message('error', 'File move reported success but file not found');
    //                     return $this->response->setJSON([
    //                         'status' => 'error',
    //                         'message' => 'File upload failed'
    //                     ]);
    //                 }

    //                 log_message('debug', 'File successfully moved to: ' . $destination);
    //                 $billData['image'] = $logoName; // ✅ Only set if successfully uploaded

    //             } catch (\Exception $e) {
    //                 log_message('error', 'File move exception: ' . $e->getMessage());
    //                 return $this->response->setJSON([
    //                     'status' => 'error',
    //                     'message' => 'File upload failed: ' . $e->getMessage()
    //                 ]);
    //             }
    //         } else {
    //             log_message('debug', 'No file was uploaded or upload skipped');
    //         }
    //         log_message('info', 'Bill data after file processing: ' . json_encode($billData));
    //         $billId = $billModel->insert($billData);

    //         $db->transCommit();

    //         $response = [
    //             'success' => true,
    //             'message' => 'Bill saved successfully',
    //             // 'invoice_no' => $billData['invoice_no']
    //         ];

    //     } catch (\Exception $e) {
    //         $db->transRollback();
    //         $response['message'] = 'Error: ' . $e->getMessage();
    //     }

    //     return $this->response->setJSON($response);
    // }

    public function purchess_save()
    {
        $request = \Config\Services::request();
        $db = \Config\Database::connect();
        $billModel = new PurchaseBillModel();
        $response = ['success' => false, 'message' => ''];

        try {
            $validation = $this->validate([
                'provider_id' => 'required|numeric',
                'payment_status' => 'required|in_list[Paid,Due]',
                'items' => 'required'
            ]);

            if (!$validation) {
                throw new \RuntimeException(implode(' ', $this->validator->getErrors()));
            }

            $items = $request->getPost('items');
            $grandTotal = 0;

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);
                $vat = (float) ($item['vat'] ?? 0);

                $subtotal = $qty * $rate;
                $total = $subtotal + ($subtotal * ($vat / 100));
                $grandTotal += $total;
            }

            $billData = [
                'admin_id' => session()->get('user_id'),
                'provider' => $request->getPost('provider_id'),
                'invoice_number' => $request->getPost('invoice_number') ?? $this->generateInvoiceNumber(),
                'payment_status' => $request->getPost('payment_status'),
                'billing_date' => $request->getPost('billing_date'),
                'remarks' => $request->getPost('remarks'),
                'items' => json_encode($items),
                'total' => $grandTotal,
                // New payment fields
                'discount' => $request->getPost('discount') ?? 0,
                'payment_method' => $request->getPost('payment_method'),
                'received_by' => $request->getPost('received_by'),
                'paid_by' => $request->getPost('paid_by'),
                'paid_number' => $request->getPost('paid_number'),
            ];

            log_message('info', 'Bill data: ' . json_encode($billData));
            $logo = $request->getFile('image') ?? null;
            $uploadPath = FCPATH . 'assets/img/purchase_bill';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $db->transBegin();
            $id = $request->getPost('id'); // Check if it's update

            if ($id) {
                // ✅ Update: Fetch current bill
                $existingBill = $billModel->find($id);

                if ($logo && $logo->isValid() && !$logo->hasMoved()) {
                    // ✅ Delete previous image if exists
                    if (!empty($existingBill['image'])) {
                        $oldImagePath = $uploadPath . '/' . $existingBill['image'];
                        log_message('info', 'Deleting old image: ' . $oldImagePath);
                        if (is_file($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    // ✅ Upload new image
                    $logoName = $logo->getRandomName();
                    $logo->move($uploadPath, $logoName);
                    $billData['image'] = $logoName;
                }

                $billModel->update($id, $billData);
                $message = 'Bill updated successfully';
            } else {
                // ✅ Insert new bill
                if ($logo && $logo->isValid() && !$logo->hasMoved()) {
                    $logoName = $logo->getRandomName();
                    $logo->move($uploadPath, $logoName);
                    $billData['image'] = $logoName;
                }

                $billModel->insert($billData);
                $message = 'Bill saved successfully';
            }

            $db->transCommit();
            $response = ['success' => true, 'message' => $message];
        } catch (\Exception $e) {
            $db->transRollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }

        return $this->response->setJSON($response);
    }



    // Delete bills
    public function purchess_delete()
    {
        $request = \Config\Services::request();
        $ids = $request->getPost('ids');
        log_message('info', 'Delete request received:' . json_encode($ids));

        $uploadPath = FCPATH . 'assets/img/purchase_bill';

        try {
            if (empty($ids)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No bills selected'
                ]);
            }

            $purchaseBillModel = new PurchaseBillModel();

            // Fetch the image names for the selected bills
            $bills = $purchaseBillModel->select('image')->whereIn('id', $ids)->findAll();

            if (empty($bills)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Bills not found'
                ]);
            }

            foreach ($bills as $bill) {
                if (!empty($bill['image'])) {
                    $filePath = $uploadPath . DIRECTORY_SEPARATOR . $bill['image'];
                    if (file_exists($filePath)) {
                        unlink($filePath); // Delete the file
                    }
                }
            }

            // Now delete the records from the database
            $purchaseBillModel->whereIn('id', $ids)->delete();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Bills and associated images deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }



    // Generate invoice number
    private function generateInvoiceNumber()
    {

        $billModel = new PurchaseBillModel();

        $prefix = 'INV-' . date('Y') . '-';
        $last = $billModel
            ->like('invoice_no', $prefix)
            ->orderBy('id', 'DESC')
            ->first();

        $next = $last ? ((int) str_replace($prefix, '', $last['invoice_no']) + 1) : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function purchess_fetch()
    {

        $request = \Config\Services::request();
        // $db = \Config\Database::connect();

        $userId = session()->get('user_id');
        $userole = session()->get('user_role');

        // Get filter inputs from the request
        $provider = $request->getPost('reseller');
        $status = $request->getPost('status');
        $fromDate = $request->getPost('fromDate');
        $toDate = $request->getPost('toDate');
        $today = date('Y-m-d H:i:s', strtotime('-29 days'));

        $model = new PurchaseBillModel();
        $ProviderModel = new ProviderModel();
        // Build the query with provider join
        $builder = $model->builder()
            ->select('*') // Use quotes and parentheses
            ->where('admin_id', $userId)
            ->orderBy('id', 'desc');

        $results = $builder->get()->getResultArray(); // To fetch the results
        // log_message('info', 'Results: ' . json_encode($results));

        // Apply filters
        if (!empty($provider)) {
            $builder->where('provider', $provider);
        }

        if (!empty($status)) {
            $builder->where('payment_status', $status);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $builder->where('billing_date >=', $fromDate)
                ->where('billing_date <=', $toDate);
        } elseif (!empty($fromDate)) {
            $builder->where('billing_date >=', $fromDate);
        } elseif (empty($fromDate) && empty($toDate)) {
            $builder->where('created_at >=', $today);
        }

        // Initialize DataTables
        $datatables = new DataTablesCodeIgniter4($builder);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('customer', 'delete')) {
            $datatables->addColumn('select', function ($row) {
                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        // $data['bandwidths'] = $model->getBandwidthPackageById($row->provider);
        // Add columns
        $datatables->addColumn('provider', function ($row) use ($ProviderModel) {
            return $ProviderModel->getBandwidthPackageById($row->provider)['company_name'] ?? '--';
        });
        // $datatables->addColumn('provider', function ($row) {
        //     return $row->provider ?? '--';
        // });


        $datatables->addColumn('invoice', function ($row) {
            return $row->invoice_number ?? '--';
        });

        $datatables->addColumn('amount', function ($row) {
            return number_format($row->total, 2) . ' ৳';
        });

        // $datatables->addColumn('received', function ($row) {
        //     // Decode the JSON items array
        //     $items = json_decode($row->items, true);

        //     // Get the first item's total (adjust index if multiple items)
        //     $total = $items[0]['total'] ?? 0.00;

        //     // Format with currency symbol
        //     return number_format((float) $total, 2) . ' ৳';
        // });
        $datatables->addColumn('received', function ($row) {
            return number_format($row->paid_number, 2) . ' ৳';
        });

        $datatables->addColumn('discount', function ($row) {
            return number_format($row->discount, 2) . ' ৳';
        });

        $datatables->addColumn('due', function ($row) {
            $due = $row->total - $row->paid_number - $row->discount;
            return number_format(max($due, 0), 2) . ' ৳';
        });

        $datatables->format('created_at', function ($value) {
            return $value ? date('d M Y H:i', strtotime($value)) : '--';
        });

        $datatables->addColumn('comments', function ($row) {
            return $row->remarks ?? '--';
        });

        $datatables->addColumn('status', function ($row) {
            $value = $row->payment_status;
            if ($value === 'Paid') {
                return '<span class="ipb-pay-badge is-success">Paid</span>';
            } elseif ($value === 'Due') {
                return '<span class="ipb-pay-badge is-danger">Due</span>';
            } else {
                return '<span class="ipb-pay-badge is-warning">Pending</span>';
            }
        });

        // Exclude the original payment_status from the response

        // if (userHasPermission('customer', 'update')) {
        //     $datatables->addColumn('action', function ($row) {
        //         return '<a href="' . route_to('purchase.edit', $row->id) . '" 
        //             class="btn btn-sm btn-primary">
        //             <i class="fa fa-edit"></i> Edit</a>';
        //     });
        // }

        if (userHasPermission('customer', 'update')) {
            $datatables->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-sm btn-info edit-bill"
            data-id="' . $row->id . '"
            data-provider="' . $row->provider . '"
            data-status="' . $row->payment_status . '"
            data-date="' . $row->billing_date . '"
            data-invoice="' . $row->invoice_number . '"
            data-remarks="' . htmlspecialchars($row->remarks) . '"
            data-image="' . $row->image . '"
            data-discount="' . htmlspecialchars($row->discount) . '"
            data-payment-method="' . htmlspecialchars($row->payment_method) . '"
            data-paid-by="' . htmlspecialchars($row->paid_by) . '"
            data-received-by="' . htmlspecialchars($row->received_by) . '"
            data-paid-number="' . htmlspecialchars($row->paid_number) . '"
            data-items=\'' . htmlspecialchars(json_encode(json_decode($row->items), JSON_HEX_APOS | JSON_HEX_QUOT)) . '\'
            data-toggle="modal" data-target="#billModal">
            <i class="fa fa-edit"></i> Edit
        </button>';
            });
        }


        // $datatables->except(['id', 'admin_id', 'created_at', 'updated_at','payment_status']);
        $datatables->asObject();
        $datatables->generate();
    }
}

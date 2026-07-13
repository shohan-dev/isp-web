<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

use App\Libraries\BulkSmsBd;

class Sms extends BaseController
{

    protected $sms_model, $user_model;

    public function __construct()
    {
        /**
         * Sms Model
         */
        $this->sms_model = model('App\Models\Sms');

        /**
         * User Model
         */
        $this->user_model = model('App\Models\User');

        /**
         * Sms Helper
         */
        helper('sms');
    }


    /**
     * Sms
     * @action: All Sms View
     */
    public function index()
    {
        $data = [
            'title' => 'SMS',
        ];

        return view('sms/all', $data);
    }
    // public function sms_Tamplates()
    // {
    //     $data = [
    //         'title' => 'sms_Tamplates',
    //     ];

    //     return view('sms/template', $data);
    // }


    /**
     * Sms
     * @action: Fetch Sms
     */
    public function fetch()
    {
        $userId = session()->get('user_id');

        $data = $this->sms_model->builder()
            ->select('*')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc');

        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('sms_message', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->format('datetime', function ($value) {

            return !empty($value) ? date('d M Y, h:i a', strtotime($value)) : '--';
        });

        $datatables->addColumn('send_by', function ($row) {

            $user = getUserById($row->user_id);

            return !empty($user) ? $user->name . ' (' . ucwords($user->role) . ')' : '--';
        });

        $datatables->format('status', function ($value) {

            return ($value === 'success')
                ? '<span class="ipb-pay-badge is-success">Successful</span>'
                : '<span class="ipb-pay-badge is-danger">Failed</span>';
        });

        $datatables->addColumn('action', function ($row) {

            return '<div class="ipb-row-actions"><button type="button" class="ipb-row-btn tone-brand log-btn" data-log="' . esc((string) ($row->logs ?? ''), 'attr') . '" title="View Log"><i class="fa fa-circle-info"></i> Log</button></div>';
        });

        $datatables->except(['id', 'send_by', 'user_id', 'logs']);

        $datatables->asObject();

        $datatables->generate();
    }


    /**
     * Sms
     * @action: New Sms View
     */
    public function new()
    {
        $area_model = model('App\Models\Area');
        $template = model('App\Models\SmsTemplateModel');

        $package_model = model('App\Models\Package');
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');



        if ($userRole === 'super_admin') {
            $data = [
                'title' => 'New SMS',
                'area' => $area_model->where('user_id', $userId)->findAll(),
                'template' => $template->where(['template_type' => 'default'])->orwhere('user_id', $userId)->findAll(),
                'packages' => $package_model->where('user_id', $userId)->findAll(),
                'customers' => $this->user_model->where(['role' => 'admin', 'status' => 'active'])->findAll(),
            ];
        } else {
            $data = [
                'title' => 'New SMS',
                'area' => $area_model->where('user_id', $userId)->findAll(),
                'template' => $template->where(['template_type' => 'default'])->orwhere('user_id', $userId)->findAll(),
                'packages' => $package_model->where('user_id', $userId)->findAll(),
                'customers' => $this->user_model->where(['role' => 'user', 'status' => 'active'])->where('admin_id', $userId)->findAll(),
            ];
        }
        // log_message('debug', 'System Resources my Data: ' . print_r($data, true));

        return view('sms/new', $data);
    }


    /**
     * Sms
     * @action: New Sms Create
     */
    public function create()
    {
        $userRole = session()->get('user_role');

        $this->validate([
            'content' => [
                'rules'  => 'required',
                'errors' => [
                    'required'   => 'Enter sms content',
                    'max_length' => 'SMS content will be maximum 239 characters',
                ]
            ],
        ]);

        if (!$this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $content  = $this->request->getPost('content');
        $tmpid    = $this->request->getPost('tmp_id') ?? null;
        $area_id  = $this->request->getPost('area') ?? null;
        $send_to  = $this->request->getPost('send_to');  // array: ['all'] or [1,2,3]
        $customMessages = $this->request->getPost('custom_messages'); // array: [userId => content]

        if (empty($send_to)) {
            return requestResponse('error', 'Select at least one customer', 400);
        }

        // ---- Resolve target users ----------------------------------------
        $userId   = getSession('user_id');
        $condition = ['status' => 'active'];

        if ($userRole === 'super_admin') {
            $condition['role'] = 'admin';
        } else {
            $condition['role']     = 'user';
            $condition['admin_id'] = $userId;
        }

        if (in_array('all', (array) $send_to)) {
            // Send to all customers (optionally filtered by area)
            if (!empty($area_id)) {
                $condition['area_id'] = $area_id;
            }
            $users    = $this->user_model->where($condition)->asArray()->findAll();
            $receiver = empty($area_id) ? 'All Customers' : 'All Customers (area #' . $area_id . ')';
        } else {
            $ids   = array_filter((array) $send_to, fn($v) => is_numeric($v));
            if (empty($ids)) {
                return requestResponse('error', 'No valid customers selected', 400);
            }
            $users    = $this->user_model->whereIn('id', $ids)->asArray()->findAll();
            $names    = array_column($users, 'name');
            $receiver = implode(', ', $names);
        }

        if (empty($users)) {
            return requestResponse('error', 'No matching customers found', 400);
        }

        // ---- Send SMS per user (personalised or custom) ------------------
        // Async opt-in: when queue.smsEnabled is true each SMS is enqueued for the
        // `php spark queue:work` worker instead of being sent inline (the inline blast
        // holds the request/worker for the whole list — minutes for "All Customers").
        // OFF by default. Before enabling, verify on staging that the worker resolves
        // the correct PER-TENANT SMS gateway: Send_SMs() reads the gateway via session/
        // tenant context, which is absent in CLI (see app/Commands/QueueWork::dispatch()).
        $useQueue = (bool) env('queue.smsEnabled', false);
        if ($useQueue) {
            helper('queue');
        }
        $successCount = 0;
        $failCount    = 0;
        $queuedCount  = 0;

        foreach ($users as $user) {
            $uId = $user['id'];
            
            // PRIORITY: Use custom message from editable preview if available
            // FALLBACK: Use template with placeholder replacement
            if (!empty($customMessages[$uId])) {
                $personalContent = $customMessages[$uId];
            } else {
                $personalContent = $this->personaliseContent($content, $user);
            }

            $row = [
                'user_id'  => $userId,
                'datetime' => date('Y-m-d H:i:s'),
                'content'  => $personalContent,
                'send_to'  => $user['name'] . ' (' . ($user['mobile'] ?? '--') . ')',
            ];
            $this->sms_model->insert($row, false);
            $insertedId = $this->sms_model->getInsertID();

            if ($useQueue) {
                enqueue('sms', [
                    'user'       => $user,            // carries admin_id/package_id/etc. for tenant + template context
                    'content'    => $personalContent,
                    'sms_log_id' => $insertedId,
                    'owner_id'   => $userId,          // initiating admin/tenant
                ]);
                $queuedCount++;
            } else {
                $result = Send_SMs([$user], null, null, null, $personalContent, $insertedId);

                if (!empty($result['status']) && $result['status'] === 'success') {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
        }

        $total = count($users);
        if ($queuedCount > 0) {
            return requestResponse('success', "SMS queued for background delivery to {$queuedCount}/{$total} customer(s).", 200);
        }
        if ($successCount === $total) {
            return requestResponse('success', "SMS sent to all {$total} customer(s) successfully", 200);
        } elseif ($successCount > 0) {
            return requestResponse('success', "SMS sent to {$successCount}/{$total} customer(s). {$failCount} failed.", 200);
        } else {
            return requestResponse('error', 'SMS could not be sent! Check logs for details', 500);
        }
    }

    /**
     * Replace template placeholders for a single user row (array).
     */
    private function personaliseContent(string $content, array $user): string
    {
        $userId = $user['id'] ?? 0;
        $packageAmount = '--';

        if (!empty($userId)) {
            $userPackage = getUserPackage($userId);
            if ($userPackage) {
                // getUserPackage can return object or array depending on the internal path taken
                $p = is_object($userPackage) ? ($userPackage->price ?? '--') : ($userPackage['selling_price'] ?? $userPackage['price'] ?? '--');
                // For resellers, selling_price is the source of truth if available
                if (is_array($userPackage) && !empty($userPackage['selling_price']) && $userPackage['selling_price'] !== '--') {
                    $p = $userPackage['selling_price'];
                }
                $packageAmount = $p;
            }
        }

        $adminId = $user['admin_id'] ?? 0;
        $admin = !empty($adminId) ? getUserById($adminId) : null;
        $appName = getSetting('app_name', 'ISP', $adminId);

        if (!empty($adminId)) {
            $regModel = model('App\Models\Registration');
            $reg = $regModel->where('userid', $adminId)->first();
            if (!empty($reg['organization_name'])) {
                $appName = $reg['organization_name'];
            }
        }

        $map = [
            'CustomerName'  => $user['name']      ?? '--',
            'EmployeeName'  => $user['name']       ?? '--',
            'c_name'        => $user['name']       ?? '--',
            'Mobile'        => $user['mobile']     ?? '--',
            'Email'         => $user['email']      ?? '--',
            'PackageAmount' => $packageAmount,
            'PaidAmount'    => $packageAmount,
            'PaymentAmount' => $packageAmount,
            'will_expire'   => $user['will_expire'] ?? '--',
            'ClientCode'    => $user['pppoe_id']   ?? $user['username'] ?? '--',
            'UserName'      => $user['username']   ?? '--',
            'Password'      => $user['code']       ?? '--',
            'LoginUserName' => $user['email']      ?? '--',
            'LoginPassword' => $user['code']       ?? '--',
            'MonthName'     => date('F'),
            'CompanyName'   => $appName,
            'company_name'  => $appName,
            'CompanyMobile' => $admin->mobile ?? '--',
            'company_cell'  => $admin->mobile ?? '--',
            'BaseSiteURL'   => base_url(),
        ];

        foreach ($map as $key => $val) {
            $content = str_ireplace(["{{{$key}}}", "{{$key}}"], (string)$val, $content);
        }
        return $content;
    }


    /**
     * Sms
     * @action: Delete Sms Messages
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $result = $this->sms_model->whereIn('id', $ids)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Sms
     * @action: Get User
     */
    public function getUser()
    {
        $this->validate(['area' => ['rules' => 'required']]);

        if ($this->validation->run()) {

            $area_id = getPostInput('area');

            $html = '';

            $user_model = model('App\Models\User');

            $userRole = session()->get('user_role');



            if ($userRole === 'super_admin') {
                $users = $this->user_model->where(['role' => 'admin', 'status' => 'active',])->findAll();
            } else {
                $users = $this->user_model->where(['area_id' => $area_id, 'role' => 'user', 'status' => 'active',])->findAll();
            }
            if (!empty($users)) {

                $html .= '<option value="all">All Customers</option>';

                foreach ($users as $user) {

                    $html .= '<option value="' . $user->id . '">' . $user->name . '</option>';
                }
            } else {

                $html .= '<option value="">No customer found!</option>';
            }

            return requestResponse('success', $html, 200);
        }

        return requestResponse('validation-error', $this->validation->getErrors()['area'], 400);
    }
 
    /**
     * Sms
     * @action: Get Multiple Customer Details (for live preview)
     */
    public function getMultipleCustomerDetails()
    {
        $ids = $this->request->getPost('ids');  // array of IDs
        if (empty($ids) || !is_array($ids)) {
            return requestResponse('error', 'No IDs provided', 400);
        }

        $ids = array_filter($ids, fn($v) => is_numeric($v));
        if (empty($ids)) {
            return requestResponse('error', 'No valid IDs', 400);
        }

        $users = $this->user_model->whereIn('id', $ids)->findAll();
        $result = [];

        foreach ($users as $user) {
            $userId = $user->id;
            $packageAmount = '--';

            if (!empty($userId)) {
                $userPackage = getUserPackage($userId);
                if ($userPackage) {
                    $p = is_object($userPackage) ? ($userPackage->price ?? '--') : ($userPackage['selling_price'] ?? $userPackage['price'] ?? '--');
                    if (is_array($userPackage) && !empty($userPackage['selling_price']) && $userPackage['selling_price'] !== '--') {
                        $p = $userPackage['selling_price'];
                    }
                    $packageAmount = $p;
                }
            }

            $adminId = $user->admin_id;
            $admin = !empty($adminId) ? getUserById($adminId) : null;
            $appName = getSetting('app_name', 'ISP', $adminId);

            if (!empty($adminId)) {
                $regModel = model('App\Models\Registration');
                $reg = $regModel->where('userid', $adminId)->first();
                if (!empty($reg['organization_name'])) {
                    $appName = $reg['organization_name'];
                }
            }

            $result[$user->id] = [
                'CustomerName'  => $user->name,
                'EmployeeName'  => $user->name,
                'c_name'        => $user->name,
                'Mobile'        => $user->mobile,
                'Email'         => $user->email,
                'PackageAmount' => $packageAmount,
                'PaidAmount'    => $packageAmount,
                'PaymentAmount' => $packageAmount,
                'will_expire'   => $user->will_expire ?? '--',
                'ClientCode'    => $user->pppoe_id ?? $user->username ?? '--',
                'UserName'      => $user->username ?? '--',
                'Password'      => $user->code ?? '--',
                'LoginUserName' => $user->email,
                'LoginPassword' => $user->code ?? '--',
                'MonthName'     => date('F'),
                'CompanyName'   => $appName,
                'company_name'  => $appName,
                'CompanyMobile' => $admin ? ($admin->mobile ?? '--') : '--',
                'company_cell'  => $admin ? ($admin->mobile ?? '--') : '--',
                'BaseSiteURL'   => base_url(),
            ];
        }

        return $this->response->setJSON(['status' => 'success', 'response' => $result]);
    }

    /**
     * Sms
     * @action: Get Customer Details
     */
    public function getCustomerDetails()
    {
        $id = $this->request->getPost('id');
        if (empty($id) || $id === 'all') {
            return requestResponse('error', 'Invalid customer', 400);
        }
 
        $user = getUserById($id);
        if (!$user) {
            return requestResponse('error', 'Customer not found', 404);
        }
 
        // Fetch Admin/Reseller info for company details
        $admin = getUserById($user->admin_id);
 
        // Fetch package amount if applicable
        $packageAmount = 0;
        if (!empty($user->package_id)) {
            if ($admin && ($admin->role === 'resellerAdmin' || $user->created_by === 'resellerAdmin')) {
                // Use the helper that resolves selling_price ?? price for resellers
                $packageAmount = ResellerPackagePrice($user->package_id, null, (int)$user->admin_id, 'resellerAdmin') ?? 0;
            } else {
                $package_model = model('App\Models\AdminPackage');
                $package = $package_model->find($user->package_id);
                $packageAmount = $package->price ?? 0;
            }
        }
        $appName = getSetting('app_name', 'ISP', $user->admin_id);
        if (!empty($user->admin_id)) {
            $regModel = model('App\Models\Registration');
            $reg = $regModel->where('userid', $user->admin_id)->first();
            if (!empty($reg['organization_name'])) {
                $appName = $reg['organization_name'];
            }
        }
 
        $data = [
            'CustomerName' => $user->name,
            'c_name'       => $user->name,
            'EmployeeName' => $user->name,
            'Mobile'       => $user->mobile,
            'Email'        => $user->email,
            'PackageAmount'=> $packageAmount,
            'PaidAmount'   => $packageAmount, 
            'PaymentAmount'=> $packageAmount,
            'will_expire'  => $user->will_expire ?? '--',
            'ClientCode'   => $user->pppoe_id ?? $user->username ?? '--',
            'UserName'     => $user->username ?? '--',
            'Password'     => $user->code ?? '--', // plain text password from 'code' field
            'LoginUserName'=> $user->email,
            'LoginPassword'=> $user->code ?? '--',
            'MonthName'    => date('F'),
            'CompanyName'  => $appName,
            'company_name' => $appName,
            'CompanyMobile'=> $admin->mobile ?? '--',
            'company_cell' => $admin->mobile ?? '--',
            'BaseSiteURL'  => base_url(),
        ];
 
        return $this->response->setJSON(['status' => 'success', 'response' => $data]);
    }
}

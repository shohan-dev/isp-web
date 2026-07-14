<?php

namespace App\Controllers;

use App\Models\SmsTemplateModel;
use CodeIgniter\HTTP\ResponseInterface;

use CodeIgniter\Controller;

class SmsTemplateController extends Controller
{
    protected $SmsTemplateModel;

    public function __construct()
    {
        $this->SmsTemplateModel = model(SmsTemplateModel::class);
    }

    public function index()
    {
        $userId = session()->get('user_id');

        // Fetch all templates
        $templates = $this->SmsTemplateModel
            ->where('user_id', $userId)
            ->orWhere('template_type', 'default')
            ->findAll();


        // $templates = $this->SmsTemplateModel
        //     ->where(function($query) use ($userId) {
        //         $query->where('template_type', 'default')
        //             ->orWhere(function($query) use ($userId) {
        //                 $query->where('template_type', 'custom')
        //                         ->where('user_id', $userId);
        //             });
        //     })
        //     ->findAll();


        // Prepare data for the view
        $data = [
            'title' => 'SMS Templates',
            'templates' => $templates,
            'message' => empty($templates) ? 'No templates found.' : null,
        ];

        // Load the view with data
        return view('sms/template', $data);
    }

    public function create()
    {
        return view('sms/template/create');
    }

    public function store()
    {
        $request = $this->request;

        // Validate the request
        $validation = \Config\Services::validation();
        $validation->setRules([
            'template_name' => 'required|string',
            'message_body' => 'required|string',
            'template_type' => 'required|in_list[custom,default]',
        ]);

        if (!$validation->withRequest($request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }
        $userId = session()->get('user_id');

        // Retrieve form data
        $data = [
            'user_id'   =>$userId,
            'template_name' => getPostInput('template_name'),
            'message_body' => getPostInput('message_body'),
            'template_type' => getPostInput('template_type'),
        ];

        // Insert data into the database
        $this->SmsTemplateModel->insert($data);

        // Redirect to the SMS templates page
        return redirect()->route('route.sms_Tamplates')->with('success', 'Template added successfully');
    }




public function update()
    {
        $smsTemplateModel = new SmsTemplateModel();

        // Retrieve POST data
         $id = getPostInput('id');
        $templateName = getPostInput('template_name');
        $messageBody = getPostInput('message_body');
        $templateType = getPostInput('template_type');

        // Validate the input data
        if (!$this->validate([
            'template_name' => 'required',
            'message_body' => 'required',
            'template_type' => 'required|in_list[custom,default]',
        ])) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['response' => 'Invalid input data']);
        }

        // Update the template in the database
        $data = [
            'template_name' => $templateName,
            'message_body' => $messageBody,
            'template_type' => $templateType,
        ];

        $updated = $smsTemplateModel->update($id, $data);

        if ($updated) {
            return $this->response->setJSON(['response' => 'success']);
        } else {
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON(['response' => 'Failed to update template']);
        }
        
    }

    public function delete()
{
    $id = getPostInput('id'); // Get the ID from POST request
    $smsTemplateModel = new SmsTemplateModel();
    
    if ($smsTemplateModel->delete($id)) {
        return $this->response->setJSON(['response' => 'success']);
    } else {
        return $this->response->setJSON(['response' => 'error']);
    }
}

    /**
     * SMS Event Config
     * @action: Show the event→template mapping page for sAdmin
     */
    public function eventConfig()
    {
        $adminId   = session()->get('user_id');

        // All templates this admin can use (their own customs + all defaults)
        $templates = $this->SmsTemplateModel
            ->where('user_id', $adminId)
            ->orWhere('template_type', 'default')
            ->findAll();

        $configModel = model('App\Models\SmsEventConfig');
        $configs     = $configModel->getConfigsForAdmin((int) $adminId);

        $events = [
            'user_created'    => ['label' => 'User / Customer Created',      'default_id' => 2],
            'payment_done'    => ['label' => 'Payment / Renewal Done',        'default_id' => 13],
            'user_expired'    => ['label' => 'Subscription Expired',          'default_id' => 12],
            'expiry_notice'   => ['label' => 'Expiry Reminder (Before Exp.)', 'default_id' => 12],
            'employee_create' => ['label' => 'Employee Account Created',      'default_id' => 6],
            'employee_pay'    => ['label' => 'Employee Salary Payment',       'default_id' => 1],
            'password_reset'  => ['label' => 'Password Reset (Customer)',     'default_id' => 5],
        ];

        return view('sms/event_config', [
            'title'     => 'SMS Event Notifications',
            'templates' => $templates,
            'configs'   => $configs,
            'events'    => $events,
        ]);
    }

    /**
     * SMS Event Config
     * @action: Save event→template mappings via AJAX
     */
    public function saveEventConfig()
    {
        $adminId     = session()->get('user_id');
        $configModel = model('App\Models\SmsEventConfig');

        $events = ['user_created', 'payment_done', 'user_expired', 'expiry_notice', 'employee_create', 'employee_pay', 'password_reset'];


        foreach ($events as $event) {
            $templateId = getPostInput("template_{$event}") ?: null;
            $isEnabled  = getPostInput("enabled_{$event}") ? 1 : 0;
            if ($templateId !== null) $templateId = (int) $templateId;

            $configModel->upsert((int) $adminId, $event, $templateId, $isEnabled);
        }

        return requestResponse('success', 'SMS event settings saved successfully', 200);
    }


}

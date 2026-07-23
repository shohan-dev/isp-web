<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\TrashService;
use Ngekoding\CodeIgniterDataTables\DataTablesCodeIgniter4;

class SupportTicket extends BaseController
{

    protected $ticket_model, $user_model;

    public function __construct()
    {
        /**
         * Ticket Model
         */
        $this->ticket_model = model('App\Models\Ticket');
        $this->user_model = model('App\Models\User');
    }

    /**
     * Support Tickets
     * @action: All Tickets View
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $details = $this->user_model->where(['id' => $userId])->first();
        $userRole = session()->get('user_role');

        if ($userRole === 'employee') {
            // $userModel = model('App\Models\User');
            // $details = $userModel->where(['id' => $userId])->first();
            $Pre_created_by = $details->created_by;
            if ($Pre_created_by === 'admin') {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
            } else {
                $userId = $details->admin_id;
                $details = $this->user_model->where(['id' => $userId])->first();
                $userId = $details->admin_id;
            }
        }
        $resellers = $this->user_model
            ->select('*')
            ->where('role', 'employee')
            ->where('admin_id', $userId)
            ->findAll();


        $data = [
            'title' => 'Support Tickets',
            'resellers' => $resellers,
            'details' => $details
        ];

        return view('tickets/all', $data);
    }

    protected function condition($id = null)
    {
        $condition = [];

        if (!empty($id)) {

            $condition['id'] = $id;

            return $condition;
        }
        if (getSession('user_role') === 'admin') {

            $condition['user_id'] = getSession('user_id');
        }
        if (getSession('user_role') === 'resellerAdmin') {

            $condition['user_id'] = getSession('user_id');
        }
        if (getSession('user_role') === 'user') {

            $condition['user_id'] = getSession('user_id');
        }

        return $condition;
    }
    /**
     * Shared ticket list query (role-scoped).
     */
    protected function ticketListBuilder()
    {
        $userRole = session()->get('user_role');
        $userId = session()->get('user_id');

        if ($userRole === 'employee') {
            return $this->ticket_model->builder()
                ->select('*')
                ->where("JSON_CONTAINS(admin_ids, '\"" . (int) $userId . "\"')", null, false)
                ->orderBy('id', 'desc');
        }

        if ($userRole === 'admin') {
            return $this->ticket_model->builder()
                ->select('*')
                ->where($this->condition())
                ->orWhere("JSON_CONTAINS(admin_ids, '\"" . (int) $userId . "\"')", null, false)
                ->orderBy('id', 'desc');
        }

        return $this->ticket_model->builder()
            ->select('*')
            ->where($this->condition())
            ->orderBy('id', 'desc');
    }

    /**
     * Support Tickets — JSON inbox list (JSX split-pane).
     *
     * The split-pane view loads this whole list client-side (search/status-tab
     * filtering and the KPI strip are computed in JS), so we bound it with a
     * generous LIMIT here rather than paginating — there's no page-number/load-more
     * UI in tickets/all.php to hook a real page param into.
     */
    public function inbox()
    {
        $rows = $this->ticketListBuilder()->limit(300)->get()->getResult();
        $showUser = getSession('user_role') != 'user';
        $canUpdate = userHasPermission('support_ticket', 'update');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $userIds = array_values(array_unique(array_filter(array_map(fn($row) => $row->user_id, $rows))));
        $usersById = [];
        if ($showUser && !empty($userIds)) {
            $usersById = $this->user_model->select('id, name')->whereIn('id', $userIds)->findAll();
            $usersById = array_column($usersById, 'name', 'id');
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'subject' => (string) ($row->subject ?? ''),
                'user' => $showUser ? ($usersById[$row->user_id] ?? '--') : '',
                'category' => ucwords((string) ($row->category ?? '')),
                'priority' => strtolower((string) ($row->priority ?? 'low')),
                'status' => (string) ($row->status ?? 'closed'),
                'viewed' => (string) ($row->viewed ?? 'no') === 'yes',
                'datetime' => !empty($row->datetime) ? date('M j, Y', strtotime($row->datetime)) : '',
                'details_url' => route_to('route.ticket.details', $row->id),
                'edit_url' => $canUpdate ? route_to('route.ticket.edit', $row->id) : '',
            ];
        }

        return $this->response->setJSON(['data' => $items]);
    }

    /**
     * Support Tickets
     * @action: Fetch Tickets
     */
    public function fetch()
    {
        $data = $this->ticketListBuilder();
        $datatables = new DataTablesCodeIgniter4($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('support_ticket', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->format('datetime', function ($value) {

            return date('d-m-Y, h:i a', strtotime($value));
        });

        if (getSession('user_role') != 'user') {

            $datatables->addColumn('user', function ($row) {

                return getUserById($row->user_id)->name ?? '--';
            });
        }

        $datatables->format('category', function ($value) {

            return ucwords($value);
        });

        $datatables->format('priority', function ($value) {

            return ucwords($value);
        });

        $datatables->format('viewed', function ($value) {

            return ($value === 'yes')
                ? '<span class="ipb-pay-badge is-success">Yes</span>'
                : '<span class="ipb-pay-badge is-danger">No</span>';
        });

        $datatables->format('status', function ($value) {

            if ($value === 'opened') {

                return '<span class="ipb-pay-badge is-success">Opened</span>';
            } elseif ($value === 'processing') {

                return '<span class="ipb-pay-badge is-warning">Processing</span>';
            } else {

                return '<span class="ipb-pay-badge is-danger">Closed</span>';
            }
        });

        $datatables->addColumn('action', function ($row) {

            $html = '<div class="ipb-row-actions">';
            $html .= '<a href="' . route_to('route.ticket.details', $row->id) . '" class="ipb-row-btn tone-info" title="Details" data-toggle="tooltip"><i class="far fa-eye" aria-hidden="true"></i><span class="sr-only">Details</span></a>';

            if (userHasPermission('support_ticket', 'update')) {

                $html .= '<a href="' . route_to('route.ticket.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update" data-toggle="tooltip"><i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Update</span></a>';
            }

            $html .= '</div>';
            return $html;
        });

        $datatables->except(['id', 'user_id', 'details', 'remarks']);

        $datatables->asObject();

        $datatables->generate();
    }


    /**
     * Support Tickets
     * @action: New Support Ticket View
     */
    public function new()
    {
        $userId = session()->get('user_id');

        $userModel = model('App\Models\User');
        $employees = $userModel->where(['role' => 'employee'])->where(['admin_id' => $userId])->findAll();
        log_message('debug', 'employees data: ' . print_r($employees, true));

        $data = [
            'title' => 'New Ticket',
            'employees' => $employees,
        ];

        return view('tickets/new', $data);
    }


    /**
     * Support Tickets
     * @action: New Support Ticket
     */
    public function create()
    {
        $this->validate([
            'subject' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter subject',
                ]
            ],
            'category' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select category',
                ]
            ],
            'priority' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select priority',
                ]
            ],
            // 'customer' => [
            //     'rules' => 'required',
            //     'errors' => [
            //         'required' => 'Select employee',
            //     ]
            // ],
            'message' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter your message',
                ]
            ]
        ]);

        if ($this->validation->run()) {
            $uid = getSession('user_id');
            $details = $this->user_model->where(['id' => $uid])->first();

            $admin_id = $details->admin_id;

            $id = getPostInput('customer');
            $id = (!empty($id)) ? $id : $admin_id;

            $data = [
                'user_id'   => getSession('user_id'),
                'subject'   => getPostInput('subject'),
                'category'  => getPostInput('category'),
                'priority'  => getPostInput('priority'),
                'admin_ids' => json_encode($id),
                'details'   => json_encode([[
                    'sender'   => getSession('user_id'),
                    'datetime' => date("d-m-y, h:i a"),
                    'msg'      => getPostInput('message'),
                ]]),
                'datetime'  => date("Y-m-d, H:i:s"),
            ];
            log_message('debug', 'result data: ' . print_r($data, true));

            $result = $this->ticket_model->insert($data);
            log_message('debug', 'result data: ' . print_r($result, true));

            if ($result) {

                $ticket = $this->ticket_model->where($this->condition($result))->first();

                $user = getUserById($data['user_id']);

                $email_data = [
                    'user' => $user->name,
                    'subject' => $ticket->subject,
                    'status_text' => 'has been opened'
                ];

                //send email notification
                sendMail(
                    $user->email,
                    getSetting('app_name') . ' | Support Ticket Update',
                    view('emails/support-ticket-open-closed', $email_data),
                );

                return requestResponse('success', 'Ticket opened successfully', 200);
            }

            return requestResponse('success', 'Something went wrong! Please try again', 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Support Tickets
     * @action: Delete Support Tickets
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        log_message('debug', 'Deleting tickets with IDs: ' . print_r($ids, true));

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {

            $tickets = $this->ticket_model->whereIn('id', $ids)->findAll();
            $result = (new TrashService())->trash('support_ticket', $tickets);

            if ($result > 0) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected!", 400);
    }


    /**
     * Support Tickets
     * @action: Support Ticket Details
     */
    public function details($id)
    {
        log_message('debug', 'Fetching details for ticket ID: ' . $id);
        log_message('debug', 'Condition applied: ' . print_r($this->condition($id), true));
        $details = $this->ticket_model->where($this->condition($id))->first();

        // Employees may only see tickets assigned via admin_ids
        if (empty($details) && session()->get('user_role') === 'employee') {
            $details = $this->ticket_model->builder()
                ->where('id', (int) $id)
                ->where("JSON_CONTAINS(admin_ids, '\"" . (int) session()->get('user_id') . "\"')", null, false)
                ->get()
                ->getRow();
        }

        // sAdmin may also see tickets assigned to them
        if (empty($details) && session()->get('user_role') === 'admin') {
            $details = $this->ticket_model->builder()
                ->where('id', (int) $id)
                ->where("JSON_CONTAINS(admin_ids, '\"" . (int) session()->get('user_id') . "\"')", null, false)
                ->get()
                ->getRow();
        }

        log_message('debug', 'Ticket details: ' . print_r($details, true));
        if (!empty($details)) {

            if (getSession('user_role') != 'user') {

                $this->ticket_model->update($id, ['viewed' => 'yes']);
            }

            $user = getUserById($details->user_id);
            $ticketUser = getUserById($details->user_id);

            $data = [
                'title'      => 'Ticket Details',
                'details'    => $details,
                'canReply'   => !empty($user),
                'ticketUser' => $ticketUser,
            ];

            if ($this->request->isAJAX() || $this->request->getGet('partial') === '1') {
                return view('tickets/partials/thread', $data);
            }

            // Employees list for transfer on standalone details page
            $adminId = (int) session()->get('user_id');
            $role = session()->get('user_role');
            if ($role === 'employee') {
                $me = $this->user_model->where(['id' => $adminId])->first();
                $adminId = (int) ($me->admin_id ?? $adminId);
            }
            $data['resellers'] = $this->user_model
                ->where('role', 'employee')
                ->where('admin_id', $adminId)
                ->findAll();

            return view('tickets/details', $data);
        }

        show_404();
    }

    /**
     * Support Tickets
     * @action: Send Message
     */
    public function sendMessage($id)
    {
        $this->validate([
            'message' => [
                'rules' => 'required',
            ],
        ]);

        if ($this->validation->run()) {

            $ticket = $this->ticket_model->where($this->condition($id))->first();

            $details = $ticket->details ? json_decode($ticket->details) : [];

            $new_data = [
                'sender' => getSession('user_id'),
                'msg' => trim(getPostInput('message')),
                'datetime' => date("d-m-y, h:i a"),
            ];

            array_push($details, $new_data);

            $data = [
                'details' => json_encode($details),
            ];

            $result = $this->ticket_model->update($id, $data);

            if ($result) {

                if ($ticket->user_id != getSession('user_id')) {

                    $user = getUserById($ticket->user_id);

                    //send email notification
                    sendMail(
                        $user->email,
                        getSetting('app_name') . ' | Support Ticket Update',
                        view('emails/support-ticket-answered', [
                            'user'    => $user->name,
                            'subject' => $ticket->subject,
                            'message' => 'Your support ticket from ' . getSetting("site_name") . ' has been answered'
                        ]),
                    );
                }

                return requestResponse('success', [
                    'sender' => getUserById(getSession('user_id'))->name,
                    'msg' => $new_data['msg'],
                    'datetime' => $new_data['datetime']
                ], 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Support Tickets
     * @action: Edit Support Ticket
     */
    public function edit($id)
    {
        $details = $this->ticket_model->where($this->condition($id))->first();

        if (!empty($details)) {

            $data = [
                'title'    => 'Update Ticket',
                'details'  => $details,
            ];

            return view('tickets/edit', $data);
        }

        show_404();
    }


    /**
     * Support Tickets
     * @action: Update Support Ticket
     */
    public function update($id)
    {
        $this->validate([
            'subject' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter subject',
                ]
            ],
            'category' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select category',
                ]
            ],
            'priority' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select priority',
                ]
            ],
        ]);

        if (getSession('user_role') != 'user') {

            $this->validate([
                'status' => [
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Select status',
                    ]
                ]
            ]);
        }

        if ($this->validation->run()) {

            $ticket = $this->ticket_model->where($this->condition($id))->first();

            $data = [
                'subject'   => getPostInput('subject'),
                'category'  => getPostInput('category'),
                'priority'  => getPostInput('priority'),
            ];

            if (getSession('user_role') != 'user') {

                $data['remarks'] = getPostInput('remarks');
                $data['status'] = getPostInput('status');
            }

            $result = $this->ticket_model->where($this->condition($id))->set($data)->update();

            if ($result) {

                if ((getSession('user_role') != 'user')) {

                    if (($ticket->status != $data['status'])) {

                        $user = getUserById($ticket->user_id);

                        $email_data = [
                            'user' => $user->name,
                            'subject' => $ticket->subject
                        ];


                        if ($data['status'] == 'opened') {

                            $email_data['status_text'] =  'has been opened';
                        } else if ($data['status'] == 'processing') {

                            $email_data['status_text'] = 'has been updated and is now being processed';
                        } else {

                            $email_data['status_text'] = 'has been closed';
                        }

                        //send email notification
                        sendMail(
                            $user->email,
                            getSetting('app_name') . ' | Support Ticket Update',
                            view('emails/support-ticket-open-closed', $email_data),
                        );
                    }
                }

                return requestResponse('success', 'Ticket updated successfully', 200);
            }

            return requestResponse('success', 'Something went wrong! Please try again', 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    /**
     * Support Tickets
     * @action: Quick status update (inline dropdown — no full-page navigation)
     */
    public function quickStatus($id)
    {
        if (getSession('user_role') === 'user') {
            return requestResponse('error', 'Not permitted', 403);
        }

        $this->validate([
            'status' => [
                'rules' => 'required|in_list[opened,processing,closed]',
                'errors' => [
                    'required' => 'Select status',
                    'in_list' => 'Invalid status',
                ],
            ],
        ]);

        if (!$this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $ticket = $this->ticket_model->where($this->condition($id))->first();

        if (empty($ticket)) {
            return requestResponse('error', 'Ticket not found', 404);
        }

        $newStatus = getPostInput('status');
        $result = $this->ticket_model->where($this->condition($id))->set(['status' => $newStatus])->update();

        if ($result) {
            if ($ticket->status !== $newStatus) {
                $user = getUserById($ticket->user_id);

                $email_data = [
                    'user' => $user->name,
                    'subject' => $ticket->subject,
                ];

                if ($newStatus === 'opened') {
                    $email_data['status_text'] = 'has been opened';
                } elseif ($newStatus === 'processing') {
                    $email_data['status_text'] = 'has been updated and is now being processed';
                } else {
                    $email_data['status_text'] = 'has been closed';
                }

                sendMail(
                    $user->email,
                    getSetting('app_name') . ' | Support Ticket Update',
                    view('emails/support-ticket-open-closed', $email_data),
                );
            }

            return requestResponse('success', 'Status updated successfully', 200);
        }

        return requestResponse('error', 'Something went wrong! Please try again', 500);
    }

    /**
     * Support Tickets
     * @action: Support Ticket Condition
     */



    public function transfer()
    {
        log_message('info', 'Reached transfer function. ');

        // Retrieve POST data
        $ids = $this->request->getPost('tickets');
        $resellerId = $this->request->getPost('employees');

        log_message('info', 'Fetched ids data: ' . json_encode($ids));
        log_message('info', 'Fetched resellerId data: ' . json_encode($resellerId));

        $userRole = session()->get('user_role');

        if ($userRole === 'resellerAdmin') {
            $created_by = 'admin';
        } elseif ($userRole === 'employee') {
            // Additional logic for employee if needed
        } else {
            $transfer = 'admin';
            $admin_ids = $resellerId;
        }

        // Loop through each selected customer and update the reseller_id
        foreach ($ids as $customerId) {
            // Check if the customer exists before attempting to update
            $customer = $this->ticket_model->find($customerId);
            if (!$customer) {
                log_message('error', 'Customer ID not found: ' . $customerId);
                continue; // Skip this customer if not found
            }

            // Log customer data
            log_message('info', 'Customer found: ' . json_encode($customer));

            // Access the customer properties using object notation
            if (empty($customer->admin_ids)) {
                // Initialize admin_ids as an empty array or the proper data
                $admin_ids = $resellerId; // Set admin_ids to the new values
            } else {
                // Optionally, you could append the new admin IDs if you want to keep the existing ones
                $existing = json_decode($customer->admin_ids, true);
                $admin_ids = array_merge(is_array($existing) ? $existing : [], $resellerId);
            }

            // Log and update the customer
            // log_message('info', 'Fetched admin_ids: ' . json_encode($admin_ids));
            // log_message('info', 'transfer: ' . json_encode($transfer));

            // Ensure data is valid before updating
            if ($userRole === 'admin' && !empty($admin_ids) && !empty($transfer)) {
                // Update customer with admin_ids and transfer values
                $this->ticket_model->update($customerId, ['admin_ids' => json_encode($admin_ids), 'transfer' => $transfer]);
            } elseif ($userRole === 'employee') {
                // Get the current user's ID from session
                $userId = session()->get('user_id');

                // Fetch the current admin_ids
                $existingData = $this->ticket_model->find($customerId);

                if ($existingData) {
                    // Decode existing admin_ids safely
                    $existingAdminIds = json_decode($existingData->admin_ids, true) ?? [];

                    // Ensure it's an array
                    if (!is_array($existingAdminIds)) {
                        $existingAdminIds = [];
                    }

                    // Merge and remove duplicates, resetting array keys
                    $updatedAdminIds = array_values(array_unique(array_merge($existingAdminIds, $admin_ids)));

                    // Remove the current user ID if it exists
                    $updatedAdminIds = array_filter($updatedAdminIds, function ($id) use ($userId) {
                        return $id != $userId; // Remove if it's the current user's ID
                    });

                    // Update the database with properly formatted JSON
                    $this->ticket_model->update($customerId, ['admin_ids' => json_encode(array_values($updatedAdminIds))]);
                }
            } else {
                log_message('error', 'Invalid data for update: admin_ids or transfer is empty.');
            }
        }

        // Return a JSON response indicating success
        return $this->response->setJSON(['response' => 'Customers transferred successfully.']);
    }
}

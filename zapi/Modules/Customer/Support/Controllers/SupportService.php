<?php

namespace Zapi\Modules\Customer\Support\Controllers;

use Zapi\Modules\Customer\Core\Services\CustomerBaseService;

class SupportService extends CustomerBaseService
{
    public function fetch()
    {
        $userRole = $this->request->getGet('user_role');
        $userId = $this->request->getGet('user_id');
        $pager = $this->getPaginationParams();
        $tickets = [];
        $totalFound = 0;

        if ($userRole === 'employee') {
            /* $userId comes straight off the query string and was interpolated into
               raw SQL with escaping explicitly disabled (the `false` third arg), so
               ?user_id=x")+OR+1=1--+ broke out of the string literal. User ids are
               numeric; casting to int makes the interpolation provably safe while
               keeping the JSON_CONTAINS semantics intact. */
            $employeeId = (int) $userId;
            $builder = $this->ticket_model->builder()
                ->select('*')
                ->where("JSON_CONTAINS(admin_ids, '\"{$employeeId}\"')", null, false)
                ->orderBy('id', 'desc');
            $totalFound = (int) $builder->countAllResults(false);
            $tickets = $builder->limit($pager['limit'], $pager['offset'])->get()->getResult();
        } else {
            $builder = $this->ticket_model
                ->select('*')
                ->where('user_id', $userId)
                ->orderBy('id', 'desc');
            $totalFound = (int) $builder->countAllResults(false);
            $tickets = $builder->limit($pager['limit'], $pager['offset'])->findAll();
        }

        return $this->respondSuccess([
            'response' => 'Tickets fetched successfully',
            'data' => $tickets,
            'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($tickets)),
        ]);
    }

    public function details()
    {
        $id = $this->request->getGet('ticket_id');
        $userRole = $this->request->getGet('user_role');
        if (empty($id)) {
            return $this->respondError('ticket_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        if (empty($userRole)) {
            return $this->respondError('user_role query parameter is required', 400, 'REQUEST_FAILED');
        }

        $details = $this->ticket_model->where('id', $id)->first();
        if (empty($details)) {
            return $this->respondError('Ticket not found', 404, 'REQUEST_FAILED');
        }

        if ($userRole != 'user') {
            $this->ticket_model->update($id, ['viewed' => 'yes']);
        }
        $user = getUserById($details->user_id);

        return $this->respondSuccess([
            'title' => 'Ticket Details',
            'details' => $details,
            'canReply' => !empty($user),
        ]);
    }

    public function sendMessage()
    {
        $id = $this->getInputValue('ticket_id');
        $userId = $this->getInputValue('current_user_id');
        $message = $this->getInputValue('message');

        $ticket = $this->ticket_model->where('id', $id)->first();
        if (empty($ticket)) {
            return $this->respondError('Ticket not found', 404, 'REQUEST_FAILED');
        }

        $details = $ticket->details ? json_decode($ticket->details) : [];
        $details[] = [
            'sender' => $userId,
            'msg' => trim((string) $message),
            'datetime' => date("d-m-y, h:i a"),
        ];

        $result = $this->ticket_model->update($id, ['details' => json_encode($details)]);
        if (!$result) {
            return $this->respondError('Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }

        if ($ticket->user_id != $userId) {
            $user = getUserById($ticket->user_id);
            sendMail(
                $user->email,
                getSetting('app_name') . ' | Support Ticket Update',
                view('emails/support-ticket-answered', [
                    'user' => $user->name,
                    'subject' => $ticket->subject,
                    'message' => 'Your support ticket from ' . getSetting("site_name") . ' has been answered',
                ])
            );
        }

        return $this->respondSuccess([
            'ticket_id' => $id,
            'user_id' => $userId,
            'message' => trim((string) $message),
            'datetime' => date("d-m-y, h:i a"),
        ]);
    }

    public function createTicket()
    {
        $userId = $this->getInputValue('user_id');
        $subject = $this->getInputValue('subject');
        $category = $this->getInputValue('category');
        $priority = $this->getInputValue('priority');
        $message = $this->getInputValue('message');

        $details = $this->user_model->where(['id' => $userId])->first();
        if (empty($details)) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }
        $admin_id = is_object($details) ? $details->admin_id : ($details['admin_id'] ?? null);

        $data = [
            'user_id' => $userId,
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'admin_ids' => json_encode($admin_id),
            'details' => json_encode([[
                'sender' => $userId,
                'datetime' => date("d-m-y, h:i a"),
                'msg' => $message,
            ]]),
            'datetime' => date("Y-m-d, H:i:s"),
        ];

        $result = $this->ticket_model->insert($data);
        if (!$result) {
            return $this->respondError('Something went wrong! Please try again', 500, 'REQUEST_FAILED');
        }

        $ticket = $this->ticket_model->where('id', $result)->first();
        $user = getUserById($userId);
        sendMail(
            $user->email,
            getSetting('app_name') . ' | Support Ticket Update',
            view('emails/support-ticket-open-closed', [
                'user' => $user->name,
                'subject' => $ticket->subject,
                'status_text' => 'has been opened',
            ])
        );

        return $this->respondSuccess(['message' => 'Ticket opened successfully']);
    }

    public function contact()
    {
        $userId = $this->request->getGet('user_id');
        if (empty($userId)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }

        if (!$this->actorCanAccessUser($userId)) {
            return $this->respondError('Forbidden', 403, 'REQUEST_FAILED');
        }

        $contact = $this->resolveSupportContactForUser((int) $userId);
        if ($contact === null) {
            return $this->respondError('Support contact not found', 404, 'REQUEST_FAILED');
        }

        return $this->respondSuccess($contact);
    }
}


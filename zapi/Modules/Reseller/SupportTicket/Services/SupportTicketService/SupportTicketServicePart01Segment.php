<?php

namespace Zapi\Modules\Reseller\SupportTicket\Services\SupportTicketService;

trait SupportTicketServicePart01Segment
{
        /**
         * GET /api/reseller/support-tickets/{resellerId}
         */
        public function fetch($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $pager = $this->getPaginationParams();
            $builder = $this->ticket_model->where('user_id', $resellerId);
            $totalFound = (int) $builder->countAllResults(false);
            $tickets = $builder
                ->orderBy('id', 'desc')
                ->findAll($pager['per_page'], $pager['offset']);
    
            return $this->respondPaginatedSuccess($tickets, $totalFound, $pager['current_page'], $pager['per_page']);
        }
    
        /**
         * GET /api/reseller/support-tickets/{resellerId}/{ticketId}
         */
        public function details($resellerId = null, $ticketId = null)
        {
            if (empty($resellerId) || empty($ticketId)) {
                return $this->respondError((string) 'Missing reseller id or ticket id', 400, 'REQUEST_FAILED');
            }
    
            $ticket = $this->ticket_model->where(['id' => $ticketId, 'user_id' => $resellerId])->first();
    
            if (empty($ticket)) {
                return $this->respondError((string) 'Ticket not found', 404, 'REQUEST_FAILED');
            }
    
            $ticketData = is_object($ticket) ? (array) $ticket : $ticket;
            $ticketData['messages'] = json_decode($ticketData['details'] ?? '[]', true);
    
            if (!empty($ticketData['messages'])) {
                foreach ($ticketData['messages'] as &$msg) {
                    $sender = getUserById($msg['sender'] ?? 0);
                    $msg['sender_name'] = $sender->name ?? '--';
                }
            }
    
            return $this->respondSuccess($ticketData);
        }
    
        /**
         * POST /api/reseller/support-tickets/{resellerId}
         */
        public function create($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $rules = [
                'subject' => 'required',
                'category' => 'required',
                'priority' => 'required',
                'message' => 'required',
            ];
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $reseller = $this->user_model->find($resellerId);
            $adminId = $reseller->admin_id ?? $resellerId;
            $assignTo = $input['assign_to'] ?? $adminId;
    
            $data = [
                'user_id' => $resellerId,
                'subject' => $input['subject'],
                'category' => $input['category'],
                'priority' => $input['priority'],
                'admin_ids' => json_encode($assignTo),
                'details' => json_encode([[
                    'sender' => (string) $resellerId,
                    'datetime' => date('d-m-y, h:i a'),
                    'msg' => $input['message'],
                ]]),
                'datetime' => date('Y-m-d, H:i:s'),
            ];
    
            $result = $this->ticket_model->insert($data);
    
            if ($result) {
                return $this->respondSuccess(['id' => $result, 'message' => 'Ticket created successfully']);
            }
    
            return $this->respondError((string) 'Insert failed', 500, 'REQUEST_FAILED');
        }
    
        /**
         * POST /api/reseller/support-tickets/{resellerId}/{ticketId}/message
         */
        public function sendMessage($resellerId = null, $ticketId = null)
        {
            if (empty($resellerId) || empty($ticketId)) {
                return $this->respondError((string) 'Missing reseller id or ticket id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            if (empty($input['message'])) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $ticket = $this->ticket_model->where(['id' => $ticketId, 'user_id' => $resellerId])->first();
            if (empty($ticket)) {
                return $this->respondError((string) 'Ticket not found', 404, 'REQUEST_FAILED');
            }
    
            $details = is_object($ticket) ? $ticket->details : ($ticket['details'] ?? '[]');
            $messages = json_decode($details, true) ?: [];
    
            $newMsg = [
                'sender' => (string) $resellerId,
                'msg' => trim($input['message']),
                'datetime' => date('d-m-y, h:i a'),
            ];
    
            $messages[] = $newMsg;
    
            $result = $this->ticket_model->update($ticketId, ['details' => json_encode($messages)]);
    
            if ($result) {
                $senderUser = getUserById($resellerId);
                $newMsg['sender_name'] = $senderUser->name ?? '--';
                return $this->respondSuccess($newMsg);
            }
    
            return $this->respondError((string) 'Failed to send message', 500, 'REQUEST_FAILED');
        }
    
        /**
         * PUT /api/reseller/support-tickets/{resellerId}/{ticketId}
         */
        public function update($resellerId = null, $ticketId = null)
        {
            if (empty($resellerId) || empty($ticketId)) {
                return $this->respondError((string) 'Missing reseller id or ticket id', 400, 'REQUEST_FAILED');
            }
    
            $ticket = $this->ticket_model->where(['id' => $ticketId, 'user_id' => $resellerId])->first();
            if (empty($ticket)) {
                return $this->respondError((string) 'Ticket not found', 404, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
    
            $rules = [
                'subject' => 'required',
                'category' => 'required',
                'priority' => 'required',
            ];
    
            if (!$this->validate($rules)) {
                return $this->respondError('Validation failed.', 400, 'VALIDATION_ERROR', (array) $this->validator->getErrors());
            }
    
            $data = [
                'subject' => $input['subject'],
                'category' => $input['category'],
                'priority' => $input['priority'],
            ];
    
            if (isset($input['status'])) {
                $data['status'] = $input['status'];
            }
            if (isset($input['remarks'])) {
                $data['remarks'] = $input['remarks'];
            }
    
            $result = $this->ticket_model->update($ticketId, $data);
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Ticket updated successfully']);
            }
    
            return $this->respondError((string) 'Update failed', 500, 'REQUEST_FAILED');
        }
    
        /**
         * DELETE /api/reseller/support-tickets/{resellerId}
         */
        public function delete($resellerId = null)
        {
            if (empty($resellerId)) {
                return $this->respondError((string) 'Missing reseller id', 400, 'REQUEST_FAILED');
            }
    
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            $ids = $input['ids'] ?? null;
    
            if (empty($ids) || !is_array($ids)) {
                return $this->respondError((string) 'Nothing selected', 400, 'REQUEST_FAILED');
            }
    
            $result = $this->ticket_model->where('user_id', $resellerId)->whereIn('id', $ids)->delete();
    
            if ($result) {
                return $this->respondSuccess(['message' => 'Deleted successfully']);
            }
    
            return $this->respondError((string) 'Delete failed', 500, 'REQUEST_FAILED');
        }
    
}

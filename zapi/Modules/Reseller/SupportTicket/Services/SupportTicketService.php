<?php

namespace Zapi\Modules\Reseller\SupportTicket\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\SupportTicket\Services\SupportTicketService\SupportTicketServicePart01Segment;

class SupportTicketService extends BaseApiController
{

    use SupportTicketServicePart01Segment;

    protected $ticket_model;
    protected $user_model;

    public function __construct()
    {
        $this->ticket_model = model('App\Models\Ticket');
        $this->user_model = model('App\Models\User');
        helper(['url', 'user']);
    }


}


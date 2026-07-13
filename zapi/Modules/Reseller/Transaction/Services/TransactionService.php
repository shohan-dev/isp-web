<?php

namespace Zapi\Modules\Reseller\Transaction\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\Transaction\Services\TransactionService\TransactionServicePart01Segment;

/* Extends ResellerBaseService to reach canAccessReseller() — see delete(). */
class TransactionService extends \Zapi\Modules\Reseller\Core\Services\ResellerBaseService
{

    use TransactionServicePart01Segment;

    protected $transaction_model;

    public function __construct()
    {
        $this->transaction_model = model('App\Models\ResellerTransactions');
        helper(['url', 'user']);
    }


}


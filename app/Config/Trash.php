<?php

namespace Config;

use App\Models\Package;
use App\Models\Ticket;
use App\Models\User;
use CodeIgniter\Config\BaseConfig;

/**
 * Entity registry for TrashService — whitelist-only cascade definitions.
 */
class Trash extends BaseConfig
{
    /** Days before a bin row is eligible for cron purge. */
    public int $retentionDays = 30;

    /**
     * tenant modes:
     *   field:<column>  — use parent row column value
     *   sadmin:<column> — getSAdminIdForUser((int) parent row column)
     *
     * @var array<string, array<string, mixed>>
     */
    public array $entities = [
        'package' => [
            'model'    => Package::class,
            'table'    => 'packages',
            'pk'       => 'id',
            'tenant'   => 'field:user_id',
            'label'    => 'package_name',
            'children' => [],
        ],
        'customer' => [
            'model'    => User::class,
            'table'    => 'users',
            'pk'       => 'id',
            'tenant'   => 'sadmin:id',
            'label'    => 'name',
            'children' => [
                ['table' => 'user_router_data', 'fk' => 'user_id'],
                ['table' => 'registrations', 'fk' => 'userid'],
            ],
        ],
        'reseller' => [
            'model'    => User::class,
            'table'    => 'users',
            'pk'       => 'id',
            'tenant'   => 'sadmin:id',
            'label'    => 'name',
            'children' => [
                ['table' => 'registrations', 'fk' => 'userid'],
                [
                    'table'    => 'users',
                    'fk'       => 'admin_id',
                    'where'    => ['role' => 'user'],
                    'children' => [
                        ['table' => 'user_router_data', 'fk' => 'user_id'],
                        ['table' => 'registrations', 'fk' => 'userid'],
                    ],
                ],
            ],
        ],
        'employee' => [
            'model'    => User::class,
            'table'    => 'users',
            'pk'       => 'id',
            'tenant'   => 'field:admin_id',
            'label'    => 'name',
            'children' => [
                ['table' => 'registrations', 'fk' => 'userid'],
            ],
        ],
        'support_ticket' => [
            'model'    => Ticket::class,
            'table'    => 'tickets',
            'pk'       => 'id',
            'tenant'   => 'sadmin:user_id',
            'label'    => 'subject',
            'children' => [],
        ],
    ];
}

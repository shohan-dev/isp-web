<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
    public array $default = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'tohidur_isp',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => true,
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
    ];

    /**
     * This database connection is used when
     * running PHPUnit database tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => 'utf8_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    /**
     * Phase I1 — MySQL read-replica group skeleton.
     *
     * When a read replica is available:
     *   1. Set READ_REPLICA_HOST, READ_REPLICA_USER, READ_REPLICA_PASS in .env.
     *   2. Change `hostname` below to match.
     *   3. Switch reporting and DataTables queries to Database::connect('read').
     *
     * Leave `hostname` as 'localhost' (= same host as default) until a real
     * replica is provisioned — it still works but gives no read-scale benefit.
     * The group is intentionally not auto-promoted to $defaultGroup; write paths
     * must stay on 'default' to avoid replica-lag bugs.
     */
    public array $read = [
        'DSN'      => '',
        'hostname' => 'localhost',   // replace with replica host when ready
        'username' => 'root',
        'password' => '',
        'database' => 'tohidur_isp',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => false,         // never throw on replica failure — fall back to default
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [
            [
                'hostname' => 'localhost',  // primary as failover if replica lags
                'username' => 'root',
                'password' => '',
                'database' => 'tohidur_isp',
                'port'     => 3306,
            ],
        ],
        'port' => 3306,
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // Phase I1: override read-replica credentials from .env when set.
        if (! empty($_ENV['READ_REPLICA_HOST'])) {
            $this->read['hostname'] = $_ENV['READ_REPLICA_HOST'];
            $this->read['username'] = $_ENV['READ_REPLICA_USER'] ?? $this->read['username'];
            $this->read['password'] = $_ENV['READ_REPLICA_PASS'] ?? $this->read['password'];
            $this->read['database'] = $_ENV['READ_REPLICA_DB']   ?? $this->read['database'];
        }
    }
}

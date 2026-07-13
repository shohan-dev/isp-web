<?php

use App\Services\PaygBillingService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * PAYG billing counts all customers (any subscription_status), not active-only.
 *
 * @internal
 */
final class PaygBillingCountTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    /** PaygBillingService::totalCustomerCount() reads via the User model, which is DBGroup-prefix-aware. */
    private string $usersTable;

    protected function setUp(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for database tests.');
        }
        parent::setUp();
        $this->usersTable = $this->db->DBPrefix . 'users';
        $this->db->query('DROP TABLE IF EXISTS ' . $this->usersTable);
        $this->db->query('CREATE TABLE ' . $this->usersTable . ' (
            id INTEGER PRIMARY KEY,
            role TEXT NOT NULL,
            admin_id INTEGER,
            subscription_status TEXT
        )');

        // Tenant sAdmin id=10
        $this->db->query("INSERT INTO " . $this->usersTable . " (id, role, admin_id, subscription_status) VALUES
            (10, 'admin', NULL, 'active'),
            (101, 'user', 10, 'active'),
            (102, 'user', 10, 'inactive'),
            (103, 'user', 10, 'expired'),
            (201, 'resellerAdmin', 10, 'active'),
            (301, 'user', 201, 'active'),
            (302, 'user', 201, 'inactive')
        ");
    }

    public function testTotalCustomerCountIncludesAllStatuses(): void
    {
        $service = new PaygBillingService();

        $this->assertSame(5, $service->totalCustomerCount(10));
    }
}

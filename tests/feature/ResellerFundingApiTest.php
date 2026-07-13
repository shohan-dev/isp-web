<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Zapi\Modules\Reseller\Funding\Services\FundingService;

/**
 * Funding API hardening: BOLA ownership + self-recharge never credits on client success.
 *
 * @internal
 */
final class ResellerFundingApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    /**
     * FundService's raw queries and the ResellerFundingModel/Payment/User
     * models used by FundingServicePart01Segment are all prefix-aware, so
     * their real table names are `{DBPrefix}<table>` (the 'tests' group sets
     * DBPrefix='db_' specifically to catch prefix-unsafe code). Bare names
     * here would create second, empty tables invisible to the app code.
     */
    private string $usersTable;
    private string $fundingTable;
    private string $paymentsTable;
    private string $fundTxTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usersTable = $this->db->DBPrefix . 'users';
        $this->fundingTable = $this->db->DBPrefix . 'reseller_funding';
        $this->paymentsTable = $this->db->DBPrefix . 'payments';
        $this->fundTxTable = $this->db->DBPrefix . 'fund_transactions';

        $this->db->query('DROP TABLE IF EXISTS ' . $this->fundTxTable);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->paymentsTable);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->fundingTable);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->usersTable);

        $this->db->query('CREATE TABLE ' . $this->usersTable . ' (
            id INTEGER PRIMARY KEY,
            fund REAL NOT NULL DEFAULT 0,
            admin_id INTEGER,
            role TEXT,
            name TEXT
        )');
        $this->db->query(
            'CREATE TABLE ' . $this->fundingTable . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer INTEGER,
                admin_id INTEGER,
                amount REAL,
                received_amount REAL,
                invoice_number TEXT,
                paid_via TEXT,
                received_date TEXT,
                comments TEXT,
                status TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
        $this->db->query(
            'CREATE TABLE ' . $this->paymentsTable . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                user_type TEXT,
                admin_id INTEGER,
                paidby INTEGER,
                invoice TEXT,
                amount REAL,
                pay_amount REAL,
                month TEXT,
                created_at TEXT,
                status TEXT,
                paid_via TEXT,
                paid_to INTEGER,
                method_trx TEXT
            )'
        );
        $this->db->query(
            'CREATE TABLE ' . $this->fundTxTable . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                amount REAL NOT NULL,
                balance_after REAL NOT NULL,
                reference TEXT,
                description TEXT,
                created_by INTEGER,
                created_at TEXT
            )'
        );
        $this->db->query('CREATE UNIQUE INDEX fund_transactions_reference ON ' . $this->fundTxTable . '(reference)');

        $this->db->query("INSERT INTO " . $this->usersTable . " (id, fund, admin_id, role, name) VALUES
            (10, 500, 1, 'admin', 'Tenant Admin'),
            (20, 100, 10, 'resellerAdmin', 'Reseller A'),
            (30, 200, 10, 'resellerAdmin', 'Reseller B')");
    }

    /**
     * Builds a real, signed Bearer token and sets it as the Authorization
     * header — matching how JwtAuthFilter actually authenticates a request in
     * production. The service's assertOwnsReseller() re-decodes this header
     * directly (Request::$globals, what the old setGlobal()-based fixture
     * simulated, is a protected property with no public getter — reading it
     * back from outside RequestTrait's own class always silently no-ops).
     */
    /** @var IncomingRequest The request last built by serviceWithJwt() — FundingService::$request is protected. */
    private IncomingRequest $lastRequest;

    private function serviceWithJwt(int $jwtUserId): FundingService
    {
        $secret = \Zapi\utils\JwtToken::secret();
        $token = \Zapi\Core\Support\Auth\JwtToken::issue(['sub' => $jwtUserId], $secret);

        $service = new FundingService();
        $request = new IncomingRequest(new \Config\App(), new \CodeIgniter\HTTP\URI('http://localhost'), null, new \CodeIgniter\HTTP\UserAgent());
        $request->setHeader('Authorization', 'Bearer ' . $token);
        $this->lastRequest = $request;

        $response = service('response');
        $logger = service('logger');
        $service->initController($request, $response, $logger);

        return $service;
    }

    private function decodeResponse($response): array
    {
        $body = (string) $response->getBody();

        return json_decode($body, true) ?? [];
    }

    public function testFetchDeniedForNonOwnerReseller(): void
    {
        $service = $this->serviceWithJwt(20);
        $response = $service->fetch(30);
        $payload = $this->decodeResponse($response);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['success'] ?? true);
    }

    public function testFetchAllowedForOwnerReseller(): void
    {
        $service = $this->serviceWithJwt(20);
        $response = $service->fetch(20);
        $payload = $this->decodeResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success'] ?? false);
    }

    public function testFetchAllowedForParentAdmin(): void
    {
        $service = $this->serviceWithJwt(10);
        $response = $service->fetch(20);
        $payload = $this->decodeResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success'] ?? false);
    }

    public function testCreateSelfRechargeNeverCreditsOnClientSuccessfulStatus(): void
    {
        $service = $this->serviceWithJwt(20);

        $request = $this->lastRequest;
        $request->setHeader('Content-Type', 'application/json');
        $request->setBody(json_encode([
            'amount' => 250,
            'paid_via' => 'Bkash',
            'status' => 'successful',
            'recharge_type' => 'self_recharge',
        ]));

        $response = $service->create(20);
        $payload = $this->decodeResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('pending', $payload['data']['status'] ?? null);
        $this->assertNotEmpty($payload['data']['payment_url'] ?? null);
        $this->assertSame(100.0, (float) $this->db->query('SELECT fund FROM ' . $this->usersTable . ' WHERE id = 20')->getRow()->fund);
        $this->assertSame(0, (int) $this->db->query('SELECT COUNT(*) AS c FROM ' . $this->fundTxTable)->getRow()->c);
    }
}

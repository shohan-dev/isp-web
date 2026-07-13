<?php

use App\Services\FundService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Validates App\Services\FundService against an in-memory SQLite DB (the CI4
 * `tests` connection). Self-contained: builds minimal `users` + `fund_transactions`
 * tables in setUp rather than running the app migrations.
 *
 * @internal
 */
final class FundServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    /**
     * FundService's raw queries and the `App\Models\FundTransaction` model
     * (used by writeLedger()) are both prefix-aware, so their real table
     * names are `{DBPrefix}users` / `{DBPrefix}fund_transactions` (the
     * 'tests' group sets DBPrefix='db_' specifically to catch prefix-unsafe
     * code). Using bare names here previously created second, empty tables
     * that only the test could see, making every assertion read stale data.
     */
    private string $usersTable;
    private string $fundTxTable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usersTable = $this->db->DBPrefix . 'users';
        $this->fundTxTable = $this->db->DBPrefix . 'fund_transactions';
        $this->db->query('DROP TABLE IF EXISTS ' . $this->fundTxTable);
        $this->db->query('DROP TABLE IF EXISTS ' . $this->usersTable);
        $this->db->query('CREATE TABLE ' . $this->usersTable . ' (id INTEGER PRIMARY KEY, fund REAL NOT NULL DEFAULT 0)');
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
        $this->db->query('INSERT INTO ' . $this->usersTable . ' (id, fund) VALUES (1, 100), (2, 0), (3, 50)');
    }

    private function fund(int $id): float
    {
        return (float) $this->db->query('SELECT fund FROM ' . $this->usersTable . ' WHERE id = ?', [$id])->getRow()->fund;
    }

    private function ledgerCount(?string $reference = null): int
    {
        if ($reference === null) {
            return (int) $this->db->query('SELECT COUNT(*) AS c FROM ' . $this->fundTxTable)->getRow()->c;
        }

        return (int) $this->db->query(
            'SELECT COUNT(*) AS c FROM ' . $this->fundTxTable . ' WHERE reference = ?',
            [$reference]
        )->getRow()->c;
    }

    public function testDeductSucceedsWhenSufficient(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->deduct(1, 30));
        $this->assertSame(70.0, $this->fund(1));
        $this->assertSame(1, $this->ledgerCount());
    }

    public function testDeductExactBalanceSucceeds(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->deduct(3, 50));
        $this->assertSame(0.0, $this->fund(3));
    }

    public function testDeductRejectsWhenInsufficientAndLeavesBalanceUnchanged(): void
    {
        $svc = new FundService($this->db);

        $this->assertFalse($svc->deduct(2, 30));
        $this->assertSame(0.0, $this->fund(2));
        $this->assertSame(0, $this->ledgerCount());

        $this->assertFalse($svc->deduct(3, 50.01));
        $this->assertSame(50.0, $this->fund(3));
    }

    public function testAddIncrementsBalance(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->add(2, 25));
        $this->assertSame(25.0, $this->fund(2));
        $this->assertSame(1, $this->ledgerCount());
    }

    public function testAddPreservesFractionalAmounts(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->add(2, 199.50));
        $this->assertSame(199.50, $this->fund(2));
    }

    public function testIdempotentAddWithSameReference(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->add(2, 40, 'payment:99', 'Gateway replay'));
        $this->assertTrue($svc->add(2, 40, 'payment:99', 'Gateway replay'));
        $this->assertSame(40.0, $this->fund(2));
        $this->assertSame(1, $this->ledgerCount('payment:99'));
    }

    public function testIdempotentDeductWithSameReference(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->deduct(1, 20, 'sub:7', 'Subscription'));
        $this->assertTrue($svc->deduct(1, 20, 'sub:7', 'Subscription'));
        $this->assertSame(80.0, $this->fund(1));
        $this->assertSame(1, $this->ledgerCount('sub:7'));
    }

    public function testBalanceAfterRecordedCorrectly(): void
    {
        $svc = new FundService($this->db);
        $svc->add(1, 10.25, 'adj:1', 'Top-up');

        $row = $this->db->query(
            'SELECT balance_after, amount FROM ' . $this->fundTxTable . ' WHERE reference = ?',
            ['adj:1']
        )->getRow();

        $this->assertSame(110.25, (float) $row->balance_after);
        $this->assertSame(10.25, (float) $row->amount);
    }

    public function testTransferMovesFundsAtomically(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->transfer(1, 2, 40, 'xfer:1'));
        $this->assertSame(60.0, $this->fund(1));
        $this->assertSame(40.0, $this->fund(2));
        $this->assertSame(1, $this->ledgerCount('xfer:1:out'));
        $this->assertSame(1, $this->ledgerCount('xfer:1:in'));
    }

    public function testTransferRolledBackWhenSenderShort(): void
    {
        $svc = new FundService($this->db);
        $this->assertFalse($svc->transfer(2, 1, 10));
        $this->assertSame(0.0, $this->fund(2));
        $this->assertSame(100.0, $this->fund(1));
        $this->assertSame(0, $this->ledgerCount());
    }

    public function testTransferIdempotentWithSharedReference(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->transfer(1, 2, 15, 'xfer:dup'));
        $this->assertTrue($svc->transfer(1, 2, 15, 'xfer:dup'));
        $this->assertSame(85.0, $this->fund(1));
        $this->assertSame(15.0, $this->fund(2));
    }

    public function testZeroOrNegativeAmountIsNoop(): void
    {
        $svc = new FundService($this->db);
        $this->assertTrue($svc->deduct(1, 0));
        $this->assertTrue($svc->deduct(1, -5));
        $this->assertSame(100.0, $this->fund(1));
        $this->assertSame(0, $this->ledgerCount());
    }
}

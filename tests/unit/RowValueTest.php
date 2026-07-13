<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * row_value(): the safe accessor for a possibly-null model ->first() result.
 * A missing DB row must yield the default, never a PHP 8 \Error on null->prop.
 */
final class RowValueTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('utility');
    }

    public function testNullOrFalseRowReturnsDefault(): void
    {
        $this->assertNull(row_value(null, 'name'));
        $this->assertSame('x', row_value(null, 'name', 'x'));
        $this->assertSame('x', row_value(false, 'name', 'x'));
    }

    public function testObjectRow(): void
    {
        $row = (object) ['name' => 'Alice', 'fund' => 0];
        $this->assertSame('Alice', row_value($row, 'name'));
        $this->assertSame(0, row_value($row, 'fund'));
        $this->assertSame('d', row_value($row, 'missing', 'd'));
    }

    public function testArrayRow(): void
    {
        $row = ['name' => 'Bob'];
        $this->assertSame('Bob', row_value($row, 'name'));
        $this->assertSame('d', row_value($row, 'missing', 'd'));
    }
}

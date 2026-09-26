<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class SelectRowTest extends TestCase
{
    protected SelectRow $object;

    protected function setUp(): void
    {
        $this->object = new SelectRow('foo', [
            'table' => 'table',
            'column' => 'col',
        ]);
    }

    public function testSet(): void
    {
        $this->assertEquals('foo', $this->object->value());

        $this->object->set(1);
        $this->assertEquals(1, $this->object->value());
    }

    public function testTableAndColumn(): void
    {
        $this->assertEquals('table', $this->object->table());
        $this->assertEquals('col', $this->object->column());
    }

    public function testTableAndColumnReturnConfiguredOptions(): void
    {
        $selectRow = new SelectRow('1', [
            'table' => 'training',
            'column' => 'id',
        ]);

        self::assertSame('training', $selectRow->table());
        self::assertSame('id', $selectRow->column());
    }

    public function testTableAndColumnDefaultToEmptyStringWhenNotConfigured(): void
    {
        $selectRow = new SelectRow('1');

        self::assertSame('', $selectRow->table());
        self::assertSame('', $selectRow->column());
    }

    public function testAnyValueIsAcceptedRegardlessOfOptions(): void
    {
        $selectRow = new SelectRow('1', [
            'table' => 'training',
            'column' => 'id',
        ]);

        $selectRow->set('999');

        self::assertSame('999', $selectRow->value());
    }
}

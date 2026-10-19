<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class IntegerTest extends TestCase
{
    private Integer $object;

    protected function setUp(): void
    {
        $this->object = new Integer(0);
    }

    public function testConstructorStoresIntegerValue(): void
    {
        $this->assertSame(0, $this->object->value());
        $this->assertSame('0', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(5);

        $this->assertSame(5, $this->object->value());
        $this->assertSame('5', $this->object->valueAsString());
    }

    #[DataProvider('numericStringProvider')]
    public function testSetFromStringCastsToInteger(string $input, int $expected): void
    {
        $this->object->setFromString($input);

        $this->assertSame($expected, $this->object->value());
    }

    /**
     * @return \Iterator<string, array<(int|string)>>
     */
    public static function numericStringProvider(): \Iterator
    {
        yield 'plain integer' => ['17', 17];
        yield 'leading zero' => ['007', 7];
        yield 'negative number' => ['-5', -5];
        yield 'non-numeric text falls back to zero' => ['abc', 0];
        yield 'float-like string is truncated' => ['3.9', 3];
    }
}

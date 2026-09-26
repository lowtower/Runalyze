<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class IntegerTest extends TestCase
{
    private Integer $object;

    protected function setUp(): void
    {
        $this->object = new Integer(0);
    }

    public function testConstructorStoresIntegerValue(): void
    {
        self::assertSame(0, $this->object->value());
        self::assertSame('0', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(5);

        self::assertSame(5, $this->object->value());
        self::assertSame('5', $this->object->valueAsString());
    }

    #[DataProvider('numericStringProvider')]
    public function testSetFromStringCastsToInteger(string $input, int $expected): void
    {
        $this->object->setFromString($input);

        self::assertSame($expected, $this->object->value());
    }

    /**
     * @return array<string, string[]|int[]>
     */
    public static function numericStringProvider(): array
    {
        return [
            'plain integer' => ['17', 17],
            'leading zero' => ['007', 7],
            'negative number' => ['-5', -5],
            'non-numeric text falls back to zero' => ['abc', 0],
            'float-like string is truncated' => ['3.9', 3],
        ];
    }
}

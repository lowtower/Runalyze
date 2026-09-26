<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class BooleanTest extends TestCase
{
    private Boolean $object;

    protected function setUp(): void
    {
        $this->object = new Boolean(true);
    }

    public function testConstructorStoresRawBooleanValue(): void
    {
        self::assertTrue($this->object->value());
        self::assertIsBool($this->object->value());
    }

    public function testValueAsStringReturnsTrueForTruthyValue(): void
    {
        self::assertSame('true', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(false);

        self::assertFalse($this->object->value());
        self::assertSame('false', $this->object->valueAsString());
    }

    public function testSetFromStringParsesLiteralTrue(): void
    {
        $this->object->set(false);
        $this->object->setFromString('true');

        self::assertTrue($this->object->value());
        self::assertSame('true', $this->object->valueAsString());
    }

    #[DataProvider('nonTrueStringProvider')]
    public function testSetFromStringTreatsAnyOtherStringAsFalse(string $input): void
    {
        $this->object->setFromString($input);

        self::assertFalse($this->object->value());
    }

    /**
     * @return array<string, string[]>
     */
    public static function nonTrueStringProvider(): array
    {
        return [
            'literal false' => ['false'],
            'uppercase TRUE is not matched' => ['TRUE'],
            'numeric one' => ['1'],
            'empty string' => [''],
        ];
    }
}

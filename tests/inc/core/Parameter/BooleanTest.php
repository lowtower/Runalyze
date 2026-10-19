<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class BooleanTest extends TestCase
{
    private Boolean $object;

    protected function setUp(): void
    {
        $this->object = new Boolean(true);
    }

    public function testConstructorStoresRawBooleanValue(): void
    {
        $this->assertTrue($this->object->value());
        $this->assertIsBool($this->object->value());
    }

    public function testValueAsStringReturnsTrueForTruthyValue(): void
    {
        $this->assertSame('true', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(false);

        $this->assertFalse($this->object->value());
        $this->assertSame('false', $this->object->valueAsString());
    }

    public function testSetFromStringParsesLiteralTrue(): void
    {
        $this->object->set(false);
        $this->object->setFromString('true');

        $this->assertTrue($this->object->value());
        $this->assertSame('true', $this->object->valueAsString());
    }

    #[DataProvider('nonTrueStringProvider')]
    public function testSetFromStringTreatsAnyOtherStringAsFalse(string $input): void
    {
        $this->object->setFromString($input);

        $this->assertFalse($this->object->value());
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function nonTrueStringProvider(): \Iterator
    {
        yield 'literal false' => ['false'];
        yield 'uppercase TRUE is not matched' => ['TRUE'];
        yield 'numeric one' => ['1'];
        yield 'empty string' => [''];
    }
}

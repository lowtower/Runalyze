<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class SelectTest extends TestCase
{
    private Select $object;

    protected function setUp(): void
    {
        $this->object = new Select('a', [
            'options' => [
                'a' => 'one',
                'b' => 'two',
                'c' => 'three',
            ],
        ]);
    }

    public function testDefaultValueIsSetFromConstructor(): void
    {
        self::assertSame('a', $this->object->value());
        self::assertSame('a', $this->object->valueAsString());
    }

    public function testSetChangesValueToAllowedOption(): void
    {
        $this->object->set('c');

        self::assertSame('c', $this->object->value());
        self::assertSame('c', $this->object->valueAsString());
    }

    public function testValueAsLongStringReturnsLabelForCurrentValue(): void
    {
        self::assertSame('one', $this->object->valueAsLongString());

        $this->object->set('c');

        self::assertSame('three', $this->object->valueAsLongString());
    }

    public function testOptionsReturnsConfiguredOptionsList(): void
    {
        self::assertSame([
            'a' => 'one',
            'b' => 'two',
            'c' => 'three',
        ], $this->object->options());
    }

    public function testSetFallsBackToDefaultForUnknownOption(): void
    {
        $this->object->set('foobar');

        self::assertSame('a', $this->object->value());
    }

    public function testSelectWithoutOptionsKeepsDefaultValue(): void
    {
        $object = new Select('');

        self::assertSame('', $object->value());
        self::assertSame('', $object->valueAsString());
    }

    public function testSelectWithoutOptionsCannotBeChanged(): void
    {
        $object = new Select('default-value');
        $object->set('anything-else');

        self::assertSame('default-value', $object->value());
    }

    public function testSetThrowsExceptionWhenFallbackIsDisabledAndOptionIsUnknown(): void
    {
        $object = new class('a', [
            'options' => [
                'a' => 'one',
            ],
        ]) extends Select {
            protected bool $UseFallback = false;
        };

        $this->expectException(\InvalidArgumentException::class);

        $object->set('unknown-option');
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class SelectTest extends TestCase
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
        $this->assertSame('a', $this->object->value());
        $this->assertSame('a', $this->object->valueAsString());
    }

    public function testSetChangesValueToAllowedOption(): void
    {
        $this->object->set('c');

        $this->assertSame('c', $this->object->value());
        $this->assertSame('c', $this->object->valueAsString());
    }

    public function testValueAsLongStringReturnsLabelForCurrentValue(): void
    {
        $this->assertSame('one', $this->object->valueAsLongString());

        $this->object->set('c');

        $this->assertSame('three', $this->object->valueAsLongString());
    }

    public function testOptionsReturnsConfiguredOptionsList(): void
    {
        $this->assertSame([
            'a' => 'one',
            'b' => 'two',
            'c' => 'three',
        ], $this->object->options());
    }

    public function testSetFallsBackToDefaultForUnknownOption(): void
    {
        $this->object->set('foobar');

        $this->assertSame('a', $this->object->value());
    }

    public function testSelectWithoutOptionsKeepsDefaultValue(): void
    {
        $object = new Select('');

        $this->assertSame('', $object->value());
        $this->assertSame('', $object->valueAsString());
    }

    public function testSelectWithoutOptionsCannotBeChanged(): void
    {
        $object = new Select('default-value');
        $object->set('anything-else');

        $this->assertSame('default-value', $object->value());
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

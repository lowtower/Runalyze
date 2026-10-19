<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class TextlineTest extends TestCase
{
    public function testConstructorStoresInitialValue(): void
    {
        $parameter = new Textline('OpenStreetMap');

        $this->assertSame('OpenStreetMap', $parameter->value());
    }

    public function testSetOverwritesValue(): void
    {
        $parameter = new Textline('initial');
        $parameter->set('updated');

        $this->assertSame('updated', $parameter->value());
    }

    public function testSetFromStringDelegatesToSet(): void
    {
        $parameter = new Textline('initial');
        $parameter->setFromString('from string');

        $this->assertSame('from string', $parameter->value());
    }

    public function testValueAsStringReturnsStringValueUnchanged(): void
    {
        $parameter = new Textline('OpenStreetMap');

        $this->assertSame('OpenStreetMap', $parameter->valueAsString());
    }

    public function testValueAsStringCastsNonStringValueToString(): void
    {
        $parameter = new Textline('irrelevant');
        $parameter->set(42);

        $this->assertSame('42', $parameter->valueAsString());
    }
}

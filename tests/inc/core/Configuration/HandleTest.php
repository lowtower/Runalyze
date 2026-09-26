<?php

declare(strict_types=1);

namespace Runalyze\Configuration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Boolean;
use Runalyze\Parameter\Integer;
use Runalyze\Parameter\SelectRow;
use Runalyze\Parameter\Textline;

#[Group('default')]
class HandleTest extends TestCase
{
    protected Handle $object;

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(Handle::class, 'TableHandles');
        $property->setValue(null, []);

        $this->object = new Handle('key', new Integer(42));
    }

    public function testKey(): void
    {
        $this->assertEquals('key', $this->object->key());
    }

    public function testObject(): void
    {
        $this->object->object()
            ->set(1337);
        $this->assertEquals(1337, $this->object->object()->value());
    }

    public function testValue(): void
    {
        $this->assertEquals(42, $this->object->value());
    }

    public function testKeyReturnsTheKeyGivenToConstructor(): void
    {
        $handle = new Handle('TRAINING_LEAFLET_LAYER', new Textline('OpenStreetMap'));

        self::assertSame('TRAINING_LEAFLET_LAYER', $handle->key());
    }

    public function testObjectReturnsTheGivenParameterInstance(): void
    {
        $parameter = new Textline('OpenStreetMap');
        $handle = new Handle('TRAINING_LEAFLET_LAYER', $parameter);

        self::assertSame($parameter, $handle->object());
    }

    public function testValueDelegatesToParameterValue(): void
    {
        $handle = new Handle('TRAINING_MAP_SHOW_FIRST', new Boolean(true));

        self::assertTrue($handle->value());
    }

    public function testProcessOnchangeEventsInvokesRegisteredCallback(): void
    {
        $handle = new Handle('SOME_KEY', new Boolean(true));
        $wasCalled = false;

        $handle->registerOnchangeEvent(function () use (&$wasCalled): void {
            $wasCalled = true;
        });
        $handle->processOnchangeEvents();

        self::assertTrue($wasCalled);
    }

    public function testProcessOnchangeEventsWithoutRegisteredCallbackDoesNothing(): void
    {
        $handle = new Handle('SOME_KEY', new Boolean(true));

        $handle->processOnchangeEvents();

        $this->expectNotToPerformAssertions();
    }

    public function testConstructingWithSelectRowRegistersItInTableHandles(): void
    {
        $parameter = new SelectRow('1', [
            'table' => 'training',
            'column' => 'id',
        ]);
        new Handle('KEY_FOR_TABLE', $parameter);

        self::assertSame([
            'KEY_FOR_TABLE' => 'training',
        ], Handle::tableHandles());
    }

    public function testConstructingWithNonSelectRowParameterDoesNotRegisterTableHandle(): void
    {
        new Handle('KEY_FOR_TEXT', new Textline('value'));

        self::assertSame([], Handle::tableHandles());
    }
}

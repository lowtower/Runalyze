<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PaceAxisMaximumTest extends \PHPUnit\Framework\TestCase
{
    protected PaceAxisMaximum $object;

    protected function setUp(): void
    {
        $this->object = new PaceAxisMaximum();
    }

    public function testDefaultValueIsAutomatic(): void
    {
        self::assertSame(PaceAxisLimit::AUTO, $this->object->value());
        self::assertTrue($this->object->automatic());
    }

    public function testSettingAStringValueDisablesAutomatic(): void
    {
        $this->object->set('900');

        self::assertSame('900', $this->object->value());
        self::assertFalse($this->object->automatic());
    }

    public function testSettingAnIntegerValueIsSilentlyCoercedInsteadOfThrowing(): void
    {
        $this->object->set(900);

        self::assertSame(900, $this->object->value());
        self::assertFalse($this->object->automatic());
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('60');
        $this->object->set('not-a-valid-pace');

        self::assertSame(PaceAxisLimit::AUTO, $this->object->value());
        self::assertTrue($this->object->automatic());
    }
}

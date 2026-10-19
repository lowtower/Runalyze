<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class PaceAxisMinimumTest extends \PHPUnit\Framework\TestCase
{
    protected PaceAxisMinimum $object;

    protected function setUp(): void
    {
        $this->object = new PaceAxisMinimum();
    }

    public function testDefaultValueIsAutomatic(): void
    {
        $this->assertSame(PaceAxisLimit::AUTO, $this->object->value());
        $this->assertTrue($this->object->automatic());
    }

    public function testSettingAStringValueDisablesAutomatic(): void
    {
        $this->object->set('60');

        $this->assertSame('60', $this->object->value());
        $this->assertFalse($this->object->automatic());
    }

    public function testSettingAnIntegerValueIsSilentlyCoercedInsteadOfThrowing(): void
    {
        $this->object->set(60);

        $this->assertSame(60, $this->object->value());
        $this->assertFalse($this->object->automatic());
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('60');
        $this->object->set('not-a-valid-pace');

        $this->assertSame(PaceAxisLimit::AUTO, $this->object->value());
        $this->assertTrue($this->object->automatic());
    }
}

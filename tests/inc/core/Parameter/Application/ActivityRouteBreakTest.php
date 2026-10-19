<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityRouteBreakTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityRouteBreak $object;

    protected function setUp(): void
    {
        $this->object = new ActivityRouteBreak();
    }

    public function testDefaultValueIsFifteenSecondsAndNeverIsFalse(): void
    {
        $this->assertSame('15', $this->object->value());
        $this->assertFalse($this->object->never());
    }

    public function testNeverReturnsTrueForNoBreakOption(): void
    {
        $this->object->set(ActivityRouteBreak::NO_BREAK);

        $this->assertSame(ActivityRouteBreak::NO_BREAK, $this->object->value());
        $this->assertTrue($this->object->never());
    }

    public function testNeverReturnsFalseForAnyTimedOption(): void
    {
        foreach (['15', '30', '60', '120', '240', '300', '600'] as $option) {
            $this->object->set($option);

            $this->assertFalse($this->object->never());
            $this->assertSame($option, $this->object->value());
        }
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set(ActivityRouteBreak::NO_BREAK);
        $this->object->set('unknown');

        $this->assertSame('15', $this->object->value());
        $this->assertFalse($this->object->never());
    }
}

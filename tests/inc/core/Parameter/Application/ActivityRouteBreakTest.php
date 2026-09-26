<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityRouteBreakTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityRouteBreak $object;

    protected function setUp(): void
    {
        $this->object = new ActivityRouteBreak();
    }

    public function testDefaultValueIsFifteenSecondsAndNeverIsFalse(): void
    {
        self::assertSame('15', $this->object->value());
        self::assertFalse($this->object->never());
    }

    public function testNeverReturnsTrueForNoBreakOption(): void
    {
        $this->object->set(ActivityRouteBreak::NO_BREAK);

        self::assertSame(ActivityRouteBreak::NO_BREAK, $this->object->value());
        self::assertTrue($this->object->never());
    }

    public function testNeverReturnsFalseForAnyTimedOption(): void
    {
        foreach (['15', '30', '60', '120', '240', '300', '600'] as $option) {
            $this->object->set($option);

            self::assertFalse($this->object->never());
            self::assertSame($option, $this->object->value());
        }
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set(ActivityRouteBreak::NO_BREAK);
        $this->object->set('unknown');

        self::assertSame('15', $this->object->value());
        self::assertFalse($this->object->never());
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Data\Cadence;

final class RunningTest extends \PHPUnit\Framework\TestCase
{
    public function testValue(): void
    {
        $Cadence = new Running(90);

        $this->assertSame(180, $Cadence->value());
    }

    public function testFactor(): void
    {
        $Cadence = new Running();

        $this->assertEquals(160, $Cadence->useFactor(80));
    }

    public function testManipulatingArray(): void
    {
        $array = [70, 80, 90, 100, 105];
        $Cadence = new Running();
        $Cadence->manipulateArray($array);

        $this->assertEquals([140, 160, 180, 200, 210], $array);
    }
}

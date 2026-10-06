<?php

declare(strict_types=1);

namespace Runalyze\Tests\Sports\Performance;

use Runalyze\Sports\Performance\Monotony;

class MonotonyTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyArray(): void
    {
        $monotony = new Monotony([]);
        $monotony->calculate();

        $this->assertEquals(0, $monotony->value());
    }

    public function testRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $monotony = new Monotony([100]);
        $monotony->value();
    }

    public function testSingleValue(): void
    {
        $monotony = new Monotony([1, 1, 1, 1, 1, 1, 1]);
        $monotony->calculate();

        $this->assertSame(Monotony::MAX, $monotony->value());
    }

    public function testSimpleExample(): void
    {
        $monotony = new Monotony([0, 20, 10, 10, 10, 10, 10]);
        $monotony->calculate();

        $this->assertSame(10 / sqrt(200 / 7), $monotony->value());
        $this->assertSame(7 * 10 * 10 / sqrt(200 / 7), $monotony->trainingStrain());
    }

    public function testAnotherSimpleExample(): void
    {
        $monotony = new Monotony([5, 15, 20, 20, 20, 25, 35]);
        $monotony->calculate();

        $this->assertEqualsWithDelta(20 / 8.45, $monotony->value(), 0.01);
        $this->assertEqualsWithDelta(7 * 20 * 20 / 8.45, $monotony->trainingStrain(), 0.1);
    }

    public function testEmptyDays(): void
    {
        $withZeroes = new Monotony([0, 0, 0, 10, 20, 30, 40]);
        $withZeroes->calculate();

        $withoutZeroes = new Monotony([10, 20, 30, 40]);
        $withoutZeroes->calculate();

        $this->assertSame($withZeroes->value(), $withoutZeroes->value());
        $this->assertSame($withZeroes->trainingStrain(), $withoutZeroes->trainingStrain());
    }
}

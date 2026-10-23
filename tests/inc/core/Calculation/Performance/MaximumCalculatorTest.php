<?php

namespace Runalyze\Calculation\Performance;

use Runalyze\Sports\Performance\Model\AbstractModel;
use Runalyze\Sports\Performance\Model\TsbModel;

final class FakeAbstractModel extends AbstractModel
{
    protected function calculateArrays(): void
    {
        foreach ($this->Trimp as $i => $Trimp) {
            $this->Fitness[$i] = 2 * $Trimp;
            $this->Fatigue[$i] = 5 * $Trimp;
        }
    }
}

final class MaximumCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testWrongClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MaximumCalculator(function (): void {}, []);
    }

    public function testNoModelInClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MaximumCalculator(fn (): \stdClass => new \stdClass(), []);
    }

    public function testTSBclosure(): void
    {
        $Calc = new MaximumCalculator(fn (array $array): TsbModel => new TsbModel($array), []);

        $this->assertSame(0, $Calc->maxFitness());
        $this->assertSame(0, $Calc->maxFatigue());
        $this->assertSame(0, $Calc->maxTrimp());
    }

    public function testFakeModel(): void
    {
        $Calc = new MaximumCalculator(fn (array $array): \Runalyze\Calculation\Performance\FakeAbstractModel => new FakeAbstractModel($array), [10, 0, 50, 100, 0, 75, 13]);

        $this->assertSame(200, $Calc->maxFitness());
        $this->assertSame(500, $Calc->maxFatigue());
        $this->assertSame(100, $Calc->maxTrimp());
    }
}

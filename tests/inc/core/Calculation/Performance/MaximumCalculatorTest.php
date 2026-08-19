<?php

namespace Runalyze\Calculation\Performance;

use Runalyze\Sports\Performance\Model\AbstractModel;
use Runalyze\Sports\Performance\Model\TsbModel;

class FakeAbstractModel extends AbstractModel
{
    protected function calculateArrays()
    {
        foreach ($this->Trimp as $i => $Trimp) {
            $this->Fitness[$i] = 2 * $Trimp;
            $this->Fatigue[$i] = 5 * $Trimp;
        }
    }
}

class MaximumCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testWrongClosure()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MaximumCalculator(function () {
        }, []);
    }

    public function testNoModelInClosure()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MaximumCalculator(function (): \stdClass {
            return new \stdClass();
        }, []);
    }

    public function testTSBclosure()
    {
        $Calc = new MaximumCalculator(function (array $array): \Runalyze\Sports\Performance\Model\TsbModel {
            return new TsbModel($array);
        }, []);

        $this->assertEquals(0, $Calc->maxFitness());
        $this->assertEquals(0, $Calc->maxFatigue());
        $this->assertEquals(0, $Calc->maxTrimp());
    }

    public function testFakeModel()
    {
        $Calc = new MaximumCalculator(function (array $array): \Runalyze\Calculation\Performance\FakeAbstractModel {
            return new FakeAbstractModel($array);
        }, [10, 0, 50, 100, 0, 75, 13]);

        $this->assertEquals(200, $Calc->maxFitness());
        $this->assertEquals(500, $Calc->maxFatigue());
        $this->assertEquals(100, $Calc->maxTrimp());
    }
}

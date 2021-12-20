<?php

namespace Runalyze\Activity;

class SelfEvaluationPerceivedEffortTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fitValuesProvider
     */
    public function testConversionFromFit($fitValue, $expected)
    {
        $this->assertEquals(
            $expected,
            SelfEvaluationPerceivedEffort::fromFitToEnum($fitValue)
        );
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        SelfEvaluationPerceivedEffort::fromFitToEnum(101);
    }

    public function fitValuesProvider()
    {
        return [
            [0,   SelfEvaluationPerceivedEffort::VERY_WEAK],
            [25,  SelfEvaluationPerceivedEffort::WEAK],
            [50,  SelfEvaluationPerceivedEffort::NORMAL],
            [75,  SelfEvaluationPerceivedEffort::STRONG],
            [100, SelfEvaluationPerceivedEffort::VERY_STRONG]
        ];
    }
}

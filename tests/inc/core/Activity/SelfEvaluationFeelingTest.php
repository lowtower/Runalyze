<?php

namespace Runalyze\Activity;

class SelfEvaluationFeelingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fitValuesProvider
     */
    public function testConversionFromFit($fitValue, $expected)
    {
        $this->assertEquals(
            $expected,
            SelfEvaluationFeeling::fromFitToEnum($fitValue)
        );
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        SelfEvaluationFeeling::fromFitToEnum(101);
    }

    public function fitValuesProvider()
    {
        return [
            [0,   SelfEvaluationFeeling::VERY_WEAK],
            [25,  SelfEvaluationFeeling::WEAK],
            [50,  SelfEvaluationFeeling::NORMAL],
            [75,  SelfEvaluationFeeling::STRONG],
            [100, SelfEvaluationFeeling::VERY_STRONG]
        ];
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Activity;

class SelfEvaluationFeelingTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('fitValuesProvider')]
    public function testConversionFromFit(int $fitValue, int $expected): void
    {
        $this->assertEquals(
            $expected,
            SelfEvaluationFeeling::fromFitToEnum($fitValue)
        );
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SelfEvaluationFeeling::fromFitToEnum(101);
    }

    /**
     * @return array<int, int[]>
     */
    public static function fitValuesProvider(): array
    {
        return [
            [0,   SelfEvaluationFeeling::VERY_WEAK],
            [25,  SelfEvaluationFeeling::WEAK],
            [50,  SelfEvaluationFeeling::NORMAL],
            [75,  SelfEvaluationFeeling::STRONG],
            [100, SelfEvaluationFeeling::VERY_STRONG],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Activity;

final class SelfEvaluationFeelingTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('fitValuesProvider')]
    public function testConversionFromFit(int $fitValue, int $expected): void
    {
        $this->assertSame(
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
     * @return \Iterator<int, array<int>>
     */
    public static function fitValuesProvider(): \Iterator
    {
        yield [0,   SelfEvaluationFeeling::VERY_WEAK];
        yield [25,  SelfEvaluationFeeling::WEAK];
        yield [50,  SelfEvaluationFeeling::NORMAL];
        yield [75,  SelfEvaluationFeeling::STRONG];
        yield [100, SelfEvaluationFeeling::VERY_STRONG];
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FitTrainingEffectBenefitTest extends TestCase
{
    /**
     * @return \Iterator<string, array<(float|int)>>
     */
    public static function validFitValuesProvider(): \Iterator
    {
        yield 'lower boundary' => [1.0, FitTrainingEffectBenefit::RECOVERY];
        yield 'lower boundary with fraction is truncated down' => [1.9, FitTrainingEffectBenefit::RECOVERY];
        yield 'base' => [2.0, FitTrainingEffectBenefit::BASE];
        yield 'pace' => [3.0, FitTrainingEffectBenefit::PACE];
        yield 'threshold' => [4.0, FitTrainingEffectBenefit::THRESHOLD];
        yield 'vo2max' => [5.0, FitTrainingEffectBenefit::VO2MAX];
        yield 'anaerobic' => [6.0, FitTrainingEffectBenefit::ANAEROBIC];
        yield 'anaerobic with fraction is truncated down' => [6.99, FitTrainingEffectBenefit::ANAEROBIC];
        yield 'upper boundary' => [7.0, FitTrainingEffectBenefit::SPRINT];
        yield 'upper boundary with fraction is truncated down' => [7.99, FitTrainingEffectBenefit::SPRINT];
    }

    #[DataProvider('validFitValuesProvider')]
    public function testFromFitToEnumReturnsExpectedEnumForValidValues(float $fitValue, int $expectedEnum): void
    {
        $this->assertSame($expectedEnum, FitTrainingEffectBenefit::fromFitToEnum($fitValue));
    }

    /**
     * @return \Iterator<string, array<float>>
     */
    public static function invalidFitValuesProvider(): \Iterator
    {
        yield 'zero' => [0.0];
        yield 'just below lower boundary' => [0.99];
        yield 'negative value' => [-1.0];
        yield 'just above upper boundary' => [8.0];
        yield 'far above upper boundary' => [100.0];
    }

    #[DataProvider('invalidFitValuesProvider')]
    public function testFromFitToEnumThrowsExceptionForInvalidValues(float $fitValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('Provided TrainingEffect benefit %s is invalid.', $fitValue));

        FitTrainingEffectBenefit::fromFitToEnum($fitValue);
    }

    /**
     * @return \Iterator<string, array<(int|string)>>
     */
    public static function labelProvider(): \Iterator
    {
        yield 'recovery' => [FitTrainingEffectBenefit::RECOVERY, 'Recovery'];
        yield 'base' => [FitTrainingEffectBenefit::BASE, 'Base'];
        yield 'pace' => [FitTrainingEffectBenefit::PACE, 'Pace'];
        yield 'threshold' => [FitTrainingEffectBenefit::THRESHOLD, 'Schwelle'];
        yield 'vo2max' => [FitTrainingEffectBenefit::VO2MAX, 'VO2Max'];
        yield 'anaerobic' => [FitTrainingEffectBenefit::ANAEROBIC, 'Anaerobic'];
        yield 'sprint' => [FitTrainingEffectBenefit::SPRINT, 'Sprint'];
    }

    #[DataProvider('labelProvider')]
    public function testLabelOfEnumReturnsExpectedLabelForKnownValues(int $enum, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, FitTrainingEffectBenefit::labelOfEnum($enum));
    }

    /**
     * @return \Iterator<string, array<int>>
     */
    public static function unknownEnumProvider(): \Iterator
    {
        yield 'zero' => [0];
        yield 'above sprint' => [8];
    }

    #[DataProvider('unknownEnumProvider')]
    public function testLabelOfEnumReturnsFallbackLabelForUnknownValues(int $enum): void
    {
        $this->assertSame(sprintf('Unknown %u', $enum), FitTrainingEffectBenefit::labelOfEnum($enum));
    }

    public function testLabelOfEnumOverflowsForNegativeValuesDueToUnsignedFormatBug(): void
    {
        $expectedOverflowedLabel = sprintf('Unknown %u', -1);

        $this->assertSame($expectedOverflowedLabel, FitTrainingEffectBenefit::labelOfEnum(-1));
    }

    /**
     * @return \Iterator<string, array<(int|string)>>
     */
    public static function descriptionProvider(): \Iterator
    {
        yield 'recovery' => [FitTrainingEffectBenefit::RECOVERY, '#0aa476', 'Recovery'];
        yield 'base' => [FitTrainingEffectBenefit::BASE, '#0aa476', 'Base'];
        yield 'pace' => [FitTrainingEffectBenefit::PACE, '#f27019', 'Pace'];
        yield 'threshold' => [FitTrainingEffectBenefit::THRESHOLD, '#f27019', 'Schwelle'];
        yield 'vo2max' => [FitTrainingEffectBenefit::VO2MAX, '#f27019', 'VO2Max'];
        yield 'anaerobic' => [FitTrainingEffectBenefit::ANAEROBIC, '#6439e4', 'Anaerobic'];
        yield 'sprint' => [FitTrainingEffectBenefit::SPRINT, '#6439e4', 'Sprint'];
    }

    #[DataProvider('descriptionProvider')]
    public function testDescriptionFromNumReturnsExpectedHtmlForKnownValues(
        int $enum,
        string $expectedColor,
        string $expectedLabel
    ): void {
        $expected = sprintf('<span style="color:%s;">%s</span>', $expectedColor, $expectedLabel);

        $this->assertSame($expected, FitTrainingEffectBenefit::descriptionFromNum($enum));
    }

    /**
     * @return \Iterator<string, array<int>>
     */
    public static function invalidDescriptionProvider(): \Iterator
    {
        yield 'zero' => [0];
        yield 'above sprint' => [8];
    }

    #[DataProvider('invalidDescriptionProvider')]
    public function testDescriptionFromNumReturnsFallbackColorForInvalidValues(int $enum): void
    {
        $expected = sprintf('<span style="color:#ffff00;">%s</span>', FitTrainingEffectBenefit::labelOfEnum($enum));

        $this->assertSame($expected, FitTrainingEffectBenefit::descriptionFromNum($enum));
    }

    public function testColorsConstantHasExactlySevenEntriesOneForEachBenefit(): void
    {
        $this->assertCount(7, FitTrainingEffectBenefit::COLORS);
    }
}

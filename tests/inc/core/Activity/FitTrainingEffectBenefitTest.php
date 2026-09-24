<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FitTrainingEffectBenefitTest extends TestCase
{
    /**
     * @return array<string, float[]|int[]>
     */
    public static function validFitValuesProvider(): array
    {
        return [
            'lower boundary' => [1.0, FitTrainingEffectBenefit::RECOVERY],
            'lower boundary with fraction is truncated down' => [1.9, FitTrainingEffectBenefit::RECOVERY],
            'base' => [2.0, FitTrainingEffectBenefit::BASE],
            'pace' => [3.0, FitTrainingEffectBenefit::PACE],
            'threshold' => [4.0, FitTrainingEffectBenefit::THRESHOLD],
            'vo2max' => [5.0, FitTrainingEffectBenefit::VO2MAX],
            'anaerobic' => [6.0, FitTrainingEffectBenefit::ANAEROBIC],
            'anaerobic with fraction is truncated down' => [6.99, FitTrainingEffectBenefit::ANAEROBIC],
            'upper boundary' => [7.0, FitTrainingEffectBenefit::SPRINT],
            'upper boundary with fraction is truncated down' => [7.99, FitTrainingEffectBenefit::SPRINT],
        ];
    }

    #[DataProvider('validFitValuesProvider')]
    public function testFromFitToEnumReturnsExpectedEnumForValidValues(float $fitValue, int $expectedEnum): void
    {
        self::assertSame($expectedEnum, FitTrainingEffectBenefit::fromFitToEnum($fitValue));
    }

    /**
     * @return array<string, float[]>
     */
    public static function invalidFitValuesProvider(): array
    {
        return [
            'zero' => [0.0],
            'just below lower boundary' => [0.99],
            'negative value' => [-1.0],
            'just above upper boundary' => [8.0],
            'far above upper boundary' => [100.0],
        ];
    }

    #[DataProvider('invalidFitValuesProvider')]
    public function testFromFitToEnumThrowsExceptionForInvalidValues(float $fitValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Provided TrainingEffect benefit %s is invalid.', $fitValue));

        FitTrainingEffectBenefit::fromFitToEnum($fitValue);
    }

    /**
     * @return array<string, int[]|string[]>
     */
    public static function labelProvider(): array
    {
        return [
            'recovery' => [FitTrainingEffectBenefit::RECOVERY, 'Recovery'],
            'base' => [FitTrainingEffectBenefit::BASE, 'Base'],
            'pace' => [FitTrainingEffectBenefit::PACE, 'Pace'],
            'threshold' => [FitTrainingEffectBenefit::THRESHOLD, 'Schwelle'],
            'vo2max' => [FitTrainingEffectBenefit::VO2MAX, 'VO2Max'],
            'anaerobic' => [FitTrainingEffectBenefit::ANAEROBIC, 'Anaerobic'],
            'sprint' => [FitTrainingEffectBenefit::SPRINT, 'Sprint'],
        ];
    }

    #[DataProvider('labelProvider')]
    public function testLabelOfEnumReturnsExpectedLabelForKnownValues(int $enum, string $expectedLabel): void
    {
        self::assertSame($expectedLabel, FitTrainingEffectBenefit::labelOfEnum($enum));
    }

    /**
     * @return array<string, int[]>
     */
    public static function unknownEnumProvider(): array
    {
        return [
            'zero' => [0],
            'above sprint' => [8],
        ];
    }

    #[DataProvider('unknownEnumProvider')]
    public function testLabelOfEnumReturnsFallbackLabelForUnknownValues(int $enum): void
    {
        self::assertSame(sprintf('Unknown %u', $enum), FitTrainingEffectBenefit::labelOfEnum($enum));
    }

    public function testLabelOfEnumOverflowsForNegativeValuesDueToUnsignedFormatBug(): void
    {
        $expectedOverflowedLabel = sprintf('Unknown %u', -1);

        self::assertSame($expectedOverflowedLabel, FitTrainingEffectBenefit::labelOfEnum(-1));
    }

    /**
     * @return array<string, int[]|string[]>
     */
    public static function descriptionProvider(): array
    {
        return [
            'recovery' => [FitTrainingEffectBenefit::RECOVERY, '#0aa476', 'Recovery'],
            'base' => [FitTrainingEffectBenefit::BASE, '#0aa476', 'Base'],
            'pace' => [FitTrainingEffectBenefit::PACE, '#f27019', 'Pace'],
            'threshold' => [FitTrainingEffectBenefit::THRESHOLD, '#f27019', 'Schwelle'],
            'vo2max' => [FitTrainingEffectBenefit::VO2MAX, '#f27019', 'VO2Max'],
            'anaerobic' => [FitTrainingEffectBenefit::ANAEROBIC, '#6439e4', 'Anaerobic'],
            'sprint' => [FitTrainingEffectBenefit::SPRINT, '#6439e4', 'Sprint'],
        ];
    }

    #[DataProvider('descriptionProvider')]
    public function testDescriptionFromNumReturnsExpectedHtmlForKnownValues(
        int $enum,
        string $expectedColor,
        string $expectedLabel
    ): void {
        $expected = sprintf('<span style="color:%s;">%s</span>', $expectedColor, $expectedLabel);

        self::assertSame($expected, FitTrainingEffectBenefit::descriptionFromNum($enum));
    }

    /**
     * @return array<string, int[]>
     */
    public static function invalidDescriptionProvider(): array
    {
        return [
            'zero' => [0],
            'above sprint' => [8],
        ];
    }

    #[DataProvider('invalidDescriptionProvider')]
    public function testDescriptionFromNumReturnsFallbackColorForInvalidValues(int $enum): void
    {
        $expected = sprintf('<span style="color:#ffff00;">%s</span>', FitTrainingEffectBenefit::labelOfEnum($enum));

        self::assertSame($expected, FitTrainingEffectBenefit::descriptionFromNum($enum));
    }

    public function testColorsConstantHasExactlySevenEntriesOneForEachBenefit(): void
    {
        self::assertCount(7, FitTrainingEffectBenefit::COLORS);
    }
}

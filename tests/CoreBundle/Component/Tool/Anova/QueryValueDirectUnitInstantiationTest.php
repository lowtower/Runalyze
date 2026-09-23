<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\BrakingGsLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\BrakingGsRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\ClimbScore;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Duration;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitHrvAnalysis;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitPerformanceConditionEnd;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitPerformanceConditionStart;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitRecoveryTime;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitTrainingEffect;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FitVo2maxEstimate;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FlightRatio;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FlightTime;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FootstrikeTypeLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FootstrikeTypeRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\GroundContactBalance;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\GroundContactTime;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\ImpactGsLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\ImpactGsRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PercentageHilly;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Power;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PronationExcursionLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PronationExcursionRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Rpe;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Trimp;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\VerticalOscillation;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherHumidity;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherPressure;
use Runalyze\Metrics\Common\Unit\Factorial;
use Runalyze\Metrics\Common\Unit\Linear;
use Runalyze\Metrics\Common\Unit\None;
use Runalyze\Metrics\Common\Unit\Simple;
use Runalyze\Metrics\GroundContactBalance\Unit\PercentLeft;
use Runalyze\Metrics\Power\Unit\Watts;
use Runalyze\Metrics\Time\Unit\Hours;

class QueryValueDirectUnitInstantiationTest extends TestCase
{
    #[DataProvider('provideClassesAndExpectedUnitTypes')]
    public function testGetValueUnitReturnsExpectedUnitType(string $className, string $expectedUnitClass): void
    {
        $instance = new $className();
        $unitSystem = $this->createMock(UnitSystem::class);

        $unit = $instance->getValueUnit($unitSystem);

        $this->assertInstanceOf($expectedUnitClass, $unit);
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function provideClassesAndExpectedUnitTypes(): array
    {
        return [
            'FitVo2maxEstimate -> None' => [FitVo2maxEstimate::class, None::class],
            'FootstrikeTypeLeft -> None' => [FootstrikeTypeLeft::class, None::class],
            'FootstrikeTypeRight -> None' => [FootstrikeTypeRight::class, None::class],
            'GroundContactTime -> Simple' => [GroundContactTime::class, Simple::class],
            'ImpactGsLeft -> Simple' => [ImpactGsLeft::class, Simple::class],
            'ImpactGsRight -> Simple' => [ImpactGsRight::class, Simple::class],
            'PercentageHilly -> Factorial' => [PercentageHilly::class, Factorial::class],
            'Power -> Watts' => [Power::class, Watts::class],
            'PronationExcursionLeft -> Simple' => [PronationExcursionLeft::class, Simple::class],
            'PronationExcursionRight -> Simple' => [PronationExcursionRight::class, Simple::class],
            'Rpe -> None' => [Rpe::class, None::class],
            'VerticalOscillation -> Factorial' => [VerticalOscillation::class, Factorial::class],
            'BrakingGsLeft -> Simple' => [BrakingGsLeft::class, Simple::class],
            'BrakingGsRight -> Simple' => [BrakingGsRight::class, Simple::class],
            'FitRecoveryTime -> Factorial' => [FitRecoveryTime::class, Factorial::class],
            'Trimp -> None' => [Trimp::class, None::class],
            'WeatherPressure -> Simple' => [WeatherPressure::class, Simple::class],
            'GroundContactBalance -> PercentLeft' => [GroundContactBalance::class, PercentLeft::class],
            'ClimbScore -> None' => [ClimbScore::class, None::class],
            'FitHrvAnalysis -> None' => [FitHrvAnalysis::class, None::class],
            'FitTrainingEffect -> None' => [FitTrainingEffect::class, None::class],
            'WeatherHumidity -> Simple' => [WeatherHumidity::class, Simple::class],
            'Duration -> Hours' => [Duration::class, Hours::class],
            'FitPerformanceConditionStart -> Linear' => [FitPerformanceConditionStart::class, Linear::class],
            'FitPerformanceConditionEnd -> Linear' => [FitPerformanceConditionEnd::class, Linear::class],
            'FlightTime -> Simple' => [FlightTime::class, Simple::class],
            'FlightRatio -> Factorial' => [FlightRatio::class, Factorial::class],
        ];
    }
}

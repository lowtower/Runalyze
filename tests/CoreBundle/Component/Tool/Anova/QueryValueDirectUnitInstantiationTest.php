<?php

declare(strict_types=1);

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

final class QueryValueDirectUnitInstantiationTest extends TestCase
{
    #[DataProvider('provideClassesAndExpectedUnitTypes')]
    public function testGetValueUnitReturnsExpectedUnitType(string $className, string $expectedUnitClass): void
    {
        $instance = new $className();
        $unitSystem = $this->createStub(UnitSystem::class);

        $unit = $instance->getValueUnit($unitSystem);

        $this->assertInstanceOf($expectedUnitClass, $unit);
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function provideClassesAndExpectedUnitTypes(): \Iterator
    {
        yield 'FitVo2maxEstimate -> None' => [FitVo2maxEstimate::class, None::class];
        yield 'FootstrikeTypeLeft -> None' => [FootstrikeTypeLeft::class, None::class];
        yield 'FootstrikeTypeRight -> None' => [FootstrikeTypeRight::class, None::class];
        yield 'GroundContactTime -> Simple' => [GroundContactTime::class, Simple::class];
        yield 'ImpactGsLeft -> Simple' => [ImpactGsLeft::class, Simple::class];
        yield 'ImpactGsRight -> Simple' => [ImpactGsRight::class, Simple::class];
        yield 'PercentageHilly -> Factorial' => [PercentageHilly::class, Factorial::class];
        yield 'Power -> Watts' => [Power::class, Watts::class];
        yield 'PronationExcursionLeft -> Simple' => [PronationExcursionLeft::class, Simple::class];
        yield 'PronationExcursionRight -> Simple' => [PronationExcursionRight::class, Simple::class];
        yield 'Rpe -> None' => [Rpe::class, None::class];
        yield 'VerticalOscillation -> Factorial' => [VerticalOscillation::class, Factorial::class];
        yield 'BrakingGsLeft -> Simple' => [BrakingGsLeft::class, Simple::class];
        yield 'BrakingGsRight -> Simple' => [BrakingGsRight::class, Simple::class];
        yield 'FitRecoveryTime -> Factorial' => [FitRecoveryTime::class, Factorial::class];
        yield 'Trimp -> None' => [Trimp::class, None::class];
        yield 'WeatherPressure -> Simple' => [WeatherPressure::class, Simple::class];
        yield 'GroundContactBalance -> PercentLeft' => [GroundContactBalance::class, PercentLeft::class];
        yield 'ClimbScore -> None' => [ClimbScore::class, None::class];
        yield 'FitHrvAnalysis -> None' => [FitHrvAnalysis::class, None::class];
        yield 'FitTrainingEffect -> None' => [FitTrainingEffect::class, None::class];
        yield 'WeatherHumidity -> Simple' => [WeatherHumidity::class, Simple::class];
        yield 'Duration -> Hours' => [Duration::class, Hours::class];
        yield 'FitPerformanceConditionStart -> Linear' => [FitPerformanceConditionStart::class, Linear::class];
        yield 'FitPerformanceConditionEnd -> Linear' => [FitPerformanceConditionEnd::class, Linear::class];
        yield 'FlightTime -> Simple' => [FlightTime::class, Simple::class];
        yield 'FlightRatio -> Factorial' => [FlightRatio::class, Factorial::class];
    }
}

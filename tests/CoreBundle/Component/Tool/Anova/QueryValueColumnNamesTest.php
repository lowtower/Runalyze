<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FootstrikeTypeLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FootstrikeTypeRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\GroundContactBalance;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\GroundContactTime;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\HeartRateAverage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\HeartRateMaximum;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\ImpactGsLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\ImpactGsRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PercentageHilly;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Power;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PronationExcursionLeft;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\PronationExcursionRight;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Rpe;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Trimp;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\VerticalOscillation;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2max;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2maxWithElevation;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherHumidity;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherPressure;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherTemperature;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherWindSpeed;

final class QueryValueColumnNamesTest extends TestCase
{
    #[DataProvider('provideClassesAndExpectedColumns')]
    public function testGetColumnReturnsExpectedColumnName(string $className, string $expectedColumn): void
    {
        $instance = new $className();

        $reflection = new \ReflectionObject($instance);
        $method = $reflection->getMethod('getColumn');

        $this->assertSame($expectedColumn, $method->invoke($instance));
    }

    public static function provideClassesAndExpectedColumns(): \Iterator
    {
        yield 'FitVo2maxEstimate' => [FitVo2maxEstimate::class, 'fitVO2maxEstimate'];
        yield 'FootstrikeTypeLeft' => [FootstrikeTypeLeft::class, 'avgFootstrikeTypeLeft'];
        yield 'FootstrikeTypeRight' => [FootstrikeTypeRight::class, 'avgFootstrikeTypeRight'];
        yield 'GroundContactTime' => [GroundContactTime::class, 'groundcontact'];
        yield 'ImpactGsLeft' => [ImpactGsLeft::class, 'avgImpactGsLeft'];
        yield 'ImpactGsRight' => [ImpactGsRight::class, 'avgImpactGsRight'];
        yield 'PercentageHilly' => [PercentageHilly::class, 'percentageHilly'];
        yield 'Power' => [Power::class, 'power'];
        yield 'PronationExcursionLeft' => [PronationExcursionLeft::class, 'avgPronationExcursionLeft'];
        yield 'PronationExcursionRight' => [PronationExcursionRight::class, 'avgPronationExcursionRight'];
        yield 'Rpe' => [Rpe::class, 'rpe'];
        yield 'VerticalOscillation' => [VerticalOscillation::class, 'verticalOscillation'];
        yield 'WeatherWindSpeed' => [WeatherWindSpeed::class, 'windSpeed'];
        yield 'BrakingGsLeft' => [BrakingGsLeft::class, 'avgBrakingGsLeft'];
        yield 'BrakingGsRight' => [BrakingGsRight::class, 'avgBrakingGsRight'];
        yield 'FitRecoveryTime' => [FitRecoveryTime::class, 'fitRecoveryTime'];
        yield 'Trimp' => [Trimp::class, 'trimp'];
        yield 'WeatherPressure' => [WeatherPressure::class, 'pressure'];
        yield 'GroundContactBalance' => [GroundContactBalance::class, 'groundcontactBalance'];
        yield 'HeartRateMaximum' => [HeartRateMaximum::class, 'pulseMax'];
        yield 'ClimbScore' => [ClimbScore::class, 'climbScore'];
        yield 'FitHrvAnalysis' => [FitHrvAnalysis::class, 'fitHrvAnalysis'];
        yield 'FitTrainingEffect' => [FitTrainingEffect::class, 'fitTrainingEffect'];
        yield 'HeartRateAverage' => [HeartRateAverage::class, 'pulseAvg'];
        yield 'WeatherHumidity' => [WeatherHumidity::class, 'humidity'];
        yield 'WeatherTemperature' => [WeatherTemperature::class, 'temperature'];
        yield 'Duration' => [Duration::class, 's'];
        yield 'FitPerformanceConditionStart' => [FitPerformanceConditionStart::class, 'fitPerformanceCondition'];
        yield 'FitPerformanceConditionEnd' => [FitPerformanceConditionEnd::class, 'fitPerformanceConditionEnd'];
        yield 'Vo2max' => [Vo2max::class, 'vo2max'];
        // Vo2maxWithElevation is the ONLY thing it overrides from Vo2max
        //    its addSelectionToQuery()/getValueUnit() are inherited unchanged (see Vo2maxAndElevationTest).
        yield 'Vo2maxWithElevation' => [Vo2maxWithElevation::class, 'vo2maxWithElevation'];
    }
}

<?php

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

class QueryValueColumnNamesTest extends TestCase
{
    #[DataProvider('provideClassesAndExpectedColumns')]
    public function testGetColumnReturnsExpectedColumnName(string $className, string $expectedColumn): void
    {
        $instance = new $className();

        $reflection = new \ReflectionObject($instance);
        $method = $reflection->getMethod('getColumn');

        $this->assertSame($expectedColumn, $method->invoke($instance));
    }

    public static function provideClassesAndExpectedColumns(): array
    {
        return [
            'FitVo2maxEstimate' => [FitVo2maxEstimate::class, 'fitVO2maxEstimate'],
            'FootstrikeTypeLeft' => [FootstrikeTypeLeft::class, 'avgFootstrikeTypeLeft'],
            'FootstrikeTypeRight' => [FootstrikeTypeRight::class, 'avgFootstrikeTypeRight'],
            'GroundContactTime' => [GroundContactTime::class, 'groundcontact'],
            'ImpactGsLeft' => [ImpactGsLeft::class, 'avgImpactGsLeft'],
            'ImpactGsRight' => [ImpactGsRight::class, 'avgImpactGsRight'],
            'PercentageHilly' => [PercentageHilly::class, 'percentageHilly'],
            'Power' => [Power::class, 'power'],
            'PronationExcursionLeft' => [PronationExcursionLeft::class, 'avgPronationExcursionLeft'],
            'PronationExcursionRight' => [PronationExcursionRight::class, 'avgPronationExcursionRight'],
            'Rpe' => [Rpe::class, 'rpe'],
            'VerticalOscillation' => [VerticalOscillation::class, 'verticalOscillation'],
            'WeatherWindSpeed' => [WeatherWindSpeed::class, 'windSpeed'],
            'BrakingGsLeft' => [BrakingGsLeft::class, 'avgBrakingGsLeft'],
            'BrakingGsRight' => [BrakingGsRight::class, 'avgBrakingGsRight'],
            'FitRecoveryTime' => [FitRecoveryTime::class, 'fitRecoveryTime'],
            'Trimp' => [Trimp::class, 'trimp'],
            'WeatherPressure' => [WeatherPressure::class, 'pressure'],
            'GroundContactBalance' => [GroundContactBalance::class, 'groundcontactBalance'],
            'HeartRateMaximum' => [HeartRateMaximum::class, 'pulseMax'],
            'ClimbScore' => [ClimbScore::class, 'climbScore'],
            'FitHrvAnalysis' => [FitHrvAnalysis::class, 'fitHrvAnalysis'],
            'FitTrainingEffect' => [FitTrainingEffect::class, 'fitTrainingEffect'],
            'HeartRateAverage' => [HeartRateAverage::class, 'pulseAvg'],
            'WeatherHumidity' => [WeatherHumidity::class, 'humidity'],
            'WeatherTemperature' => [WeatherTemperature::class, 'temperature'],
            'Duration' => [Duration::class, 's'],
            'FitPerformanceConditionStart' => [FitPerformanceConditionStart::class, 'fitPerformanceCondition'],
            'FitPerformanceConditionEnd' => [FitPerformanceConditionEnd::class, 'fitPerformanceConditionEnd'],
            'Vo2max' => [Vo2max::class, 'vo2max'],
            // Vo2maxWithElevation is the ONLY thing it overrides from Vo2max
            //    its addSelectionToQuery()/getValueUnit() are inherited unchanged (see Vo2maxAndElevationTest).
            'Vo2maxWithElevation' => [Vo2maxWithElevation::class, 'vo2maxWithElevation'],
        ];
    }
}

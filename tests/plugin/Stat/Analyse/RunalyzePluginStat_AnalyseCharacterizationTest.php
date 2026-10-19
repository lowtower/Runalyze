<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Analyse.
 */
#[Group('plugin')]
final class RunalyzePluginStat_AnalyseCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Analyse $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginStat_Analyse::class)
            ->newInstanceWithoutConstructor();
    }

    #[DataProvider('provideBarPercentages')]
    public function testGetBarForCalculatesWidthAndOpacity(float $percentage, string $expectedHtml): void
    {
        $html = $this->callGetBarFor($percentage);

        $this->assertSame($expectedHtml, $html);
    }

    /**
     * @return \Iterator<string, array<(float|string)>>
     */
    public static function provideBarPercentages(): \Iterator
    {
        yield 'zero' => [0.0, '<div class="analysis-bar" style="width:0%;opacity:0;"></div>'];
        yield 'fifty percent' => [50.0, '<div class="analysis-bar" style="width:50%;opacity:0.5;"></div>'];
        yield 'exactly at clamp boundary' => [95.0, '<div class="analysis-bar" style="width:95%;opacity:0.95;"></div>'];
    }

    public function testGetBarForClampsWidthAtNinetyFiveButNotOpacity(): void
    {
        $html = $this->callGetBarFor(120.0);

        $this->assertSame('<div class="analysis-bar" style="width:95%;opacity:1.2;"></div>', $html);
    }

    public function testSetGroupDataInitializesAndAccumulatesForNewGroupAndTimer(): void
    {
        $data = [];
        $result = [
            'group' => 5,
            'timer' => 3,
            'num' => 2,
            'distance' => 10.0,
            's' => 1800,
        ];

        $this->callSetGroupData($data, $result);

        $this->assertSame(
            [
                5 => [
                    3 => [
                        'num' => 2,
                        'distance' => 10.0,
                        's' => 1800,
                    ],
                ],
            ],
            $data
        );
    }

    public function testSetGroupDataAccumulatesMultipleResultsForSameGroupAndTimer(): void
    {
        $data = [];
        $first = [
            'group' => 5,
            'timer' => 3,
            'num' => 2,
            'distance' => 10.0,
            's' => 1800,
        ];
        $second = [
            'group' => 5,
            'timer' => 3,
            'num' => 1,
            'distance' => 5.0,
            's' => 900,
        ];

        $this->callSetGroupData($data, $first);
        $this->callSetGroupData($data, $second);

        $this->assertSame(
            [
                5 => [
                    3 => [
                        'num' => 3,
                        'distance' => 15.0,
                        's' => 2700,
                    ],
                ],
            ],
            $data
        );
    }

    public function testSetGroupDataKeepsSeparateEntriesForDifferentTimers(): void
    {
        $data = [];
        $first = [
            'group' => 5,
            'timer' => 3,
            'num' => 2,
            'distance' => 10.0,
            's' => 1800,
        ];
        $second = [
            'group' => 5,
            'timer' => 4,
            'num' => 1,
            'distance' => 5.0,
            's' => 900,
        ];

        $this->callSetGroupData($data, $first);
        $this->callSetGroupData($data, $second);

        $this->assertSame(
            [
                5 => [
                    3 => [
                        'num' => 2,
                        'distance' => 10.0,
                        's' => 1800,
                    ],
                    4 => [
                        'num' => 1,
                        'distance' => 5.0,
                        's' => 900,
                    ],
                ],
            ],
            $data
        );
    }

    public function testSetSumDataAccumulatesAllTimerAndIdSums(): void
    {
        $data = [
            'all_sum_km' => 0,
            'all_sum_s' => 0,
            'timer_sum_km' => [],
            'timer_sum_s' => [],
            'id_sum_km' => [],
            'id_sum_s' => [],
        ];
        $result = [
            'group' => 5,
            'timer' => 3,
            'num' => 2,
            'distance' => 10.0,
            's' => 1800,
        ];

        $this->callSetSumData($data, $result);

        $this->assertEqualsWithDelta(10.0, $data['all_sum_km'], PHP_FLOAT_EPSILON);
        $this->assertSame(1800, $data['all_sum_s']);
        $this->assertEqualsWithDelta(10.0, $data['timer_sum_km'][3], PHP_FLOAT_EPSILON);
        $this->assertSame(1800, $data['timer_sum_s'][3]);
        $this->assertSame(2, $data['id_sum_num'][5]);
        $this->assertEqualsWithDelta(10.0, $data['id_sum_km'][5], PHP_FLOAT_EPSILON);
        $this->assertSame(1800, $data['id_sum_s'][5]);
    }

    public function testSetSumDataAccumulatesAcrossMultipleCallsForSameGroup(): void
    {
        $data = [
            'all_sum_km' => 0,
            'all_sum_s' => 0,
            'timer_sum_km' => [],
            'timer_sum_s' => [],
            'id_sum_km' => [],
            'id_sum_s' => [],
        ];
        $first = [
            'group' => 5,
            'timer' => 3,
            'num' => 2,
            'distance' => 10.0,
            's' => 1800,
        ];
        $second = [
            'group' => 5,
            'timer' => 3,
            'num' => 1,
            'distance' => 4.0,
            's' => 600,
        ];

        $this->callSetSumData($data, $first);
        $this->callSetSumData($data, $second);

        $this->assertEqualsWithDelta(14.0, $data['all_sum_km'], PHP_FLOAT_EPSILON);
        $this->assertSame(3, $data['id_sum_num'][5]);
        $this->assertEqualsWithDelta(14.0, $data['id_sum_km'][5], PHP_FLOAT_EPSILON);
    }

    public function testInitTimerForSpecificYearGroupsByMonth(): void
    {
        $this->setYear(2024);

        $this->callInitTimer();

        $this->assertSame('MONTH', $this->getProtectedProperty('Timer'));
        $this->assertSame(1, $this->getProtectedProperty('TimerStart'));
        $this->assertSame(12, $this->getProtectedProperty('TimerEnd'));
        $this->assertSame('MONTH(FROM_UNIXTIME(`time`))', $this->getProtectedProperty('GroupTime'));
        $this->assertStringContainsString("UNIX_TIMESTAMP('2024-01-01')", (string) $this->getProtectedProperty('WhereTime'));
        $this->assertStringContainsString("UNIX_TIMESTAMP('2025-01-01')", (string) $this->getProtectedProperty('WhereTime'));
    }

    public function testInitTimerForSpecificYearComputesColspanFromMonthRange(): void
    {
        $this->setYear(2024);

        $this->callInitTimer();

        // Colspan = TimerEnd - TimerStart + 3 = 12 - 1 + 3 = 14
        $this->assertSame(14, $this->getProtectedProperty('Colspan'));
    }

    public function testInitTimerForLast6MonthsGroupsByMonthWithoutYearRestriction(): void
    {
        $this->setYear(6);

        $this->callInitTimer();

        $this->assertSame('MONTH', $this->getProtectedProperty('Timer'));
        $this->assertSame(1, $this->getProtectedProperty('TimerStart'));
        $this->assertSame(12, $this->getProtectedProperty('TimerEnd'));
        $this->assertStringContainsString('`time` >', (string) $this->getProtectedProperty('WhereTime'));
    }

    public function testInitTimerForLast12MonthsGroupsByMonthWithoutYearRestriction(): void
    {
        $this->setYear(12);

        $this->callInitTimer();

        $this->assertSame('MONTH', $this->getProtectedProperty('Timer'));
        $this->assertStringContainsString('`time` >', (string) $this->getProtectedProperty('WhereTime'));
    }

    public function testInitTimerForAllYearsGroupsByYearFromStartYearToCurrentYear(): void
    {
        $this->setYear(-1);

        $this->callInitTimer();

        $this->assertSame('YEAR', $this->getProtectedProperty('Timer'));
        $this->assertSame((int) START_YEAR, $this->getProtectedProperty('TimerStart'));
        $this->assertSame((int) date('Y'), $this->getProtectedProperty('TimerEnd'));
        $this->assertSame('YEAR(FROM_UNIXTIME(`time`))', $this->getProtectedProperty('GroupTime'));
        $this->assertSame('', $this->getProtectedProperty('WhereTime'));
    }

    public function testInitTimerForAllYearsComputesColspanFromYearRange(): void
    {
        $this->setYear(-1);

        $this->callInitTimer();

        $expectedColspan = ((int) date('Y')) - ((int) START_YEAR) + 3;
        $this->assertSame($expectedColspan, $this->getProtectedProperty('Colspan'));
    }

    #[DataProvider('provideKnownDatValues')]
    public function testGetAnalysisTypeReturnsLabelForKnownDatValue(string $dat, string $expectedLabel): void
    {
        $this->setDat($dat);

        $this->assertSame($expectedLabel, $this->callGetAnalysisType());
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function provideKnownDatValues(): \Iterator
    {
        yield 'empty string means in percent' => ['', 'in percent'];
        yield 'km means by distance' => ['km', 'by distance'];
        yield 's means by time' => ['s', 'by time'];
    }

    public function testGetAnalysisTypeFallsBackToDefaultForUnknownDatValue(): void
    {
        $this->setDat('some_unexpected_value');

        $this->assertSame('in percent', $this->callGetAnalysisType());
    }

    #[DataProvider('providePacegroupStepValues')]
    public function testGetPaceStepAndCeilCorrectionUsesConfiguredStepWhenPositive(int $configuredStep, int $speedMin, int $expectedStep, int $expectedCeilCorr): void
    {
        $this->setConfigurationValue('pacegroup_step', $configuredStep);

        [$step, $ceilCorr] = $this->callGetPaceStepAndCeilCorrection($speedMin);

        $this->assertSame($expectedStep, $step);
        $this->assertSame($expectedCeilCorr, $ceilCorr);
    }

    /**
     * @return \Iterator<string, array<int>>
     */
    public static function providePacegroupStepValues(): \Iterator
    {
        yield 'default step of 15' => [15, 450, 15, 0];
        yield 'step of 7 with remainder' => [7, 450, 7, 450 % 7];
    }

    public function testGetPaceStepAndCeilCorrectionClampsZeroStepToOneInsteadOfCrashing(): void
    {
        $this->setConfigurationValue('pacegroup_step', 0);

        [$step, $ceilCorr] = $this->callGetPaceStepAndCeilCorrection(450);

        $this->assertSame(1, $step);
        $this->assertSame(0, $ceilCorr);
    }

    /**
     * Same regression, for a negative configured value.
     */
    public function testGetPaceStepAndCeilCorrectionClampsNegativeStepToOne(): void
    {
        $this->setConfigurationValue('pacegroup_step', -5);

        [$step, $ceilCorr] = $this->callGetPaceStepAndCeilCorrection(450);

        $this->assertSame(1, $step);
        $this->assertSame(0, $ceilCorr);
    }

    private function callGetPaceStepAndCeilCorrection(int $speedMin): array
    {
        $closure = \Closure::bind(
            fn (int $speedMin): array => $this->getPaceStepAndCeilCorrection($speedMin),
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        return $closure($speedMin);
    }

    private function setConfigurationValue(string $key, mixed $value): void
    {
        $valueObject = new PluginConfigurationValueInt($key);
        $valueObject->setValue($value);

        $configuration = new PluginConfiguration(0);
        $configuration->addValue($valueObject);

        $catchedValuesProperty = new \ReflectionProperty(PluginConfiguration::class, 'CatchedValues');
        $catchedValuesProperty->setValue($configuration, true);

        $setConfigurationMethod = new \ReflectionMethod(RunalyzePluginStat_Analyse::class, 'setConfiguration');
        $setConfigurationMethod->invoke($this->plugin, $configuration);
    }

    private function callGetAnalysisType(): string
    {
        $closure = \Closure::bind(
            fn (): string => $this->getAnalysisType(),
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        return $closure();
    }

    private function setDat(string $dat): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'dat');
        $property->setValue($this->plugin, $dat);
    }

    private function callGetBarFor(float $percentage): string
    {
        $closure = \Closure::bind(
            fn (float $percentage): string => $this->getBarFor($percentage),
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        return $closure($percentage);
    }

    /**
     * @param array<string, int>|array<string, float> $result
     */
    private function callSetGroupData(array &$data, array &$result): void
    {
        $closure = \Closure::bind(
            function (array &$data, array &$result): void {
                $this->setGroupData($data, $result);
            },
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        $closure($data, $result);
    }

    /**
     * @param array<string, int>|array<string, float> $result
     */
    private function callSetSumData(array &$data, array &$result): void
    {
        $closure = \Closure::bind(
            function (array &$data, array &$result): void {
                $this->setSumData($data, $result);
            },
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        $closure($data, $result);
    }

    private function callInitTimer(): void
    {
        $closure = \Closure::bind(
            function (): void {
                $this->initTimer();
            },
            $this->plugin,
            RunalyzePluginStat_Analyse::class
        );

        $closure();
    }

    private function setYear(int $year): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'year');
        $property->setValue($this->plugin, $year);
    }

    private function getProtectedProperty(string $name): mixed
    {
        $property = new \ReflectionProperty(RunalyzePluginStat_Analyse::class, $name);

        return $property->getValue($this->plugin);
    }
}

<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Statistiken.
 */
#[Group('plugin')]
final class RunalyzePluginStat_StatistikenCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Statistiken $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginStat_Statistiken::class)
            ->newInstanceWithoutConstructor();
    }

    public function testWantToShowAllWeeksIsTrueWhenDatIsAllWeeks(): void
    {
        $this->setDat('allWeeks');

        $this->assertTrue($this->callWantToShowAllWeeks());
    }

    public function testWantToShowAllWeeksIsFalseForEmptyDat(): void
    {
        $this->setDat('');

        $this->assertFalse($this->callWantToShowAllWeeks());
    }

    public function testWantToShowAllWeeksIsFalseForAnyOtherDat(): void
    {
        $this->setDat('something_else');

        $this->assertFalse($this->callWantToShowAllWeeks());
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
        yield 'empty string means general overview' => ['', 'General overview'];
        yield 'allWeeks means all training weeks' => ['allWeeks', 'All training weeks'];
    }

    public function testGetAnalysisTypeFallsBackToDefaultForUnknownDatValue(): void
    {
        $this->setDat('some_unexpected_value');

        $this->assertSame('General overview', $this->callGetAnalysisType());
    }

    public function testSkipWeekTableIsTrueForAllYearsUnlessShowingAllWeeks(): void
    {
        $this->setYear(-1);

        $this->assertTrue($this->callSkipWeekTable(false));
        $this->assertFalse($this->callSkipWeekTable(true));
    }

    public function testSkipWeekTableIsTrueForPastSpecificYearUnlessShowingAllWeeks(): void
    {
        $this->setYear(((int) date('Y')) - 1);

        $this->assertTrue($this->callSkipWeekTable(false));
        $this->assertFalse($this->callSkipWeekTable(true));
    }

    public function testSkipWeekTableIsFalseForCurrentYear(): void
    {
        $this->setYear((int) date('Y'));

        $this->assertFalse($this->callSkipWeekTable(false));
    }

    public function testSkipWeekTableIsFalseForLast6MonthsRange(): void
    {
        $this->setYear(6);

        $this->assertFalse($this->callSkipWeekTable(false));
    }

    public function testSkipWeekTableIsFalseForLast12MonthsRange(): void
    {
        $this->setYear(12);

        $this->assertFalse($this->callSkipWeekTable(false));
    }

    private function setDat(string $dat): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'dat');
        $property->setValue($this->plugin, $dat);
    }

    private function setYear(int $year): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'year');
        $property->setValue($this->plugin, $year);
    }

    private function callWantToShowAllWeeks(): bool
    {
        $closure = \Closure::bind(
            fn (): bool => $this->wantToShowAllWeeks(),
            $this->plugin,
            RunalyzePluginStat_Statistiken::class
        );

        return $closure();
    }

    private function callGetAnalysisType(): string
    {
        $closure = \Closure::bind(
            fn (): string => $this->getAnalysisType(),
            $this->plugin,
            RunalyzePluginStat_Statistiken::class
        );

        return $closure();
    }

    private function callSkipWeekTable(bool $showAllWeeks): bool
    {
        $closure = \Closure::bind(
            fn (bool $showAllWeeks): bool => $this->skipWeekTable($showAllWeeks),
            $this->plugin,
            RunalyzePluginStat_Statistiken::class
        );

        return $closure($showAllWeeks);
    }
}

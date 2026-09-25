<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginPanel_Rechenspiele.
 */
#[Group('plugin')]
class RunalyzePluginPanel_RechenspieleCharacterizationTest extends TestCase
{
    private RunalyzePluginPanel_Rechenspiele $plugin;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginPanel_Rechenspiele::class))
            ->newInstanceWithoutConstructor();
    }

    public function testGetNoEffectiveVO2maxDataErrorReturnsBothExplanationLines(): void
    {
        $error = $this->plugin->getNoEffectiveVO2maxDataError();

        $plainText = strip_tags(str_replace('<sub>2</sub>', '2', $error));

        $this->assertStringContainsString('There are no current activities with an estimated', $plainText);
        $this->assertStringContainsString('VO2max can only be estimated for runs with heart rate data', $plainText);
    }

    public function testGetNoEffectiveVO2maxDataErrorJoinsBothLinesWithLineBreak(): void
    {
        $error = $this->plugin->getNoEffectiveVO2maxDataError();

        $this->assertStringContainsString('<br>', $error);
    }

    public function testTooltipFormatStringsWithEscapedPercentSignDoNotThrow(): void
    {
        $atlTooltip = sprintf(__('Current value: %s<br>Maximal value: %s<br>as percentage: %s%%'), 27, 100, 27);
        $tsbTooltip = sprintf(__('absolute: %s<br>as percentage: %s%%'), '80 - 27 = +53', 53);
        $doneByTooltip = sprintf(__('done by %s%%'), 63);

        $this->assertSame('Current value: 27<br>Maximal value: 100<br>as percentage: 27%', $atlTooltip);
        $this->assertSame('absolute: 80 - 27 = +53<br>as percentage: 53%', $tsbTooltip);
        $this->assertSame('done by 63%', $doneByTooltip);
    }

    public function testTsbModelCalculatesFitnessFatigueAndPerformanceForKnownTrimpData(): void
    {
        $TSBmodel = new \Runalyze\Sports\Performance\Model\TsbModel(
            [
                -2 => 100.0,
                0 => 50.0,
            ],
            42,
            7
        );
        $TSBmodel->calculate();

        $this->assertSame(7, $TSBmodel->fitnessAt(0));
        $this->assertSame(27, $TSBmodel->fatigueAt(0));
        $this->assertSame(-20, $TSBmodel->performanceAt(0));
    }

    public function testMonotonyIsHighForSimilarDailyTrimpValues(): void
    {
        $Monotony = new \Runalyze\Sports\Performance\Monotony(
            [50.0, 60.0, 55.0, 45.0, 50.0, 55.0, 60.0],
            120.0
        );
        $Monotony->calculate();

        $this->assertEqualsWithDelta(10.0, $Monotony->value(), 0.01);
    }

    public function testMonotonyIsLowForVariedDailyTrimpValues(): void
    {
        $Monotony = new \Runalyze\Sports\Performance\Monotony(
            [10.0, 90.0, 20.0, 80.0, 15.0, 85.0, 30.0],
            120.0
        );
        $Monotony->calculate();

        $this->assertEqualsWithDelta(1.41, $Monotony->value(), 0.01);
    }
}

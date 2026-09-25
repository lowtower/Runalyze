<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Activity\Distance;

/**
 * Characterization test for RunalyzePluginStat_Hoehenmeter.
 */
#[Group('plugin')]
class RunalyzePluginStat_HoehenmeterCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Hoehenmeter $plugin;

    private string $originalDecimalSeparator;

    private string $originalThousandsSeparator;

    private int $originalDefaultDecimals;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginStat_Hoehenmeter::class))
            ->newInstanceWithoutConstructor();

        $this->originalDecimalSeparator = Distance::$DecimalSeparator;
        $this->originalThousandsSeparator = Distance::$ThousandsSeparator;
        $this->originalDefaultDecimals = Distance::$DefaultDecimals;

        Distance::$DecimalSeparator = ',';
        Distance::$ThousandsSeparator = '.';
        Distance::$DefaultDecimals = 3;
    }

    protected function tearDown(): void
    {
        Distance::$DecimalSeparator = $this->originalDecimalSeparator;
        Distance::$ThousandsSeparator = $this->originalThousandsSeparator;
        Distance::$DefaultDecimals = $this->originalDefaultDecimals;
    }

    public function testLabelForReturnsRouteWithTitleInParenthesesWhenBothArePresent(): void
    {
        $this->assertSame(
            "Alpe d'Huez (<em>Summer climb</em>)",
            $this->callLabelFor("Alpe d'Huez", 'Summer climb')
        );
    }

    public function testLabelForReturnsRouteOnlyWhenTitleIsEmpty(): void
    {
        $this->assertSame(
            "Alpe d'Huez",
            $this->callLabelFor("Alpe d'Huez", '')
        );
    }

    public function testLabelForReturnsItalicTitleWhenRouteIsEmpty(): void
    {
        $this->assertSame(
            '<em>Summer climb</em>',
            $this->callLabelFor('', 'Summer climb')
        );
    }

    public function testLabelForReturnsUnlabeledWhenBothAreEmpty(): void
    {
        $this->assertSame(
            '<em>unlabeled</em>',
            $this->callLabelFor('', '')
        );
    }

    public function testFormatMostElevationCellComputesGradeFromElevationAndDistance(): void
    {
        $html = $this->callFormatMostElevationCell([
            'elevation' => 500,
            'distance' => 10.0,
        ]);

        $this->assertStringContainsString('500&nbsp;m', $html);
        $this->assertStringContainsString('5&nbsp;%', $html);
        $this->assertStringContainsString('10,000&nbsp;km', $html);
    }

    public function testFormatMostElevationCellHandlesZeroDistanceWithoutDivisionByZero(): void
    {
        $html = $this->callFormatMostElevationCell([
            'elevation' => 500,
            'distance' => 0.0,
        ]);

        $this->assertStringContainsString('0&nbsp;%', $html);
    }

    public function testFormatSteepestRouteCellUsesPrecomputedGradientDirectly(): void
    {
        $html = $this->callFormatSteepestRouteCell([
            'elevation' => 500,
            'distance' => 10.0,
            'gradient' => 50.0,
        ]);

        $this->assertStringContainsString('5&nbsp;%', $html);
        $this->assertStringContainsString('500&nbsp;m', $html);
        $this->assertStringContainsString('10,000&nbsp;km', $html);
    }

    public function testFormatClimbScoreCellUsesPrecomputedGradientDirectly(): void
    {
        $html = $this->callFormatClimbScoreCell([
            'elevation' => 500,
            'distance' => 10.0,
            'gradient' => 50.0,
        ]);

        $this->assertStringContainsString('5&nbsp;%', $html);
        $this->assertStringContainsString('500&nbsp;m', $html);
        $this->assertStringContainsString('10,000&nbsp;km', $html);
    }

    private function callLabelFor(string $route, string $title): string
    {
        $closure = \Closure::bind(
            fn (string $route, string $title): string => $this->labelFor($route, $title),
            $this->plugin,
            RunalyzePluginStat_Hoehenmeter::class
        );

        return $closure($route, $title);
    }

    /**
     * @param array<string, int>|array<string, float> $activityData
     */
    private function callFormatMostElevationCell(array $activityData): string
    {
        $closure = \Closure::bind(
            fn (array $activityData): string => $this->formatMostElevationCell($activityData),
            $this->plugin,
            RunalyzePluginStat_Hoehenmeter::class
        );

        return $closure($activityData);
    }

    /**
     * @param array<string, int>|array<string, float> $activityData
     */
    private function callFormatSteepestRouteCell(array $activityData): string
    {
        $closure = \Closure::bind(
            fn (array $activityData): string => $this->formatSteepestRouteCell($activityData),
            $this->plugin,
            RunalyzePluginStat_Hoehenmeter::class
        );

        return $closure($activityData);
    }

    /**
     * @param array<string, int>|array<string, float> $activityData
     */
    private function callFormatClimbScoreCell(array $activityData): string
    {
        $closure = \Closure::bind(
            fn (array $activityData): string => $this->formatClimbScoreCell($activityData),
            $this->plugin,
            RunalyzePluginStat_Hoehenmeter::class
        );

        return $closure($activityData);
    }
}

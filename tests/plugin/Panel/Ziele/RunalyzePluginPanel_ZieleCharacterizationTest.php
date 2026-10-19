<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Activity\Distance;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Characterization test for RunalyzePluginPanel_Ziele.
 */
#[Group('plugin')]
final class RunalyzePluginPanel_ZieleCharacterizationTest extends TestCase
{
    private RunalyzePluginPanel_Ziele $plugin;

    private string $originalDecimalSeparator;

    private string $originalThousandsSeparator;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginPanel_Ziele::class)
            ->newInstanceWithoutConstructor();

        $this->originalDecimalSeparator = Distance::$DecimalSeparator;
        $this->originalThousandsSeparator = Distance::$ThousandsSeparator;
        Distance::$DecimalSeparator = ',';
        Distance::$ThousandsSeparator = '.';
        Configuration::General()->distanceUnitSystem()->set(DistanceUnitSystem::METRIC);
    }

    protected function tearDown(): void
    {
        Distance::$DecimalSeparator = $this->originalDecimalSeparator;
        Distance::$ThousandsSeparator = $this->originalThousandsSeparator;
    }

    public function testGetLevelStyleForHeadlineLevel(): void
    {
        $this->assertSame(' style="font-weight:bold;"', $this->callPrivateMethod('getLevelStyle', 1));
    }

    public function testGetLevelStyleForLineLevel(): void
    {
        $this->assertSame(' style="padding-left: 10px;"', $this->callPrivateMethod('getLevelStyle', 2));
    }

    public function testGetLevelStyleForUnknownLevelIsEmpty(): void
    {
        $this->assertSame('', $this->callPrivateMethod('getLevelStyle', 3));
    }

    public function testAddHeadlineAppendsLevelOneEntryWithTopBorderByDefault(): void
    {
        $this->callPrivateMethod('addHeadline', 'Current', 12.5, 3);

        $this->assertSame(
            [[
                'name' => 'Current',
                'lvl' => 1,
                'sep' => true,
                'km' => 12.5,
                'anz' => 3,
            ]],
            $this->getLinesProperty()
        );
    }

    public function testAddHeadlineOmitsTopBorderWhenRequested(): void
    {
        $this->callPrivateMethod('addHeadline', 'Current', 12.5, 3, true);

        $this->assertSame(
            [[
                'name' => 'Current',
                'lvl' => 1,
                'sep' => false,
                'km' => 12.5,
                'anz' => 3,
            ]],
            $this->getLinesProperty()
        );
    }

    public function testAddLineAppendsLevelTwoEntryWithoutSepKey(): void
    {
        $this->callPrivateMethod('addLine', 'Some line', 5.0, 2);

        $this->assertSame(
            [[
                'name' => 'Some line',
                'lvl' => 2,
                'km' => 5.0,
                'anz' => 2,
            ]],
            $this->getLinesProperty()
        );
    }

    public function testAddBarAppendsBarTypeEntry(): void
    {
        $this->callPrivateMethod('addBar', 'Me', 30.0, 100.0, '#f99', 20.0, '#ddf');

        $this->assertSame(
            [[
                'name' => 'Me',
                'type' => 'bar',
                'val' => 30.0,
                'max' => 100.0,
                'color' => '#f99',
                'add' => 20.0,
                'addcolor' => '#ddf',
            ]],
            $this->getLinesProperty()
        );
    }

    public function testClearLinesEmptiesTheLinesArray(): void
    {
        $this->callPrivateMethod('addLine', 'Some line');
        $this->callPrivateMethod('clearLines');

        $this->assertSame([], $this->getLinesProperty());
    }

    public function testShowLineRendersHeadlineWithDistanceAndActivityCount(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'Test Name',
            'lvl' => 1,
            'sep' => true,
            'km' => 10.5,
            'anz' => 3,
        ]);

        $this->assertSame(
            '<p style="border-top:1px solid #ccc;">'
            . '<span class="right"><small><small>(3x)</small></small> <span style="font-weight:bold;">10,5&nbsp;km</span></span>'
            . '<span style="font-weight:bold;">Test Name</span>'
            . '</p>',
            $output
        );
    }

    public function testShowLineRendersPlainLineWithoutKmOrCount(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'Line2',
            'lvl' => 2,
            'km' => 0,
            'anz' => 0,
        ]);

        $this->assertSame(
            '<p><span class="right"> </span><span style="padding-left: 10px;">Line2</span></p>',
            $output
        );
    }

    public function testShowLineOmitsDistanceWhenKmIsZeroEvenIfKeyIsSet(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'No distance',
            'lvl' => 2,
            'km' => 0,
        ]);

        $this->assertStringNotContainsString('km', $output);
    }

    public function testShowLineProducesWellFormedSpanTagWhenLvlKeyIsMissing(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'No level',
            'km' => 0,
            'anz' => 0,
        ]);

        $this->assertSame(
            '<p><span class="right"> </span><span>No level</span></p>',
            $output
        );
    }

    public function testShowBarRendersSingleBarWithoutAddSegment(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'Bar Name',
            'type' => 'bar',
            'val' => 50.0,
            'max' => 100.0,
            'color' => '#f00',
            'add' => null,
            'addcolor' => null,
        ]);

        $this->assertSame(
            '<div style="padding-left:5px; padding-right:7px; padding-bottom:2px">'
            . '<div style="border: 1px solid black; padding: 1px; background-color: transparent; width: 100%; height: 15px">'
            . '<span style="position: absolute; right: 15px">50%&nbsp;</span>'
            . '<span style="position: absolute; left: 15px">Bar Name</span>'
            . '<div class="progress_bar" style="float: left; background-color:#f00; width:50%;">&nbsp;</div>'
            . '</div></div>',
            $output
        );
    }

    public function testShowBarRendersAddSegmentWhenAddIsSet(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'Bar Name',
            'type' => 'bar',
            'val' => 30.0,
            'max' => 100.0,
            'color' => '#00f',
            'add' => 20.0,
            'addcolor' => '#0f0',
        ]);

        $this->assertSame(
            '<div style="padding-left:5px; padding-right:7px; padding-bottom:2px">'
            . '<div style="border: 1px solid black; padding: 1px; background-color: transparent; width: 100%; height: 15px">'
            . '<span style="position: absolute; right: 15px">50%&nbsp;</span>'
            . '<span style="position: absolute; left: 15px">Bar Name</span>'
            . '<div class="progress_bar" style="float: left; background-color:#00f; width:30%;">&nbsp;</div>'
            . '<div id="progress_bar_add" style="float: left; background-color:#0f0; width:20%;">&nbsp;</div>'
            . '</div></div>',
            $output
        );
    }

    public function testShowBarClampsPercentageAtOneHundredWhenValueExceedsMax(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'Over goal',
            'type' => 'bar',
            'val' => 150.0,
            'max' => 100.0,
            'color' => '#0f0',
            'add' => null,
            'addcolor' => null,
        ]);

        $this->assertStringContainsString('width:100%;', $output);
    }

    public function testShowLineOrBarDispatchesToShowBarWhenTypeIsBar(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'X',
            'type' => 'bar',
            'val' => 1.0,
            'max' => 1.0,
            'color' => '#000',
            'add' => null,
            'addcolor' => null,
        ]);

        $this->assertStringContainsString('progress_bar', $output);
    }

    public function testShowLineOrBarDispatchesToShowLineWhenTypeIsAbsent(): void
    {
        $output = $this->captureShowLineOrBar([
            'name' => 'X',
            'lvl' => 2,
        ]);

        $this->assertStringStartsWith('<p>', $output);
    }

    public function testGetTimesetContainsAllExpectedTimeRanges(): void
    {
        $timeset = $this->callPrivateMethod('getTimeset');

        $this->assertSame(['woche', 'mon', 'hj', 'saison', 'jahr'], array_keys($timeset));
    }

    public function testGetTimesetEntriesHaveStartBeforeOrEqualEnd(): void
    {
        $timeset = $this->callPrivateMethod('getTimeset');

        foreach ($timeset as $key => $entry) {
            $this->assertInstanceOf(\DateTime::class, $entry['start'], "start missing for {$key}");
            $this->assertInstanceOf(\DateTime::class, $entry['end'], "end missing for {$key}");
            $this->assertLessThanOrEqual($entry['end'], $entry['start'], "start > end for {$key}");
            $this->assertIsString($entry['name']);
        }
    }

    public function testGetTimesetOnlySaisonEntryHasNote(): void
    {
        $timeset = $this->callPrivateMethod('getTimeset');

        foreach ($timeset as $key => $entry) {
            if ($key === 'saison') {
                $this->assertArrayHasKey('note', $entry);
            } else {
                $this->assertArrayNotHasKey('note', $entry);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function captureShowLineOrBar(array $data): string
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Ziele::class, 'showLineOrBar');

        ob_start();
        try {
            $method->invokeArgs($this->plugin, [&$data]);
        } finally {
            $output = ob_get_clean();
        }

        return $output;
    }

    private function getLinesProperty(): array
    {
        $property = new \ReflectionProperty(RunalyzePluginPanel_Ziele::class, 'Lines');

        return $property->getValue($this->plugin);
    }

    private function callPrivateMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Ziele::class, $name);

        return $method->invoke($this->plugin, ...$arguments);
    }
}

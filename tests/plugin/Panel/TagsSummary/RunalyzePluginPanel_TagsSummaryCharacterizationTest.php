<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginPanel_TagsSummary.
 */
#[Group('plugin')]
final class RunalyzePluginPanel_TagsSummaryCharacterizationTest extends TestCase
{
    private RunalyzePluginPanel_TagsSummary $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginPanel_TagsSummary::class)
            ->newInstanceWithoutConstructor();
    }

    #[DataProvider('provideUsagePercentages')]
    public function testUsageImageSnapshotForVariousPercentages(float $percentage, string $expectedHtml): void
    {
        $html = $this->callProtectedMethod('getUsageImage', $percentage);

        $this->assertSame($expectedHtml, $html);
    }

    /**
     * @return \Iterator<string, array<(float|string)>>
     */
    public static function provideUsagePercentages(): \Iterator
    {
        yield 'zero usage' => [0.0, '<span class="tags-usage" style="width:0px;"></span>'];
        yield 'half usage' => [0.5, '<span class="tags-usage" style="width:165px;"></span>'];
        yield 'full usage' => [1.0, '<span class="tags-usage" style="width:330px;"></span>'];
        yield 'one third usage' => [0.333, '<span class="tags-usage" style="width:110px;"></span>'];
    }

    public function testUsageImageClampsPercentageAboveOneToMaxWidth(): void
    {
        $html = $this->callProtectedMethod('getUsageImage', 1.5);

        $this->assertSame('<span class="tags-usage" style="width:330px;"></span>', $html);
    }

    public function testUsageImageClampsNegativePercentageToZeroWidth(): void
    {
        $html = $this->callProtectedMethod('getUsageImage', -0.1);

        $this->assertSame('<span class="tags-usage" style="width:0px;"></span>', $html);
    }

    public function testStyleSnapshot(): void
    {
        $style = $this->callProtectedMethod('getStyle');

        $this->assertSame(
            '<style type="text/css">.tags-usage { position: absolute; bottom: 0; left: 0; background-image:url(assets/images/damage.png); background-position:left center; height: 2px; max-width: 100%; }</style>',
            $style
        );
    }

    private function callProtectedMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_TagsSummary::class, $name);

        return $method->invoke($this->plugin, ...$arguments);
    }
}

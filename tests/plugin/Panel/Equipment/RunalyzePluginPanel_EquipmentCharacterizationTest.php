<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginPanel_Equipment's HTML rendering.
 */
#[Group('plugin')]
#[Group('requiresDoctrine')]
class RunalyzePluginPanel_EquipmentCharacterizationTest extends TestCase
{
    private RunalyzePluginPanel_Equipment $plugin;

    protected function setUp(): void
    {
        $this->plugin = new \ReflectionClass(RunalyzePluginPanel_Equipment::class)
            ->newInstanceWithoutConstructor();
    }

    #[DataProvider('provideUsagePercentages')]
    public function testUsageImageSnapshotForVariousPercentages(float $percentage, string $expectedHtml): void
    {
        $html = $this->callProtectedMethod('getUsageImage', $percentage);

        $this->assertSame($expectedHtml, $html);
    }

    /**
     * @return array<string, float[]|string[]>
     */
    public static function provideUsagePercentages(): array
    {
        return [
            'zero usage' => [0.0, '<span class="equipment-usage" style="width:0px;"></span>'],
            'half usage' => [0.5, '<span class="equipment-usage" style="width:165px;"></span>'],
            'full usage' => [1.0, '<span class="equipment-usage" style="width:330px;"></span>'],
            'one third usage' => [0.333, '<span class="equipment-usage" style="width:110px;"></span>'],
        ];
    }

    public function testUsageImageClampsPercentageAboveOneToMaxWidth(): void
    {
        $html = $this->callProtectedMethod('getUsageImage', 1.5);

        $this->assertSame('<span class="equipment-usage" style="width:330px;"></span>', $html);
    }

    public function testUsageImageClampsNegativePercentageToZeroWidth(): void
    {
        $html = $this->callProtectedMethod('getUsageImage', -0.1);

        $this->assertSame('<span class="equipment-usage" style="width:0px;"></span>', $html);
    }

    public function testUsageImageAcceptsCustomMaxWidth(): void
    {
        $html = $this->callProtectedMethod('getUsageImage', 0.5, 200);

        $this->assertSame('<span class="equipment-usage" style="width:100px;"></span>', $html);
    }

    public function testStyleSnapshot(): void
    {
        $style = $this->callProtectedMethod('getStyle');

        $this->assertSame(
            '<style type="text/css">.equipment-usage { position: absolute; bottom: 0; left: 0; background-image:url(assets/images//damage.png); background-position:left center; height: 2px; max-width: 100%; }</style>',
            $style
        );
    }

    private function callProtectedMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(RunalyzePluginPanel_Equipment::class, $name);

        return $method->invoke($this->plugin, ...$arguments);
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;

#[Group('plugin')]
final class PluginDiscoveryTest extends TestCase
{
    public function testReturnsSortedKeysFromBothRegistries(): void
    {
        $panel = $this->createStub(ModernPanelInterface::class);
        $panel->method('getKey')->willReturn('Sports');

        $statistic = $this->createStub(ModernStatisticsInterface::class);
        $statistic->method('getKey')->willReturn('Analysis');

        $discovery = new PluginDiscovery(
            new ModernPanelRegistry([$panel]),
            new ModernStatisticsRegistry([$statistic])
        );

        $this->assertSame(['Analysis', 'Sports'], $discovery->findAvailablePluginKeys());
    }

    public function testReturnsNoKeysWhenBothRegistriesAreEmpty(): void
    {
        $discovery = new PluginDiscovery(
            new ModernPanelRegistry([]),
            new ModernStatisticsRegistry([])
        );

        $this->assertSame([], $discovery->findAvailablePluginKeys());
    }
}

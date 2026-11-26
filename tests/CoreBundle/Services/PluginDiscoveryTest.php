<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;

#[Group('plugin')]
final class PluginDiscoveryTest extends TestCase
{
    public function testReturnsSortedKeysFromBothRegistries(): void
    {
        $panel = $this->createStub(PanelInterface::class);
        $panel->method('getKey')->willReturn('Sports');

        $statistic = $this->createStub(StatisticsInterface::class);
        $statistic->method('getKey')->willReturn('Analysis');

        $discovery = new PluginDiscovery(
            new PanelRegistry([$panel]),
            new StatisticsRegistry([$statistic])
        );

        $this->assertSame(['Analysis', 'Sports'], $discovery->findAvailablePluginKeys());
    }

    public function testReturnsNoKeysWhenBothRegistriesAreEmpty(): void
    {
        $discovery = new PluginDiscovery(
            new PanelRegistry([]),
            new StatisticsRegistry([])
        );

        $this->assertSame([], $discovery->findAvailablePluginKeys());
    }
}

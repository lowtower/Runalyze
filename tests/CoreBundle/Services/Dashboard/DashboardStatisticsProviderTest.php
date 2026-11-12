<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Dashboard;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Plugin;
use PluginFactory;
use PluginStat;
use PluginType;
use Runalyze\Bundle\CoreBundle\Services\Dashboard\DashboardStatisticsProvider;
use Symfony\Component\HttpFoundation\Request;

final class DashboardStatisticsProviderTestPlugin extends PluginStat
{
    public function name(): string
    {
        return 'Test statistics';
    }

    public function description(): string
    {
        return 'Test statistics plugin.';
    }

    protected function displayContent(): void {}
}

final class DashboardStatisticsProviderTest extends TestCase
{
    public function testItSelectsTheFirstActivePluginByDefault(): void
    {
        $plugin = $this->plugin(17, Plugin::ACTIVE);
        $factory = $this->createMock(PluginFactory::class);
        $factory->expects($this->once())
            ->method('activePlugins')
            ->with(PluginType::STAT)
            ->willReturn(['DashboardStatisticsProviderTestPlugin']);
        $factory->method('variousPlugins')->willReturn([]);
        $factory->expects($this->exactly(2))
            ->method('newInstance')
            ->with('DashboardStatisticsProviderTestPlugin')
            ->willReturn($plugin);

        $statistics = new DashboardStatisticsProvider($factory)->getStatistics(new Request());

        $this->assertSame('<li class="active"><a rel="statistics" href="my/plugin/17">Test statistics</a></li>', $statistics->navigationHtml);
        $this->assertSame($plugin, $statistics->plugin);
        $this->assertNull($statistics->activityId);
        $this->assertTrue($statistics->hasActiveStatistics);
    }

    public function testItSelectsTheRequestedActivePlugin(): void
    {
        $plugin = $this->plugin(23, Plugin::ACTIVE);
        $factory = $this->createMock(PluginFactory::class);
        $factory->method('activePlugins')->willReturn(['DashboardStatisticsProviderTestPlugin']);
        $factory->method('variousPlugins')->willReturn([]);
        $factory->method('newInstance')->willReturn($plugin);
        $factory->expects($this->once())
            ->method('newInstanceFor')
            ->with(23)
            ->willReturn($plugin);

        $statistics = new DashboardStatisticsProvider($factory)->getStatistics(
            new Request(query: [
                'pluginid' => '23',
            ])
        );

        $this->assertSame($plugin, $statistics->plugin);
    }

    public function testItFallsBackToTheFirstActivePluginWhenTheRequestedPluginIsInactive(): void
    {
        $activePlugin = $this->plugin(17, Plugin::ACTIVE);
        $inactivePlugin = $this->plugin(23, Plugin::ACTIVE_NOT);
        $factory = $this->createMock(PluginFactory::class);
        $factory->method('activePlugins')->willReturn(['DashboardStatisticsProviderTestPlugin']);
        $factory->method('variousPlugins')->willReturn([]);
        $factory->method('newInstance')->willReturn($activePlugin);
        $factory->expects($this->once())
            ->method('newInstanceFor')
            ->with(23)
            ->willReturn($inactivePlugin);

        $statistics = new DashboardStatisticsProvider($factory)->getStatistics(
            new Request(query: [
                'pluginid' => '23',
            ])
        );

        $this->assertSame($activePlugin, $statistics->plugin);
    }

    public function testItReturnsNoPluginWhenNoStatisticsAreActive(): void
    {
        $factory = $this->createStub(PluginFactory::class);
        $factory->method('activePlugins')->willReturn([]);
        $factory->method('variousPlugins')->willReturn([]);

        $statistics = new DashboardStatisticsProvider($factory)->getStatistics(new Request());

        $this->assertNull($statistics->plugin);
        $this->assertFalse($statistics->hasActiveStatistics);
    }

    public function testActivitySelectionTakesPrecedenceOverPluginSelection(): void
    {
        $factory = $this->createMock(PluginFactory::class);
        $factory->method('activePlugins')->willReturn([]);
        $factory->method('variousPlugins')->willReturn([]);
        $factory->expects($this->never())->method('newInstanceFor');

        $statistics = new DashboardStatisticsProvider($factory)->getStatistics(
            new Request(query: [
                'id' => '42',
                'pluginid' => '23',
            ])
        );

        $this->assertSame(42, $statistics->activityId);
        $this->assertNull($statistics->plugin);
    }

    public function testUnknownRequestedPluginIsNotSilentlyReplaced(): void
    {
        $factory = $this->createMock(PluginFactory::class);
        $factory->method('activePlugins')->willReturn([]);
        $factory->method('variousPlugins')->willReturn([]);
        $factory->expects($this->once())
            ->method('newInstanceFor')
            ->with(999)
            ->willThrowException(new InvalidArgumentException('Unknown plugin.'));

        $this->expectException(InvalidArgumentException::class);

        new DashboardStatisticsProvider($factory)->getStatistics(
            new Request(query: [
                'pluginid' => '999',
            ])
        );
    }

    private function plugin(int $id, int $active): DashboardStatisticsProviderTestPlugin
    {
        $plugin = new \ReflectionClass(DashboardStatisticsProviderTestPlugin::class)
            ->newInstanceWithoutConstructor();

        $idProperty = new \ReflectionProperty(Plugin::class, 'id');
        $idProperty->setValue($plugin, $id);

        $activeProperty = new \ReflectionProperty(Plugin::class, 'active');
        $activeProperty->setValue($plugin, $active);

        return $plugin;
    }
}

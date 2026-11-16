<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Plugin;
use PluginPanel;
use PluginStat;
use PluginType;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\Dashboard\DashboardStatisticsProvider;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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

final class DashboardStatisticsProviderTestPanel extends PluginPanel
{
    public function name(): string
    {
        return 'Test panel';
    }

    public function description(): string
    {
        return 'Test panel plugin.';
    }

    protected function defaultSport(): int
    {
        return -1;
    }

    protected function displayContent(): void {}
}

final class DashboardStatisticsProviderTest extends TestCase
{
    public function testItSelectsTheFirstActivePluginByDefault(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(17, Plugin::ACTIVE, 1),
        ]));

        $statistics = $provider->getStatistics(new Request());

        $this->assertSame([
            [
                'label' => 'Test statistics',
                'href' => 'my/plugin/17',
                'active' => true,
                'children' => [],
            ],
        ], $statistics->navigation);
        $this->assertSame(17, $statistics->plugin?->id());
        $this->assertNull($statistics->activityId);
        $this->assertTrue($statistics->hasActiveStatistics);
    }

    public function testItSelectsTheRequestedActivePlugin(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(23, Plugin::ACTIVE, 1),
        ]));

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '23',
        ]));

        $this->assertSame(23, $statistics->plugin?->id());
    }

    public function testItFallsBackToTheFirstActivePluginWhenTheRequestedPluginIsInactive(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(17, Plugin::ACTIVE, 1),
            $this->pluginData(23, Plugin::ACTIVE_NOT, 2),
        ]));

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '23',
        ]));

        $this->assertSame(17, $statistics->plugin?->id());
    }

    public function testItReturnsNoPluginWhenNoStatisticsAreActive(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(17, Plugin::ACTIVE_NOT, 1),
        ]));

        $statistics = $provider->getStatistics(new Request());

        $this->assertNull($statistics->plugin);
        $this->assertFalse($statistics->hasActiveStatistics);
    }

    public function testActivitySelectionTakesPrecedenceOverPluginSelection(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([]));

        $statistics = $provider->getStatistics(new Request(query: [
            'id' => '42',
            'pluginid' => '23',
        ]));

        $this->assertSame(42, $statistics->activityId);
        $this->assertNull($statistics->plugin);
    }

    public function testUnknownRequestedPluginIsNotSilentlyReplaced(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([]));

        $this->expectException(InvalidArgumentException::class);

        $provider->getStatistics(new Request(query: [
            'pluginid' => '999',
        ]));
    }

    public function testItGroupsVariousStatisticsInASubmenu(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(31, Plugin::ACTIVE_VARIOUS, 1),
        ]));

        $statistics = $provider->getStatistics(new Request());

        $this->assertSame([
            [
                'label' => __('Miscellaneous'),
                'href' => '#',
                'active' => false,
                'children' => [
                    [
                        'label' => 'Test statistics',
                        'href' => 'my/plugin/31',
                    ],
                ],
            ],
        ], $statistics->navigation);
    }

    public function testRequestedInstalledPluginCanBeSelectedEvenWhenItIsNotAStatistic(): void
    {
        $provider = new DashboardStatisticsProvider($this->createSettingsProvider([
            $this->pluginData(41, Plugin::ACTIVE, 1, PluginType::string(PluginType::PANEL), DashboardStatisticsProviderTestPanel::class),
        ]));

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '41',
        ]));

        $this->assertSame(41, $statistics->plugin?->id());
        $this->assertFalse($statistics->hasActiveStatistics);
    }

    /**
     * @param list<array{id: int, key: string, type: string, active: int, order: int}> $data
     */
    private function createSettingsProvider(array $data): PluginSettingsProvider
    {
        $repository = $this->createStub(PluginRepository::class);
        $repository->method('findDataForAccount')->willReturn($data);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repository);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new Account());

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $instantiator = new LegacyPluginInstantiator();

        return new PluginSettingsProvider(
            new PluginInstallationManager($doctrine, $tokenStorage, $instantiator),
            $instantiator,
            new PluginDiscovery(sys_get_temp_dir() . '/runalyze-dashboard-plugins')
        );
    }

    /**
     * @return array{id: int, key: string, type: string, active: int, order: int}
     */
    private function pluginData(
        int $id,
        int $active,
        int $order,
        string $type = 'stat',
        string $key = DashboardStatisticsProviderTestPlugin::class
    ): array {
        return [
            'id' => $id,
            'key' => $key,
            'type' => $type,
            'active' => $active,
            'order' => $order,
        ];
    }
}

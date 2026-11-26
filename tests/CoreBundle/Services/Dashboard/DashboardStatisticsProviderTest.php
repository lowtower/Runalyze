<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\Dashboard\DashboardStatisticsProvider;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class DashboardStatisticsProviderTest extends TestCase
{
    public function testItSelectsTheFirstActivePluginByDefault(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(17, Plugin::STATE_ACTIVE, 1),
        ]);

        $statistics = $provider->getStatistics(new Request());

        $this->assertSame([
            [
                'label' => 'Test statistics',
                'href' => 'my/plugin/17',
                'active' => true,
                'children' => [],
            ],
        ], $statistics->navigation);
        $this->assertSame(17, $statistics->pluginId);
        $this->assertNull($statistics->activityId);
        $this->assertTrue($statistics->hasActiveStatistics);
    }

    public function testItSelectsTheRequestedActivePlugin(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(23, Plugin::STATE_ACTIVE, 1),
        ]);

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '23',
        ]));

        $this->assertSame(23, $statistics->pluginId);
    }

    public function testItFallsBackToTheFirstActivePluginWhenTheRequestedPluginIsInactive(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(17, Plugin::STATE_ACTIVE, 1),
            $this->pluginData(23, Plugin::STATE_INACTIVE, 2),
        ]);

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '23',
        ]));

        $this->assertSame(17, $statistics->pluginId);
    }

    public function testItReturnsNoPluginWhenNoStatisticsAreActive(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(17, Plugin::STATE_INACTIVE, 1),
        ]);

        $statistics = $provider->getStatistics(new Request());

        $this->assertNull($statistics->pluginId);
        $this->assertFalse($statistics->hasActiveStatistics);
    }

    public function testActivitySelectionTakesPrecedenceOverPluginSelection(): void
    {
        $provider = $this->createProvider([]);

        $statistics = $provider->getStatistics(new Request(query: [
            'id' => '42',
            'pluginid' => '23',
        ]));

        $this->assertSame(42, $statistics->activityId);
        $this->assertNull($statistics->pluginId);
    }

    public function testUnknownRequestedPluginIsNotSilentlyReplaced(): void
    {
        $provider = $this->createProvider([]);

        $this->expectException(InvalidArgumentException::class);

        $provider->getStatistics(new Request(query: [
            'pluginid' => '999',
        ]));
    }

    public function testItGroupsVariousStatisticsInASubmenu(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(31, Plugin::STATE_HIDDEN, 1),
        ]);

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
        $provider = $this->createProvider([
            $this->pluginData(41, Plugin::STATE_ACTIVE, 1, Plugin::TYPE_PANEL, 'TestPanel'),
        ]);

        $statistics = $provider->getStatistics(new Request(query: [
            'pluginid' => '41',
        ]));

        $this->assertSame(41, $statistics->pluginId);
        $this->assertFalse($statistics->hasActiveStatistics);
    }

    /**
     * @param list<array{id: int, key: string, type: string, active: int, order: int}> $data
     */
    private function createProvider(array $data): DashboardStatisticsProvider
    {
        $repository = $this->createStub(PluginRepository::class);
        $repository->method('findDataForAccount')->willReturn($data);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repository);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new Account());

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $panels = new ModernPanelRegistry([]);
        $statistic = $this->createStub(ModernStatisticsInterface::class);
        $statistic->method('getKey')->willReturn('TestStatistic');
        $statistic->method('getName')->willReturn('Test statistics');
        $statistics = new ModernStatisticsRegistry([$statistic]);

        return new DashboardStatisticsProvider(
            new PluginSettingsProvider(
                new PluginInstallationManager($doctrine, $tokenStorage, $panels, $statistics),
                new PluginDiscovery($panels, $statistics),
                $panels,
                $statistics
            ),
            $statistics
        );
    }

    /**
     * @return array{id: int, key: string, type: string, active: int, order: int}
     */
    private function pluginData(
        int $id,
        int $active,
        int $order,
        string $type = Plugin::TYPE_STAT,
        string $key = 'TestStatistic'
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

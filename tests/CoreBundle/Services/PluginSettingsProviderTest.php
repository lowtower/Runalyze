<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class PanelMetadataFixture implements PanelInterface
{
    public function getKey(): string
    {
        return 'RegisteredPanel';
    }

    public function getName(): string
    {
        return 'Example panel';
    }

    public function getDescription(): string
    {
        return 'Example panel description';
    }

    public function getTemplate(): string
    {
        return 'panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    public function present(Account $account, Plugin $installation): array
    {
        return [];
    }
}

#[Group('plugin')]
final class PluginSettingsProviderTest extends TestCase
{
    public function testInstalledEntriesPreserveOrderAndStatesForRegisteredPanels(): void
    {
        $provider = $this->createProvider([
            $this->installation(3, 'RegisteredPanel', Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 2),
            $this->installation(2, 'RegisteredPanel', Plugin::TYPE_PANEL, Plugin::STATE_HIDDEN, 1),
            $this->installation(1, 'RegisteredPanel', Plugin::TYPE_PANEL, Plugin::STATE_INACTIVE, 1),
            $this->installation(4, 'RetiredPanel', Plugin::TYPE_PANEL, Plugin::STATE_ACTIVE, 2),
            $this->installation(5, 'RegisteredStatistic', Plugin::TYPE_STAT, Plugin::STATE_ACTIVE, 1),
        ]);

        $entries = $provider->getInstalledPluginAdminEntries(Plugin::TYPE_PANEL);
        $this->assertSame([1, 2, 3], array_column($entries, 'id'));
        $this->assertSame([Plugin::STATE_INACTIVE, Plugin::STATE_HIDDEN, Plugin::STATE_ACTIVE], array_column($entries, 'active'));
        $this->assertSame('Example panel', $entries[0]['name']);

        $entries = $provider->getInstalledPluginAdminEntries(Plugin::TYPE_STAT);
        $this->assertSame([5], array_column($entries, 'id'));
        $this->assertSame('Example statistic', $entries[0]['name']);
    }

    public function testAvailableEntriesExcludeAlreadyInstalledPlugins(): void
    {
        $provider = $this->createProvider([], ['RegisteredPanel']);

        $this->assertSame([[
            'key' => 'RegisteredStatistic',
            'name' => 'Example statistic',
            'description' => 'Example statistic description',
            'type' => Plugin::TYPE_STAT,
        ]], $provider->getAvailablePluginAdminEntries());
    }

    public function testAvailableEntriesIncludeRegisteredPanels(): void
    {
        $provider = $this->createProvider([], ['RegisteredStatistic']);

        $this->assertSame([[
            'key' => 'RegisteredPanel',
            'name' => 'Example panel',
            'description' => 'Example panel description',
            'type' => Plugin::TYPE_PANEL,
        ]], $provider->getAvailablePluginAdminEntries());
    }

    /**
     * @param list<array{id: int, key: string, type: string, active: int, order: int}> $data
     * @param list<string> $installedKeys
     */
    private function createProvider(array $data, array $installedKeys = []): PluginSettingsProvider
    {
        $repository = $this->createStub(PluginRepository::class);
        $repository->method('findDataForAccount')->willReturn($data);
        $repository->method('findKeysForAccount')->willReturn($installedKeys);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repository);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new Account());

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $panels = new PanelRegistry([new PanelMetadataFixture()]);
        $statistic = $this->createStub(StatisticsInterface::class);
        $statistic->method('getKey')->willReturn('RegisteredStatistic');
        $statistic->method('getName')->willReturn('Example statistic');
        $statistic->method('getDescription')->willReturn('Example statistic description');
        $statistics = new StatisticsRegistry([$statistic]);

        return new PluginSettingsProvider(
            new PluginInstallationManager($doctrine, $tokenStorage, $panels, $statistics),
            new PluginDiscovery($panels, $statistics),
            $panels,
            $statistics
        );
    }

    /**
     * @return array{id: int, key: string, type: string, active: int, order: int}
     */
    private function installation(int $id, string $key, string $type, int $active, int $order): array
    {
        return [
            'id' => $id,
            'key' => $key,
            'type' => $type,
            'active' => $active,
            'order' => $order,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Plugin;
use PluginInstaller;
use PluginPanel;
use PluginType;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class PluginSettingsProviderFixture extends PluginPanel
{
    public function name(): string
    {
        return 'Fixture';
    }

    public function description(): string
    {
        return 'Plugin settings provider fixture.';
    }

    protected function defaultSport(): int
    {
        return -1;
    }

    protected function displayContent(): void {}
}

final class ModernPanelMetadataFixture implements ModernPanelInterface, ModernPanelMetadataInterface
{
    public function getKey(): string
    {
        return 'ModernPanelWithoutLegacyClass';
    }

    public function getName(): string
    {
        return 'Modern Panel';
    }

    public function getDescription(): string
    {
        return 'Modern panel description';
    }

    public function getTemplate(): string
    {
        return 'modern.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    public function present(Account $account, PluginInstallation $installation): array
    {
        return [];
    }
}

#[Group('plugin')]
final class PluginSettingsProviderTest extends TestCase
{
    public function testReturnsInstalledPluginsInDisplayOrderAndFiltersByType(): void
    {
        $data = [
            $this->pluginData(3, PluginType::string(PluginType::STAT), Plugin::ACTIVE_NOT, 1),
            $this->pluginData(2, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 2),
            $this->pluginData(1, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 1),
            $this->pluginData(4, PluginType::string(PluginType::PANEL), Plugin::ACTIVE_VARIOUS, 1),
        ];
        $provider = $this->createProvider($data);

        $plugins = $provider->getInstalledPlugins(PluginType::PANEL);

        $this->assertSame([1, 4, 2], array_map(static fn (Plugin $plugin): int => $plugin->id(), $plugins));
    }

    public function testKeepsHiddenPanelsBetweenActivePanelsAccordingToTheirPositions(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(3, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 3),
            $this->pluginData(2, PluginType::string(PluginType::PANEL), Plugin::ACTIVE_VARIOUS, 2),
            $this->pluginData(1, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 1),
        ]);

        $plugins = $provider->getInstalledPluginsExceptInactive(PluginType::PANEL);

        $this->assertSame([1, 2, 3], array_map(static fn (Plugin $plugin): int => $plugin->id(), $plugins));
    }

    public function testReturnsInstalledPluginsExceptInactiveAndFiltersByType(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(1, PluginType::string(PluginType::PANEL), Plugin::ACTIVE_NOT, 1),
            $this->pluginData(2, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 2),
            $this->pluginData(3, PluginType::string(PluginType::PANEL), Plugin::ACTIVE_VARIOUS, 1),
            $this->pluginData(4, PluginType::string(PluginType::STAT), Plugin::ACTIVE, 1),
        ]);

        $plugins = $provider->getInstalledPluginsExceptInactive(PluginType::PANEL);

        $this->assertSame([3, 2], array_map(static fn (Plugin $plugin): int => $plugin->id(), $plugins));
    }

    public function testReturnsInstalledPluginById(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(17, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 1),
        ]);

        $plugin = $provider->getInstalledPluginById(17);

        $this->assertInstanceOf(PluginSettingsProviderFixture::class, $plugin);
        $this->assertSame(17, $plugin->id());
    }

    public function testThrowsWhenInstalledPluginIdDoesNotExist(): void
    {
        $provider = $this->createProvider([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('No plugin found for id "17".');

        $provider->getInstalledPluginById(17);
    }

    public function testReturnsInstalledPluginByKey(): void
    {
        $provider = $this->createProvider([
            $this->pluginData(17, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 1),
        ]);

        $plugin = $provider->getInstalledPluginByKey(PluginSettingsProviderFixture::class);

        $this->assertInstanceOf(PluginSettingsProviderFixture::class, $plugin);
        $this->assertSame(17, $plugin->id());
    }

    public function testThrowsWhenInstalledPluginKeyDoesNotExist(): void
    {
        $provider = $this->createProvider([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Plugin with key "missing-plugin" is not installed.');

        $provider->getInstalledPluginByKey('missing-plugin');
    }

    public function testReturnsAvailablePluginInstancesExcludingInstalledPlugins(): void
    {
        $directory = sys_get_temp_dir() . '/runalyze-plugin-settings-' . bin2hex(random_bytes(8));
        mkdir($directory);
        mkdir($directory . '/' . PluginSettingsProviderFixture::class);

        try {
            $provider = $this->createProvider(
                [],
                [PluginSettingsProviderFixture::class],
                new PluginDiscovery($directory)
            );

            $this->assertSame([], $provider->getAvailablePluginsToInstall());
        } finally {
            rmdir($directory . '/' . PluginSettingsProviderFixture::class);
            rmdir($directory);
        }
    }

    public function testInstantiatesAvailablePluginsForInstallation(): void
    {
        $directory = sys_get_temp_dir() . '/runalyze-plugin-settings-' . bin2hex(random_bytes(8));
        mkdir($directory);
        mkdir($directory . '/' . PluginSettingsProviderFixture::class);

        try {
            $provider = $this->createProvider([], [], new PluginDiscovery($directory));
            $plugins = $provider->getAvailablePluginsToInstall();

            $this->assertCount(1, $plugins);
            $this->assertInstanceOf(PluginSettingsProviderFixture::class, $plugins[0]);
            $this->assertSame(PluginInstaller::ID, $plugins[0]->id());
        } finally {
            rmdir($directory . '/' . PluginSettingsProviderFixture::class);
            rmdir($directory);
        }
    }

    public function testModernPanelAdminEntriesDoNotInstantiateLegacyPlugin(): void
    {
        $registry = new ModernPanelRegistry([new ModernPanelMetadataFixture()]);
        $data = [[
            'id' => 17,
            'key' => 'ModernPanelWithoutLegacyClass',
            'type' => PluginType::string(PluginType::PANEL),
            'active' => Plugin::ACTIVE,
            'order' => 1,
        ]];
        $provider = $this->createProvider($data, [], null, $registry);

        $this->assertSame([[
            'id' => 17,
            'key' => 'ModernPanelWithoutLegacyClass',
            'name' => 'Modern Panel',
            'description' => 'Modern panel description',
            'type' => PluginType::PANEL,
            'active' => Plugin::ACTIVE,
            'order' => 1,
            'config_link' => null,
        ]], $provider->getInstalledPluginAdminEntries(PluginType::PANEL));
    }

    public function testModernPanelCanBeListedForInstallationWithoutLegacyDirectory(): void
    {
        $directory = sys_get_temp_dir() . '/runalyze-plugin-settings-' . bin2hex(random_bytes(8));
        mkdir($directory);

        try {
            $registry = new ModernPanelRegistry([new ModernPanelMetadataFixture()]);
            $provider = $this->createProvider(
                [],
                [],
                new PluginDiscovery($directory, $registry),
                $registry
            );

            $this->assertSame([[
                'key' => 'ModernPanelWithoutLegacyClass',
                'name' => 'Modern Panel',
                'description' => 'Modern panel description',
                'type' => PluginType::PANEL,
            ]], $provider->getAvailablePluginAdminEntries());
        } finally {
            rmdir($directory);
        }
    }

    /**
     * @param list<array{id: int, key: string, type: string, active: int, order: int}> $data
     * @param list<string> $installedKeys
     */
    private function createProvider(
        array $data,
        array $installedKeys = [],
        ?PluginDiscovery $discovery = null,
        ?ModernPanelRegistry $modernPanelRegistry = null
    ): PluginSettingsProvider {
        $repository = $this->createStub(PluginRepository::class);
        $repository->method('findDataForAccount')->willReturn($data);
        $repository->method('findKeysForAccount')->willReturn($installedKeys);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturn($repository);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new Account());

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $installationManager = new PluginInstallationManager(
            $doctrine,
            $tokenStorage,
            new LegacyPluginInstantiator()
        );

        $directory = $discovery;
        $directory ??= new PluginDiscovery(sys_get_temp_dir() . '/runalyze-plugin-settings-empty');

        return new PluginSettingsProvider(
            $installationManager,
            new LegacyPluginInstantiator(),
            $directory,
            $modernPanelRegistry
        );
    }

    /**
     * @return array{id: int, key: string, type: string, active: int, order: int}
     */
    private function pluginData(int $id, string $type, int $active, int $order): array
    {
        return [
            'id' => $id,
            'key' => PluginSettingsProviderFixture::class,
            'type' => $type,
            'active' => $active,
            'order' => $order,
        ];
    }
}

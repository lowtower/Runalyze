<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Tests\\Services;

use Doctrine\\Persistence\\ManagerRegistry;
use PHPUnit\\Framework\\Attributes\\Group;
use PHPUnit\\Framework\\TestCase;
use Plugin;
use PluginInstaller;
use PluginPanel;
use PluginType;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Repository\\PluginRepository;
use Runalyze\\Bundle\\CoreBundle\\Services\\LegacyPluginInstantiator;
use Runalyze\\Bundle\\CoreBundle\\Services\\PluginDiscovery;
use Runalyze\\Bundle\\CoreBundle\\Services\\PluginInstallationManager;
use Runalyze\\Bundle\\CoreBundle\\Services\\PluginSettingsProvider;
use Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface;
use Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface;

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

        $this->assertSame([4, 1, 2], array_map(static fn (Plugin $plugin): int => $plugin->id(), $plugins));
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

    /**
     * @param list<array{id: int, key: string, type: string, active: int, order: int}> $data
     * @param list<string> $installedKeys
     */
    private function createProvider(
        array $data,
        array $installedKeys = [],
        ?PluginDiscovery $discovery = null
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
            $directory
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

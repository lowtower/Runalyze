<?php

declare(strict_types=1);

namespace Runalyze\Tests;

use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginConfigurationStorage;

#[Group('dependsOn')]
#[Group('dependsOnOldDatabase')]
final class LegacyPluginConfigurationStorageTest extends DatabaseTestCase
{
    private LegacyPluginConfigurationStorage $storage;

    /**
     * @var int[]
     */
    private array $pluginIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new LegacyPluginConfigurationStorage();
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->pluginIds as $pluginId) {
                \Cache::delete('PluginConf_' . $pluginId);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function testLoadForPluginsReturnsGroupedConfigurationValues(): void
    {
        $firstPluginId = $this->createPlugin('first-plugin');
        $secondPluginId = $this->createPlugin('second-plugin');

        $this->insertConfiguration($firstPluginId, 'first-key', 'first-value');
        $this->insertConfiguration($secondPluginId, 'second-key', 'second-value');

        $this->assertSame(
            [
                $firstPluginId => [
                    'first-key' => 'first-value',
                ],
                $secondPluginId => [
                    'second-key' => 'second-value',
                ],
            ],
            $this->storage->loadForPlugins([
                $firstPluginId,
                (string) $firstPluginId,
                $secondPluginId,
            ])
        );
    }

    public function testLoadForPluginsLoadsMissingConfigurationsWithoutReloadingCachedOnes(): void
    {
        $firstPluginId = $this->createPlugin('first-plugin');
        $secondPluginId = $this->createPlugin('second-plugin');

        $this->insertConfiguration($firstPluginId, 'key', 'cached-value');

        $this->assertSame(
            [
                $firstPluginId => [
                    'key' => 'cached-value',
                ],
            ],
            $this->storage->loadForPlugins([$firstPluginId])
        );

        $this->insertConfiguration($secondPluginId, 'key', 'loaded-value');

        $this->DB->exec(
            'UPDATE `' . PREFIX . 'plugin_conf` '
            . 'SET `value` = "changed-value" '
            . 'WHERE `pluginid` = ' . $firstPluginId
        );

        $this->assertSame(
            [
                $firstPluginId => [
                    'key' => 'cached-value',
                ],
                $secondPluginId => [
                    'key' => 'loaded-value',
                ],
            ],
            $this->storage->loadForPlugins([$firstPluginId, $secondPluginId])
        );
    }

    public function testLoadForPluginsCachesEmptyConfigurations(): void
    {
        $pluginId = $this->createPlugin('empty-plugin');

        $this->assertSame(
            [
                $pluginId => [],
            ],
            $this->storage->loadForPlugins([$pluginId])
        );

        $this->insertConfiguration($pluginId, 'key', 'new-value');

        $this->assertSame(
            [
                $pluginId => [],
            ],
            $this->storage->loadForPlugins([$pluginId])
        );
    }

    public function testInsertInvalidatesOnlyTheAffectedPluginConfiguration(): void
    {
        $firstPluginId = $this->createPlugin('first-plugin');
        $secondPluginId = $this->createPlugin('second-plugin');

        $this->insertConfiguration($firstPluginId, 'existing-key', 'existing-value');
        $this->insertConfiguration($secondPluginId, 'key', 'cached-value');

        $this->storage->loadForPlugins([$firstPluginId, $secondPluginId]);

        $this->DB->exec(
            'UPDATE `' . PREFIX . 'plugin_conf` '
            . 'SET `value` = "changed-value" '
            . 'WHERE `pluginid` = ' . $secondPluginId
        );

        $this->storage->insert($firstPluginId, 'new-key', 'new-value');

        $this->assertSame(
            [
                $firstPluginId => [
                    'existing-key' => 'existing-value',
                    'new-key' => 'new-value',
                ],
                $secondPluginId => [
                    'key' => 'cached-value',
                ],
            ],
            $this->storage->loadForPlugins([$firstPluginId, $secondPluginId])
        );
    }

    public function testUpdateInvalidatesOnlyTheAffectedPluginConfiguration(): void
    {
        $firstPluginId = $this->createPlugin('first-plugin');
        $secondPluginId = $this->createPlugin('second-plugin');

        $this->insertConfiguration($firstPluginId, 'key', 'old-value');
        $this->insertConfiguration($secondPluginId, 'key', 'cached-value');

        $this->storage->loadForPlugins([$firstPluginId, $secondPluginId]);

        $this->DB->exec(
            'UPDATE `' . PREFIX . 'plugin_conf` '
            . 'SET `value` = "changed-value" '
            . 'WHERE `pluginid` = ' . $secondPluginId
        );

        $this->storage->update($firstPluginId, 'key', 'updated-value');

        $this->assertSame(
            [
                $firstPluginId => [
                    'key' => 'updated-value',
                ],
                $secondPluginId => [
                    'key' => 'cached-value',
                ],
            ],
            $this->storage->loadForPlugins([$firstPluginId, $secondPluginId])
        );
    }

    private function createPlugin(string $key): int
    {
        $pluginId = $this->DB->insert(
            'plugin',
            ['key', 'type', 'active', 'order'],
            [$key, 'stat', 1, 0]
        );

        $this->assertIsInt($pluginId);

        $this->pluginIds[] = $pluginId;

        return $pluginId;
    }

    private function insertConfiguration(int $pluginId, string $key, string $value): void
    {
        $id = $this->DB->insert(
            'plugin_conf',
            ['pluginid', 'config', 'value'],
            [$pluginId, $key, $value]
        );

        $this->assertIsInt($id);
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Tests\CoreBundle\Services;

use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginConfigurationStorage;
use Runalyze\Tests\DatabaseTestCase;

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
        foreach ($this->pluginIds as $pluginId) {
            \Cache::delete('PluginConf_' . $pluginId);
        }

        parent::tearDown();
    }

    public function testLoadForPluginsReturnsConfigurationGroupedByPluginId(): void
    {
        $firstPluginId = $this->createPlugin();
        $secondPluginId = $this->createPlugin();

        $this->insertConfiguration($firstPluginId, 'first-setting', 'first-value');
        $this->insertConfiguration($secondPluginId, 'second-setting', 'second-value');

        self::assertSame([
            $firstPluginId => ['first-setting' => 'first-value'],
            $secondPluginId => ['second-setting' => 'second-value'],
        ], $this->storage->loadForPlugins([$firstPluginId, $secondPluginId]));
    }

    public function testLoadForPluginsLoadsMissingPluginConfigurationsWithoutRefreshingCachedPlugins(): void
    {
        $cachedPluginId = $this->createPlugin();
        $missingPluginId = $this->createPlugin();

        $this->insertConfiguration($cachedPluginId, 'cached-setting', 'cached-value');

        self::assertSame([
            $cachedPluginId => ['cached-setting' => 'cached-value'],
        ], $this->storage->loadForPlugins([$cachedPluginId]));

        $this->insertConfiguration($cachedPluginId, 'new-cached-setting', 'not-loaded');
        $this->insertConfiguration($missingPluginId, 'missing-setting', 'missing-value');

        self::assertSame([
            $cachedPluginId => ['cached-setting' => 'cached-value'],
            $missingPluginId => ['missing-setting' => 'missing-value'],
        ], $this->storage->loadForPlugins([$cachedPluginId, $missingPluginId]));
    }

    public function testLoadForPluginsCachesEmptyConfigurations(): void
    {
        $pluginId = $this->createPlugin();

        self::assertSame([$pluginId => []], $this->storage->loadForPlugins([$pluginId]));

        $this->insertConfiguration($pluginId, 'new-setting', 'new-value');

        self::assertSame([$pluginId => []], $this->storage->loadForPlugins([$pluginId]));
    }

    public function testInsertInvalidatesOnlyTheAffectedPluginCache(): void
    {
        $pluginId = $this->createPlugin();
        $otherPluginId = $this->createPlugin();

        $this->insertConfiguration($pluginId, 'existing-setting', 'existing-value');
        $this->insertConfiguration($otherPluginId, 'other-setting', 'other-value');

        $this->storage->loadForPlugins([$pluginId, $otherPluginId]);

        $this->insertConfiguration($otherPluginId, 'external-setting', 'not-loaded');

        $this->storage->insert($pluginId, 'inserted-setting', 'inserted-value');

        self::assertSame([
            $pluginId => [
                'existing-setting' => 'existing-value',
                'inserted-setting' => 'inserted-value',
            ],
            $otherPluginId => ['other-setting' => 'other-value'],
        ], $this->storage->loadForPlugins([$pluginId, $otherPluginId]));
    }

    public function testUpdateInvalidatesOnlyTheAffectedPluginCache(): void
    {
        $pluginId = $this->createPlugin();
        $otherPluginId = $this->createPlugin();

        $this->insertConfiguration($pluginId, 'setting', 'original-value');
        $this->insertConfiguration($otherPluginId, 'other-setting', 'original-other-value');

        $this->storage->loadForPlugins([$pluginId, $otherPluginId]);

        $this->DB->query(
            'UPDATE `' . PREFIX . 'plugin_conf` '
            . 'SET `value`="external-value" '
            . 'WHERE `pluginid`=' . $otherPluginId . ' AND `config`="other-setting"'
        );

        $this->storage->update($pluginId, 'setting', 'updated-value');

        self::assertSame([
            $pluginId => ['setting' => 'updated-value'],
            $otherPluginId => ['other-setting' => 'original-other-value'],
        ], $this->storage->loadForPlugins([$pluginId, $otherPluginId]));
    }

    private function createPlugin(): int
    {
        $pluginId = $this->DB->insert(
            'plugin',
            ['key', 'type'],
            ['LegacyPluginConfigurationStorageTest', 'stat']
        );

        if (! is_int($pluginId)) {
            self::fail('The plugin fixture could not be inserted.');
        }

        $this->pluginIds[] = $pluginId;

        return $pluginId;
    }

    private function insertConfiguration(int $pluginId, string $key, string $value): void
    {
        $insertedId = $this->DB->insert(
            'plugin_conf',
            ['pluginid', 'config', 'value'],
            [$pluginId, $key, $value]
        );

        self::assertIsInt($insertedId);
    }
}

<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class PluginFactoryTestFixture extends PluginPanel
{
    final public function name(): string
    {
        return 'Fixture';
    }

    final public function description(): string
    {
        return 'Fixture plugin for PluginFactoryTest.';
    }

    protected function displayContent(): void
    {
    }
}

#[Group('plugin')]
final class PluginFactoryTest extends TestCase
{
    private ?array $originalPlugins;

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(PluginFactory::class, 'Plugins');
        $this->originalPlugins = $property->getValue();
    }

    protected function tearDown(): void
    {
        $this->setPluginsData($this->originalPlugins);
    }

    private function setPluginsData(?array $plugins): void
    {
        $property = new \ReflectionProperty(PluginFactory::class, 'Plugins');
        $property->setValue(null, $plugins);
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginEntry(int $id, string $key, string $type, int $active, int $order = 1): array
    {
        return [
            'id' => $id,
            'key' => $key,
            'type' => $type,
            'active' => $active,
            'order' => $order,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function samplePlugins(): array
    {
        $panelType = PluginType::string(PluginType::PANEL);
        $statType = PluginType::string(PluginType::STAT);

        return [
            $this->pluginEntry(1, 'PluginFactoryTestFixture', $panelType, Plugin::ACTIVE, 1),
            $this->pluginEntry(2, 'SomeStatKey', $statType, Plugin::ACTIVE_NOT, 2),
            $this->pluginEntry(3, 'SomeVariousStatKey', $statType, Plugin::ACTIVE_VARIOUS, 3),
            $this->pluginEntry(4, 'SomeInactivePanelKey', $panelType, Plugin::ACTIVE_NOT, 4),
        ];
    }

    public function testDataForReturnsMatchingPluginData(): void
    {
        $plugins = $this->samplePlugins();
        $this->setPluginsData($plugins);

        $this->assertSame($plugins[1], PluginFactory::dataFor(2));
    }

    public function testDataForReturnsEmptyArrayForUnknownId(): void
    {
        $this->setPluginsData($this->samplePlugins());

        $this->assertSame([], PluginFactory::dataFor(999));
    }

    public function testAllIDsReturnsAllPluginIds(): void
    {
        $this->setPluginsData($this->samplePlugins());

        $this->assertSame([1, 2, 3, 4], PluginFactory::allIDs());
    }

    public function testKeyForReturnsMatchingKey(): void
    {
        $this->setPluginsData($this->samplePlugins());

        $this->assertSame('SomeStatKey', PluginFactory::keyFor(2));
    }

    public function testKeyForThrowsInvalidArgumentExceptionForUnknownId(): void
    {
        $this->setPluginsData($this->samplePlugins());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('No plugin found for id "999".');

        PluginFactory::keyFor(999);
    }

    public function testCompleteDataReturnsAllPluginsWhenTypeIsNull(): void
    {
        $plugins = $this->samplePlugins();
        $this->setPluginsData($plugins);
        $factory = new PluginFactory();

        $this->assertSame($plugins, $factory->completeData());
    }

    public function testCompleteDataFiltersByType(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $result = $factory->completeData(PluginType::PANEL);
        $this->assertCount(2, $result);
        foreach ($result as $plugin) {
            $this->assertSame(PluginType::string(PluginType::PANEL), $plugin['type']);
        }
    }

    public function testNewInstanceCreatesInstanceForKnownKey(): void
    {
        $this->setPluginsData([
            $this->pluginEntry(PluginInstaller::ID, 'PluginFactoryTestFixture', PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
        ]);
        $factory = new PluginFactory();

        $instance = $factory->newInstance('PluginFactoryTestFixture');

        $this->assertInstanceOf(PluginFactoryTestFixture::class, $instance);
    }

    public function testNewInstanceThrowsInvalidArgumentExceptionForUnknownKey(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Plugin with key "NoSuchKey" is not installed.');

        $factory->newInstance('NoSuchKey');
    }

    public function testNewInstanceForDelegatesToKeyForAndNewInstance(): void
    {
        $this->setPluginsData([
            $this->pluginEntry(PluginInstaller::ID, 'PluginFactoryTestFixture', PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
        ]);
        $factory = new PluginFactory();

        $instance = $factory->newInstanceFor(PluginInstaller::ID);

        $this->assertInstanceOf(PluginFactoryTestFixture::class, $instance);
    }

    public function testNewInstallerInstanceCreatesInstanceWithInstallerId(): void
    {
        $factory = new PluginFactory();

        $instance = $factory->newInstallerInstance(PluginFactoryTestFixture::class);

        $this->assertInstanceOf(PluginFactoryTestFixture::class, $instance);
        $this->assertSame(PluginInstaller::ID, $instance->id());
    }

    public function testIsInstalledIsTrueForKnownKey(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertTrue($factory->isInstalled('SomeStatKey'));
    }

    public function testIsInstalledIsFalseForUnknownKey(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertFalse($factory->isInstalled('NoSuchKey'));
    }

    public function testAllPluginsReturnsAllKeysRegardlessOfState(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertSame(
            ['PluginFactoryTestFixture', 'SomeStatKey', 'SomeVariousStatKey', 'SomeInactivePanelKey'],
            $factory->allPlugins()
        );
    }

    public function testActivePluginsReturnsOnlyActiveKeys(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertSame(['PluginFactoryTestFixture'], $factory->activePlugins());
    }

    public function testInactivePluginsWithNoTypeFilterReturnsAllInactiveKeysRegardlessOfType(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertSame(['SomeStatKey', 'SomeInactivePanelKey'], $factory->inactivePlugins());
    }

    public function testVariousPluginsReturnsOnlyVariousStatKeys(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertSame(['SomeVariousStatKey'], $factory->variousPlugins());
    }

    public function testEnabledPanelsReturnsOnlyNonInactivePanelKeys(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();
        $this->assertSame(['PluginFactoryTestFixture'], $factory->enabledPanels());
    }

    public function testActivePluginsCanBeFilteredByTypeViaGetPlugins(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->assertSame(['PluginFactoryTestFixture'], $factory->activePlugins(PluginType::PANEL));
    }

    public function testInstallPluginNoLongerThrowsFatalErrorOnPrivatePropertyAccess(): void
    {
        $instance = new PluginFactoryTestFixture(PluginInstaller::ID);

        $this->assertSame(PluginInstaller::ID, $instance->id());
    }

    public function testNotInstalledPluginsExcludesAnAlreadyInstalledPlugin(): void
    {
        $this->setPluginsData([
            $this->pluginEntry(1, 'RunalyzePluginStat_Wettkampf', PluginType::string(PluginType::STAT), Plugin::ACTIVE),
        ]);
        $factory = new PluginFactory();

        $result = $factory->notInstalledPlugins();
        $keys = array_column($result, 'key');

        $this->assertNotContains('RunalyzePluginStat_Wettkampf', $keys);
    }

    public function testNotInstalledPluginsReturnsArrayOfEntriesWithKeyField(): void
    {
        $this->setPluginsData([]);
        $factory = new PluginFactory();

        $result = $factory->notInstalledPlugins();

        $this->assertIsArray($result);
        foreach ($result as $entry) {
            $this->assertArrayHasKey('key', $entry);
        }
    }
}

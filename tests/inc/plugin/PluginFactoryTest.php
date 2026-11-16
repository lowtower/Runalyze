<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginConfigurationStorage;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginDataContext;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;

final class PluginFactoryTestFixture extends PluginPanel
{
    final public function name(): string
    {
        return 'Fixture';
    }

    final public function description(): string
    {
        return 'Fixture plugin for PluginFactoryTest.';
    }

    protected function displayContent(): void {}
}

final class PluginFactoryCustomConstructorFixture extends PluginPanel
{
    public function __construct(string $id)
    {
        parent::__construct((int) $id);
    }

    final public function name(): string
    {
        return 'Custom constructor fixture';
    }

    final public function description(): string
    {
        return 'Fixture plugin with a custom constructor.';
    }

    protected function displayContent(): void {}
}

final class PluginFactoryConfigurationFixture extends PluginPanel
{
    protected function initConfiguration(): void
    {
        $configuration = new PluginConfiguration($this->id());
        $configuration->addValue(new PluginConfigurationValue('fixture-setting', '', '', 'default'));
        $this->setConfiguration($configuration);
    }

    final public function name(): string
    {
        return 'Configuration fixture';
    }

    final public function description(): string
    {
        return 'Fixture plugin with a configuration value.';
    }

    protected function displayContent(): void {}
}

final class PluginFactoryTestConfigurationStorage implements PluginConfigurationStorageInterface
{
    /**
     * @var int[]
     */
    public array $loadedPluginIds = [];

    /**
     * @var array<int, array{int, string, string}>
     */
    public array $insertedValues = [];

    public function loadForPlugins(array $pluginIds): array
    {
        $this->loadedPluginIds = $pluginIds;

        return [];
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        $this->insertedValues[] = [$pluginId, $key, $value];
    }

    public function update(int $pluginId, string $key, string $value): void {}
}

#[Group('plugin')]
final class PluginFactoryTest extends TestCase
{
    private array $originalPlugins;

    private array $originalAccount;

    protected function setUp(): void
    {
        $pluginsProperty = new \ReflectionProperty(PluginFactory::class, 'Plugins');
        $this->originalPlugins = $pluginsProperty->getValue();

        $accountProperty = new \ReflectionProperty(SessionAccountHandler::class, 'Account');
        $this->originalAccount = $accountProperty->getValue();
    }

    protected function tearDown(): void
    {
        SessionAccountHandler::setAccount($this->originalAccount);

        $property = new \ReflectionProperty(PluginFactory::class, 'Plugins');
        $property->setValue(null, $this->originalPlugins);
    }

    private function setPluginsData(array $plugins): void
    {
        $property = new \ReflectionProperty(PluginFactory::class, 'Plugins');
        $allPlugins = $property->getValue();
        $allPlugins[SessionAccountHandler::getId()] = $plugins;
        $property->setValue(null, $allPlugins);
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

    public function testPluginDataIsIsolatedBetweenAccounts(): void
    {
        $accountAPlugins = $this->samplePlugins();
        $accountBPlugins = [
            $this->pluginEntry(22, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
        ];

        SessionAccountHandler::setAccount([
            'id' => 11,
        ]);
        $this->setPluginsData($accountAPlugins);

        $accountAPlugin = (new PluginFactory())->newInstance(PluginFactoryTestFixture::class);
        $this->assertSame(1, $accountAPlugin->id());

        SessionAccountHandler::setAccount([
            'id' => 22,
        ]);
        $this->setPluginsData($accountBPlugins);

        $accountBPlugin = (new PluginFactory())->newInstance(PluginFactoryTestFixture::class);
        $this->assertSame(22, $accountBPlugin->id());

        SessionAccountHandler::setAccount([
            'id' => 11,
        ]);

        $accountAPlugin = (new PluginFactory())->newInstance(PluginFactoryTestFixture::class);
        $this->assertSame(1, $accountAPlugin->id());
    }

    public function testLegacyPluginDataContextFindsScopedDataAndClearsAfterCallback(): void
    {
        $pluginData = $this->pluginEntry(17, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE);

        $result = LegacyPluginDataContext::withPluginData(
            $pluginData,
            fn (): ?array => LegacyPluginDataContext::find($pluginData['id'])
        );

        $this->assertSame($pluginData, $result);
        $this->assertNull(LegacyPluginDataContext::find(17));
    }

    public function testLegacyPluginDataContextPrefersInnermostMatchingData(): void
    {
        $outerPluginData = $this->pluginEntry(17, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE);
        $innerPluginData = $this->pluginEntry(17, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE_NOT);

        $result = LegacyPluginDataContext::withPluginData(
            $outerPluginData,
            fn (): ?array => LegacyPluginDataContext::withPluginData(
                $innerPluginData,
                fn (): ?array => LegacyPluginDataContext::find(17)
            )
        );

        $this->assertSame($innerPluginData, $result);
        $this->assertNull(LegacyPluginDataContext::find(17));
    }

    public function testLegacyPluginDataContextIsClearedWhenCallbackThrows(): void
    {
        $pluginData = $this->pluginEntry(17, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE);

        try {
            LegacyPluginDataContext::withPluginData($pluginData, static function (): never {
                throw new RuntimeException('Instantiation failed.');
            });
            $this->fail('Expected the callback to throw an exception.');
        } catch (RuntimeException $runtimeException) {
            $this->assertSame('Instantiation failed.', $runtimeException->getMessage());
        }

        $this->assertNull(LegacyPluginDataContext::find(17));
    }

    public function testLegacyPluginInstantiatorRequiresPluginDataForRegularInstances(): void
    {
        $instantiator = new LegacyPluginInstantiator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Plugin data is required for regular plugin instances.');

        $instantiator->instantiate(PluginFactoryTestFixture::class, 17);
    }

    public function testPluginCannotBeInstantiatedWithoutScopedPluginData(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('No plugin data available for plugin with id "17".');

        new PluginFactoryTestFixture(17);
    }

    public function testLegacyPluginInstantiatorUsesProvidedPluginData(): void
    {
        $pluginData = $this->pluginEntry(17, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 4);
        $instantiator = new LegacyPluginInstantiator();

        $plugin = $instantiator->instantiate(PluginFactoryTestFixture::class, 17, $pluginData);

        $this->assertTrue($plugin->isActive());
        $this->assertSame(4, $plugin->order());
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

    public function testNewInstanceSupportsCustomPluginConstructors(): void
    {
        $this->setPluginsData([
            $this->pluginEntry(17, PluginFactoryCustomConstructorFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
        ]);
        $factory = new PluginFactory(new LegacyPluginInstantiator());

        $instance = $factory->newInstance(PluginFactoryCustomConstructorFixture::class);

        $this->assertInstanceOf(PluginFactoryCustomConstructorFixture::class, $instance);
        $this->assertSame(17, $instance->id());
    }

    public function testNewInstanceUsesInjectedPluginConfigurationStorage(): void
    {
        $pluginId = 17;
        $this->setPluginsData([
            $this->pluginEntry($pluginId, PluginFactoryConfigurationFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
            $this->pluginEntry(18, PluginFactoryTestFixture::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE, 2),
        ]);
        $storage = new PluginFactoryTestConfigurationStorage();
        $factory = new PluginFactory(new LegacyPluginInstantiator($storage));

        $instance = $factory->newInstance(PluginFactoryConfigurationFixture::class);

        $this->assertSame('default', $instance->Configuration()->value('fixture-setting'));
        $this->assertSame([$pluginId], $storage->loadedPluginIds);
        $this->assertSame([[$pluginId, 'fixture-setting', 'default']], $storage->insertedValues);

        $configuration = new PluginConfiguration($pluginId);
        $storageProperty = new \ReflectionProperty(PluginConfiguration::class, 'Storage');
        $this->assertInstanceOf(LegacyPluginConfigurationStorage::class, $storageProperty->getValue($configuration));
    }

    public function testNewInstanceRejectsNonScalarPluginIdentifiers(): void
    {
        $this->setPluginsData([
            [
                'id' => [],
                'key' => PluginFactoryTestFixture::class,
                'type' => PluginType::string(PluginType::PANEL),
                'active' => Plugin::ACTIVE,
                'order' => 1,
            ],
        ]);
        $factory = new PluginFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Plugin "PluginFactoryTestFixture" has an invalid identifier.');

        $factory->newInstance(PluginFactoryTestFixture::class);
    }

    public function testNewInstanceRejectsClassesThatAreNotPlugins(): void
    {
        $this->setPluginsData([
            $this->pluginEntry(17, DateTimeImmutable::class, PluginType::string(PluginType::PANEL), Plugin::ACTIVE),
        ]);
        $factory = new PluginFactory(new LegacyPluginInstantiator());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Class "DateTimeImmutable" is not an instantiable plugin.');

        $factory->newInstance(DateTimeImmutable::class);
    }

    public function testNewInstanceThrowsInvalidArgumentExceptionForUnknownKey(): void
    {
        $this->setPluginsData($this->samplePlugins());
        $factory = new PluginFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Plugin with key "NoSuchKey" is not installed.');

        $factory->newInstance('NoSuchKey');
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

    public function testPluginCanBeInstantiatedWithInstallerId(): void
    {
        $instance = new PluginFactoryTestFixture(PluginInstaller::ID);

        $this->assertSame(PluginInstaller::ID, $instance->id());
    }

    public function testGetActivationLinkDoesNotRequirePluginConfiguration(): void
    {
        $plugin = new PluginFactoryTestFixture(PluginInstaller::ID);

        $activationLink = $plugin->getActivationLink('Activate plugin', Plugin::ACTIVE);

        $this->assertStringContainsString('call/call.Plugin.config.php?id=' . PluginInstaller::ID . '&active=' . Plugin::ACTIVE, $activationLink);
        $this->assertStringContainsString('Activate plugin', $activationLink);
    }
}

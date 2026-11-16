<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Plugin;
use PluginConfiguration;
use PluginConfigurationValue;
use PluginInstaller;
use PluginPanel;
use PluginType;
use ReflectionProperty;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginDataContext;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginConfigurationStorage;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use RuntimeException;

final class LegacyPluginInstantiatorCustomConstructorFixture extends PluginPanel
{
    public function __construct(string $id)
    {
        parent::__construct((int) $id);
    }

    public function name(): string
    {
        return 'Custom constructor fixture';
    }

    public function description(): string
    {
        return 'Fixture plugin with a custom constructor.';
    }

    protected function displayContent(): void {}
}

final class LegacyPluginInstantiatorConfigurationFixture extends PluginPanel
{
    protected function initConfiguration(): void
    {
        $configuration = new PluginConfiguration($this->id());
        $configuration->addValue(new PluginConfigurationValue('fixture-setting', '', '', 'default'));
        $this->setConfiguration($configuration);
    }

    public function name(): string
    {
        return 'Configuration fixture';
    }

    public function description(): string
    {
        return 'Fixture plugin with a configuration value.';
    }

    protected function displayContent(): void {}
}

final class LegacyPluginInstantiatorTestConfigurationStorage implements PluginConfigurationStorageInterface
{
    /**
     * @var list<int>
     */
    public array $loadedPluginIds = [];

    /**
     * @var list<array{int, string, string}>
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

final class LegacyPluginInstantiatorTestFixture extends PluginPanel
{
    public function name(): string
    {
        return 'Fixture';
    }

    public function description(): string
    {
        return 'Fixture plugin for LegacyPluginInstantiatorTest.';
    }

    protected function displayContent(): void {}
}

#[Group('plugin')]
final class LegacyPluginInstantiatorTest extends TestCase
{
    /**
     * @return array{id: int, key: string, type: string, active: int, order: int}
     */
    private function pluginData(int $id, int $active = 1, int $order = 1): array
    {
        return [
            'id' => $id,
            'key' => LegacyPluginInstantiatorTestFixture::class,
            'type' => PluginType::string(PluginType::PANEL),
            'active' => $active,
            'order' => $order,
        ];
    }

    public function testLegacyPluginDataContextFindsScopedDataAndClearsAfterCallback(): void
    {
        $pluginData = $this->pluginData(17);

        $result = LegacyPluginDataContext::withPluginData(
            $pluginData,
            fn (): ?array => LegacyPluginDataContext::find($pluginData['id'])
        );

        $this->assertSame($pluginData, $result);
        $this->assertNull(LegacyPluginDataContext::find(17));
    }

    public function testLegacyPluginDataContextPrefersInnermostMatchingData(): void
    {
        $outerPluginData = $this->pluginData(17, 1);
        $innerPluginData = $this->pluginData(17, 0);

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
        $pluginData = $this->pluginData(17);

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
        $this->expectExceptionMessage('Plugin data is required for regular plugin instances.');

        $instantiator->instantiate(LegacyPluginInstantiatorTestFixture::class, 17);
    }

    public function testPluginCannotBeInstantiatedWithoutScopedPluginData(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No plugin data available for plugin with id "17".');

        new LegacyPluginInstantiatorTestFixture(17);
    }

    public function testLegacyPluginInstantiatorUsesProvidedPluginData(): void
    {
        $pluginData = $this->pluginData(17, 1, 4);
        $instantiator = new LegacyPluginInstantiator();

        $plugin = $instantiator->instantiate(LegacyPluginInstantiatorTestFixture::class, 17, $pluginData);

        $this->assertTrue($plugin->isActive());
        $this->assertSame(4, $plugin->order());
    }

    public function testLegacyPluginInstantiatorSupportsCustomPluginConstructors(): void
    {
        $pluginData = [
            ...$this->pluginData(17),
        ];
        $instantiator = new LegacyPluginInstantiator();

        $plugin = $instantiator->instantiate(LegacyPluginInstantiatorCustomConstructorFixture::class, 17, $pluginData);

        $this->assertInstanceOf(LegacyPluginInstantiatorCustomConstructorFixture::class, $plugin);
        $this->assertSame(17, $plugin->id());
    }

    public function testLegacyPluginInstantiatorUsesInjectedPluginConfigurationStorage(): void
    {
        $pluginId = 17;
        $pluginData = [
            ...$this->pluginData($pluginId),
        ];
        $pluginData['key'] = LegacyPluginInstantiatorConfigurationFixture::class;
        $storage = new LegacyPluginInstantiatorTestConfigurationStorage();
        $instantiator = new LegacyPluginInstantiator($storage);

        $plugin = $instantiator->instantiate(LegacyPluginInstantiatorConfigurationFixture::class, $pluginId, $pluginData);

        $this->assertSame('default', $plugin->Configuration()->value('fixture-setting'));
        $this->assertSame([$pluginId], $storage->loadedPluginIds);
        $this->assertSame([[$pluginId, 'fixture-setting', 'default']], $storage->insertedValues);

        $configuration = new PluginConfiguration($pluginId);
        $storageProperty = new ReflectionProperty(PluginConfiguration::class, 'Storage');
        $this->assertInstanceOf(LegacyPluginConfigurationStorage::class, $storageProperty->getValue($configuration));
    }

    public function testLegacyPluginInstantiatorRejectsClassesThatAreNotPlugins(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "DateTimeImmutable" is not an instantiable plugin.');

        (new LegacyPluginInstantiator())->instantiate(DateTimeImmutable::class, 17, $this->pluginData(17));
    }

    public function testPluginCanBeInstantiatedWithInstallerId(): void
    {
        $instance = new LegacyPluginInstantiatorTestFixture(PluginInstaller::ID);

        $this->assertSame(PluginInstaller::ID, $instance->id());
    }

    public function testGetActivationLinkDoesNotRequirePluginConfiguration(): void
    {
        $plugin = new LegacyPluginInstantiatorTestFixture(PluginInstaller::ID);

        $activationLink = $plugin->getActivationLink('Activate plugin', Plugin::ACTIVE);

        $this->assertStringContainsString(
            'call/call.Plugin.config.php?id=' . PluginInstaller::ID . '&active=' . Plugin::ACTIVE,
            $activationLink
        );
        $this->assertStringContainsString('Activate plugin', $activationLink);
    }
}

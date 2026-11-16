<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use InvalidArgumentException;
use Plugin;
use PluginConfiguration;
use ReflectionClass;

final readonly class LegacyPluginInstantiator
{
    public function __construct(
        private ?PluginConfigurationStorageInterface $configurationStorage = null
    ) {}

    /**
     * @param array<string, mixed>|null $pluginData
     *
     * @throws InvalidArgumentException if the class is not an instantiable plugin implementation or plugin data is missing
     */
    public function instantiate(string $className, int|string $pluginId, ?array $pluginData = null): Plugin
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Plugin class "%s" does not exist.', $className));
        }

        $reflection = new ReflectionClass($className);

        if (! $reflection->isInstantiable() || ! $reflection->isSubclassOf(Plugin::class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" is not an instantiable plugin.', $className));
        }

        if ($pluginData === null && (int) $pluginId !== PluginInstaller::ID) {
            throw new InvalidArgumentException('Plugin data is required for regular plugin instances.');
        }

        $instantiate = fn (): Plugin => $pluginData === null
            ? new $className($pluginId)
            : LegacyPluginDataContext::withPluginData($pluginData, fn (): Plugin => new $className($pluginId));

        if (! $this->configurationStorage instanceof PluginConfigurationStorageInterface) {
            return $instantiate();
        }

        return PluginConfiguration::withStorage($this->configurationStorage, $instantiate);
    }
}

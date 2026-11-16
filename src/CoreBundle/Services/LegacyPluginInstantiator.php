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
     * @throws InvalidArgumentException if the class is not an instantiable plugin implementation
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

        $instantiate = fn (): Plugin => $pluginData === null
            ? new $className($pluginId)
            : LegacyPluginDataContext::withPluginData($pluginData, fn (): Plugin => new $className($pluginId));

        if (! $this->configurationStorage instanceof PluginConfigurationStorageInterface) {
            return $instantiate();
        }

        return PluginConfiguration::withStorage($this->configurationStorage, $instantiate);
    }
}

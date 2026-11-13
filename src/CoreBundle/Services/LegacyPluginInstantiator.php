<?php

namespace Runalyze\Bundle\CoreBundle\Services;

use InvalidArgumentException;
use Plugin;
use PluginConfiguration;
use ReflectionClass;

final class LegacyPluginInstantiator
{
    public function __construct(
        private readonly ?PluginConfigurationStorageInterface $configurationStorage = null
    ) {}

    /**
     * @throws InvalidArgumentException if the class is not an instantiable plugin implementation
     */
    public function instantiate(string $className, int|string $pluginId): Plugin
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Plugin class "%s" does not exist.', $className));
        }

        $reflection = new ReflectionClass($className);

        if (! $reflection->isInstantiable() || ! $reflection->isSubclassOf(Plugin::class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" is not an instantiable plugin.', $className));
        }

        if ($this->configurationStorage === null) {
            return new $className($pluginId);
        }

        return PluginConfiguration::withStorage(
            $this->configurationStorage,
            fn (): Plugin => new $className($pluginId)
        );
    }
}

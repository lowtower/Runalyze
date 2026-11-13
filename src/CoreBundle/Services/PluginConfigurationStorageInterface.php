<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Services;

interface PluginConfigurationStorageInterface
{
    /**
     * @param int[] $pluginIds
     * @return array<int, array<string, string>>
     */
    public function loadForPlugins(array $pluginIds): array;

    public function insert(int $pluginId, string $key, string $value): void;

    public function update(int $pluginId, string $key, string $value): void;

    public function clearCache(): void;
}

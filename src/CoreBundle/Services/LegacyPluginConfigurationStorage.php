<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

final class LegacyPluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    public function loadForPlugins(array $pluginIds): array
    {
        if ($pluginIds === []) {
            return [];
        }

        $cacheKey = 'PluginConf_' . \SessionAccountHandler::getId();
        $rows = \Cache::get($cacheKey);

        if ($rows === null) {
            $rows = \DB::getInstance()->query(
                'SELECT `pluginid`,`config`,`value` '
                . 'FROM `' . PREFIX . 'plugin_conf` '
                . 'WHERE `pluginid` IN (' . implode(',', array_map('intval', $pluginIds)) . ')'
            )->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);

            \Cache::set($cacheKey, $rows, 60);
        }

        $values = [];
        foreach ($rows as $pluginId => $pluginRows) {
            foreach ($pluginRows as $row) {
                $values[(int) $pluginId][$row['config']] = $row['value'];
            }
        }

        return $values;
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        \DB::getInstance()->insert('plugin_conf', ['pluginid', 'config', 'value'], [$pluginId, $key, $value]);
        $this->clearCache();
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        \DB::getInstance()->updateWhere(
            'plugin_conf',
            '`pluginid`=' . $pluginId . ' AND `config`="' . $key . '"',
            'value',
            $value
        );
        $this->clearCache();
    }

    private function clearCache(): void
    {
        \Cache::delete('PluginConf_' . \SessionAccountHandler::getId());
    }
}

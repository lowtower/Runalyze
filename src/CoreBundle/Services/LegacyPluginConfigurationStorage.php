<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

final class LegacyPluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    public function loadForPlugins(array $pluginIds): array
    {
        $pluginIds = array_values(array_unique(array_map(intval(...), $pluginIds)));

        if ($pluginIds === []) {
            return [];
        }

        $values = [];
        $missingPluginIds = [];

        foreach ($pluginIds as $pluginId) {
            $rows = \Cache::get($this->cacheKey($pluginId));

            if ($rows === null) {
                $missingPluginIds[] = $pluginId;
                continue;
            }

            $values[$pluginId] = $this->mapRows($rows);
        }

        if ($missingPluginIds !== []) {
            $rows = \DB::getInstance()->query(
                'SELECT `pluginid`,`config`,`value` '
                . 'FROM `' . PREFIX . 'plugin_conf` '
                . 'WHERE `pluginid` IN (' . implode(',', $missingPluginIds) . ')'
            )->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);

            foreach ($missingPluginIds as $pluginId) {
                $pluginRows = $rows[$pluginId] ?? [];
                \Cache::set($this->cacheKey($pluginId), $pluginRows, 60);
                $values[$pluginId] = $this->mapRows($pluginRows);
            }
        }

        $orderedValues = [];

        foreach ($pluginIds as $pluginId) {
            $orderedValues[$pluginId] = $values[$pluginId];
        }

        return $orderedValues;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @return array<string, string>
     */
    private function mapRows(array $rows): array
    {
        $values = [];

        foreach ($rows as $row) {
            $values[$row['config']] = $row['value'];
        }

        return $values;
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        \DB::getInstance()->insert('plugin_conf', ['pluginid', 'config', 'value'], [$pluginId, $key, $value]);
        $this->clearCache($pluginId);
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        \DB::getInstance()->updateWhere(
            'plugin_conf',
            '`pluginid`=' . $pluginId . ' AND `config`="' . $key . '"',
            'value',
            $value
        );
        $this->clearCache($pluginId);
    }

    private function cacheKey(int $pluginId): string
    {
        return 'PluginConf_' . $pluginId;
    }

    private function clearCache(int $pluginId): void
    {
        \Cache::delete($this->cacheKey($pluginId));
    }
}

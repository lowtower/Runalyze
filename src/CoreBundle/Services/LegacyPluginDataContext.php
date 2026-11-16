<?php

namespace Runalyze\\Bundle\\CoreBundle\\Services;

final class LegacyPluginDataContext
{
    /**
     * @var list<array<string, mixed>>
     */
    private static array $pluginDataStack = [];

    /**
     * @param array<string, mixed> $pluginData
     */
    public static function withPluginData(array $pluginData, callable $callback): mixed
    {
        self::$pluginDataStack[] = $pluginData;

        try {
            return $callback();
        } finally {
            array_pop(self::$pluginDataStack);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $pluginId): ?array
    {
        foreach (array_reverse(self::$pluginDataStack) as $pluginData) {
            if (isset($pluginData['id']) && (int) $pluginData['id'] === $pluginId) {
                return $pluginData;
            }
        }

        return null;
    }
}

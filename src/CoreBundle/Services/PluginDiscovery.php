<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;

final readonly class PluginDiscovery
{
    public function __construct(
        private ModernPanelRegistry $modernPanelRegistry,
        private ModernStatisticsRegistry $modernStatisticsRegistry
    ) {}

    /**
     * @return list<string>
     */
    public function findAvailablePluginKeys(): array
    {
        $keys = array_unique([...$this->modernPanelRegistry->getKeys(), ...$this->modernStatisticsRegistry->getKeys()]);
        sort($keys, SORT_STRING);

        return $keys;
    }
}

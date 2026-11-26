<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;

final readonly class PluginDiscovery
{
    public function __construct(
        private PanelRegistry $panelRegistry,
        private StatisticsRegistry $statisticsRegistry
    ) {}

    /**
     * @return list<string>
     */
    public function findAvailablePluginKeys(): array
    {
        $keys = array_unique([...$this->panelRegistry->getKeys(), ...$this->statisticsRegistry->getKeys()]);
        sort($keys, SORT_STRING);

        return $keys;
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use LogicException;

final readonly class ModernStatisticsRegistry
{
    /**
     * @var array<string, ModernStatisticsInterface>
     */
    private array $statistics;

    /**
     * @param iterable<ModernStatisticsInterface> $statistics
     */
    public function __construct(iterable $statistics)
    {
        $registered = [];

        foreach ($statistics as $statistic) {
            $key = $statistic->getKey();

            if (isset($registered[$key])) {
                throw new LogicException(sprintf('A modern statistics plugin with key "%s" is already registered.', $key));
            }

            $registered[$key] = $statistic;
        }

        $this->statistics = $registered;
    }

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        return array_keys($this->statistics);
    }

    public function find(string $key): ?ModernStatisticsInterface
    {
        return $this->statistics[$key] ?? null;
    }
}

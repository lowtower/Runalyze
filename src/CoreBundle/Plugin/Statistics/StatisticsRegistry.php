<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class StatisticsRegistry
{
    /**
     * @var array<string, StatisticsInterface>
     */
    private array $statistics;

    /**
     * @param iterable<StatisticsInterface> $statistics
     */
    public function __construct(
        #[AutowireIterator('runalyze.stat')]
        iterable $statistics
    ) {
        $registered = [];

        foreach ($statistics as $statistic) {
            $key = $statistic->getKey();

            if (isset($registered[$key])) {
                throw new LogicException(sprintf('A statistics plugin with key "%s" is already registered.', $key));
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

    public function find(string $key): ?StatisticsInterface
    {
        return $this->statistics[$key] ?? null;
    }
}

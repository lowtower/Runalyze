<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Analyse;

/**
 * @phpstan-type Totals array{count: int, distance: float, duration: float}
 */
final class AnalysisTable
{
    private const array EMPTY = [
        'count' => 0,
        'distance' => 0.0,
        'duration' => 0.0,
    ];

    /**
     * @var array<int, array<int, Totals>>
     */
    public private(set) array $cells = [];

    /**
     * @var array<int, Totals>
     */
    public private(set) array $rows = [];

    /**
     * @var array<int, Totals>
     */
    public private(set) array $columns = [];

    /**
     * @var Totals
     */
    public private(set) array $total = self::EMPTY;

    public function add(int $group, int $period, float $distance, float $duration): void
    {
        $this->cells[$group][$period] ??= self::EMPTY;
        $this->rows[$group] ??= self::EMPTY;
        $this->columns[$period] ??= self::EMPTY;

        $this->accumulate($this->cells[$group][$period], $distance, $duration);
        $this->accumulate($this->rows[$group], $distance, $duration);
        $this->accumulate($this->columns[$period], $distance, $duration);
        $this->accumulate($this->total, $distance, $duration);
    }

    /**
     * @param Totals $totals
     */
    private function accumulate(array &$totals, float $distance, float $duration): void
    {
        ++$totals['count'];
        $totals['distance'] += $distance;
        $totals['duration'] += $duration;
    }
}

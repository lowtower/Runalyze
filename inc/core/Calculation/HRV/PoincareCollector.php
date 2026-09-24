<?php

declare(strict_types=1);

/**
 * This file contains class::PoincareCollector
 * @package Runalyze\Calculation\HRV
 */

namespace Runalyze\Calculation\HRV;

use Runalyze\Model\HRV\Entity;

/**
 * Create data for a poincare plot
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\HRV
 */
class PoincareCollector
{
    /**
     * @deprecated since we now collect all pairs instead of deduplicating by preceding value
     */
    protected array $PlotData = [];

    /**
     * @var array<int, array{0: int|float, 1: int|float}>
     */
    protected array $Pairs = [];

    /**
     * Calculator for hrv statistics
     * @param int $Filter maximal difference to include in plot
     */
    public function __construct(
        protected Entity $Object,
        protected int $Filter = 200
    ) {
        $this->collectPlotData();
    }

    protected function collectPlotData(): void
    {
        $data = $this->Object->data();
        $num = $this->Object->num();

        for ($i = 1; $i < $num; ++$i) {
            if (abs($data[$i - 1] - $data[$i]) < $this->Filter) {
                // Legacy format, kept for backwards compatibility.
                // Note: identical preceding values overwrite each other here.
                $this->PlotData[(string) $data[$i - 1]] = $data[$i];

                $this->Pairs[] = [$data[$i - 1], $data[$i]];
            }
        }
    }

    /**
     * Plot data
     *
     * @deprecated use pairs() instead, this collapses duplicate preceding values
     */
    public function data(): array
    {
        return $this->PlotData;
    }

    /**
     * All (x, y) pairs for the poincare plot, without deduplication
     *
     * @return array<int, array{0: int|float, 1: int|float}>
     */
    public function pairs(): array
    {
        return $this->Pairs;
    }
}

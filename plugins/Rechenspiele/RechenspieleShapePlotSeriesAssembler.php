<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele;

use DateTimeImmutable;

/**
 * Converts calculated daily shape data into the series expected by the plot renderer.
 */
final readonly class RechenspieleShapePlotSeriesAssembler
{
    public function __construct(private RechenspieleShapePlotTimestampMapper $timestampMapper) {}

    /**
     * @param array{
     *     atl: array<int, float>,
     *     ctl: array<int, float>,
     *     tsb: array<int, float>,
     *     trimp: array<int, float>,
     *     vo2max: array<int, float>,
     *     vo2max_day: array<int, float>,
     *     max_atl: int,
     *     max_ctl: int,
     *     max_trimp: float,
     *     show_in_percent: bool
     * } $calculated
     * @param array<int, int> $marathonShape
     * @return array{
     *     atl: array<string, float>,
     *     ctl: array<string, float>,
     *     tsb: array<string, float>,
     *     trimp: array<string, float>,
     *     vo2max: array<string, float>,
     *     vo2max_day: array<string, float>,
     *     marathon_shape: array<string, int>,
     *     max_atl: int,
     *     max_ctl: int,
     *     max_trimp: float,
     *     show_in_percent: bool
     * }
     */
    public function assemble(
        array $calculated,
        array $marathonShape,
        DateTimeImmutable $startDay,
        int $warmupDays
    ): array {
        return [
            'atl' => $this->timestampMapper->map($calculated['atl'], $startDay, $warmupDays),
            'ctl' => $this->timestampMapper->map($calculated['ctl'], $startDay, $warmupDays),
            'tsb' => $this->timestampMapper->map($calculated['tsb'], $startDay, $warmupDays),
            'trimp' => $this->timestampMapper->map($calculated['trimp'], $startDay, $warmupDays),
            'vo2max' => $this->timestampMapper->map($calculated['vo2max'], $startDay, $warmupDays),
            'vo2max_day' => $this->timestampMapper->map($calculated['vo2max_day'], $startDay, $warmupDays),
            'marathon_shape' => $this->timestampMapper->map($marathonShape, $startDay, $warmupDays),
            'max_atl' => $calculated['max_atl'],
            'max_ctl' => $calculated['max_ctl'],
            'max_trimp' => $calculated['max_trimp'],
            'show_in_percent' => $calculated['show_in_percent'],
        ];
    }
}

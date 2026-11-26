<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Converts daily plot indices to account-local noon timestamps for Flot.
 */
final readonly class RechenspieleShapePlotTimestampMapper
{
    /**
     * @template TValue of int|float
     * @param array<int, TValue> $series
     * @return array<string, TValue>
     *
     * @throws InvalidArgumentException if a daily index or warmup is invalid
     */
    public function map(array $series, DateTimeImmutable $startDay, int $warmupDays): array
    {
        $result = [];

        foreach ($series as $index => $value) {
            $result[$this->timestampForIndex($startDay, $warmupDays, $index)] = $value;
        }

        return $result;
    }

    /**
     * @throws InvalidArgumentException if a daily index or warmup is invalid
     */
    public function timestampForIndex(DateTimeImmutable $startDay, int $warmupDays, int $index): string
    {
        if ($warmupDays < 0 || $index < 0) {
            throw new InvalidArgumentException('Shape plot indices and warmup must not be negative.');
        }

        $day = new DateTimeImmutable($startDay->format('Y-m-d'), $startDay->getTimezone());
        $day = $day->modify(sprintf('%+d days', $index - $warmupDays));

        return (string) ($day->setTime(12, 0)->getTimestamp() * 1000);
    }
}

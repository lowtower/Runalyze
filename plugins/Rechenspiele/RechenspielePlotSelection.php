<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele;

/**
 * Validated shape-plot selections from the existing window query parameters.
 */
final readonly class RechenspielePlotSelection
{
    private const array RANGE_KEYS = ['all', 'lasthalf', 'lastyear'];

    private const array MODELS = ['tsb', 'banister'];

    private function __construct(
        public string $range,
        public string $model
    ) {}

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query, int $startYear, int $currentYear): self
    {
        $range = $query['y'] ?? 'lasthalf';

        if (! is_string($range) || ! self::isValidRange($range, $startYear, $currentYear)) {
            $range = 'lasthalf';
        }

        $model = $query['m'] ?? 'tsb';

        if (! is_string($model) || ! in_array($model, self::MODELS, true)) {
            $model = 'tsb';
        }

        return new self($range, $model);
    }

    /**
     * @return list<string>
     */
    public static function availableRanges(int $startYear, int $currentYear): array
    {
        $years = [];

        for ($year = $currentYear; $year >= $startYear; --$year) {
            $years[] = (string) $year;
        }

        return [...self::RANGE_KEYS, ...$years];
    }

    /**
     * @return list<string>
     */
    public static function availableModels(): array
    {
        return self::MODELS;
    }

    private static function isValidRange(string $range, int $startYear, int $currentYear): bool
    {
        if (in_array($range, self::RANGE_KEYS, true)) {
            return true;
        }

        if (! preg_match('/^[0-9]{4}$/D', $range)) {
            return false;
        }

        $year = (int) $range;

        return $year >= $startYear && $year <= $currentYear;
    }
}

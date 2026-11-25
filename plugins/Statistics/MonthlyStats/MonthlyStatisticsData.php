<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableTime;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableValue;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use RuntimeException;

final class MonthlyStatisticsData
{
    private float $maximum = 1.0;

    private array $data = [];

    private ?ValueExtension $valueExtension = null;

    public function __construct(
        private readonly Selection $sportSelection,
        private readonly MonthlyStatisticsMetricSelection $metricSelection,
        TrainingRepository $trainingRepository,
        Account $account
    ) {
        $this->fetchDataFrom($trainingRepository, $account);
    }

    private function fetchDataFrom(TrainingRepository $trainingRepository, Account $account): void
    {
        $stats = $trainingRepository->getMonthlyStatsFor($account, $this->getColumn(), $this->getSportId());

        foreach ($stats as $result) {
            $year = (int) $result['year'];
            $month = (int) $result['month'];
            $value = (float) $result['value'];

            $this->data[$year] ??= array_fill(1, 12, 0.0);

            $this->data[$year][$month] = $value;

            if ($value > $this->maximum) {
                $this->maximum = $value;
            }
        }
    }

    private function getColumn(): ?string
    {
        return match ($this->metricSelection->getCurrentKey()) {
            MonthlyStatisticsMetricSelection::DISTANCE => 'distance',
            MonthlyStatisticsMetricSelection::TIME => 's',
            MonthlyStatisticsMetricSelection::ENERGY => 'kcal',
            MonthlyStatisticsMetricSelection::ELEVATION => 'elevation',
            MonthlyStatisticsMetricSelection::TRIMP => 'trimp',
            default => null,
        };
    }

    private function getSportId(): ?int
    {
        if (! $this->sportSelection->hasCurrentKey() || $this->sportSelection->getCurrentKey() == SportSelectionFactory::ALL) {
            return null;
        }

        return (int) $this->sportSelection->getCurrentKey();
    }

    public function setValueExtension(ValueExtension $valueExtension): void
    {
        $this->valueExtension = $valueExtension;
    }

    public function getSportSelection(): Selection
    {
        return $this->sportSelection;
    }

    public function getMetricSelection(): MonthlyStatisticsMetricSelection
    {
        return $this->metricSelection;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * @throws RuntimeException
     */
    public function getYears(): array
    {
        if ($this->isEmpty()) {
            throw new RuntimeException('MonthlyStatisticsData::getYears() called with no data available; check isEmpty() first.');
        }

        $allYears = array_keys($this->data);

        return range(max($allYears), min($allYears));
    }

    /**
     * @throws RuntimeException if setValueExtension() has not been called yet
     */
    public function getValue(int $year, int $month): DisplayableValue|DisplayableTime
    {
        if (! $this->valueExtension instanceof \Runalyze\Bundle\CoreBundle\Twig\ValueExtension) {
            throw new RuntimeException('Value extension must be set');
        }

        $rawValue = $this->getRawValue($year, $month);

        return match ($this->metricSelection->getCurrentKey()) {
            MonthlyStatisticsMetricSelection::DISTANCE => $this->valueExtension->distance($rawValue),
            MonthlyStatisticsMetricSelection::TIME => new DisplayableTime($rawValue),
            MonthlyStatisticsMetricSelection::ENERGY => $this->valueExtension->energy($rawValue),
            MonthlyStatisticsMetricSelection::ELEVATION => $this->valueExtension->elevation($rawValue),
            default => new DisplayableValue($rawValue, ''),
        };
    }

    public function getRawValue(int $year, int $month): float
    {
        if (isset($this->data[$year]) && isset($this->data[$year][$month])) {
            return $this->data[$year][$month];
        }

        return 0.0;
    }

    public function getMaximum(): float
    {
        return $this->maximum;
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableTime;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableValue;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use RuntimeException;

class AnalysisData
{
    protected float $Maximum = 1.0;

    protected array $Data = [];

    protected DisplayableValue $DefaultValue;

    protected ?ValueExtension $ValueExtension = null;

    public function __construct(
        protected Selection $SportSelection,
        protected AnalysisSelection $AnalysisSelection,
        TrainingRepository $trainingRepository,
        Account $account
    ) {
        $this->fetchDataFrom($trainingRepository, $account);
    }

    protected function fetchDataFrom(TrainingRepository $trainingRepository, Account $account): void
    {
        $stats = $trainingRepository->getMonthlyStatsFor($account, $this->getColumn(), $this->getSportId());

        foreach ($stats as $result) {
            $year = (int) $result['year'];
            $month = (int) $result['month'];
            $value = (float) $result['value'];

            $this->Data[$year] ??= array_fill(1, 12, 0.0);

            $this->Data[$year][$month] = $value;

            if ($value > $this->Maximum) {
                $this->Maximum = $value;
            }
        }
    }

    protected function getColumn(): ?string
    {
        return match ($this->AnalysisSelection->getCurrentKey()) {
            AnalysisSelection::DISTANCE => 'distance',
            AnalysisSelection::TIME => 's',
            AnalysisSelection::ENERGY => 'kcal',
            AnalysisSelection::ELEVATION => 'elevation',
            AnalysisSelection::TRIMP => 'trimp',
            default => null,
        };
    }

    protected function getSportId(): mixed
    {
        if (! $this->SportSelection->hasCurrentKey() || $this->SportSelection->getCurrentKey() == SportSelectionFactory::ALL) {
            return null;
        }

        return $this->SportSelection->getCurrentKey();
    }

    public function setValueExtension(ValueExtension $valueExtension): void
    {
        $this->ValueExtension = $valueExtension;
    }

    public function getSportSelection(): Selection
    {
        return $this->SportSelection;
    }

    public function getAnalysisSelection(): AnalysisSelection
    {
        return $this->AnalysisSelection;
    }

    public function isEmpty(): bool
    {
        return $this->Data === [];
    }

    /**
     * @throws RuntimeException
     */
    public function getYears(): array
    {
        if ($this->isEmpty()) {
            throw new RuntimeException('AnalysisData::getYears() called with no data available; check isEmpty() first.');
        }

        $allYears = array_keys($this->Data);

        return range(max($allYears), min($allYears));
    }

    /**
     * @throws RuntimeException if setValueExtension() has not been called yet
     */
    public function getValue(int $year, int $month): DisplayableValue|DisplayableTime
    {
        if (! $this->ValueExtension instanceof \Runalyze\Bundle\CoreBundle\Twig\ValueExtension) {
            throw new RuntimeException('Value extension must be set');
        }

        $rawValue = $this->getRawValue($year, $month);

        return match ($this->AnalysisSelection->getCurrentKey()) {
            AnalysisSelection::DISTANCE => $this->ValueExtension->distance($rawValue),
            AnalysisSelection::TIME => new DisplayableTime($rawValue),
            AnalysisSelection::ENERGY => $this->ValueExtension->energy($rawValue),
            AnalysisSelection::ELEVATION => $this->ValueExtension->elevation($rawValue),
            default => new DisplayableValue($rawValue, ''),
        };
    }

    public function getRawValue(int $year, int $month): float
    {
        if (isset($this->Data[$year]) && isset($this->Data[$year][$month])) {
            return $this->Data[$year][$month];
        }

        return 0.0;
    }

    public function getMaximum(): float
    {
        return $this->Maximum;
    }
}

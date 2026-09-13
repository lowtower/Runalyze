<?php

namespace Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableTime;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableValue;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;

class AnalysisData
{
    /**
     * @var int|float
     */
    protected $Maximum = 1;

    /**
     * @var array
     */
    protected $Data = [];

    /**
     * @var Selection
     */
    protected $SportSelection;

    /**
     * @var AnalysisSelection
     */
    protected $AnalysisSelection;

    /**
     * @var DisplayableValue
     */
    protected $DefaultValue;

    /**
     * @var null|ValueExtension
     */
    protected $ValueExtension;

    public function __construct(
        Selection $sportSelection,
        AnalysisSelection $analysisSelection,
        TrainingRepository $trainingRepository,
        Account $account
    ) {
        $this->SportSelection = $sportSelection;
        $this->AnalysisSelection = $analysisSelection;

        $this->fetchDataFrom($trainingRepository, $account);
    }

    protected function fetchDataFrom(TrainingRepository $trainingRepository, Account $account)
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

    /**
     * @return string|null
     */
    protected function getColumn()
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

    /**
     * @return mixed|null
     */
    protected function getSportId()
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

    /**
     * @return Selection
     */
    public function getSportSelection()
    {
        return $this->SportSelection;
    }

    /**
     * @return AnalysisSelection
     */
    public function getAnalysisSelection()
    {
        return $this->AnalysisSelection;
    }

    public function isEmpty(): bool
    {
        return $this->Data === [];
    }

    public function getYears(): array
    {
        $allYears = array_keys($this->Data);

        return range(max($allYears), min($allYears));
    }

    /**
     * @param int $year
     * @param int $month
     * @return DisplayableValue
     *
     * @throws \RuntimeException
     */
    public function getValue($year, $month)
    {
        if ($this->ValueExtension === null) {
            throw new \RuntimeException('Value extension must be set');
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

    /**
     * @param int $year
     * @param int $month
     * @return float
     */
    public function getRawValue($year, $month)
    {
        if (isset($this->Data[$year]) && isset($this->Data[$year][$month])) {
            return $this->Data[$year][$month];
        }

        return 0.0;
    }

    /**
     * @return float|int
     */
    public function getMaximum()
    {
        return $this->Maximum;
    }
}

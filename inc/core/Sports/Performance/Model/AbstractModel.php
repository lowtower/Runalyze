<?php

namespace Runalyze\Sports\Performance\Model;

/**
 * Model for human performance
 *
 * @see http://fellrnr.com/wiki/Modeling_Human_Performance
 */
abstract class AbstractModel
{
    /**
     * @var int
     */
    public const FITNESS = 0;

    /**
     * @var int
     */
    public const FATIGUE = 1;

    /**
     * @var int
     */
    public const PERFORMANCE = 2;

    protected array $Trimp = [];

    protected array $Fitness = [];

    protected array $Fatigue = [];

    protected array $Performance = [];

    /**
     * @var array array('from' => int, 'to' => int)
     */
    protected array $Range = [];

    /**
     * @param array $trimpData array('days back' => 'trimp value')
     */
    public function __construct(array $trimpData)
    {
        ksort($trimpData);

        $this->Trimp = $trimpData;
    }

    final public function setRange(int $from, int $to)
    {
        $this->Range = [
            'from' => $from,
            'to' => $to,
        ];
    }

    final public function calculate()
    {
        if ($this->Range === []) {
            if ($this->Trimp === []) {
                $this->setRange(0, 0);
            } else {
                $Keys = array_keys($this->Trimp);
                $this->setRange($Keys[0], max(0, end($Keys)));
            }
        }

        $this->prepareArrays();
        $this->calculateArrays();
        $this->finishArrays();
    }

    protected function prepareArrays()
    {
        $this->Fitness[$this->Range['from'] - 1] = 0;
        $this->Fatigue[$this->Range['from'] - 1] = 0;
        $this->Performance[$this->Range['from'] - 1] = 0;
    }

    protected function finishArrays()
    {
        unset($this->Fitness[$this->Range['from'] - 1]);
        unset($this->Fatigue[$this->Range['from'] - 1]);
        unset($this->Performance[$this->Range['from'] - 1]);
    }

    abstract protected function calculateArrays();

    /**
     * @return array<int, mixed[]> array(enum => data)
     */
    final public function getArrays()
    {
        return [
            self::FITNESS => $this->Fitness,
            self::FATIGUE => $this->Fatigue,
            self::PERFORMANCE => $this->Performance,
        ];
    }

    final public function maxFitness(): int
    {
        return (int) round(max($this->Fitness));
    }

    final public function maxFatigue(): int
    {
        return (int) round(max($this->Fatigue));
    }

    final public function maxPerformance(): int
    {
        return (int) round(max($this->Performance));
    }

    final public function minPerformance(): int
    {
        return (int) round(min($this->Performance));
    }

    /**
     * @param int $index 0 for today
     * @return int
     */
    final public function fitnessAt($index)
    {
        return $this->at($index, self::FITNESS);
    }

    /**
     * @param int $index 0 for today
     */
    final public function fatigueAt($index): int
    {
        return $this->at($index, self::FATIGUE);
    }

    /**
     * @param int $index 0 for today
     */
    final public function performanceAt($index): int
    {
        return $this->at($index, self::PERFORMANCE);
    }

    /**
     * @param int $index e.g. -1 for yesterday
     * @param int $enum enum for value
     */
    private function at(int $index, int $enum): int
    {
        $Array = $this->arrayFor($enum);

        if (isset($Array[$index])) {
            return (int) round($Array[$index]);
        }

        return 0;
    }

    private function arrayFor(int $enum): array
    {
        return match ($enum) {
            self::FITNESS => $this->Fitness,
            self::FATIGUE => $this->Fatigue,
            self::PERFORMANCE => $this->Performance,
            default => [],
        };
    }
}

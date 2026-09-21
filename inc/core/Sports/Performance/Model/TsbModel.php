<?php

declare(strict_types=1);

namespace Runalyze\Sports\Performance\Model;

/**
 * Model for human performance: TSB model
 *
 * @see http://fellrnr.com/wiki/Modeling_Human_Performance#The_TSB_Model
 */
class TsbModel extends AbstractModel
{
    protected float $LambdaCTL;

    protected float $LambdaATL;

    /**
     * @param array $trimpData array('days back' => 'trimp value')
     */
    public function __construct(
        array $trimpData,
        protected int $DaysForCtl = 42,
        protected int $DaysForAtl = 7
    ) {
        $this->LambdaCTL = 2 / ($this->DaysForCtl + 1);
        $this->LambdaATL = 2 / ($this->DaysForAtl + 1);

        parent::__construct($trimpData);
    }

    protected function calculateArrays()
    {
        for ($i = $this->Range['from']; $i <= $this->Range['to']; ++$i) {
            $T = $this->Trimp[$i] ?? 0;

            $this->Fitness[$i] = $T * $this->LambdaCTL + (1 - $this->LambdaCTL) * $this->Fitness[$i - 1];
            $this->Fatigue[$i] = $T * $this->LambdaATL + (1 - $this->LambdaATL) * $this->Fatigue[$i - 1];
            $this->Performance[$i] = $this->Fitness[$i] - $this->Fatigue[$i];
        }
    }

    /**
     * @return float number of days required to reach TSB[n+x] <= 0
     */
    public function restDays(float $ctl, float $atl): float
    {
        if ($atl == 0 || $this->LambdaATL === $this->LambdaCTL || $this->LambdaCTL == 1) {
            return 0.0;
        }

        $ctl = max(1.0, $ctl);
        $restDays = log($ctl / $atl) / (log((1 - $this->LambdaATL) / (1 - $this->LambdaCTL)));

        return max(0.0, $restDays);
    }

    /**
     * @param int $index 0 for today
     * @return float number of days required to reach TSB[n+x] <= 0
     */
    public function restDaysAt(int $index): float
    {
        return $this->restDays($this->fitnessAt($index), $this->fatigueAt($index));
    }

    /**
     * @return float maximal trimp value to keep TSB[n+1] >= 0
     */
    public function maxTrimpToBalanced(float $ctl, float $atl): int|float
    {
        if ($this->LambdaCTL === $this->LambdaATL) {
            return 0;
        }

        return ($atl - $ctl) / ($this->LambdaCTL - $this->LambdaATL);
    }

    /**
     * @param int $index 0 for today
     * @return float maximal trimp value to keep TSB[n+1] >= 0
     */
    public function maxTrimpToBalancedAt(int $index): float
    {
        return $this->maxTrimpToBalanced($this->fitnessAt($index), $this->fatigueAt($index));
    }
}

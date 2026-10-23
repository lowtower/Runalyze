<?php

namespace Runalyze\Sports\Running;

use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;

class MarathonShape
{
    /**
     * @var float [ml/kg/min]
     */
    public const float MINIMAL_EFFECTIVE_VO2MAX = 25.0;

    /**
     * @param float $EffectiveVO2max [ml/kg/min]
     */
    public function __construct(
        protected $EffectiveVO2max,
        protected BasicEndurance $Configuration
    ) {}

    /**
     * @param float $effectiveVO2max [ml/kg/min]
     */
    public function setEffectiveVO2max(float $effectiveVO2max): self
    {
        $this->EffectiveVO2max = $effectiveVO2max;

        return $this;
    }

    /**
     * @param float $totalDistanceInTimePeriod [km]
     * @param float $totalWeightedRelativeLongJogPoints one point per week matches the target
     * @return int [0, inf)
     */
    public function getShapeFor(float $totalDistanceInTimePeriod, float $totalWeightedRelativeLongJogPoints, ?int $numberOfDaysSinceFirstActivity = null): int
    {
        $percentageWeekly = $totalDistanceInTimePeriod * 7.0 / $this->Configuration->getDaysToConsiderForWeeklyMileage($numberOfDaysSinceFirstActivity) / $this->getTargetForWeeklyMileage();
        $percentageLongJogs = $totalWeightedRelativeLongJogPoints * 7.0 / $this->Configuration->getDaysToConsiderForLongJogs();

        return (int) round(100.0 * (
            $percentageWeekly * $this->Configuration->getPercentageForWeeklyMileage()
            + $percentageLongJogs * $this->Configuration->getPercentageForLongJogs()
        ));
    }

    /**
     * @return float [km]
     */
    public function getTargetForWeeklyMileage(): float
    {
        return max($this->EffectiveVO2max, self::MINIMAL_EFFECTIVE_VO2MAX) ** 1.135;
    }

    /**
     * @return float [km]
     */
    public function getTargetForLongJogEachWeek(): float
    {
        return log(max($this->EffectiveVO2max, self::MINIMAL_EFFECTIVE_VO2MAX) / 4.0) * 12.0;
    }
}

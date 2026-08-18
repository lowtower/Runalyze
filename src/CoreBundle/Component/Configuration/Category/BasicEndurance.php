<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class BasicEndurance extends AbstractCategory
{
    /** @var float [ml/kg/min] */
    const MINIMAL_EFFECTIVE_VO2MAX = 25.0;

    /**
     * @return array
     */
    public function getDefaultVariables(): array
    {
        return [
            'BE_MIN_KM_FOR_LONGJOG' => '13',
            'BE_DAYS_FOR_LONGJOGS' => '70',
            'BE_DAYS_FOR_WEEK_KM' => '182',
            'BE_DAYS_FOR_WEEK_KM_MIN' => '70',
            'BE_PERCENTAGE_WEEK_KM' => '0.67',
        ];
    }

    /**
     * @return int
     */
    public function getDaysToConsider(): int
    {
        return max($this->getDaysToConsiderForWeeklyMileage(), $this->getDaysToConsiderForLongJogs());
    }

    /**
     * @return int
     */
    public function getDaysToConsiderForLongJogs(): int
    {
        return (int) $this->Variables['BE_DAYS_FOR_LONGJOGS'];
    }

    /**
     * @return int
     */
    public function getDaysToConsiderForWeeklyMileage($numberOfDaysSinceFirstActivity = null)
    {
        if (null !== $numberOfDaysSinceFirstActivity) {
            return max(
                min((int) $this->Variables['BE_DAYS_FOR_WEEK_KM'], $numberOfDaysSinceFirstActivity),
                (int) $this->Variables['BE_DAYS_FOR_WEEK_KM_MIN']
            );
        }

        return (int) $this->Variables['BE_DAYS_FOR_WEEK_KM'];
    }

    /**
     * @return int [km]
     */
    public function getMinimalDistanceForLongJog(): int
    {
        return (int) $this->Variables['BE_MIN_KM_FOR_LONGJOG'];
    }

    /**
     * @return float [0.00 .. 1.00]
     */
    public function getPercentageForWeeklyMileage(): float
    {
        return (float) $this->Variables['BE_PERCENTAGE_WEEK_KM'];
    }

    /**
     * @return float [0.00 .. 1.00]
     */
    public function getPercentageForLongJogs(): float
    {
        return 1.0 - $this->getPercentageForWeeklyMileage();
    }

    /**
     * @return string
     */
    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\BasicEndurance::class;
    }
}

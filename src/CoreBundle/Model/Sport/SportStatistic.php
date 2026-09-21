<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Sport;

class SportStatistic
{
    /**
     * @var null|int
     */
    protected $NumberOfActivities;

    /**
     * @var null|int
     */
    protected $NumberOfActivitiesWithDistance;

    /**
     * @var null|float
     */
    protected $TotalDistance;

    /**
     * @var null|float
     */
    protected $TotalDuration;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\Sport $Sport
    ) {
    }

    public function getSport(): \Runalyze\Bundle\CoreBundle\Entity\Sport
    {
        return $this->Sport;
    }

    /**
     * @param int $numberOfActivities
     * @param int|null $numberOfActivitiesWithDistance
     */
    public function setNumberOfActivities($numberOfActivities, $numberOfActivitiesWithDistance = null): void
    {
        $this->NumberOfActivities = (int) $numberOfActivities;
        $this->NumberOfActivitiesWithDistance = $numberOfActivitiesWithDistance !== null ? (int) $numberOfActivitiesWithDistance : null;
    }

    /**
     * @return int|null
     */
    public function getNumberOfActivities()
    {
        return $this->NumberOfActivities;
    }

    public function areMostActivitiesWithDistance(): bool
    {
        if ($this->NumberOfActivitiesWithDistance !== null) {
            return $this->NumberOfActivitiesWithDistance >= $this->NumberOfActivities / 2.0;
        }

        return $this->TotalDistance !== null && $this->TotalDistance > 0.0;
    }

    /**
     * @param float $kilometer [km]
     */
    public function setTotalDistance($kilometer): void
    {
        $this->TotalDistance = $kilometer !== null ? (float) $kilometer : null;
    }

    /**
     * @return float|null [km]
     */
    public function getTotalDistance()
    {
        return $this->TotalDistance;
    }

    public function isTotalDistanceKnown(): bool
    {
        return $this->TotalDistance !== null;
    }

    /**
     * @param float $timeInSeconds [s]
     */
    public function setTotalDuration($timeInSeconds): void
    {
        $this->TotalDuration = $timeInSeconds !== null ? (float) $timeInSeconds : null;
    }

    /**
     * @return float|null [s]
     */
    public function getTotalDuration()
    {
        return $this->TotalDuration;
    }

    public function isTotalDurationKnown(): bool
    {
        return $this->TotalDuration !== null;
    }
}

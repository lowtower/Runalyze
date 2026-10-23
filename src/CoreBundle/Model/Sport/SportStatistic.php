<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Sport;

use Runalyze\Bundle\CoreBundle\Entity\Sport;

class SportStatistic
{
    protected ?int $NumberOfActivities = null;

    protected ?int $NumberOfActivitiesWithDistance = null;

    protected ?float $TotalDistance = null;

    protected ?float $TotalDuration = null;

    public function __construct(
        protected Sport $Sport
    ) {}

    public function getSport(): Sport
    {
        return $this->Sport;
    }

    public function setNumberOfActivities(int $numberOfActivities, ?int $numberOfActivitiesWithDistance = null): void
    {
        $this->NumberOfActivities = $numberOfActivities;
        $this->NumberOfActivitiesWithDistance = $numberOfActivitiesWithDistance ?? null;
    }

    public function getNumberOfActivities(): ?int
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
     * @param ?float $kilometer [km]
     */
    public function setTotalDistance(?float $kilometer): void
    {
        $this->TotalDistance = $kilometer ?? null;
    }

    /**
     * @return ?float [km]
     */
    public function getTotalDistance(): ?float
    {
        return $this->TotalDistance;
    }

    public function isTotalDistanceKnown(): bool
    {
        return $this->TotalDistance !== null;
    }

    /**
     * @param ?float $timeInSeconds [s]
     */
    public function setTotalDuration(?float $timeInSeconds): void
    {
        $this->TotalDuration = $timeInSeconds ?? null;
    }

    /**
     * @return ?float [s]
     */
    public function getTotalDuration(): ?float
    {
        return $this->TotalDuration;
    }

    public function isTotalDurationKnown(): bool
    {
        return $this->TotalDuration !== null;
    }
}

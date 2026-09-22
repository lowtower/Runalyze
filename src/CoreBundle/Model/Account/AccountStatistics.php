<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Account;

class AccountStatistics
{
    protected ?int $NumberOfActivities = null;

    /**
     * @var ?float [s]
     */
    protected ?float $TotalDuration = null;

    /**
     * @var ?float [km]
     */
    protected ?float $TotalDistance = null;

    public function setNumberOfActivities(?int $numberOfActivities): void
    {
        $this->NumberOfActivities = $numberOfActivities;
    }

    public function getNumberOfActivities(): ?int
    {
        return $this->NumberOfActivities;
    }

    /**
     * @param ?float $totalDuration [s]
     */
    public function setTotalDuration(?float $totalDuration): void
    {
        $this->TotalDuration = $totalDuration;
    }

    /**
     * @return ?float [s]
     */
    public function getTotalDuration(): ?float
    {
        return $this->TotalDuration;
    }

    /**
     * @param ?float $totalDistance [km]
     */
    public function setTotalDistance(?float $totalDistance): void
    {
        $this->TotalDistance = $totalDistance;
    }

    /**
     * @return ?float [km]
     */
    public function getTotalDistance(): ?float
    {
        return $this->TotalDistance;
    }
}

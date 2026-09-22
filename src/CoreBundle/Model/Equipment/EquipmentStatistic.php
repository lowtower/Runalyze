<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Equipment;

class EquipmentStatistic
{
    protected ?int $NumberOfActivities = null;

    /**
     * @var ?float [s/km]
     */
    protected ?float $MaximalPace = null;

    /**
     * @var ?float [km]
     */
    protected ?float $MaximalDistance = null;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\Equipment $Equipment
    ) {
    }

    public function getEquipment(): \Runalyze\Bundle\CoreBundle\Entity\Equipment
    {
        return $this->Equipment;
    }

    public function setNumberOfActivities(?int $numberOfActivities): void
    {
        $this->NumberOfActivities = $numberOfActivities;
    }

    public function getNumberOfActivities(): ?int
    {
        return $this->NumberOfActivities;
    }

    /**
     * @param ?float $secondsPerKilometer [s/km]
     */
    public function setMaximalPace(?float $secondsPerKilometer): void
    {
        $this->MaximalPace = $secondsPerKilometer;
    }

    /**
     * @return ?float [s/km]
     */
    public function getMaximalPace(): ?float
    {
        return $this->MaximalPace;
    }

    /**
     * @param ?float $totalDistance [km]
     */
    public function setMaximalDistance(?float $totalDistance): void
    {
        $this->MaximalDistance = $totalDistance;
    }

    /**
     * @return ?float [km]
     */
    public function getMaximalDistance(): ?float
    {
        return $this->MaximalDistance;
    }
}

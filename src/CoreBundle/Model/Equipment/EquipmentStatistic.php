<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Model\Equipment;

class EquipmentStatistic
{
    /**
     * @var null|int
     */
    protected $NumberOfActivities;

    /**
     * @var null|float [s/km]
     */
    protected $MaximalPace;

    /**
     * @var null|float [km]
     */
    protected $MaximalDistance;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\Equipment $Equipment
    ) {
    }

    public function getEquipment(): \Runalyze\Bundle\CoreBundle\Entity\Equipment
    {
        return $this->Equipment;
    }

    /**
     * @param null|int $numberOfActivities
     */
    public function setNumberOfActivities($numberOfActivities): void
    {
        $this->NumberOfActivities = $numberOfActivities;
    }

    /**
     * @return int|null
     */
    public function getNumberOfActivities()
    {
        return $this->NumberOfActivities;
    }

    /**
     * @param float|null $secondsPerKilometer [s/km]
     */
    public function setMaximalPace($secondsPerKilometer): void
    {
        $this->MaximalPace = $secondsPerKilometer;
    }

    /**
     * @return float|null [s/km]
     */
    public function getMaximalPace()
    {
        return $this->MaximalPace;
    }

    /**
     * @param float|null $totalDistance [km]
     */
    public function setMaximalDistance($totalDistance): void
    {
        $this->MaximalDistance = $totalDistance;
    }

    /**
     * @return float|null [km]
     */
    public function getMaximalDistance()
    {
        return $this->MaximalDistance;
    }
}

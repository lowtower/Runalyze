<?php

namespace Runalyze\Bundle\CoreBundle\Model\Sport;

use Runalyze\Bundle\CoreBundle\Entity\Sport;

class SportStatistics
{
    /**
     * @var SportStatistic[]
     */
    protected $Sports = [];

    public function __construct(
        protected \DateTime $StartDate,
        array $queryResult
    ) {
        foreach ($queryResult as $row) {
            /** @var Sport $sport */
            $sport = $row[0];

            $sportStatistic = new SportStatistic($sport);
            $sportStatistic->setNumberOfActivities($row['num'], $row['count_distance']);
            $sportStatistic->setTotalDistance($row['distance']);
            $sportStatistic->setTotalDuration($row['time_in_s']);

            $this->Sports[(string) $sport->getId()] = $sportStatistic;
        }
    }

    public function getStartDate(): \DateTime
    {
        return $this->StartDate;
    }

    /**
     * @return SportStatistic[]
     */
    public function getStatistics(): array
    {
        return $this->Sports;
    }

    public function hasStatisticFor(Sport $sport): bool
    {
        return isset($this->Sports[(string) $sport->getId()]);
    }

    public function getStatisticFor(Sport $sport): SportStatistic
    {
        if ($this->hasStatisticFor($sport)) {
            return $this->Sports[(string) $sport->getId()];
        }

        return new SportStatistic($sport);
    }

    public function getCount(): int
    {
        return count($this->Sports);
    }
}

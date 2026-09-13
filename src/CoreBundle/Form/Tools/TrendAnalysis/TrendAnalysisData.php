<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis;

use DateTime;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;

class TrendAnalysisData
{
    /**
     * @var DateTime
     */
    protected $DateFrom;

    /**
     * @var DateTime
     */
    protected $DateTo;

    /**
     * @var Sport[]
     */
    protected $Sport = [];

    /**
     * @var Type[]
     */
    protected $Type = [];

    /**
     * @var string|null
     */
    protected $ValueToLookAt;

    /**
     * @param Sport[] $sports
     */
    public static function getDefault(array $sports, array $types): self
    {
        $data = new self();
        $data->setSport($sports);
        $data->setType($types);
        $data->setDateFrom((new \DateTime())->sub(new \DateInterval('P6M')));
        $data->setDateTo(new \DateTime());

        return $data;
    }

    public function setDateFrom(DateTime $dateFrom): void
    {
        $this->DateFrom = $dateFrom;
    }

    /**
     * @return DateTime
     */
    public function getDateFrom()
    {
        return $this->DateFrom;
    }

    public function getDateFromTimestamp(): int
    {
        return $this->getDateFrom()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->getTimestamp();
    }

    public function setDateTo(DateTime $dateTo): void
    {
        $this->DateTo = $dateTo;
    }

    /**
     * @return DateTime
     */
    public function getDateTo()
    {
        return $this->DateTo;
    }

    public function getDateToTimestamp(): int
    {
        return $this->getDateTo()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->setTime(23, 59, 00)
            ->getTimestamp();
    }

    /**
     * @param Sport[] $sports
     */
    public function setSport(array $sports): void
    {
        $this->Sport = $sports;
    }

    /**
     * @return Sport[]
     */
    public function getSport()
    {
        return $this->Sport;
    }

    /**
     * @param Type[] $types
     */
    public function setType(array $types): void
    {
        $this->Type = $types;
    }

    /**
     * @return Type[]
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param string|null $valueToLookAt
     */
    public function setValueToLookAt($valueToLookAt): void
    {
        $this->ValueToLookAt = $valueToLookAt;
    }

    /**
     * @return string|null
     */
    public function getValueToLookAt()
    {
        return $this->ValueToLookAt;
    }
}

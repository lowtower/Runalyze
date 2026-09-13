<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\Anova;

use DateTime;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;

class AnovaData
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
     * @var string
     */
    protected $ValueToGroupBy;

    /**
     * @var string
     */
    protected $ValueToLookAt;

    /**
     * @param Sport[] $sport
     * @param Type[] $type
     */
    public static function getDefault(array $sport, array $type): self
    {
        $data = new self();
        $data->setSport($sport);
        $data->setType($type);
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
     * @param Sport[] $sport
     */
    public function setSport(array $sport): void
    {
        $this->Sport = $sport;
    }

    /**
     * @return Sport[]
     */
    public function getSport()
    {
        return $this->Sport;
    }

    /**
     * @param Type[] $type
     */
    public function setType(array $type): void
    {
        $this->Type = $type;
    }

    /**
     * @return Type[]
     */
    public function getType()
    {
        return $this->Type;
    }

    public function setValueToGroupBy($valueToGroupBy): void
    {
        $this->ValueToGroupBy = $valueToGroupBy;
    }

    /**
     * @return string
     */
    public function getValueToGroupBy()
    {
        return $this->ValueToGroupBy;
    }

    public function setValueToLookAt($valueToLookAt): void
    {
        $this->ValueToLookAt = $valueToLookAt;
    }

    /**
     * @return string
     */
    public function getValueToLookAt()
    {
        return $this->ValueToLookAt;
    }
}

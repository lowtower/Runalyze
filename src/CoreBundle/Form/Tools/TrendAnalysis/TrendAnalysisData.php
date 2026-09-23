<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis;

use DateTime;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;

class TrendAnalysisData
{
    protected DateTime $DateFrom;

    protected DateTime $DateTo;

    /**
     * @var Sport[]
     */
    protected array $Sport = [];

    /**
     * @var Type[]
     */
    protected array $Type = [];

    protected ?string $ValueToLookAt = null;

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

    public function getDateFrom(): DateTime
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

    public function getDateTo(): DateTime
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
    public function getSport(): array
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
    public function getType(): array
    {
        return $this->Type;
    }

    public function setValueToLookAt(?string $valueToLookAt): void
    {
        $this->ValueToLookAt = $valueToLookAt;
    }

    public function getValueToLookAt(): ?string
    {
        return $this->ValueToLookAt;
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\Anova;

use DateTime;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;

class AnovaData
{
    protected ?DateTime $DateFrom = null;

    protected ?DateTime $DateTo = null;

    /**
     * @var Sport[]
     */
    protected array $Sport = [];

    /**
     * @var Type[]
     */
    protected array $Type = [];

    protected ?string $ValueToGroupBy = null;

    protected ?string $ValueToLookAt = null;

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

    public function setDateFrom(?DateTime $dateFrom): void
    {
        $this->DateFrom = $dateFrom;
    }

    public function getDateFrom(): ?DateTime
    {
        return $this->DateFrom;
    }

    /**
     * @throws \LogicException if no date has been set yet
     */
    public function getDateFromTimestamp(): int
    {
        if (! $this->DateFrom instanceof \DateTime) {
            throw new \LogicException('Cannot compute a timestamp for a date that has not been set.');
        }

        return $this->DateFrom
            ->setTimezone(new \DateTimeZone('UTC'))
            ->getTimestamp();
    }

    public function setDateTo(?DateTime $dateTo): void
    {
        $this->DateTo = $dateTo;
    }

    public function getDateTo(): ?DateTime
    {
        return $this->DateTo;
    }

    /**
     * @throws \LogicException if no date has been set yet
     */
    public function getDateToTimestamp(): int
    {
        if (! $this->DateTo instanceof \DateTime) {
            throw new \LogicException('Cannot compute a timestamp for a date that has not been set.');
        }

        return $this->DateTo
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
    public function getSport(): array
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
    public function getType(): array
    {
        return $this->Type;
    }

    public function setValueToGroupBy(?string $valueToGroupBy): void
    {
        $this->ValueToGroupBy = $valueToGroupBy;
    }

    public function getValueToGroupBy(): ?string
    {
        return $this->ValueToGroupBy;
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

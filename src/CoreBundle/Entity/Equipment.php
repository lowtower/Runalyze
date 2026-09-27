<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;

#[ORM\Table(name: 'equipment')]
#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment implements IdentifiableEntityInterface, NamedEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'notes', type: 'text', length: 255, nullable: false)]
    private string $notes = '';

    /**
     * @var float [km]
     */
    #[ORM\Column(name: 'distance', type: 'casted_decimal_2', precision: 8, scale: 2, options: [
        'unsigned' => true,
    ])]
    private float $distance = 0.00;

    /**
     * @var int [s]
     */
    #[ORM\Column(name: 'time', type: 'integer', nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $time = 0;

    /**
     * @var ?int [km]
     */
    #[ORM\Column(name: 'additional_km', type: 'smallint', nullable: false, options: [
        'unsigned' => true,
    ])]
    private ?int $additionalKm = 0;

    #[ORM\Column(name: 'date_start', type: 'date', nullable: true)]
    private ?\DateTime $dateStart = null;

    #[ORM\Column(name: 'date_end', type: 'date', nullable: true)]
    private ?\DateTime $dateEnd = null;

    #[ORM\JoinColumn(name: 'typeid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: EquipmentType::class, inversedBy: 'equipment')]
    private EquipmentType $type;

    #[ORM\JoinTable(name: 'equipment_to_sport')]
    #[ORM\JoinColumn(name: 'equipment_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'sportid', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Sport::class, inversedBy: 'equipment')]
    private \Doctrine\Common\Collections\Collection $sport;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'equipment')]
    private Account $account;

    #[ORM\ManyToMany(targetEntity: Training::class, mappedBy: 'equipment')]
    private \Doctrine\Common\Collections\Collection $activity;

    public function __construct()
    {
        $this->sport = new ArrayCollection();
        $this->activity = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @param float $distance [km]
     */
    public function addDistance(?float $distance = null): self
    {
        if ($distance !== null) {
            $this->distance += $distance;
        }

        return $this;
    }

    /**
     * @return float [km]
     */
    public function getDistance(): float
    {
        return $this->distance;
    }

    /**
     * @param int $time [s]
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @param int $time [s]
     */
    public function addTime(?int $time = null): self
    {
        if ($time !== null) {
            $this->time += $time;
        }

        return $this;
    }

    /**
     * @return int [s]
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @return ?float [s/km]
     */
    public function getPace(): ?float
    {
        return $this->distance > 0 ? $this->time / $this->distance : null;
    }

    /**
     * @param ?int $additionalKm [km]
     */
    public function setAdditionalKm(?int $additionalKm): self
    {
        $this->additionalKm = $additionalKm ?? 0;

        return $this;
    }

    /**
     * @return int [km]
     */
    public function getAdditionalKm(): int
    {
        return $this->additionalKm ?? 0;
    }

    /**
     * @return float [km]
     */
    public function getTotalDistance(): float
    {
        return $this->distance + $this->getAdditionalKm();
    }

    public function setDateStart(?\DateTime $dateStart = null): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateStart(): ?\DateTime
    {
        return $this->dateStart;
    }

    public function hasStartDate(): bool
    {
        return $this->dateStart instanceof \DateTime;
    }

    public function setDateEnd(?\DateTime $dateEnd = null): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function isActive(): bool
    {
        return ! $this->dateEnd instanceof \DateTime;
    }

    public function setType(EquipmentType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): EquipmentType
    {
        return $this->type;
    }

    public function addSport(Sport $sport): self
    {
        $this->sport[] = $sport;

        return $this;
    }

    public function removeSport(Sport $sport): void
    {
        $this->sport->removeElement($sport);
    }

    public function getSport(): \Doctrine\Common\Collections\Collection
    {
        return $this->sport;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function addActivity(Training $activity): self
    {
        $this->activity[] = $activity;

        return $this;
    }

    public function removeActivity(Training $activity): void
    {
        $this->activity->removeElement($activity);
    }

    public function getActivity(): \Doctrine\Common\Collections\Collection
    {
        return $this->activity;
    }
}

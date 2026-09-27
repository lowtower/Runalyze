<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'equipment_type')]
#[ORM\Entity(repositoryClass: EquipmentTypeRepository::class)]
class EquipmentType implements AccountRelatedEntityInterface
{
    /**
     * @var int only one equipment object can be used at once
     */
    public const CHOICE_SINGLE = 0;

    /**
     * @var int multiple equipment objects can be used at once
     */
    public const CHOICE_MULTIPLE = 1;

    #[ORM\Column(name: 'id', type: 'integer', precision: 10, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name = '';

    /**
     * @see self::CHOICE_SINGLE
     * @see self::CHOICE_MULTIPLE
     */
    #[ORM\Column(name: 'input', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $input = 0;

    /**
     * @var ?int [km]
     */
    #[ORM\Column(name: 'max_km', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $maxKm = null;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'max_time', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $maxTime = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'equipmentTypes')]
    private Account $account;

    #[ORM\JoinTable(name: 'equipment_sport')]
    #[ORM\JoinColumn(name: 'equipment_typeid', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'sportid', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Sport::class, inversedBy: 'equipmentType')]
    private Collection $sport;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Equipment::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $equipment;

    public function __construct()
    {
        $this->sport = new ArrayCollection();
        $this->equipment = new ArrayCollection();
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

    /**
     * @see self::CHOICE_SINGLE
     * @see self::CHOICE_MULTIPLE
     */
    public function setInput(int $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @see self::CHOICE_SINGLE
     * @see self::CHOICE_MULTIPLE
     */
    public function getInput(): int
    {
        return $this->input;
    }

    public function allowsMultipleValues(): bool
    {
        return $this->input === self::CHOICE_MULTIPLE;
    }

    /**
     * @param ?int $maxKm [km]
     */
    public function setMaxKm(?int $maxKm): self
    {
        $this->maxKm = $maxKm;

        return $this;
    }

    /**
     * @return ?int [km]
     */
    public function getMaxKm(): ?int
    {
        return $this->maxKm;
    }

    public function hasMaximalDistance(): bool
    {
        return $this->maxKm !== null && $this->maxKm > 0;
    }

    /**
     * @param ?int $maxTime [s]
     */
    public function setMaxTime(?int $maxTime): self
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /**
     * @return ?int [s]
     */
    public function getMaxTime(): ?int
    {
        return $this->maxTime;
    }

    public function hasMaximalDuration(): bool
    {
        return $this->maxTime !== null && $this->maxTime > 0;
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

    public function addSport(Sport $sport): self
    {
        $this->sport[] = $sport;

        return $this;
    }

    public function removeSport(Sport $sport): void
    {
        $this->sport->removeElement($sport);
    }

    public function getSport(): Collection
    {
        return $this->sport;
    }

    public function getEquipment(): Collection
    {
        return $this->equipment;
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private $name = '';

    /**
     * @var int see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     */
    #[ORM\Column(name: 'input', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $input = 0;

    /**
     * @var null|int [km]
     */
    #[ORM\Column(name: 'max_km', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $maxKm;

    /**
     * @var null|int [s]
     */
    #[ORM\Column(name: 'max_time', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $maxTime;

    /**
     * @var Account
     */
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'equipmentTypes')]
    private $account;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'equipment_sport')]
    #[ORM\JoinColumn(name: 'equipment_typeid', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'sportid', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Sport::class, inversedBy: 'equipmentType')]
    private $sport;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Equipment::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $equipment;

    public function __construct()
    {
        $this->sport = new ArrayCollection();
        $this->equipment = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $input see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     */
    public function setInput($input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return int see self::CHOICE_SINGLE and self::CHOICE_MULTIPLE
     */
    public function getInput()
    {
        return $this->input;
    }

    public function allowsMultipleValues(): bool
    {
        return $this->input === self::CHOICE_MULTIPLE;
    }

    /**
     * @param null|int $maxKm [km]
     */
    public function setMaxKm($maxKm): self
    {
        $this->maxKm = $maxKm;

        return $this;
    }

    /**
     * @return null|int [km]
     */
    public function getMaxKm()
    {
        return $this->maxKm;
    }

    public function hasMaximalDistance(): bool
    {
        return $this->maxKm !== null && $this->maxKm > 0;
    }

    /**
     * @param null|int $maxTime [s]
     */
    public function setMaxTime($maxTime): self
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getMaxTime()
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

    /**
     * @return Account
     */
    public function getAccount()
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

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquipment()
    {
        return $this->equipment;
    }
}

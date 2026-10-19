<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Calculation\Activity\HrZonesCalculator;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Profile\Sport\AbstractSport;
use Runalyze\Profile\Sport\ProfileInterface;
use Runalyze\Profile\Sport\SportProfile;

#[ORM\Table(name: 'sport')]
#[ORM\UniqueConstraint(name: 'unique_internal_id', columns: ['accountid', 'internal_sport_id'])]
#[ORM\Entity(repositoryClass: SportRepository::class)]
class Sport implements IdentifiableEntityInterface, NamedEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 50, nullable: false)]
    private string $name = '';

    #[ORM\Column(name: 'img', type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: false, options: [
        'default' => 'icons8-Sports-Mode',
    ])]
    private string $img = 'icons8-Sports-Mode';

    #[ORM\Column(name: 'short', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $short = false;

    /**
     * @var int [kcal/h]
     */
    #[ORM\Column(name: 'kcal', type: \Doctrine\DBAL\Types\Types::SMALLINT, precision: 4, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $kcal = 0;

    /**
     * @var int [bpm]
     */
    #[ORM\Column(name: 'HFavg', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $hfavg = 120;

    #[ORM\Column(name: 'distances', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $distances = true;

    /**
     * @var int see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    #[ORM\Column(name: 'speed', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private int $speed = 6;

    #[ORM\Column(name: 'power', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $power = false;

    #[ORM\Column(name: 'outside', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $outside = false;

    /**
     * @var bool true: defaults to private
     */
    #[ORM\Column(name: 'default_privacy', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $defaultPrivacy = true;

    /**
     * @var array|null [string]
     */
    #[ORM\Column(name: 'hr_zone_bounds', type: 'pipe_array_str', nullable: true)]
    private ?array $hrZoneBounds = null;

    #[ORM\JoinColumn(name: 'main_equipmenttypeid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: EquipmentType::class)]
    private ?EquipmentType $mainEquipmenttype = null;

    /**
     * @var Collection<int, Equipment>
     */
    #[ORM\ManyToMany(targetEntity: Equipment::class, mappedBy: 'sport')]
    private Collection $equipment;

    #[ORM\JoinColumn(name: 'default_typeid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Type::class)]
    private ?Type $defaultType = null;

    #[ORM\Column(name: 'is_main', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $isMain = false;

    /**
     * @var ?int see \Runalyze\Profile\Sport\SportProfile
     */
    #[ORM\Column(name: 'internal_sport_id', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $internalSportId = null;

    private ?AbstractSport $internalSport = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'sports')]
    private Account $account;

    /**
     * @var Collection<int, EquipmentType>
     */
    #[ORM\ManyToMany(targetEntity: EquipmentType::class, mappedBy: 'sport')]
    private Collection $equipmentType;

    /**
     * @var Collection<int, Type>
     */
    #[ORM\OneToMany(targetEntity: Type::class, mappedBy: 'sport', fetch: 'EXTRA_LAZY')]
    protected Collection $types;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'sport', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected Collection $trainings;

    public function __construct()
    {
        $this->equipmentType = new ArrayCollection();
        $this->types = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->equipment = new ArrayCollection();
    }

    public function setDataFrom(ProfileInterface $profile): self
    {
        $this->setInternalSportId($profile->getInternalProfileEnum());
        $this->setImg($profile->getIconClass());
        $this->setHfavg($profile->getAverageHeartRate());
        $this->setName($profile->getName());
        $this->setDistances($profile->hasDistances());
        $this->setPower($profile->hasPower());
        $this->setOutside($profile->isOutside());
        $this->setSpeed($profile->getPaceUnitEnum());
        $this->setKcal($profile->getCaloriesPerHour());

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->name = $name ?? '';

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setImg(string $img): self
    {
        $this->img = $img;

        return $this;
    }

    public function getImg(): string
    {
        return $this->img;
    }

    public function setShort(bool $flag): self
    {
        $this->short = $flag;

        return $this;
    }

    public function getShort(): bool
    {
        return $this->short;
    }

    /**
     * @param int $kcal [kcal/h]
     */
    public function setKcal(int $kcal): self
    {
        $this->kcal = $kcal;

        return $this;
    }

    /**
     * @return int [kcal/h]
     */
    public function getKcal(): int
    {
        return $this->kcal;
    }

    /**
     * @param int $bpm [bpm]
     */
    public function setHfavg(int $bpm): self
    {
        $this->hfavg = $bpm;

        return $this;
    }

    /**
     * @return int [bpm]
     */
    public function getHfavg(): int
    {
        return $this->hfavg;
    }

    public function setDistances(bool $flag): self
    {
        $this->distances = $flag;

        return $this;
    }

    public function getDistances(): bool
    {
        return $this->distances;
    }

    /**
     * @param int $enum see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    public function setSpeed(int $enum): self
    {
        $this->speed = $enum;

        return $this;
    }

    /**
     * @return int see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function getSpeedUnit(): AbstractPaceUnit
    {
        return PaceEnum::get($this->speed);
    }

    public function setPower(bool $flag): self
    {
        $this->power = $flag;

        return $this;
    }

    public function getPower(): bool
    {
        return $this->power;
    }

    public function setOutside(bool $flag): self
    {
        $this->outside = $flag;

        return $this;
    }

    public function getOutside(): bool
    {
        return $this->outside;
    }

    /**
     * @param bool $privacy true: defaults to private
     */
    public function setDefaultPrivacy(bool $privacy): self
    {
        $this->defaultPrivacy = $privacy;

        return $this;
    }

    /**
     * @return bool true: defaults to private
     */
    public function getDefaultPrivacy(): bool
    {
        return $this->defaultPrivacy;
    }

    /**
     * @param array<int, string>|null $hrZoneBounds [bpm]
     */
    public function setHrZoneBounds(?array $hrZoneBounds): self
    {
        if ($hrZoneBounds !== null) {
            $hrZoneBounds = HrZonesCalculator::sortZones($hrZoneBounds);
        }

        $this->hrZoneBounds = $hrZoneBounds;

        return $this;
    }

    /**
     * @return null|array [bpm]
     */
    public function getHrZoneBounds(): ?array
    {
        return $this->hrZoneBounds;
    }

    public function setMainEquipmenttype(?EquipmentType $mainEquipmenttype = null): self
    {
        $this->mainEquipmenttype = $mainEquipmenttype;

        return $this;
    }

    public function getMainEquipmenttype(): ?EquipmentType
    {
        return $this->mainEquipmenttype;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function setDefaultType(?Type $defaultType = null): self
    {
        $this->defaultType = $defaultType;

        return $this;
    }

    public function getDefaultType(): ?Type
    {
        return $this->defaultType;
    }

    public function setIsMain(bool $flag = true): self
    {
        $this->isMain = $flag;

        return $this;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    /**
     * @param int|null $internalSportId see \Runalyze\Profile\Sport\SportProfile
     */
    public function setInternalSportId(?int $internalSportId): self
    {
        $this->internalSportId = $internalSportId;
        $this->internalSport = null;

        return $this;
    }

    public function hasInternalSportId(): bool
    {
        return $this->internalSportId !== null && $this->internalSportId !== SportProfile::GENERIC;
    }

    /**
     * @return int|null see \Runalyze\Profile\Sport\SportProfile
     */
    public function getInternalSportId(): ?int
    {
        return $this->internalSportId;
    }

    public function getInternalSport(): ?AbstractSport
    {
        if (! $this->internalSport instanceof AbstractSport) {
            $id = $this->internalSportId ?? SportProfile::GENERIC;

            try {
                $this->internalSport = SportProfile::get($id);
            } catch (\InvalidArgumentException) {
                $this->internalSportId = null;
                $this->internalSport = SportProfile::get(SportProfile::GENERIC);
            }
        }

        return $this->internalSport;
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

    public function addEquipmentType(EquipmentType $equipmentType): self
    {
        $this->equipmentType->add($equipmentType);

        return $this;
    }

    public function removeEquipmentType(EquipmentType $equipmentType): void
    {
        $this->equipmentType->removeElement($equipmentType);
    }

    /**
     * @return Collection<int, EquipmentType>
     */
    public function getEquipmentTypes(): Collection
    {
        return $this->equipmentType;
    }

    public function hasEquipmentTypes(): bool
    {
        return ! $this->equipmentType->isEmpty();
    }

    /**
     * @return Collection<int, Type>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    /**
     * @return Collection<int, Training>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }
}

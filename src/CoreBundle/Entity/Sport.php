<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;
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
    #[ORM\Column(name: 'id', type: 'integer', options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'img', type: 'string', length: 100, nullable: false, options: [
        'default' => 'icons8-Sports-Mode',
    ])]
    private $img = 'icons8-Sports-Mode';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'short', type: 'boolean')]
    private $short = false;

    /**
     * @var int [kcal/h]
     */
    #[ORM\Column(name: 'kcal', type: 'smallint', precision: 4, nullable: false, options: [
        'unsigned' => true,
    ])]
    private $kcal = 0;

    /**
     * @var int [bpm]
     */
    #[ORM\Column(name: 'HFavg', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $hfavg = 120;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'distances', type: 'boolean')]
    private $distances = true;

    /**
     * @var int see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    #[ORM\Column(name: 'speed', type: 'tinyint', options: [
        'unsigned' => true,
    ])]
    private $speed = 6;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'power', type: 'boolean')]
    private $power = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'outside', type: 'boolean')]
    private $outside = false;

    /**
     * @var bool true: defaults to private
     */
    #[ORM\Column(name: 'default_privacy', type: 'boolean')]
    private $defaultPrivacy = true;

    /**
     * @var array|null [string]
     */
    #[ORM\Column(name: 'hr_zone_bounds', type: 'pipe_array_str', nullable: true)]
    private $hrZoneBounds;

    /**
     * @var EquipmentType|null
     */
    #[ORM\JoinColumn(name: 'main_equipmenttypeid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Runalyze\Bundle\CoreBundle\Entity\EquipmentType::class)]
    private $mainEquipmenttype;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\ManyToMany(targetEntity: \Runalyze\Bundle\CoreBundle\Entity\Equipment::class, mappedBy: 'sport')]
    private $equipment;

    /**
     * @var Type|null
     */
    #[ORM\JoinColumn(name: 'default_typeid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Runalyze\Bundle\CoreBundle\Entity\Type::class)]
    private $defaultType;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_main', type: 'boolean')]
    private $isMain = false;

    /**
     * @var int|null see \Runalyze\Profile\Sport\SportProfile
     */
    #[ORM\Column(name: 'internal_sport_id', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $internalSportId;

    /**
     * @var AbstractSport|null
     */
    private $internalSport;

    /**
     * @var Account
     */
    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Runalyze\Bundle\CoreBundle\Entity\Account::class, inversedBy: 'sports')]
    private $account;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\ManyToMany(targetEntity: \Runalyze\Bundle\CoreBundle\Entity\EquipmentType::class, mappedBy: 'sport')]
    private $equipmentType;

    /**
     * @var \Doctrine\Common\Collections\Collection|Type[]
     */
    #[ORM\OneToMany(mappedBy: 'sport', targetEntity: \Runalyze\Bundle\CoreBundle\Entity\Type::class, fetch: 'EXTRA_LAZY')]
    protected $types;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(mappedBy: 'sport', targetEntity: \Runalyze\Bundle\CoreBundle\Entity\Training::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $trainings;

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
     * @param string $img
     */
    public function setImg($img): self
    {
        $this->img = $img;

        return $this;
    }

    /**
     * @return string
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param bool $flag
     */
    public function setShort($flag): self
    {
        $this->short = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShort()
    {
        return $this->short;
    }

    /**
     * @param int $kcal [kcal/h]
     */
    public function setKcal($kcal): self
    {
        $this->kcal = $kcal;

        return $this;
    }

    /**
     * @return int [kcal/h]
     */
    public function getKcal()
    {
        return $this->kcal;
    }

    /**
     * @param int $bpm [bpm]
     */
    public function setHfavg($bpm): self
    {
        $this->hfavg = $bpm;

        return $this;
    }

    /**
     * @return int [bpm]
     */
    public function getHfavg()
    {
        return $this->hfavg;
    }

    /**
     * @param bool $flag
     */
    public function setDistances($flag): self
    {
        $this->distances = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDistances()
    {
        return $this->distances;
    }

    /**
     * @param int $enum see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    public function setSpeed($enum): self
    {
        $this->speed = $enum;

        return $this;
    }

    /**
     * @return int see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @return AbstractPaceUnit
     */
    public function getSpeedUnit()
    {
        return PaceEnum::get($this->speed);
    }

    /**
     * @param bool $flag
     */
    public function setPower($flag): self
    {
        $this->power = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPower()
    {
        return $this->power;
    }

    /**
     * @param bool $flag
     */
    public function setOutside($flag): self
    {
        $this->outside = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOutside()
    {
        return $this->outside;
    }

    /**
     * @param bool $privacy true: defaults to private
     */
    public function setDefaultPrivacy($privacy): self
    {
        $this->defaultPrivacy = $privacy;

        return $this;
    }

    /**
     * @return bool true: defaults to private
     */
    public function getDefaultPrivacy()
    {
        return $this->defaultPrivacy;
    }

    /**
     * @param null|array $hrZoneBounds [bpm]
     */
    public function setHrZoneBounds($hrZoneBounds): self
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
    public function getHrZoneBounds()
    {
        return $this->hrZoneBounds;
    }

    public function setMainEquipmenttype(?EquipmentType $mainEquipmenttype = null): self
    {
        $this->mainEquipmenttype = $mainEquipmenttype;

        return $this;
    }

    /**
     * @return EquipmentType|null
     */
    public function getMainEquipmenttype()
    {
        return $this->mainEquipmenttype;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquipment()
    {
        return $this->equipment;
    }

    public function setDefaultType(?Type $defaultType = null): self
    {
        $this->defaultType = $defaultType;

        return $this;
    }

    /**
     * @return Type|null
     */
    public function getDefaultType()
    {
        return $this->defaultType;
    }

    /**
     * @param bool $flag
     */
    public function setIsMain($flag = true): self
    {
        $this->isMain = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMain()
    {
        return $this->isMain;
    }

    /**
     * @param int|null $internalSportId see \Runalyze\Profile\Sport\SportProfile
     */
    public function setInternalSportId($internalSportId): self
    {
        $this->internalSportId = $internalSportId;
        $this->internalSport = null;

        return $this;
    }

    public function hasInternalSportId(): bool
    {
        return $this->internalSportId !== null && $this->internalSportId != SportProfile::GENERIC;
    }

    /**
     * @return int|null see \Runalyze\Profile\Sport\SportProfile
     */
    public function getInternalSportId()
    {
        return $this->internalSportId;
    }

    /**
     * @return AbstractSport
     */
    public function getInternalSport()
    {
        if ($this->internalSport === null) {
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

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    public function addEquipmentType(EquipmentType $equipmentType): self
    {
        $this->equipmentType[] = $equipmentType;

        return $this;
    }

    public function removeEquipmentType(EquipmentType $equipmentType)
    {
        $this->equipmentType->removeElement($equipmentType);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquipmentTypes()
    {
        return $this->equipmentType;
    }

    public function hasEquipmentTypes(): bool
    {
        return ! $this->equipmentType->isEmpty();
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Type>
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTrainings()
    {
        return $this->trainings;
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;

#[ORM\Table(name: 'raceresult')]
#[ORM\Entity(repositoryClass: RaceresultRepository::class)]
#[ORM\EntityListeners([\Runalyze\Bundle\CoreBundle\EntityListener\RaceResultListener::class])]
class Raceresult implements AccountRelatedEntityInterface
{
    /**
     * @var ?float [km]
     */
    #[ORM\Column(name: 'official_distance', type: 'casted_decimal_2', precision: 6, scale: 2, nullable: true)]
    private $officialDistance;

    /**
     * @var float [s]
     */
    #[ORM\Column(name: 'official_time', type: 'casted_decimal_2', precision: 8, scale: 2, nullable: false)]
    private $officialTime;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'officially_measured', type: 'boolean')]
    private $officiallyMeasured = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false, options: [
        'default' => '',
    ])]
    private $name = '';

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'place_total', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $placeTotal;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'place_gender', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $placeGender;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'place_ageclass', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $placeAgeclass;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'participants_total', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $participantsTotal;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'participants_gender', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $participantsGender;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'participants_ageclass', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $participantsAgeclass;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private ?Account $account = null;

    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(targetEntity: Training::class, inversedBy: 'raceresult')]
    private ?Training $activity = null;

    /**
     * @param float $officialDistance [km]
     */
    public function setOfficialDistance($officialDistance): self
    {
        $this->officialDistance = $officialDistance;

        return $this;
    }

    /**
     * @return float [km]
     */
    public function getOfficialDistance()
    {
        return $this->officialDistance;
    }

    /**
     * @param float $officialTime [s]
     */
    public function setOfficialTime($officialTime): self
    {
        $this->officialTime = $officialTime;

        return $this;
    }

    /**
     * @return float [s]
     */
    public function getOfficialTime()
    {
        return $this->officialTime;
    }

    /**
     * @param bool $flag
     */
    public function setOfficiallyMeasured($flag): self
    {
        $this->officiallyMeasured = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOfficiallyMeasured()
    {
        return $this->officiallyMeasured;
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
     * @param int|null $placeTotal
     */
    public function setPlaceTotal($placeTotal): self
    {
        $this->placeTotal = $placeTotal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPlaceTotal()
    {
        return $this->placeTotal;
    }

    /**
     * @param int|null $placeGender
     */
    public function setPlaceGender($placeGender): self
    {
        $this->placeGender = $placeGender;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPlaceGender()
    {
        return $this->placeGender;
    }

    /**
     * @param int|null $placeAgeclass
     */
    public function setPlaceAgeclass($placeAgeclass): self
    {
        $this->placeAgeclass = $placeAgeclass;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPlaceAgeclass()
    {
        return $this->placeAgeclass;
    }

    /**
     * @param int|null $participantsTotal
     */
    public function setParticipantsTotal($participantsTotal): self
    {
        $this->participantsTotal = $participantsTotal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getParticipantsTotal()
    {
        return $this->participantsTotal;
    }

    /**
     * @param int|null $participantsGender
     */
    public function setParticipantsGender($participantsGender): self
    {
        $this->participantsGender = $participantsGender;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getParticipantsGender()
    {
        return $this->participantsGender;
    }

    /**
     * @param int|null $participantsAgeclass
     */
    public function setParticipantsAgeclass($participantsAgeclass): self
    {
        $this->participantsAgeclass = $participantsAgeclass;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getParticipantsAgeclass()
    {
        return $this->participantsAgeclass;
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

    public function setActivity(Training $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Training
     */
    public function getActivity()
    {
        return $this->activity;
    }

    public function fillFromActivity(Training $activity): self
    {
        $this->setActivity($activity);
        $this->setAccount($activity->getAccount());
        $this->setOfficialDistance($activity->getDistance() ?: 0.0);
        $this->setOfficialTime($activity->getS());
        $this->setName($activity->getTitle() ?: '');

        return $this;
    }
}

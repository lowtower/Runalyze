<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\EntityListener\RaceResultListener;

#[ORM\Table(name: 'raceresult')]
#[ORM\Entity(repositoryClass: RaceresultRepository::class)]
#[ORM\EntityListeners([RaceResultListener::class])]
class Raceresult implements AccountRelatedEntityInterface
{
    /**
     * @var ?float [km]
     */
    #[ORM\Column(name: 'official_distance', type: 'casted_decimal_2', precision: 6, scale: 2, nullable: true)]
    private ?float $officialDistance = null;

    /**
     * @var float [s]
     */
    #[ORM\Column(name: 'official_time', type: 'casted_decimal_2', precision: 8, scale: 2, nullable: false)]
    private float $officialTime;

    #[ORM\Column(name: 'officially_measured', type: 'boolean')]
    private bool $officiallyMeasured = false;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false, options: [
        'default' => '',
    ])]
    private string $name = '';

    #[ORM\Column(name: 'place_total', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $placeTotal = null;

    #[ORM\Column(name: 'place_gender', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $placeGender = null;

    #[ORM\Column(name: 'place_ageclass', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $placeAgeclass = null;

    #[ORM\Column(name: 'participants_total', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $participantsTotal = null;

    #[ORM\Column(name: 'participants_gender', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $participantsGender = null;

    #[ORM\Column(name: 'participants_ageclass', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $participantsAgeclass = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private ?Account $account = null;

    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(inversedBy: 'raceresult', targetEntity: Training::class)]
    private ?Training $activity = null;

    /**
     * @param ?float $officialDistance [km]
     */
    public function setOfficialDistance(?float $officialDistance): self
    {
        $this->officialDistance = $officialDistance;

        return $this;
    }

    /**
     * @return ?float [km]
     */
    public function getOfficialDistance(): ?float
    {
        return $this->officialDistance;
    }

    /**
     * @param float $officialTime [s]
     */
    public function setOfficialTime(float $officialTime): self
    {
        $this->officialTime = $officialTime;

        return $this;
    }

    /**
     * @return float [s]
     */
    public function getOfficialTime(): float
    {
        return $this->officialTime;
    }

    public function setOfficiallyMeasured(bool $flag): self
    {
        $this->officiallyMeasured = $flag;

        return $this;
    }

    public function getOfficiallyMeasured(): bool
    {
        return $this->officiallyMeasured;
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

    public function setPlaceTotal(?int $placeTotal): self
    {
        $this->placeTotal = $placeTotal;

        return $this;
    }

    public function getPlaceTotal(): ?int
    {
        return $this->placeTotal;
    }

    public function setPlaceGender(?int $placeGender): self
    {
        $this->placeGender = $placeGender;

        return $this;
    }

    public function getPlaceGender(): ?int
    {
        return $this->placeGender;
    }

    public function setPlaceAgeclass(?int $placeAgeclass): self
    {
        $this->placeAgeclass = $placeAgeclass;

        return $this;
    }

    public function getPlaceAgeclass(): ?int
    {
        return $this->placeAgeclass;
    }

    public function setParticipantsTotal(?int $participantsTotal): self
    {
        $this->participantsTotal = $participantsTotal;

        return $this;
    }

    public function getParticipantsTotal(): ?int
    {
        return $this->participantsTotal;
    }

    public function setParticipantsGender(?int $participantsGender): self
    {
        $this->participantsGender = $participantsGender;

        return $this;
    }

    public function getParticipantsGender(): ?int
    {
        return $this->participantsGender;
    }

    public function setParticipantsAgeclass(?int $participantsAgeclass): self
    {
        $this->participantsAgeclass = $participantsAgeclass;

        return $this;
    }

    public function getParticipantsAgeclass(): ?int
    {
        return $this->participantsAgeclass;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setActivity(Training $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getActivity(): ?Training
    {
        return $this->activity;
    }

    public function fillFromActivity(Training $activity): self
    {
        $this->setActivity($activity);
        $this->setAccount($activity->getAccount());
        $this->setOfficialDistance($activity->getDistance() ?: 0.0);
        $this->setOfficialTime($activity->getS() ?? 0.0);
        $this->setName($activity->getTitle() ?: '');

        return $this;
    }
}

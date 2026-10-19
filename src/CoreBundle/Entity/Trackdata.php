<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\TrackDataAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Repository\TrackdataRepository;
use Runalyze\Model;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

#[ORM\Table(name: 'trackdata')]
#[ORM\Entity(repositoryClass: TrackdataRepository::class)]
class Trackdata implements AccountRelatedEntityInterface
{
    /**
     * @var ?array [s]
     */
    #[ORM\Column(name: 'time', type: 'pipe_array', nullable: true)]
    private ?array $time = null;

    /**
     * @var ?array [km]
     */
    #[ORM\Column(name: 'distance', type: 'pipe_array', nullable: true)]
    private ?array $distance = null;

    /**
     * @var ?array [bpm]
     */
    #[ORM\Column(name: 'heartrate', type: 'pipe_array', nullable: true)]
    private ?array $heartrate = null;

    /**
     * @var ?array [rpm]
     */
    #[ORM\Column(name: 'cadence', type: 'pipe_array', nullable: true)]
    private ?array $cadence = null;

    /**
     * @var ?array [W]
     */
    #[ORM\Column(name: 'power', type: 'pipe_array', nullable: true)]
    private ?array $power = null;

    /**
     * @var ?array [°C]
     */
    #[ORM\Column(name: 'temperature', type: 'pipe_array', nullable: true)]
    private ?array $temperature = null;

    /**
     * @var ?array [ms]
     */
    #[ORM\Column(name: 'groundcontact', type: 'pipe_array', nullable: true)]
    private ?array $groundcontact = null;

    /**
     * @var ?array [mm]
     */
    #[ORM\Column(name: 'vertical_oscillation', type: 'pipe_array', nullable: true)]
    private ?array $verticalOscillation = null;

    /**
     * @var ?array [%*100]
     */
    #[ORM\Column(name: 'groundcontact_balance', type: 'pipe_array', nullable: true)]
    private ?array $groundcontactBalance = null;

    /**
     * @var ?array [%]
     */
    #[ORM\Column(name: 'smo2_0', type: 'pipe_array', nullable: true)]
    private ?array $smo20 = null;

    /**
     * @var ?array [%]
     */
    #[ORM\Column(name: 'smo2_1', type: 'pipe_array', nullable: true)]
    private ?array $smo21 = null;

    /**
     * @var ?array [%]
     */
    #[ORM\Column(name: 'thb_0', type: 'pipe_array', nullable: true)]
    private ?array $thb0 = null;

    /**
     * @var ?array [%]
     */
    #[ORM\Column(name: 'thb_1', type: 'pipe_array', nullable: true)]
    private ?array $thb1 = null;

    /**
     * @var ?array [G]
     */
    #[ORM\Column(name: 'impact_gs_left', type: 'pipe_array', nullable: true)]
    private ?array $impactGsLeft = null;

    /**
     * @var ?array [G]
     */
    #[ORM\Column(name: 'impact_gs_right', type: 'pipe_array', nullable: true)]
    private ?array $impactGsRight = null;

    /**
     * @var ?array [G]
     */
    #[ORM\Column(name: 'braking_gs_left', type: 'pipe_array', nullable: true)]
    private ?array $brakingGsLeft = null;

    /**
     * @var ?array [G]
     */
    #[ORM\Column(name: 'braking_gs_right', type: 'pipe_array', nullable: true)]
    private ?array $brakingGsRight = null;

    /**
     * @var ?array [°]
     */
    #[ORM\Column(name: 'footstrike_type_left', type: 'pipe_array', nullable: true)]
    private ?array $footstrikeTypeLeft = null;

    /**
     * @var ?array [°]
     */
    #[ORM\Column(name: 'footstrike_type_right', type: 'pipe_array', nullable: true)]
    private ?array $footstrikeTypeRight = null;

    /**
     * @var ?array [°]
     */
    #[ORM\Column(name: 'pronation_excursion_left', type: 'pipe_array', nullable: true)]
    private ?array $pronationExcursionLeft = null;

    /**
     * @var ?array [°]
     */
    #[ORM\Column(name: 'pronation_excursion_right', type: 'pipe_array', nullable: true)]
    private ?array $pronationExcursionRight = null;

    /**
     * @var ?array [n]
     */
    #[ORM\Column(name: 'performance_condition', type: 'pipe_array', nullable: true)]
    private ?array $performanceCondition = null;

    /**
     * @var ?array [pm]
     */
    #[ORM\Column(name: 'respiration_rate', type: 'pipe_array', nullable: true)]
    private ?array $respirationRate = null;

    #[ORM\Column(name: 'pauses', type: 'runalyze_pause_array', length: 65535, nullable: true)]
    private PauseCollection $pauses;

    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id', unique: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\OneToOne(targetEntity: Training::class, inversedBy: 'trackdata')]
    private Training $activity;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    #[ORM\Column(name: '`lock`', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $lock = false;

    private ?TrackDataAdapter $Adapter = null;

    /**
     * @var ?array [s/km]
     */
    private ?array $pace = null;

    /**
     * @var ?array [s/km]
     */
    private ?array $gradeAdjustedPace = null;

    /**
     * @var ?array (-100 .. 100)
     */
    private ?array $gradient = null;

    /**
     * @var ?array [cm]
     */
    private ?array $strideLength = null;

    /**
     * @var ?array [%]
     */
    private ?array $verticalRatio = null;

    public function __construct()
    {
        $this->pauses = new PauseCollection();
    }

    /**
     * @param ?array $time [s]
     */
    public function setTime(?array $time = null): self
    {
        $this->time = $time;

        $this->pace = null;

        return $this;
    }

    /**
     * @return ?array [s]
     */
    public function getTime(): ?array
    {
        return $this->time;
    }

    public function hasTime(): bool
    {
        return ! empty($this->time);
    }

    /**
     * @return int [s]
     */
    public function getTotalDuration(): int
    {
        if (! $this->hasTime()) {
            return 0;
        }

        return end($this->time);
    }

    /**
     * @param ?array $distance [km]
     */
    public function setDistance(?array $distance = null): self
    {
        $this->distance = $distance;

        $this->pace = null;
        $this->gradient = null;

        return $this;
    }

    /**
     * @return ?array [km]
     */
    public function getDistance(): ?array
    {
        return $this->distance;
    }

    public function hasDistance(): bool
    {
        return ! empty($this->distance);
    }

    /**
     * @return float [km]
     */
    public function getTotalDistance(): float
    {
        if (! $this->hasDistance()) {
            return 0.0;
        }

        return end($this->distance);
    }

    /**
     * @param ?array $heartRate [bpm]
     */
    public function setHeartrate(?array $heartRate = null): self
    {
        $this->heartrate = $heartRate;

        return $this;
    }

    /**
     * @return ?array [bpm]
     */
    public function getHeartrate(): ?array
    {
        return $this->heartrate;
    }

    public function hasHeartrate(): bool
    {
        return ! empty($this->heartrate);
    }

    /**
     * @param ?array $cadence [rpm]
     */
    public function setCadence(?array $cadence = null): self
    {
        $this->cadence = $cadence;

        $this->strideLength = null;

        return $this;
    }

    /**
     * @return ?array [rpm]
     */
    public function getCadence(): ?array
    {
        return $this->cadence;
    }

    public function hasCadence(): bool
    {
        return ! empty($this->cadence);
    }

    /**
     * @param ?array $power [W]
     */
    public function setPower(?array $power = null): self
    {
        $this->power = $power;

        return $this;
    }

    /**
     * @return ?array [W]
     */
    public function getPower(): ?array
    {
        return $this->power;
    }

    public function hasPower(): bool
    {
        return ! empty($this->power);
    }

    /**
     * @param ?array $temperature [°C]
     */
    public function setTemperature(?array $temperature = null): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return ?array [°C]
     */
    public function getTemperature(): ?array
    {
        return $this->temperature;
    }

    public function hasTemperature(): bool
    {
        return ! empty($this->temperature);
    }

    /**
     * @param ?array $groundcontact [ms]
     */
    public function setGroundcontact(?array $groundcontact = null): self
    {
        $this->groundcontact = $groundcontact;

        return $this;
    }

    /**
     * @return ?array [ms]
     */
    public function getGroundcontact(): ?array
    {
        return $this->groundcontact;
    }

    public function hasGroundcontact(): bool
    {
        return ! empty($this->groundcontact);
    }

    /**
     * @param ?array $verticalOscillation [mm]
     */
    public function setVerticalOscillation(?array $verticalOscillation = null): self
    {
        $this->verticalOscillation = $verticalOscillation;

        $this->verticalRatio = null;

        return $this;
    }

    /**
     * @return ?array [mm]
     */
    public function getVerticalOscillation(): ?array
    {
        return $this->verticalOscillation;
    }

    public function hasVerticalOscillation(): bool
    {
        return ! empty($this->verticalOscillation);
    }

    /**
     * @param ?array $groundcontactBalance [%*100]
     */
    public function setGroundcontactBalance(?array $groundcontactBalance = null): self
    {
        $this->groundcontactBalance = $groundcontactBalance;

        return $this;
    }

    /**
     * @return ?array [%*100]
     */
    public function getGroundcontactBalance(): ?array
    {
        return $this->groundcontactBalance;
    }

    public function hasGroundcontactBalance(): bool
    {
        return ! empty($this->groundcontactBalance);
    }

    /**
     * @param ?array $smo20 [%]
     */
    public function setSmo20(?array $smo20 = null): self
    {
        $this->smo20 = $smo20;

        return $this;
    }

    /**
     * @return ?array [%]
     */
    public function getSmo20(): ?array
    {
        return $this->smo20;
    }

    public function hasSmo20(): bool
    {
        return ! empty($this->smo20);
    }

    /**
     * @param ?array $smo21 [%]
     */
    public function setSmo21(?array $smo21 = null): self
    {
        $this->smo21 = $smo21;

        return $this;
    }

    /**
     * @return ?array [%]
     */
    public function getSmo21(): ?array
    {
        return $this->smo21;
    }

    public function hasSmo21(): bool
    {
        return ! empty($this->smo21);
    }

    /**
     * @param ?array $thb0 [%]
     */
    public function setThb0(?array $thb0 = null): self
    {
        $this->thb0 = $thb0;

        return $this;
    }

    /**
     * @return ?array [%]
     */
    public function getThb0(): ?array
    {
        return $this->thb0;
    }

    public function hasThb0(): bool
    {
        return ! empty($this->thb0);
    }

    /**
     * @param ?array $thb1 [%]
     */
    public function setThb1(?array $thb1 = null): self
    {
        $this->thb1 = $thb1;

        return $this;
    }

    /**
     * @return ?array [%]
     */
    public function getThb1(): ?array
    {
        return $this->thb1;
    }

    public function hasThb1(): bool
    {
        return ! empty($this->thb1);
    }

    /**
     * @param ?array $impactGsLeft [G]
     */
    public function setImpactGsLeft(?array $impactGsLeft = null): self
    {
        $this->impactGsLeft = $impactGsLeft;

        return $this;
    }

    /**
     * @return ?array [G]
     */
    public function getImpactGsLeft(): ?array
    {
        return $this->impactGsLeft;
    }

    public function hasImpactGsLeft(): bool
    {
        return ! empty($this->impactGsLeft);
    }

    /**
     * @param ?array $impactGsRight [G]
     */
    public function setImpactGsRight(?array $impactGsRight = null): self
    {
        $this->impactGsRight = $impactGsRight;

        return $this;
    }

    /**
     * @return ?array [G]
     */
    public function getImpactGsRight(): ?array
    {
        return $this->impactGsRight;
    }

    public function hasImpactGsRight(): bool
    {
        return ! empty($this->impactGsRight);
    }

    /**
     * @param ?array $brakingGsLeft [G]
     */
    public function setBrakingGsLeft(?array $brakingGsLeft = null): self
    {
        $this->brakingGsLeft = $brakingGsLeft;

        return $this;
    }

    /**
     * @return ?array [G]
     */
    public function getBrakingGsLeft(): ?array
    {
        return $this->brakingGsLeft;
    }

    public function hasBrakingGsLeft(): bool
    {
        return ! empty($this->brakingGsLeft);
    }

    /**
     * @param ?array $brakingGsRight [G]
     */
    public function setBrakingGsRight(?array $brakingGsRight = null): self
    {
        $this->brakingGsRight = $brakingGsRight;

        return $this;
    }

    /**
     * @return ?array [G]
     */
    public function getBrakingGsRight(): ?array
    {
        return $this->brakingGsRight;
    }

    public function hasBrakingGsRight(): bool
    {
        return ! empty($this->brakingGsRight);
    }

    /**
     * @param ?array $footstrikeTypeLeft [°]
     */
    public function setFootstrikeTypeLeft(?array $footstrikeTypeLeft = null): self
    {
        $this->footstrikeTypeLeft = $footstrikeTypeLeft;

        return $this;
    }

    /**
     * @return ?array [°]
     */
    public function getFootstrikeTypeLeft(): ?array
    {
        return $this->footstrikeTypeLeft;
    }

    public function hasFootstrikeTypeLeft(): bool
    {
        return ! empty($this->footstrikeTypeLeft);
    }

    /**
     * @param ?array $footstrikeTypeRight [°]
     */
    public function setFootstrikeTypeRight(?array $footstrikeTypeRight = null): self
    {
        $this->footstrikeTypeRight = $footstrikeTypeRight;

        return $this;
    }

    /**
     * @return ?array [°]
     */
    public function getFootstrikeTypeRight(): ?array
    {
        return $this->footstrikeTypeRight;
    }

    public function hasFootstrikeTypeRight(): bool
    {
        return ! empty($this->footstrikeTypeRight);
    }

    /**
     * @param ?array $pronationExcursionLeft [°]
     */
    public function setPronationExcursionLeft(?array $pronationExcursionLeft = null): self
    {
        $this->pronationExcursionLeft = $pronationExcursionLeft;

        return $this;
    }

    /**
     * @return ?array [°]
     */
    public function getPronationExcursionLeft(): ?array
    {
        return $this->pronationExcursionLeft;
    }

    public function hasPronationExcursionLeft(): bool
    {
        return ! empty($this->pronationExcursionLeft);
    }

    /**
     * @param ?array $pronationExcursionRight [°]
     */
    public function setPronationExcursionRight(?array $pronationExcursionRight = null): self
    {
        $this->pronationExcursionRight = $pronationExcursionRight;

        return $this;
    }

    /**
     * @return ?array [°]
     */
    public function getPronationExcursionRight(): ?array
    {
        return $this->pronationExcursionRight;
    }

    public function hasPronationExcursionRight(): bool
    {
        return ! empty($this->pronationExcursionRight);
    }

    /**
     * @param ?array $performanceCondition [n]
     */
    public function setPerformanceCondition(?array $performanceCondition = null): self
    {
        $this->performanceCondition = $performanceCondition;

        return $this;
    }

    /**
     * @return ?array [n]
     */
    public function getPerformanceCondition(): ?array
    {
        return $this->performanceCondition;
    }

    public function hasPerformanceCondition(): bool
    {
        return ! empty($this->performanceCondition);
    }

    /**
     * @param ?array $respirationRate [pm]
     */
    public function setRespirationRate(?array $respirationRate = null): self
    {
        $this->respirationRate = $respirationRate;

        return $this;
    }

    /**
     * @return ?array [pm]
     */
    public function getRespirationRate(): ?array
    {
        return $this->respirationRate;
    }

    public function hasRespirationRate(): bool
    {
        return ! empty($this->respirationRate);
    }

    public function setPauses(PauseCollection $pauses): self
    {
        $this->pauses = $pauses;

        return $this;
    }

    public function getPauses(): PauseCollection
    {
        return $this->pauses;
    }

    public function setActivity(Training $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getActivity(): ?Training
    {
        return $this->activity ?? null;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account ?? null;
    }

    public function setLock(bool $flag): self
    {
        $this->lock = $flag;

        return $this;
    }

    public function getLock(): bool
    {
        return $this->lock;
    }

    public function isLocked(): bool
    {
        return $this->lock;
    }

    public function isEmpty(): bool
    {
        return
            ! $this->hasTime() &&
            ! $this->hasDistance() &&
            ! $this->hasHeartrate() &&
            ! $this->hasCadence() &&
            ! $this->hasPower() &&
            ! $this->hasTemperature() &&
            ! $this->hasGroundcontact() &&
            ! $this->hasGroundcontactBalance() &&
            ! $this->hasVerticalOscillation() &&
            ! $this->hasSmo20() &&
            ! $this->hasSmo21() &&
            ! $this->hasThb0() &&
            ! $this->hasThb1() &&
            ! $this->hasImpactGsLeft() &&
            ! $this->hasImpactGsRight() &&
            ! $this->hasBrakingGsLeft() &&
            ! $this->hasBrakingGsRight() &&
            ! $this->hasFootstrikeTypeLeft() &&
            ! $this->hasFootstrikeTypeRight() &&
            ! $this->hasPronationExcursionLeft() &&
            ! $this->hasPronationExcursionRight() &&
            ! $this->hasPerformanceCondition() &&
            ! $this->hasRespirationRate() &&
            $this->pauses->isEmpty()
        ;
    }

    public function getLegacyModel(): Model\Trackdata\Entity
    {
        return new Model\Trackdata\Entity([
            Model\Trackdata\Entity::ACTIVITYID => $this->activity->getId(),
            Model\Trackdata\Entity::TIME => $this->time,
            Model\Trackdata\Entity::DISTANCE => $this->distance,
            Model\Trackdata\Entity::HEARTRATE => $this->heartrate,
            Model\Trackdata\Entity::CADENCE => $this->cadence,
            Model\Trackdata\Entity::POWER => $this->power,
            Model\Trackdata\Entity::TEMPERATURE => $this->temperature,
            Model\Trackdata\Entity::GROUNDCONTACT => $this->groundcontact,
            Model\Trackdata\Entity::GROUNDCONTACT_BALANCE => $this->groundcontactBalance,
            Model\Trackdata\Entity::VERTICAL_OSCILLATION => $this->verticalOscillation,
            Model\Trackdata\Entity::SMO2_0 => $this->smo20,
            Model\Trackdata\Entity::SMO2_1 => $this->smo21,
            Model\Trackdata\Entity::THB_0 => $this->thb0,
            Model\Trackdata\Entity::THB_1 => $this->thb1,
            Model\Trackdata\Entity::IMPACT_GS_LEFT => $this->impactGsLeft,
            Model\Trackdata\Entity::IMPACT_GS_RIGHT => $this->impactGsRight,
            Model\Trackdata\Entity::BRAKING_GS_LEFT => $this->brakingGsLeft,
            Model\Trackdata\Entity::BRAKING_GS_RIGHT => $this->brakingGsRight,
            Model\Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT => $this->footstrikeTypeLeft,
            Model\Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT => $this->footstrikeTypeRight,
            Model\Trackdata\Entity::PRONATION_EXCURSION_LEFT => $this->pronationExcursionLeft,
            Model\Trackdata\Entity::PRONATION_EXCURSION_RIGHT => $this->pronationExcursionRight,
            Model\Trackdata\Entity::PERFORMANCE_CONDITION => $this->performanceCondition,
            Model\Trackdata\Entity::RESPIRATION_RATE => $this->respirationRate,

            // Legacy model does still use the pauses object
            // Model\Trackdata\Entity::PAUSES => $this->pauses
        ]);
    }

    public function getAdapter(): TrackDataAdapter
    {
        $this->Adapter ??= new TrackDataAdapter($this);

        return $this->Adapter;
    }

    /**
     * @return ?array [s/km]
     */
    public function getPace(): ?array
    {
        if ($this->pace === null) {
            $this->getAdapter()
                ->calculatePace();
        }

        return $this->pace;
    }

    /**
     * @param ?array $pace [s/km]
     */
    public function setPace(?array $pace = null): void
    {
        $this->pace = $pace;
    }

    public function hasPace(): bool
    {
        return $this->hasTime() && $this->hasDistance();
    }

    /**
     * @return ?array [s/km]
     */
    public function getGradeAdjustedPace(): ?array
    {
        if ($this->gradeAdjustedPace === null || $this->pace === null || $this->gradient === null) {
            $this->getAdapter()
                ->calculateGradeAdjustedPace();
        }

        return $this->gradeAdjustedPace;
    }

    /**
     * @param ?array $gradeAdjustedPace [s/km]
     */
    public function setGradeAdjustedPace(?array $gradeAdjustedPace = null): void
    {
        $this->gradeAdjustedPace = $gradeAdjustedPace;
    }

    /**
     * @return ?array (-100 .. 100)
     */
    public function getGradient(): ?array
    {
        if ($this->gradient === null) {
            $this->getAdapter()
                ->calculateGradient();
        }

        return $this->gradient;
    }

    /**
     * @param ?array $gradient (-100 .. 100)
     */
    public function setGradient(?array $gradient = null): void
    {
        $this->gradient = $gradient;
    }

    /**
     * @return ?array [cm]
     */
    public function getStrideLength(): ?array
    {
        if ($this->strideLength === null || $this->pace === null) {
            $this->getAdapter()
                ->calculateStrideLength();
        }

        return $this->strideLength;
    }

    /**
     * @param ?array $strideLength [cm]
     */
    public function setStrideLength(?array $strideLength = null): void
    {
        $this->strideLength = $strideLength;
    }

    public function hasStrideLength(): bool
    {
        return $this->hasCadence() && $this->hasPace();
    }

    /**
     * @return ?array [%]
     */
    public function getVerticalRatio(): ?array
    {
        if ($this->verticalRatio === null || $this->strideLength === null) {
            $this->getAdapter()
                ->calculateVerticalRatio();
        }

        return $this->verticalRatio;
    }

    /**
     * @param ?array $verticalRatio [%]
     */
    public function setVerticalRatio(?array $verticalRatio = null): void
    {
        $this->verticalRatio = $verticalRatio;
    }

    public function hasVerticalRatio(): bool
    {
        return $this->hasVerticalOscillation() && $this->hasStrideLength();
    }
}

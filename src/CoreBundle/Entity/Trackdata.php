<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\TrackDataAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Model;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

/**
 * @ORM\Table(name="trackdata")
 * @ORM\Entity(repositoryClass="Runalyze\Bundle\CoreBundle\Entity\TrackdataRepository")
 */
class Trackdata implements AccountRelatedEntityInterface
{
    /**
     * @var array|null [s]
     *
     * @ORM\Column(name="time", type="pipe_array", nullable=true)
     */
    private $time;

    /**
     * @var array|null [km]
     *
     * @ORM\Column(name="distance", type="pipe_array", nullable=true)
     */
    private $distance;

    /**
     * @var array|null [bpm]
     *
     * @ORM\Column(name="heartrate", type="pipe_array", nullable=true)
     */
    private $heartrate;

    /**
     * @var array|null [rpm]
     *
     * @ORM\Column(name="cadence", type="pipe_array", nullable=true)
     */
    private $cadence;

    /**
     * @var array|null [W]
     *
     * @ORM\Column(name="power", type="pipe_array", nullable=true)
     */
    private $power;

    /**
     * @var array|null [°C]
     *
     * @ORM\Column(name="temperature", type="pipe_array", nullable=true)
     */
    private $temperature;

    /**
     * @var array|null [ms]
     *
     * @ORM\Column(name="groundcontact", type="pipe_array", nullable=true)
     */
    private $groundcontact;

    /**
     * @var array|null [mm]
     *
     * @ORM\Column(name="vertical_oscillation", type="pipe_array", nullable=true)
     */
    private $verticalOscillation;

    /**
     * @var array|null [%*100]
     *
     * @ORM\Column(name="groundcontact_balance", type="pipe_array", nullable=true)
     */
    private $groundcontactBalance;

    /**
     * @var array|null [%]
     *
     * @ORM\Column(name="smo2_0", type="pipe_array", nullable=true)
     */
    private $smo20;

    /**
     * @var array|null [%]
     *
     * @ORM\Column(name="smo2_1", type="pipe_array", nullable=true)
     */
    private $smo21;

    /**
     * @var array|null [%]
     *
     * @ORM\Column(name="thb_0", type="pipe_array", nullable=true)
     */
    private $thb0;

    /**
     * @var array|null [%]
     *
     * @ORM\Column(name="thb_1", type="pipe_array", nullable=true)
     */
    private $thb1;

    /**
     * @var array|null [G]
     *
     * @ORM\Column(name="impact_gs_left", type="pipe_array", nullable=true)
     */
    private $impactGsLeft;

    /**
     * @var array|null [G]
     *
     * @ORM\Column(name="impact_gs_right", type="pipe_array", nullable=true)
     */
    private $impactGsRight;

    /**
     * @var array|null [G]
     *
     * @ORM\Column(name="braking_gs_left", type="pipe_array", nullable=true)
     */
    private $brakingGsLeft;

    /**
     * @var array|null [G]
     *
     * @ORM\Column(name="braking_gs_right", type="pipe_array", nullable=true)
     */
    private $brakingGsRight;

    /**
     * @var array|null [°]
     *
     * @ORM\Column(name="footstrike_type_left", type="pipe_array", nullable=true)
     */
    private $footstrikeTypeLeft;

    /**
     * @var array|null [°]
     *
     * @ORM\Column(name="footstrike_type_right", type="pipe_array", nullable=true)
     */
    private $footstrikeTypeRight;

    /**
     * @var array|null [°]
     *
     * @ORM\Column(name="pronation_excursion_left", type="pipe_array", nullable=true)
     */
    private $pronationExcursionLeft;

    /**
     * @var array|null [°]
     *
     * @ORM\Column(name="pronation_excursion_right", type="pipe_array", nullable=true)
     */
    private $pronationExcursionRight;

    /**
     * @var array|null [n]
     *
     * @ORM\Column(name="performance_condition", type="pipe_array", nullable=true)
     */
    private $performanceCondition;

    /**
     * @var array|null [pm]
     *
     * @ORM\Column(name="respiration_rate", type="pipe_array", nullable=true)
     */
    private $respirationRate;

    /**
     * @var \Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection
     *
     * @ORM\Column(name="pauses", type="runalyze_pause_array", length=65535, nullable=true)
     */
    private $pauses;

    /**
     * @var Training
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Training", inversedBy="trackdata")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="activityid", referencedColumnName="id", unique=true)
     * })
     */
    private $activity;

    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Account")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    /**
     * @var bool
     *
     * @ORM\Column(name="`lock`", type="boolean")
     */
    private $lock = false;

    /**
     * @var TrackDataAdapter
     */
    private $Adapter;

    /**
     * @var false|null|array [s/km]
     */
    private $pace = false;

    /**
     * @var false|null|array [s/km]
     */
    private $gradeAdjustedPace = false;

    /**
     * @var false|null|array (-100 .. 100)
     */
    private $gradient = false;

    /**
     * @var false|null|array [cm]
     */
    private $strideLength = false;

    /**
     * @var false|null|array [%]
     */
    private $verticalRatio = false;

    public function __construct()
    {
        $this->pauses = new PauseCollection();
    }

    /**
     * @param array|null $time [s]
     */
    public function setTime(?array $time = null): self
    {
        $this->time = $time;

        $this->pace = false;

        return $this;
    }

    /**
     * @return array|null [s]
     */
    public function getTime()
    {
        return $this->time;
    }

    public function hasTime(): bool
    {
        return $this->time !== null;
    }

    /**
     * @return int [s]
     */
    public function getTotalDuration()
    {
        if (! $this->hasTime()) {
            return 0;
        }

        return end($this->time);
    }

    /**
     * @param array|null $distance [km]
     */
    public function setDistance(?array $distance = null): self
    {
        $this->distance = $distance;

        $this->pace = false;
        $this->gradient = false;

        return $this;
    }

    /**
     * @return array|null [km]
     */
    public function getDistance()
    {
        return $this->distance;
    }

    public function hasDistance(): bool
    {
        return $this->distance !== null;
    }

    /**
     * @return float [km]
     */
    public function getTotalDistance()
    {
        if (! $this->hasDistance()) {
            return 0.0;
        }

        return end($this->distance);
    }

    /**
     * @param array|null $heartRate [bpm]
     */
    public function setHeartrate(?array $heartRate = null): self
    {
        $this->heartrate = $heartRate;

        return $this;
    }

    /**
     * @return array|null [bpm]
     */
    public function getHeartrate()
    {
        return $this->heartrate;
    }

    public function hasHeartrate(): bool
    {
        return $this->heartrate !== null;
    }

    /**
     * @param array|null $cadence [rpm]
     */
    public function setCadence(?array $cadence = null): self
    {
        $this->cadence = $cadence;

        $this->strideLength = false;

        return $this;
    }

    /**
     * @return array|null [rpm]
     */
    public function getCadence()
    {
        return $this->cadence;
    }

    public function hasCadence(): bool
    {
        return $this->cadence !== null;
    }

    /**
     * @param array|null $power [W]
     */
    public function setPower(?array $power = null): self
    {
        $this->power = $power;

        return $this;
    }

    /**
     * @return array|null [W]
     */
    public function getPower()
    {
        return $this->power;
    }

    public function hasPower(): bool
    {
        return $this->power !== null;
    }

    /**
     * @param array|null $temperature [°C]
     */
    public function setTemperature(?array $temperature = null): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return array|null [°C]
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    public function hasTemperature(): bool
    {
        return $this->temperature !== null;
    }

    /**
     * @param array|null $groundcontact [ms]
     */
    public function setGroundcontact(?array $groundcontact = null): self
    {
        $this->groundcontact = $groundcontact;

        return $this;
    }

    /**
     * @return array|null [ms]
     */
    public function getGroundcontact()
    {
        return $this->groundcontact;
    }

    public function hasGroundcontact(): bool
    {
        return $this->groundcontact !== null;
    }

    /**
     * @param array|null $verticalOscillation [mm]
     */
    public function setVerticalOscillation(?array $verticalOscillation = null): self
    {
        $this->verticalOscillation = $verticalOscillation;

        $this->verticalRatio = false;

        return $this;
    }

    /**
     * @return array|null [mm]
     */
    public function getVerticalOscillation()
    {
        return $this->verticalOscillation;
    }

    public function hasVerticalOscillation(): bool
    {
        return $this->verticalOscillation !== null;
    }

    /**
     * @param array|null $groundcontactBalance [%*100]
     */
    public function setGroundcontactBalance(?array $groundcontactBalance = null): self
    {
        $this->groundcontactBalance = $groundcontactBalance;

        return $this;
    }

    /**
     * @return array|null [%*100]
     */
    public function getGroundcontactBalance()
    {
        return $this->groundcontactBalance;
    }

    public function hasGroundcontactBalance(): bool
    {
        return $this->groundcontactBalance !== null;
    }

    /**
     * @param array|null $smo20 [%]
     */
    public function setSmo20(?array $smo20 = null): self
    {
        $this->smo20 = $smo20;

        return $this;
    }

    /**
     * @return array|null [%]
     */
    public function getSmo20()
    {
        return $this->smo20;
    }

    public function hasSmo20(): bool
    {
        return $this->smo20 !== null;
    }

    /**
     * @param array|null $smo21 [%]
     */
    public function setSmo21(?array $smo21 = null): self
    {
        $this->smo21 = $smo21;

        return $this;
    }

    /**
     * @return array|null [%]
     */
    public function getSmo21()
    {
        return $this->smo21;
    }

    public function hasSmo21(): bool
    {
        return $this->smo21 !== null;
    }

    /**
     * @param array|null $thb0 [%]
     */
    public function setThb0(?array $thb0 = null): self
    {
        $this->thb0 = $thb0;

        return $this;
    }

    /**
     * @return array|null [%]
     */
    public function getThb0()
    {
        return $this->thb0;
    }

    public function hasThb0(): bool
    {
        return $this->thb0 !== null;
    }

    /**
     * @param array|null $thb1 [%]
     */
    public function setThb1(?array $thb1 = null): self
    {
        $this->thb1 = $thb1;

        return $this;
    }

    /**
     * @return array|null [%]
     */
    public function getThb1()
    {
        return $this->thb1;
    }

    public function hasThb1(): bool
    {
        return $this->thb1 !== null;
    }

    /**
     * @param array|null $impactGsLeft [G]
     */
    public function setImpactGsLeft(?array $impactGsLeft = null): self
    {
        $this->impactGsLeft = $impactGsLeft;

        return $this;
    }

    /**
     * @return array|null [G]
     */
    public function getImpactGsLeft()
    {
        return $this->impactGsLeft;
    }

    public function hasImpactGsLeft(): bool
    {
        return $this->impactGsLeft !== null;
    }

    /**
     * @param array|null $impactGsRight [G]
     */
    public function setImpactGsRight(?array $impactGsRight = null): self
    {
        $this->impactGsRight = $impactGsRight;

        return $this;
    }

    /**
     * @return array|null [G]
     */
    public function getImpactGsRight()
    {
        return $this->impactGsRight;
    }

    public function hasImpactGsRight(): bool
    {
        return $this->impactGsRight !== null;
    }

    /**
     * @param array|null $brakingGsLeft [G]
     */
    public function setBrakingGsLeft(?array $brakingGsLeft = null): self
    {
        $this->brakingGsLeft = $brakingGsLeft;

        return $this;
    }

    /**
     * @return array|null [G]
     */
    public function getBrakingGsLeft()
    {
        return $this->brakingGsLeft;
    }

    public function hasBrakingGsLeft(): bool
    {
        return $this->brakingGsLeft !== null;
    }

    /**
     * @param array|null $brakingGsRight [G]
     */
    public function setBrakingGsRight(?array $brakingGsRight = null): self
    {
        $this->brakingGsRight = $brakingGsRight;

        return $this;
    }

    /**
     * @return array|null [G]
     */
    public function getBrakingGsRight()
    {
        return $this->brakingGsRight;
    }

    public function hasBrakingGsRight(): bool
    {
        return $this->brakingGsRight !== null;
    }

    /**
     * @param array|null $footstrikeTypeLeft [°]
     */
    public function setFootstrikeTypeLeft(?array $footstrikeTypeLeft = null): self
    {
        $this->footstrikeTypeLeft = $footstrikeTypeLeft;

        return $this;
    }

    /**
     * @return array|null [°]
     */
    public function getFootstrikeTypeLeft()
    {
        return $this->footstrikeTypeLeft;
    }

    public function hasFootstrikeTypeLeft(): bool
    {
        return $this->footstrikeTypeLeft !== null;
    }

    /**
     * @param array|null $footstrikeTypeRight [°]
     */
    public function setFootstrikeTypeRight(?array $footstrikeTypeRight = null): self
    {
        $this->footstrikeTypeRight = $footstrikeTypeRight;

        return $this;
    }

    /**
     * @return array|null [°]
     */
    public function getFootstrikeTypeRight()
    {
        return $this->footstrikeTypeRight;
    }

    public function hasFootstrikeTypeRight(): bool
    {
        return $this->footstrikeTypeRight !== null;
    }

    /**
     * @param array|null $pronationExcursionLeft [°]
     */
    public function setPronationExcursionLeft(?array $pronationExcursionLeft = null): self
    {
        $this->pronationExcursionLeft = $pronationExcursionLeft;

        return $this;
    }

    /**
     * @return array|null [°]
     */
    public function getPronationExcursionLeft()
    {
        return $this->pronationExcursionLeft;
    }

    public function hasPronationExcursionLeft(): bool
    {
        return $this->pronationExcursionLeft !== null;
    }

    /**
     * @param array|null $pronationExcursionRight [°]
     */
    public function setPronationExcursionRight(?array $pronationExcursionRight = null): self
    {
        $this->pronationExcursionRight = $pronationExcursionRight;

        return $this;
    }

    /**
     * @return array|null [°]
     */
    public function getPronationExcursionRight()
    {
        return $this->pronationExcursionRight;
    }

    public function hasPronationExcursionRight(): bool
    {
        return $this->pronationExcursionRight !== null;
    }

    /**
     * @param array|null $performanceCondition [n]
     */
    public function setPerformanceCondition(?array $performanceCondition = null): self
    {
        $this->performanceCondition = $performanceCondition;

        return $this;
    }

    /**
     * @return array|null [n]
     */
    public function getPerformanceCondition()
    {
        return $this->performanceCondition;
    }

    public function hasPerformanceCondition(): bool
    {
        return $this->performanceCondition !== null;
    }

    /**
     * @param array|null $respirationRate [pm]
     */
    public function setRespirationRate(?array $respirationRate = null): self
    {
        $this->respirationRate = $respirationRate;

        return $this;
    }

    /**
     * @return array|null [n]
     */
    public function getRespirationRate()
    {
        return $this->respirationRate;
    }

    public function hasRespirationRate(): bool
    {
        return $this->respirationRate !== null;
    }

    public function setPauses(PauseCollection $pauses): self
    {
        $this->pauses = $pauses;

        return $this;
    }

    /**
     * @return \Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection
     */
    public function getPauses()
    {
        return $this->pauses;
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

    /**
     * @param bool $flag
     */
    public function setLock($flag): self
    {
        $this->lock = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->lock;
    }

    public function isEmpty(): bool
    {
        return
            ($this->time === null && empty($this->time)) &&
            ($this->distance === null && empty($this->distance)) &&
            ($this->heartrate === null && empty($this->heartrate)) &&
            ($this->cadence === null && empty($this->cadence)) &&
            ($this->power === null && empty($this->power)) &&
            ($this->temperature === null && empty($this->temperature)) &&
            ($this->groundcontact === null && empty($this->groundcontact)) &&
            ($this->groundcontactBalance === null && empty($this->groundcontactBalance)) &&
            ($this->verticalOscillation === null && empty($this->verticalOscillation)) &&
            ($this->smo20 === null && empty($this->smo20)) &&
            ($this->smo21 === null && empty($this->smo21)) &&
            ($this->thb0 === null && empty($this->thb0)) &&
            ($this->thb1 === null && empty($this->thb1)) &&
            ($this->impactGsLeft === null && empty($this->impactGsLeft)) &&
            ($this->impactGsRight === null && empty($this->impactGsRight)) &&
            ($this->brakingGsLeft === null && empty($this->brakingGsLeft)) &&
            ($this->brakingGsRight === null && empty($this->brakingGsRight)) &&
            ($this->footstrikeTypeLeft === null && empty($this->footstrikeTypeLeft)) &&
            ($this->footstrikeTypeRight === null && empty($this->footstrikeTypeRight)) &&
            ($this->pronationExcursionLeft === null && empty($this->pronationExcursionLeft)) &&
            ($this->pronationExcursionRight === null && empty($this->pronationExcursionRight)) &&
            ($this->performanceCondition === null && empty($this->performanceCondition)) &&
            ($this->respirationRate === null && empty($this->respirationRate)) &&
            $this->pauses->isEmpty()
        ;
    }

    public function getLegacyModel(): \Runalyze\Model\Trackdata\Entity
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

    /**
     * @return TrackDataAdapter
     */
    public function getAdapter()
    {
        if ($this->Adapter === null) {
            $this->Adapter = new TrackDataAdapter($this);
        }

        return $this->Adapter;
    }

    /**
     * @return array|null [s/km]
     */
    public function getPace()
    {
        if ($this->pace === false) {
            $this->getAdapter()
                ->calculatePace();
        }

        return $this->pace;
    }

    /**
     * @param array|null $pace [s/km]
     */
    public function setPace(?array $pace = null)
    {
        $this->pace = $pace;
    }

    public function hasPace(): bool
    {
        return $this->time !== null && $this->distance !== null;
    }

    /**
     * @return array|null [s/km]
     */
    public function getGradeAdjustedPace()
    {
        if (in_array(false, [$this->gradeAdjustedPace, $this->pace, $this->gradient], true)) {
            $this->getAdapter()
                ->calculateGradeAdjustedPace();
        }

        return $this->gradeAdjustedPace;
    }

    /**
     * @param array|null $gradeAdjustedPace [s/km]
     */
    public function setGradeAdjustedPace(?array $gradeAdjustedPace = null)
    {
        $this->gradeAdjustedPace = $gradeAdjustedPace;
    }

    /**
     * @return array|null (-100 .. 100)
     */
    public function getGradient()
    {
        if ($this->gradient === false) {
            $this->getAdapter()
                ->calculateGradient();
        }

        return $this->gradient;
    }

    /**
     * @param array|null $gradient (-100 .. 100)
     */
    public function setGradient(?array $gradient = null)
    {
        $this->gradient = $gradient;
    }

    /**
     * @return array|null [cm]
     */
    public function getStrideLength()
    {
        if ($this->strideLength === false || $this->pace === false) {
            $this->getAdapter()
                ->calculateStrideLength();
        }

        return $this->strideLength;
    }

    /**
     * @param array|null $strideLength [cm]
     */
    public function setStrideLength(?array $strideLength = null)
    {
        $this->strideLength = $strideLength;
    }

    public function hasStrideLength(): bool
    {
        return $this->cadence !== null && $this->hasPace();
    }

    /**
     * @return array|null [%]
     */
    public function getVerticalRatio()
    {
        if ($this->verticalRatio === false || $this->strideLength === false) {
            $this->getAdapter()
                ->calculateVerticalRatio();
        }

        return $this->verticalRatio;
    }

    /**
     * @param array|null $verticalRatio [%]
     */
    public function setVerticalRatio(?array $verticalRatio = null)
    {
        $this->verticalRatio = $verticalRatio;
    }

    public function hasVerticalRatio(): bool
    {
        return $this->verticalOscillation !== null && $this->hasStrideLength();
    }
}

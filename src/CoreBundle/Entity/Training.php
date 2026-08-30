<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\ActivityAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Util\LocalTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="training", indexes={@ORM\Index(name="time", columns={"accountid", "time"}), @ORM\Index(name="sportid", columns={"accountid", "sportid"}), @ORM\Index(name="typeid", columns={"accountid", "typeid"})})
 * @ORM\Entity(repositoryClass="Runalyze\Bundle\CoreBundle\Entity\TrainingRepository")
 * @ORM\EntityListeners({"Runalyze\Bundle\CoreBundle\EntityListener\ActivityListener"})
 * @ORM\HasLifecycleCallbacks()
 */
class Training implements IdentifiableEntityInterface, AccountRelatedEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=10, unique=true, nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Sport
     *
     * @Assert\NotBlank(message="You need to choose a sport.")
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Sport", inversedBy="trainings")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sportid", referencedColumnName="id", nullable=false)
     * })
     */
    private $sport;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Type
     *
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Type", inversedBy="trainings")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="typeid", referencedColumnName="id")
     * })
     */
    private $type;

    /**
     * @var int [timestamp]
     * @Assert\NotBlank(message="Every activity needs a time")
     *
     * @ORM\Column(name="time", type="integer", nullable=false)
     */
    private $time;

    /**
     * @var int|null [min]
     *
     * @ORM\Column(name="timezone_offset", type="smallint", nullable=true)
     */
    private ?int $timezoneOffset = null;

    /**
     * @var int|null [timestamp]
     *
     * @ORM\Column(name="created", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $created = null;

    /**
     * @var int|null [timestamp]
     *
     * @ORM\Column(name="edited", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $edited = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_public", type="boolean")
     */
    private $isPublic = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_track", type="boolean")
     */
    private $isTrack = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="with_goal", type="boolean")
     */
    private $withGoal = false;

    /**
     * @var float|null [km]
     *
     * @ORM\Column(name="distance", type="casted_decimal_2", precision=6, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $distance = null;

    /**
     * @var ?float [s]
     *
     * @Assert\GreaterThan(0)
     * @ORM\Column(name="s", type="casted_decimal_2", precision=8, scale=2, options={"unsigned"=true})
     */
    private ?float $s = 0.0;

    /**
     * @var int|null [s]
     *
     * @ORM\Column(name="elapsed_time", type="integer", nullable=true, options={"unsigned"=true})
     */
    private ?int $elapsedTime = null;

    /**
     * @var int|null [m]
     *
     * @ORM\Column(name="elevation", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private ?int $elevation = null;

    /**
     * @var float|null [0.0 .. 10.0]
     *
     * @ORM\Column(name="climb_score", type="casted_decimal_1", precision=3, scale=1, nullable=true, options={"unsigned"=true})
     */
    private ?float $climbScore = null;

    /**
     * @var float|null [0.00 .. 1.00]
     *
     * @ORM\Column(name="percentage_hilly", type="casted_decimal_2", precision=3, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $percentageHilly = null;

    /**
     * @var int|null [kcal]
     *
     * @ORM\Column(name="kcal", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private ?int $kcal = null;

    /**
     * @var int|null [kcal]
     *
     * @ORM\Column(name="kcal_rest", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private ?int $kcalInRest = null;

    /**
     * @var int|null [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     minMessage="Your average heartrate must be at least {{ limit }} bpm",
     *     maxMessage="Your average heartrate cannot be greater than {{ limit }} bpm"
     * )
     *
     * @ORM\Column(name="pulse_avg", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private ?int $pulseAvg = null;

    /**
     * @var int|null [bpm]
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     minMessage="Your maximum heartrate must be at least {{ limit }} bpm",
     *     maxMessage="Your maximum heartrate cannot be greater than {{ limit }} bpm"
     * )
     * @ORM\Column(name="pulse_max", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $pulseMax;

    /**
     * @var int|null [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     minMessage="Your average heartrate active rounds must be at least {{ limit }} bpm",
     *     maxMessage="Your average heartrate active rounds cannot be greater than {{ limit }} bpm"
     * )
     *
     * @ORM\Column(name="pulse_avg_active", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $pulseAvgActive;

    /**
     * @var int|null [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     minMessage="Your average heartrate active rounds must be at least {{ limit }} bpm",
     *     maxMessage="Your average heartrate active rounds cannot be greater than {{ limit }} bpm"
     * )
     *
     * @ORM\Column(name="max_hr_user", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $maxHrUser;

    /**
     * @var array|null [bpm]
     *
     * @ORM\Column(name="hr_zone_bounds", type="pipe_array", nullable=true)
     */
    private $hrZoneBounds;

    /**
     * @var float|null [ml/kg/min]
     *
     * @ORM\Column(name="vo2max", type="casted_decimal_2", precision=5, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $vo2max;

    /**
     * @var float|null [ml/kg/min]
     *
     * @ORM\Column(name="vo2max_by_time", type="casted_decimal_2", precision=5, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $vo2maxByTime;

    /**
     * @var float|null [ml/kg/min]
     *
     * @ORM\Column(name="vo2max_with_elevation", type="casted_decimal_2", precision=5, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $vo2maxWithElevation;

    /**
     * @var bool
     *
     * @ORM\Column(name="use_vo2max", type="boolean")
     */
    private $useVO2max = true;

    /**
     * @var float|null [ml/kg/min]
     *
     * @ORM\Column(name="fit_vo2max_estimate", type="casted_decimal_2", precision=4, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $fitVO2maxEstimate = null;

    /**
     * @var int|null [min]
     *
     * @ORM\Column(name="fit_recovery_time", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitRecoveryTime;

    /**
     * @var int|null
     *
     * @ORM\Column(name="fit_hrv_analysis", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitHrvAnalysis;

    /**
     * @var int|null [1 .. 7]
     *
     * @ORM\Column(name="fit_training_effect_benefit", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitTrainingEffectBenefit;

    /**
     * @var float|null [0.0 .. 5.0]
     *
     * @ORM\Column(name="fit_training_effect", type="casted_decimal_1", precision=2, scale=1, nullable=true, options={"unsigned"=true})
     */
    private ?float $fitTrainingEffect = null;

    /**
     * @var float|null [0.0 .. 5.0]
     *
     * #TSC: new DB column to training table
     *
     * @ORM\Column(name="fit_anaerobic_training_effect", type="casted_decimal_1", precision=2, scale=1, nullable=true, options={"unsigned"=true})
     */
    private ?float $fitAnaerobicTrainingEffect = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="fit_performance_condition", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $fitPerformanceCondition;

    /**
     * @var int|null
     *
     * @ORM\Column(name="fit_performance_condition_end", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $fitPerformanceConditionEnd;

    /**
     * @var int|null
     *
     * @ORM\Column(name="fit_lactate_threshold_hr", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $fitLactateThresholdHR;

    /**
     * @var float|null [m/s]
     *
     * @ORM\Column(name="fit_lactate_threshold_pace_ms", type="casted_decimal_2", precision=4, scale=2, nullable=true, options={"unsigned"=true})
     */
    private ?float $fitLactateThresholdPace = null;

    /**
     * @var int|null [m]
     *
     * @ORM\Column(name="fit_total_ascent", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitTotalAscent;

    /**
     * @var int|null [m]
     *
     * @ORM\Column(name="fit_total_descent", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitTotalDescent;

    /**
     * @var int|null []
     *
     * @ORM\Column(name="fit_self_evaluation_feeling", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitSelfEvaluationFeeling;

    /**
     * @var int|null []
     *
     * @ORM\Column(name="fit_self_evaluation_perceived_effort", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitSelfEvaluationPerceivedEffort;

    /**
     * @var int|null []
     *
     * @ORM\Column(name="fit_load_peak", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitLoadPeak;

    /**
     * @var int|null [s]
     *
     * @ORM\Column(name="fit_run_time", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $fitRunTime;

    /**
     * @var int|null [s]
     *
     * @ORM\Column(name="fit_walk_time", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $fitWalkTime;

    /**
     * @var int|null [s]
     *
     * @ORM\Column(name="fit_stand_time", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitStandTime;

    /**
     * @var array|null [s]
     *
     * @ORM\Column(name="fit_seconds_hr_zones", type="pipe_array", nullable=true)
     */
    private $fitSecondsInHrZones;

    /**
     * @var int|null [ml]
     *
     * @ORM\Column(name="fit_sweat_loss", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $fitSweatLoss;

    /**
     * @var int|null []
     *
     * @ORM\Column(name="avg_respiration_rate", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $avgRespirationRate;

    /**
     * @var int|null []
     *
     * @ORM\Column(name="max_respiration_rate", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $maxRespirationRate;

    /**
     * @var int|null [6 .. 20]
     *
     * @ORM\Column(name="rpe", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $rpe;

    /**
     * @var int|null
     *
     * @ORM\Column(name="trimp", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $trimp;

    /**
     * @var int|null [rpm]
     *
     * @ORM\Column(name="cadence", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $cadence;

    /**
     * @var int|null [W]
     *
     * @ORM\Column(name="power", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $power;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_power_calculated", type="boolean", nullable=true, options={"default"=null})
     */
    private $isPowerCalculated;

    /**
     * @var int|null
     *
     * @ORM\Column(name="total_strokes", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $totalStrokes;

    /**
     * @var int|null
     *
     * @ORM\Column(name="swolf", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $swolf;

    /**
     * @var int|null [cm]
     *
     * @ORM\Column(name="stride_length", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $strideLength;

    /**
     * @var int|null
     *
     * @ORM\Column(name="total_cycles", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $totalCycles;

    /**
     * @var int|null [ms]
     *
     * @ORM\Column(name="groundcontact", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $groundcontact;

    /**
     * @var int|null [%ooL]
     *
     * @ORM\Column(name="groundcontact_balance", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $groundcontactBalance;

    /**
     * @var int|null [mm]
     *
     * @ORM\Column(name="vertical_oscillation", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $verticalOscillation;

    /**
     * @var int|null [%]
     *
     * @ORM\Column(name="vertical_ratio", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $verticalRatio;

    /**
     * @var float|null [G]
     *
     * @ORM\Column(name="avg_impact_gs_left", type="float", nullable=true, options={"unsigned"=true}, columnDefinition="FLOAT")
     */
    private $avgImpactGsLeft;

    /**
     * @var float|null [G]
     *
     * @ORM\Column(name="avg_impact_gs_right", type="float", nullable=true, options={"unsigned"=true}, columnDefinition="FLOAT")
     */
    private $avgImpactGsRight;

    /**
     * @var float|null [G]
     *
     * @ORM\Column(name="avg_braking_gs_left", type="float", nullable=true, options={"unsigned"=true}, columnDefinition="FLOAT")
     */
    private $avgBrakingGsLeft;

    /**
     * @var float|null [G]
     *
     * @ORM\Column(name="avg_braking_gs_right", type="float", nullable=true, options={"unsigned"=true}, columnDefinition="FLOAT")
     */
    private $avgBrakingGsRight;

    /**
     * @var int|null [°]
     *
     * @ORM\Column(name="avg_footstrike_type_left", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $avgFootstrikeTypeLeft;

    /**
     * @var int|null [°]
     *
     * @ORM\Column(name="avg_footstrike_type_right", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $avgFootstrikeTypeRight;

    /**
     * @var float|null [°]
     *
     * @ORM\Column(name="avg_pronation_excursion_left", type="float", nullable=true, columnDefinition="FLOAT")
     */
    private $avgPronationExcursionLeft;

    /**
     * @var float|null [°]
     *
     * @ORM\Column(name="avg_pronation_excursion_right", type="float", nullable=true, columnDefinition="FLOAT")
     */
    private $avgPronationExcursionRight;

    /**
     * @var int|null [°C]
     *
     * @ORM\Column(name="temperature", type="tinyint", nullable=true)
     */
    private $temperature;

    /**
     * @var int|null [km/h]
     *
     * @ORM\Column(name="wind_speed", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $windSpeed;

    /**
     * @var int|null [°]
     * @Assert\Range(
     *     min=0,
     *     max=359,
     *     minMessage="The wind direction cannot be less than {{ limit }}",
     *     maxMessage="The wind direction cannot be greater than {{ limit }}"
     * )
     * @ORM\Column(name="wind_deg", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $windDeg;

    /**
     * @var int|null [%]
     * @Assert\Range(
     *     min=0,
     *     max=100,
     *     minMessage="The humidity cannot be less than {{ limit }}",
     *     maxMessage="The humidity cannot be greater than {{ limit }}. Even if it feels like this."
     * )
     * @ORM\Column(name="humidity", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $humidity;

    /**
     * @var int|null [hPa]
     *
     * @ORM\Column(name="pressure", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $pressure;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_night", type="boolean", nullable=true)
     */
    private $isNight;

    /**
     * @var int enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     *
     * @ORM\Column(name="weatherid", type="smallint", nullable=false, options={"unsigned"=true, "default"=1})
     */
    private $weatherid = 1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="weather_source", type="tinyint", nullable=true, options={"unsigned"=true})
     */
    private $weatherSource;

    /**
     * @var string|null
     *
     * @ORM\Column(name="route", type="text", length=65535, nullable=true)
     */
    private $routeName;

    /**
     * @var Route|null
     *
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Route")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="routeid", referencedColumnName="id", nullable=true)
     * })
     */
    private $route;

    /**
     * @var \Runalyze\Parser\Activity\Common\Data\Round\RoundCollection
     *
     * @ORM\Column(name="splits", type="runalyze_round_array", length=16777215, nullable=true)
     */
    private $splits;

    /**
     * @var array|null
     *
     * @ORM\Column(name="splits_additional", type="json", length=2147483647, nullable=true)
     */
    private $splitsAdditional;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="text", length=65535, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="partner", type="text", length=65535, nullable=true)
     */
    private $partner;

    /**
     * @var array|null
     *
     * @ORM\Column(name="pace_goal", type="json", length=2147483647, nullable=true)
     */
    private $paceGoal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", length=65535, nullable=true)
     */
    private $notes;

    /**
     * @var \Runalyze\Bundle\CoreBundle\Entity\Account
     *
     * @ORM\ManyToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Account")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=100, nullable=false, options={"default"=""})
     */
    private $creator = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="creator_details", type="text", length=255, nullable=true)
     */
    private $creatorDetails;

    /**
     * @var int|null
     *
     * @ORM\Column(name="activity_id", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $activityId;

    /**
     * @var bool
     *
     * @ORM\Column(name="`lock`", type="boolean")
     */
    private $lock = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Equipment", inversedBy="activity")
     * @ORM\JoinTable(name="activity_equipment",
     *     joinColumns={
     *         @ORM\JoinColumn(name="activityid", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="equipmentid", referencedColumnName="id")
     *     }
     * )
     */
    private $equipment;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="activity")
     * @ORM\JoinTable(name="activity_tag",
     *     joinColumns={
     *         @ORM\JoinColumn(name="activityid", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="tagid", referencedColumnName="id")
     *     }
     * )
     */
    private $tag;

    /**
     * @var Trackdata|null
     *
     * @ORM\OneToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Trackdata", mappedBy="activity")
     */
    private $trackdata;

    /**
     * @var Swimdata|null
     *
     * @ORM\OneToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Swimdata", mappedBy="activity")
     */
    private $swimdata;

    /**
     * @var Hrv|null
     *
     * @ORM\OneToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Hrv", mappedBy="activity")
     */
    private $hrv;

    /**
     * @var Raceresult|null
     *
     * @ORM\OneToOne(targetEntity="Runalyze\Bundle\CoreBundle\Entity\Raceresult", mappedBy="activity")
     */
    private $raceresult;

    /**
     * @var null|ActivityAdapter
     */
    private $Adapter;

    public function __construct()
    {
        $this->equipment = new ArrayCollection();
        $this->tag = new ArrayCollection();
        $this->splits = new RoundCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setSport(Sport $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    /**
     * @return Sport
     */
    public function getSport()
    {
        return $this->sport;
    }

    public function setType(?Type $type = null): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $time [timestamp]
     */
    public function setTime($time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return int [timestamp]
     */
    public function getTime()
    {
        return $this->time;
    }

    public function getDateTime(): \Runalyze\Util\LocalTime
    {
        return new LocalTime($this->time);
    }

    /**
     * @param null|int $timezoneOffset [min]
     */
    public function setTimezoneOffset($timezoneOffset): self
    {
        $this->timezoneOffset = $timezoneOffset;

        return $this;
    }

    /**
     * @return null|int [min]
     */
    public function getTimezoneOffset()
    {
        return $this->timezoneOffset;
    }

    /**
     * @param null|int $created [timestamp]
     */
    public function setCreated($created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return null|int [timestamp]
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedToNow()
    {
        $this->setCreated(time());
    }

    /**
     * @param null|int $edited [timestamp]
     */
    public function setEdited($edited): self
    {
        $this->edited = $edited;

        return $this;
    }

    /**
     * @return null|int [timestamp]
     */
    public function getEdited()
    {
        return $this->edited;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setEditedToNow()
    {
        $this->setEdited(time());
    }

    /**
     * @param bool $isPublic
     */
    public function setPublic($isPublic): self
    {
        $this->isPublic = (bool) $isPublic;

        return $this;
    }

    /**
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic): self
    {
        return $this->setPublic($isPublic);
    }

    public function togglePrivacy(): self
    {
        $this->isPublic = ! $this->isPublic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->isPublic;
    }

    public function setTrack(bool $isTrack): self
    {
        $this->isTrack = $isTrack;

        return $this;
    }

    public function setIsTrack(bool $isTrack): self
    {
        return $this->setTrack($isTrack);
    }

    /**
     * @return bool
     */
    public function isTrack()
    {
        return $this->isTrack;
    }

    public function setWithGoal(bool $withGoal): self
    {
        $this->withGoal = $withGoal;
        return $this;
    }

    public function isWithGoal(): bool
    {
        return $this->withGoal;
    }

    /**
     * @param null|float $distance [km]
     */
    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return null|float [km]
     */
    public function getDistance(): ?float
    {
        return $this->distance;
    }

    /**
     * @param ?float $s [s]
     */
    public function setS(?float $s): self
    {
        $this->s = $s;

        return $this;
    }

    /**
     * @return float [s]
     */
    public function getS(): float
    {
        return $this->s;
    }

    /**
     * @param null|int $elapsedTime [s]
     */
    public function setElapsedTime(?int $elapsedTime): self
    {
        $this->elapsedTime = $elapsedTime;

        return $this;
    }

    /**
     * @return null|int [s]
     */
    public function getElapsedTime(): ?int
    {
        return $this->elapsedTime;
    }

    /**
     * @param null|int $elevation [m]
     */
    public function setElevation(?int $elevation): self
    {
        $this->elevation = $elevation ?? null;

        return $this;
    }

    /**
     * @return null|int [m]
     */
    public function getElevation(): ?int
    {
        return $this->elevation;
    }

    /**
     * @param null|float $score [0.0 .. 10.0]
     */
    public function setClimbScore(?float $score): self
    {
        $this->climbScore = $score;

        return $this;
    }

    /**
     * @return null|float [0.0 .. 10.0]
     */
    public function getClimbScore(): ?float
    {
        return $this->climbScore;
    }

    /**
     * @param null|float $percentage [0.00 .. 1.00]
     */
    public function setPercentageHilly(?float $percentage): self
    {
        $this->percentageHilly = $percentage;

        return $this;
    }

    /**
     * @return null|float [0.00 .. 1.00]
     */
    public function getPercentageHilly(): ?float
    {
        return $this->percentageHilly;
    }

    /**
     * @return null|float [0.00 .. 1.00]
     */
    public function getPercentageFlat(): ?float
    {
        return $this->percentageHilly !== null ? 1.0 - $this->percentageHilly : null;
    }

    /**
     * @param null|int $kcal [kcal]
     */
    public function setKcal(?int $kcal): self
    {
        $this->kcal = $kcal;

        return $this;
    }

    /**
     * @return null|int [kcal]
     */
    public function getKcal(): ?int
    {
        return $this->kcal;
    }

    /**
     * @return null|int [kcal]
     */
    public function getKcalInRest(): ?int
    {
        return $this->kcalInRest;
    }

    /**
     * @param null|int $kcalInRest [kcal]
     */
    public function setKcalInRest(?int $kcalInRest): self
    {
        $this->kcalInRest = $kcalInRest;

        return $this;
    }

    /**
     * @param null|int $pulseAvg [bpm]
     */
    public function setPulseAvg($pulseAvg): self
    {
        $this->pulseAvg = $pulseAvg;

        return $this;
    }

    /**
     * @return null|int [bpm]
     */
    public function getPulseAvg()
    {
        return $this->pulseAvg;
    }

    /**
     * @param null|int $pulseMax [bpm]
     */
    public function setPulseMax($pulseMax): self
    {
        $this->pulseMax = $pulseMax;

        return $this;
    }

    /**
     * @return null|int [bpm]
     */
    public function getPulseMax()
    {
        return $this->pulseMax;
    }

    /**
     * @param null|int $pulseAvgActive [bpm]
     */
    public function setPulseAvgActive($pulseAvgActive): self
    {
        $this->pulseAvgActive = $pulseAvgActive;

        return $this;
    }

    /**
     * @return null|int [bpm]
     */
    public function getPulseAvgActive()
    {
        return $this->pulseAvgActive;
    }

    /**
     * @param null|int $maxHrUser [bpm]
     */
    public function setMaxHrUser($maxHrUser): self
    {
        $this->maxHrUser = $maxHrUser;

        return $this;
    }

    /**
     * @return null|int [bpm]
     */
    public function getMaxHrUser()
    {
        return $this->maxHrUser;
    }

    /**
     * @param null|array $hrZoneBounds [bpm]
     */
    public function setHrZoneBounds($hrZoneBounds): self
    {
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

    /**
     * @param null|float $vo2max [ml/kg/min]
     */
    public function setVO2max($vo2max): self
    {
        $this->vo2max = $vo2max;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2max()
    {
        return $this->vo2max;
    }

    /**
     * @param null|float $vo2maxByTime [ml/kg/min]
     */
    public function setVO2maxByTime($vo2maxByTime): self
    {
        $this->vo2maxByTime = $vo2maxByTime;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2maxByTime()
    {
        return $this->vo2maxByTime;
    }

    /**
     * @param null|float $vo2maxWithElevation [ml/kg/min]
     */
    public function setVO2maxWithElevation($vo2maxWithElevation): self
    {
        $this->vo2maxWithElevation = $vo2maxWithElevation;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2maxWithElevation()
    {
        return $this->vo2maxWithElevation;
    }

    /**
     * @param bool $useVO2max
     */
    public function setUseVO2max($useVO2max): self
    {
        $this->useVO2max = $useVO2max;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUseVO2max()
    {
        return $this->useVO2max;
    }

    /**
     * @param null|float $fitVO2maxEstimate [ml/kg/min]
     */
    public function setFitVO2maxEstimate($fitVO2maxEstimate): self
    {
        $this->fitVO2maxEstimate = $fitVO2maxEstimate;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getFitVO2maxEstimate()
    {
        return $this->fitVO2maxEstimate;
    }

    /**
     * @param null|int $fitRecoveryTime [min]
     */
    public function setFitRecoveryTime($fitRecoveryTime): self
    {
        $this->fitRecoveryTime = $fitRecoveryTime;

        return $this;
    }

    /**
     * @return null|int [min]
     */
    public function getFitRecoveryTime()
    {
        return $this->fitRecoveryTime;
    }

    /**
     * @param null|int $fitHrvAnalysis
     */
    public function setFitHrvAnalysis($fitHrvAnalysis): self
    {
        $this->fitHrvAnalysis = $fitHrvAnalysis;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitHrvAnalysis()
    {
        return $this->fitHrvAnalysis;
    }

    /**
     * @param null|int $fitTrainingEffectBenefit
     */
    public function setFitTrainingEffectBenefit($fitTrainingEffectBenefit): self
    {
        $this->fitTrainingEffectBenefit = $fitTrainingEffectBenefit;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitTrainingEffectBenefit()
    {
        return $this->fitTrainingEffectBenefit;
    }

    /**
     * @param null|float $fitTrainingEffect
     */
    public function setFitTrainingEffect($fitTrainingEffect): self
    {
        $this->fitTrainingEffect = $fitTrainingEffect;

        return $this;
    }

    /**
     * @return null|float
     */
    public function getFitTrainingEffect()
    {
        return $this->fitTrainingEffect;
    }

    /**
     * @param null|float $fitAnaerobicTrainingEffect
     */
    public function setFitAnaerobicTrainingEffect($fitAnaerobicTrainingEffect): self
    {
        $this->fitAnaerobicTrainingEffect = $fitAnaerobicTrainingEffect;

        return $this;
    }

    /**
     * @return null|float
     */
    public function getFitAnaerobicTrainingEffect()
    {
        return $this->fitAnaerobicTrainingEffect;
    }

    /**
     * @param null|int $fitPerformanceCondition
     */
    public function setFitPerformanceCondition($fitPerformanceCondition): self
    {
        $this->fitPerformanceCondition = $fitPerformanceCondition;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitPerformanceCondition()
    {
        return $this->fitPerformanceCondition;
    }

    /**
     * @param null|int $fitPerformanceConditionEnd
     */
    public function setFitPerformanceConditionEnd($fitPerformanceConditionEnd): self
    {
        $this->fitPerformanceConditionEnd = $fitPerformanceConditionEnd;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitPerformanceConditionEnd()
    {
        return $this->fitPerformanceConditionEnd;
    }

    /**
     * @param null|int $fitLactateThresholdHR
     */
    public function setFitLactateThresholdHR($fitLactateThresholdHR): self
    {
        $this->fitLactateThresholdHR = $fitLactateThresholdHR;

        return $this;
    }

    /**
     * @return null|int [bpm]
     */
    public function getFitLactateThresholdHR()
    {
        return $this->fitLactateThresholdHR;
    }

    /**
     * @return null|float [m/s]
     */
    public function getFitLactateThresholdPace()
    {
        return $this->fitLactateThresholdPace;
    }

    /**
     * @param null|float $fitLactateThresholdPace
     */
    public function setFitLactateThresholdPace($fitLactateThresholdPace): self
    {
        $this->fitLactateThresholdPace = $fitLactateThresholdPace;

        return $this;
    }

    /**
     * @param null|int $fitTotalAscent
     */
    public function setFitTotalAscent($fitTotalAscent): self
    {
        $this->fitTotalAscent = $fitTotalAscent;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitTotalAscent()
    {
        return $this->fitTotalAscent;
    }

    /**
     * @param null|int $fitTotalDescent
     */
    public function setFitTotalDescent($fitTotalDescent): self
    {
        $this->fitTotalDescent = $fitTotalDescent;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitTotalDescent()
    {
        return $this->fitTotalDescent;
    }

    /**
     * @return null|int
     */
    public function getFitSelfEvaluationFeeling()
    {
        return $this->fitSelfEvaluationFeeling;
    }

    /**
     * @param null|int $fitSelfEvaluationFeeling
     */
    public function setFitSelfEvaluationFeeling($fitSelfEvaluationFeeling): self
    {
        $this->fitSelfEvaluationFeeling = $fitSelfEvaluationFeeling;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitSelfEvaluationPerceivedEffort()
    {
        return $this->fitSelfEvaluationPerceivedEffort;
    }

    /**
     * @param null|int $fitSelfEvaluationPerceivedEffort
     */
    public function setFitSelfEvaluationPerceivedEffort($fitSelfEvaluationPerceivedEffort): self
    {
        $this->fitSelfEvaluationPerceivedEffort = $fitSelfEvaluationPerceivedEffort;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getFitLoadPeak()
    {
        return $this->fitLoadPeak;
    }

    /**
     * @param null|int $fitLoadPeak
     */
    public function setFitLoadPeak($fitLoadPeak): self
    {
        $this->fitLoadPeak = $fitLoadPeak;

        return $this;
    }

    /**
     * @return null|int [s]
     */
    public function getFitRunTime()
    {
        return $this->fitRunTime;
    }

    /**
     * @param null|int $fitRunTime [s]
     */
    public function setFitRunTime($fitRunTime): self
    {
        $this->fitRunTime = $fitRunTime;

        return $this;
    }

    /**
     * @return null|int [s]
     */
    public function getFitWalkTime()
    {
        return $this->fitWalkTime;
    }

    /**
     * @param null|int $fitWalkTime [s]
     */
    public function setFitWalkTime($fitWalkTime): self
    {
        $this->fitWalkTime = $fitWalkTime;

        return $this;
    }

    /**
     * @return null|int [s]
     */
    public function getFitStandTime()
    {
        return $this->fitStandTime;
    }

    /**
     * @param null|int $fitStandTime [s]
     */
    public function setFitStandTime($fitStandTime): self
    {
        $this->fitStandTime = $fitStandTime;

        return $this;
    }

    /**
     * @param null|array $fitSecondsInHrZones [bpm]
     */
    public function setFitSecondsInHrZones($fitSecondsInHrZones): self
    {
        $this->fitSecondsInHrZones = $fitSecondsInHrZones;

        return $this;
    }

    /**
     * @return null|array [bpm]
     */
    public function getFitSecondsInHrZones()
    {
        return $this->fitSecondsInHrZones;
    }

    /**
     * @return null|int [ml]
     */
    public function getFitSweatLoss()
    {
        return $this->fitSweatLoss;
    }

    /**
     * @param null|int $fitSweatLoss [ml]
     */
    public function setFitSweatLoss($fitSweatLoss): self
    {
        $this->fitSweatLoss = $fitSweatLoss;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getAvgRespirationRate()
    {
        return $this->avgRespirationRate;
    }

    /**
     * @param null|int $avgRespirationRate
     */
    public function setAvgRespirationRate($avgRespirationRate): self
    {
        $this->avgRespirationRate = $avgRespirationRate;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getMaxRespirationRate()
    {
        return $this->maxRespirationRate;
    }

    /**
     * @param null|int $maxRespirationRate
     */
    public function setMaxRespirationRate($maxRespirationRate): self
    {
        $this->maxRespirationRate = $maxRespirationRate;

        return $this;
    }

    /**
     * @param null|int $rpe
     */
    public function setRpe($rpe): self
    {
        $this->rpe = $rpe;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getRpe()
    {
        return $this->rpe;
    }

    /**
     * @param null|int $trimp
     */
    public function setTrimp($trimp): self
    {
        $this->trimp = $trimp;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getTrimp()
    {
        return $this->trimp;
    }

    /**
     * @param null|int $cadence [rpm]
     */
    public function setCadence($cadence): self
    {
        $this->cadence = $cadence;

        return $this;
    }

    /**
     * @return null|int [rpm]
     */
    public function getCadence()
    {
        return $this->cadence;
    }

    /**
     * @param null|int $power [W]
     */
    public function setPower($power): self
    {
        $this->power = $power;

        return $this;
    }

    /**
     * @return null|int [W]
     */
    public function getPower()
    {
        return $this->power;
    }

    /**
     * @param bool|null $flag
     */
    public function setPowerCalculated($flag): self
    {
        $this->isPowerCalculated = $flag === null ? null : (bool) $flag;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isPowerCalculated()
    {
        return $this->isPowerCalculated;
    }

    /**
     * @param null|int $totalStrokes
     */
    public function setTotalStrokes($totalStrokes): self
    {
        $this->totalStrokes = $totalStrokes;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getTotalStrokes()
    {
        return $this->totalStrokes;
    }

    /**
     * @param null|int $swolf
     */
    public function setSwolf($swolf): self
    {
        $this->swolf = $swolf;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getSwolf()
    {
        return $this->swolf;
    }

    /**
     * @param null|int $strideLength [cm]
     */
    public function setStrideLength(?int $strideLength): self
    {
        $this->strideLength = $strideLength;

        return $this;
    }

    /**
     * @return null|int [cm]
     */
    public function getStrideLength(): ?int
    {
        return $this->strideLength;
    }

    /**
     * @param null|int $totalCycles [stck]
     */
    public function setTotalCycles($totalCycles): self
    {
        $this->totalCycles = $totalCycles;

        return $this;
    }

    /**
     * @return null|int [stck]
     */
    public function getTotalCycles()
    {
        return $this->totalCycles;
    }

    /**
     * @param null|int $groundcontact [ms]
     */
    public function setGroundcontact($groundcontact): self
    {
        $this->groundcontact = $groundcontact;

        return $this;
    }

    /**
     * @return null|int [ms]
     */
    public function getGroundcontact()
    {
        return $this->groundcontact;
    }

    /**
     * @param null|int $groundcontactBalance [%ooL]
     */
    public function setGroundcontactBalance($groundcontactBalance): self
    {
        $this->groundcontactBalance = $groundcontactBalance;

        return $this;
    }

    /**
     * @return null|int [%ooL]
     */
    public function getGroundcontactBalance()
    {
        return $this->groundcontactBalance;
    }

    /**
     * @param null|int $verticalOscillation [mm]
     */
    public function setVerticalOscillation($verticalOscillation): self
    {
        $this->verticalOscillation = $verticalOscillation;

        return $this;
    }

    /**
     * @return null|int [mm]
     */
    public function getVerticalOscillation()
    {
        return $this->verticalOscillation;
    }

    /**
     * @param null|int $verticalRatio [%]
     */
    public function setVerticalRatio($verticalRatio): self
    {
        $this->verticalRatio = $verticalRatio;

        return $this;
    }

    /**
     * @return null|int [%]
     */
    public function getVerticalRatio()
    {
        return $this->verticalRatio;
    }

    /**
     * @param null|float $avgImpactGsLeft [G]
     */
    public function setAvgImpactGsLeft($avgImpactGsLeft): self
    {
        $this->avgImpactGsLeft = $avgImpactGsLeft;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgImpactGsLeft()
    {
        return $this->avgImpactGsLeft;
    }

    /**
     * @param null|float $avgImpactGsRight [G]
     */
    public function setAvgImpactGsRight($avgImpactGsRight): self
    {
        $this->avgImpactGsRight = $avgImpactGsRight;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgImpactGsRight()
    {
        return $this->avgImpactGsRight;
    }

    /**
     * @param null|float $avgBrakingGsLeft [G]
     */
    public function setAvgBrakingGsLeft($avgBrakingGsLeft): self
    {
        $this->avgBrakingGsLeft = $avgBrakingGsLeft;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgBrakingGsLeft()
    {
        return $this->avgBrakingGsLeft;
    }

    /**
     * @param null|float $avgBrakingGsRight [G]
     */
    public function setAvgBrakingGsRight($avgBrakingGsRight): self
    {
        $this->avgBrakingGsRight = $avgBrakingGsRight;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgBrakingGsRight()
    {
        return $this->avgBrakingGsRight;
    }

    /**
     * @param null|int $avgFootstrikeTypeLeft [°]
     */
    public function setAvgFootstrikeTypeLeft($avgFootstrikeTypeLeft): self
    {
        $this->avgFootstrikeTypeLeft = $avgFootstrikeTypeLeft;

        return $this;
    }

    /**
     * @return null|int [°]
     */
    public function getAvgFootstrikeTypeLeft()
    {
        return $this->avgFootstrikeTypeLeft;
    }

    /**
     * @param null|int $avgFootstrikeTypeRight [°]
     */
    public function setAvgFootstrikeTypeRight($avgFootstrikeTypeRight): self
    {
        $this->avgFootstrikeTypeRight = $avgFootstrikeTypeRight;

        return $this;
    }

    /**
     * @return null|int [°]
     */
    public function getAvgFootstrikeTypeRight()
    {
        return $this->avgFootstrikeTypeRight;
    }

    /**
     * @param null|float $avgPronationExcursionLeft [°]
     */
    public function setAvgPronationExcursionLeft($avgPronationExcursionLeft): self
    {
        $this->avgPronationExcursionLeft = $avgPronationExcursionLeft;

        return $this;
    }

    /**
     * @return null|float [°]
     */
    public function getAvgPronationExcursionLeft()
    {
        return $this->avgPronationExcursionLeft;
    }

    /**
     * @param null|float $avgPronationExcursionRight [°]
     */
    public function setAvgPronationExcursionRight($avgPronationExcursionRight): self
    {
        $this->avgPronationExcursionRight = $avgPronationExcursionRight;

        return $this;
    }

    /**
     * @return null|float [°]
     */
    public function getAvgPronationExcursionRight()
    {
        return $this->avgPronationExcursionRight;
    }

    /**
     * @param null|int $temperature [°C]
     */
    public function setTemperature($temperature): self
    {
        $this->temperature = $temperature === null ? null : (int) $temperature;

        return $this;
    }

    /**
     * @return null|int [°C]
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * @param null|int $windSpeed [km/h]
     */
    public function setWindSpeed($windSpeed): self
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    /**
     * @return null|int [km/h]
     */
    public function getWindSpeed()
    {
        return $this->windSpeed;
    }

    /**
     * @param null|int $windDeg [°]
     */
    public function setWindDeg($windDeg): self
    {
        $this->windDeg = $windDeg;

        return $this;
    }

    /**
     * @return null|int [°]
     */
    public function getWindDeg()
    {
        return $this->windDeg;
    }

    /**
     * @param null|int $humidity [%]
     */
    public function setHumidity($humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    /**
     * @return null|int [%]
     */
    public function getHumidity()
    {
        return $this->humidity;
    }

    /**
     * @param null|int $pressure [hPa]
     */
    public function setPressure($pressure): self
    {
        $this->pressure = $pressure;

        return $this;
    }

    /**
     * @return null|int [hPa]
     */
    public function getPressure()
    {
        return $this->pressure;
    }

    /**
     * @param null|bool $isNight
     */
    public function setNight($isNight): self
    {
        $this->isNight = $isNight;

        return $this;
    }

    /**
     * @return null|bool
     */
    public function isNight()
    {
        return $this->isNight;
    }

    /**
     * @param int $weatherid enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function setWeatherid($weatherid): self
    {
        $this->weatherid = $weatherid;

        return $this;
    }

    /**
     * @return int enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function getWeatherid()
    {
        return $this->weatherid;
    }

    /**
     * @param null|int $weatherSource
     */
    public function setWeatherSource($weatherSource): self
    {
        $this->weatherSource = $weatherSource === null ? null : (int) $weatherSource;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getWeatherSource()
    {
        return $this->weatherSource;
    }

    /**
     * @param null|string $routeName
     */
    public function setRouteName($routeName): self
    {
        $this->routeName = $routeName;

        if ($this->hasRoute()) {
            $this->getRoute()
                ->setName($routeName);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    public function setRoute(?Route $route = null): self
    {
        $this->route = $route;

        if ($route instanceof \Runalyze\Bundle\CoreBundle\Entity\Route && $this->account !== null) {
            $route->setAccount($this->account);
        }

        return $this;
    }

    /**
     * @return null|Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    public function hasRoute(): bool
    {
        return $this->route !== null;
    }

    public function setSplits(RoundCollection $splits): self
    {
        $this->splits = $splits;

        return $this;
    }

    public function setSplitsToClone()
    {
        $this->splits = clone $this->splits;
    }

    /**
     * @return RoundCollection
     */
    public function getSplits()
    {
        return $this->splits;
    }

    /**
     * @param null|array $splitsAdditional
     */
    public function setSplitsAdditional($splitsAdditional): self
    {
        $this->splitsAdditional = $splitsAdditional;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getSplitsAdditional()
    {
        return $this->splitsAdditional;
    }

    /**
     * @param null|string $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null|string $partner
     */
    public function setPartner($partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * @param null|array $paceGoal
     */
    public function setPaceGoal($paceGoal): self
    {
        $this->paceGoal = $paceGoal;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getPaceGoal()
    {
        return $this->paceGoal;
    }

    /**
     * @param null|string $notes
     */
    public function setNotes($notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getNotes()
    {
        return $this->notes;
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
     * @param string $creator
     */
    public function setCreator($creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param null|string $creatorDetails
     */
    public function setCreatorDetails($creatorDetails): self
    {
        $this->creatorDetails = $creatorDetails;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCreatorDetails()
    {
        return $this->creatorDetails;
    }

    /**
     * @param null|int $activityId
     */
    public function setActivityId($activityId): self
    {
        $this->activityId = $activityId === null ? null : (int) $activityId;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    public function setLock(bool $lock): self
    {
        $this->lock = $lock;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLock()
    {
        return $this->lock;
    }

    public function addEquipment(Equipment $equipment): self
    {
        $this->equipment[] = $equipment;

        return $this;
    }

    public function removeEquipment(Equipment $equipment)
    {
        $this->equipment->removeElement($equipment);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquipment()
    {
        return $this->equipment;
    }

    public function addTag(Tag $tag): self
    {
        $this->tag[] = $tag;

        return $this;
    }

    public function removeTag(Tag $tag)
    {
        $this->tag->removeElement($tag);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTag()
    {
        return $this->tag;
    }

    public function setTrackdata(?Trackdata $trackData = null)
    {
        $this->trackdata = $trackData;

        if ($trackData instanceof \Runalyze\Bundle\CoreBundle\Entity\Trackdata) {
            $trackData->setActivity($this);

            if ($this->account !== null) {
                $trackData->setAccount($this->account);
            }
        }
    }

    /**
     * @return Trackdata|null
     */
    public function getTrackdata()
    {
        return $this->trackdata;
    }

    public function hasTrackdata(): bool
    {
        return $this->trackdata !== null;
    }

    public function setSwimdata(?Swimdata $swimData = null)
    {
        $this->swimdata = $swimData;

        if ($swimData instanceof \Runalyze\Bundle\CoreBundle\Entity\Swimdata) {
            $swimData->setActivity($this);

            if ($this->account !== null) {
                $swimData->setAccount($this->account);
            }
        }
    }

    /**
     * @return Swimdata|null
     */
    public function getSwimdata()
    {
        return $this->swimdata;
    }

    public function hasSwimdata(): bool
    {
        return $this->swimdata !== null;
    }

    public function setHrv(?Hrv $hrv = null)
    {
        $this->hrv = $hrv;

        if ($hrv instanceof \Runalyze\Bundle\CoreBundle\Entity\Hrv) {
            $hrv->setActivity($this);

            if ($this->account !== null) {
                $hrv->setAccount($this->account);
            }
        }
    }

    /**
     * @return Hrv|null
     */
    public function getHrv()
    {
        return $this->hrv;
    }

    public function hasHrv(): bool
    {
        return $this->hrv !== null;
    }

    public function setRaceresult(?Raceresult $raceResult = null)
    {
        $this->raceresult = $raceResult;

        if ($raceResult instanceof \Runalyze\Bundle\CoreBundle\Entity\Raceresult) {
            $raceResult->setActivity($this);

            if ($this->account !== null) {
                $raceResult->setAccount($this->account);
            }
        }
    }

    /**
     * @return Raceresult|null
     */
    public function getRaceresult()
    {
        return $this->raceresult;
    }

    public function hasRaceresult(): bool
    {
        return $this->raceresult !== null;
    }

    /**
     * @return ActivityAdapter
     */
    public function getAdapter()
    {
        if ($this->Adapter === null) {
            $this->Adapter = new ActivityAdapter($this);
        }

        return $this->Adapter;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateSimpleCalculatedValues()
    {
        if ($this->distance == 0.0) {
            $this->distance = null;
        }

        $this->getAdapter()
            ->updateSimpleCalculatedValues();
    }

    /**
     * @ORM\PrePersist()
     */
    public function useElevationFromRouteIfEmpty()
    {
        if ($this->elevation === null || $this->elevation === 0) {
            $this->getAdapter()
                ->useElevationFromRoute();
        }
    }
}

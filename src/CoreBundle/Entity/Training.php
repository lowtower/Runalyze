<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\ActivityAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\EntityListener\ActivityListener;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Util\LocalTime;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'training')]
#[ORM\Index(columns: ['accountid', 'time'], name: 'time')]
#[ORM\Index(columns: ['accountid', 'sportid'], name: 'sportid')]
#[ORM\Index(columns: ['accountid', 'typeid'], name: 'typeid')]
#[ORM\Entity(repositoryClass: TrainingRepository::class)]
#[ORM\EntityListeners([ActivityListener::class])]
#[ORM\HasLifecycleCallbacks]
class Training implements IdentifiableEntityInterface, AccountRelatedEntityInterface
{
    #[ORM\Column(name: 'id', type: 'integer', precision: 10, unique: true, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @Assert\NotBlank(message="You need to choose a sport.")
     */
    #[ORM\JoinColumn(name: 'sportid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'trainings')]
    private Sport $sport;

    #[ORM\JoinColumn(name: 'typeid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Type::class, inversedBy: 'trainings')]
    private ?Type $type = null;

    /**
     * @var int [timestamp]
     * @Assert\NotBlank(message="Every activity needs a time")
     */
    #[ORM\Column(name: 'time', type: 'integer', nullable: false)]
    private int $time;

    /**
     * @var ?int [min]
     */
    #[ORM\Column(name: 'timezone_offset', type: 'smallint', nullable: true)]
    private ?int $timezoneOffset = null;

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'created', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $created = null;

    /**
     * @var ?int [timestamp]
     */
    #[ORM\Column(name: 'edited', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $edited = null;

    #[ORM\Column(name: 'is_public', type: 'boolean')]
    private bool $isPublic = false;

    #[ORM\Column(name: 'is_track', type: 'boolean')]
    private bool $isTrack = false;

    #[ORM\Column(name: 'with_goal', type: 'boolean')]
    private bool $withGoal = false;

    /**
     * @var ?float [km]
     */
    #[ORM\Column(name: 'distance', type: 'casted_decimal_2', precision: 6, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $distance = null;

    /**
     * @var ?float [s]
     *
     * @Assert\GreaterThan(0)
     */
    #[ORM\Column(name: 's', type: 'casted_decimal_2', precision: 8, scale: 2, options: [
        'unsigned' => true,
    ])]
    private ?float $s = 0.0;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'elapsed_time', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $elapsedTime = null;

    /**
     * @var ?int [m]
     */
    #[ORM\Column(name: 'elevation', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $elevation = null;

    /**
     * @var ?float [0.0 .. 10.0]
     */
    #[ORM\Column(name: 'climb_score', type: 'casted_decimal_1', precision: 3, scale: 1, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $climbScore = null;

    /**
     * @var ?float [0.00 .. 1.00]
     */
    #[ORM\Column(name: 'percentage_hilly', type: 'casted_decimal_2', precision: 3, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $percentageHilly = null;

    /**
     * @var ?int [kcal]
     */
    #[ORM\Column(name: 'kcal', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $kcal = null;

    /**
     * @var ?int [kcal]
     */
    #[ORM\Column(name: 'kcal_rest', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $kcalInRest = null;

    /**
     * @var ?int [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     notInRangeMessage="Your average heartrate must be between {{ min }} and {{ max }} bpm"
     * )
     */
    #[ORM\Column(name: 'pulse_avg', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pulseAvg = null;

    /**
     * @var ?int [bpm]
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     notInRangeMessage="Your maximum heartrate must be between {{ min }} and {{ max }} bpm"
     * )
     */
    #[ORM\Column(name: 'pulse_max', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pulseMax = null;

    /**
     * @var ?int [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     notInRangeMessage="Your average heartrate active rounds must be between {{ min }} and {{ max }} bpm"
     * )
     */
    #[ORM\Column(name: 'pulse_avg_active', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pulseAvgActive = null;

    /**
     * @var ?int [bpm]
     *
     * @Assert\Range(
     *     min=30,
     *     max=255,
     *     notInRangeMessage="Your maximum heart rate must be between {{ min }} and {{ max }} bpm"
     * )
     */
    #[ORM\Column(name: 'max_hr_user', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $maxHrUser = null;

    /**
     * @var ?array [bpm]
     */
    #[ORM\Column(name: 'hr_zone_bounds', type: 'pipe_array', nullable: true)]
    private ?array $hrZoneBounds = null;

    /**
     * @var ?float [ml/kg/min]
     */
    #[ORM\Column(name: 'vo2max', type: 'casted_decimal_2', precision: 5, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $vo2max = null;

    /**
     * @var ?float [ml/kg/min]
     */
    #[ORM\Column(name: 'vo2max_by_time', type: 'casted_decimal_2', precision: 5, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $vo2maxByTime = null;

    /**
     * @var ?float [ml/kg/min]
     */
    #[ORM\Column(name: 'vo2max_with_elevation', type: 'casted_decimal_2', precision: 5, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $vo2maxWithElevation = null;

    #[ORM\Column(name: 'use_vo2max', type: 'boolean')]
    private bool $useVO2max = true;

    /**
     * @var ?float [ml/kg/min]
     */
    #[ORM\Column(name: 'fit_vo2max_estimate', type: 'casted_decimal_2', precision: 4, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $fitVO2maxEstimate = null;

    /**
     * @var ?int [min]
     */
    #[ORM\Column(name: 'fit_recovery_time', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitRecoveryTime = null;

    #[ORM\Column(name: 'fit_hrv_analysis', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitHrvAnalysis = null;

    /**
     * @var ?int [1 .. 7]
     */
    #[ORM\Column(name: 'fit_training_effect_benefit', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitTrainingEffectBenefit = null;

    /**
     * @var ?float [0.0 .. 5.0]
     */
    #[ORM\Column(name: 'fit_training_effect', type: 'casted_decimal_1', precision: 2, scale: 1, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $fitTrainingEffect = null;

    /**
     * @var ?float [0.0 .. 5.0]
     *
     * #TSC: new DB column to training table
     */
    #[ORM\Column(name: 'fit_anaerobic_training_effect', type: 'casted_decimal_1', precision: 2, scale: 1, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $fitAnaerobicTrainingEffect = null;

    #[ORM\Column(name: 'fit_performance_condition', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitPerformanceCondition = null;

    #[ORM\Column(name: 'fit_performance_condition_end', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitPerformanceConditionEnd = null;

    #[ORM\Column(name: 'fit_lactate_threshold_hr', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitLactateThresholdHR = null;

    /**
     * @var ?float [m/s]
     */
    #[ORM\Column(name: 'fit_lactate_threshold_pace_ms', type: 'casted_decimal_2', precision: 4, scale: 2, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?float $fitLactateThresholdPace = null;

    /**
     * @var ?int [m]
     */
    #[ORM\Column(name: 'fit_total_ascent', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitTotalAscent = null;

    /**
     * @var ?int [m]
     */
    #[ORM\Column(name: 'fit_total_descent', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitTotalDescent = null;

    /**
     * @var ?int [-]
     */
    #[ORM\Column(name: 'fit_self_evaluation_feeling', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitSelfEvaluationFeeling = null;

    /**
     * @var ?int [-]
     */
    #[ORM\Column(name: 'fit_self_evaluation_perceived_effort', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitSelfEvaluationPerceivedEffort = null;

    /**
     * @var ?int [-]
     */
    #[ORM\Column(name: 'fit_load_peak', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitLoadPeak = null;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'fit_run_time', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitRunTime = null;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'fit_walk_time', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitWalkTime = null;

    /**
     * @var ?int [s]
     */
    #[ORM\Column(name: 'fit_stand_time', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitStandTime = null;

    /**
     * @var ?array [s]
     */
    #[ORM\Column(name: 'fit_seconds_hr_zones', type: 'pipe_array', nullable: true)]
    private ?array $fitSecondsInHrZones = null;

    /**
     * @var ?int [ml]
     */
    #[ORM\Column(name: 'fit_sweat_loss', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $fitSweatLoss = null;

    /**
     * @var ?int [-]
     */
    #[ORM\Column(name: 'avg_respiration_rate', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $avgRespirationRate = null;

    /**
     * @var ?int [-]
     */
    #[ORM\Column(name: 'max_respiration_rate', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $maxRespirationRate = null;

    /**
     * @var ?int [6 .. 20]
     */
    #[ORM\Column(name: 'rpe', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $rpe = null;

    #[ORM\Column(name: 'trimp', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $trimp = null;

    /**
     * @var ?int [rpm]
     */
    #[ORM\Column(name: 'cadence', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $cadence = null;

    /**
     * @var ?int [W]
     */
    #[ORM\Column(name: 'power', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $power = null;

    #[ORM\Column(name: 'is_power_calculated', type: 'boolean', nullable: true, options: [
        'default' => null,
    ])]
    private ?bool $isPowerCalculated = null;

    #[ORM\Column(name: 'total_strokes', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $totalStrokes = null;

    #[ORM\Column(name: 'swolf', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $swolf = null;

    /**
     * @var ?int [cm]
     */
    #[ORM\Column(name: 'stride_length', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $strideLength = null;

    #[ORM\Column(name: 'total_cycles', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $totalCycles = null;

    /**
     * @var ?int [ms]
     */
    #[ORM\Column(name: 'groundcontact', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $groundcontact = null;

    /**
     * @var ?int [%ooL]
     */
    #[ORM\Column(name: 'groundcontact_balance', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $groundcontactBalance = null;

    /**
     * @var ?int [mm]
     */
    #[ORM\Column(name: 'vertical_oscillation', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $verticalOscillation = null;

    /**
     * @var ?int [%]
     */
    #[ORM\Column(name: 'vertical_ratio', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $verticalRatio = null;

    /**
     * @var ?float [G]
     */
    #[ORM\Column(name: 'avg_impact_gs_left', type: 'float', nullable: true, options: [
        'unsigned' => true,
    ], columnDefinition: 'FLOAT')]
    private ?float $avgImpactGsLeft = null;

    /**
     * @var ?float [G]
     */
    #[ORM\Column(name: 'avg_impact_gs_right', type: 'float', nullable: true, options: [
        'unsigned' => true,
    ], columnDefinition: 'FLOAT')]
    private ?float $avgImpactGsRight = null;

    /**
     * @var ?float [G]
     */
    #[ORM\Column(name: 'avg_braking_gs_left', type: 'float', nullable: true, options: [
        'unsigned' => true,
    ], columnDefinition: 'FLOAT')]
    private ?float $avgBrakingGsLeft = null;

    /**
     * @var ?float [G]
     */
    #[ORM\Column(name: 'avg_braking_gs_right', type: 'float', nullable: true, options: [
        'unsigned' => true,
    ], columnDefinition: 'FLOAT')]
    private ?float $avgBrakingGsRight = null;

    /**
     * @var ?int [°]
     */
    #[ORM\Column(name: 'avg_footstrike_type_left', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $avgFootstrikeTypeLeft = null;

    /**
     * @var ?int [°]
     */
    #[ORM\Column(name: 'avg_footstrike_type_right', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $avgFootstrikeTypeRight = null;

    /**
     * @var ?float [°]
     */
    #[ORM\Column(name: 'avg_pronation_excursion_left', type: 'float', nullable: true, columnDefinition: 'FLOAT')]
    private ?float $avgPronationExcursionLeft = null;

    /**
     * @var ?float [°]
     */
    #[ORM\Column(name: 'avg_pronation_excursion_right', type: 'float', nullable: true, columnDefinition: 'FLOAT')]
    private ?float $avgPronationExcursionRight = null;

    /**
     * @var ?int [°C]
     */
    #[ORM\Column(name: 'temperature', type: 'tinyint', nullable: true)]
    private ?int $temperature = null;

    /**
     * @var ?int [km/h]
     */
    #[ORM\Column(name: 'wind_speed', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $windSpeed = null;

    /**
     * @var ?int [°]
     * @Assert\Range(
     *     min=0,
     *     max=359,
     *     notInRangeMessage="The wind direction must be between {{ min }} and {{ max }} degrees"
     * )
     */
    #[ORM\Column(name: 'wind_deg', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $windDeg = null;

    /**
     * @var ?int [%]
     * @Assert\Range(
     *     min=0,
     *     max=100,
     *     notInRangeMessage="The humidity must be between {{ min }} and {{ max }}%. Even if it feels higher than this."
     * )
     */
    #[ORM\Column(name: 'humidity', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $humidity = null;

    /**
     * @var ?int [hPa]
     */
    #[ORM\Column(name: 'pressure', type: 'smallint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pressure = null;

    #[ORM\Column(name: 'is_night', type: 'boolean', nullable: true)]
    private ?bool $isNight = null;

    /**
     * @var int enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    #[ORM\Column(name: 'weatherid', type: 'smallint', nullable: false, options: [
        'unsigned' => true,
        'default' => 1,
    ])]
    private int $weatherid = 1;

    #[ORM\Column(name: 'weather_source', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $weatherSource = null;

    #[ORM\Column(name: 'route', type: 'text', length: 65535, nullable: true)]
    private ?string $routeName = null;

    #[ORM\JoinColumn(name: 'routeid', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: Route::class)]
    private ?Route $route = null;

    #[ORM\Column(name: 'splits', type: 'runalyze_round_array', length: 16777215, nullable: true)]
    private ?RoundCollection $splits;

    #[ORM\Column(name: 'splits_additional', type: 'json', length: 2147483647, nullable: true)]
    private ?array $splitsAdditional = null;

    #[ORM\Column(name: 'title', type: 'text', length: 65535, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'partner', type: 'text', length: 65535, nullable: true)]
    private ?string $partner = null;

    #[ORM\Column(name: 'pace_goal', type: 'json', length: 2147483647, nullable: true)]
    private ?array $paceGoal = null;

    #[ORM\Column(name: 'notes', type: 'text', length: 65535, nullable: true)]
    private ?string $notes = null;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    #[ORM\Column(name: 'creator', type: 'string', length: 100, nullable: false, options: [
        'default' => '',
    ])]
    private string $creator = '';

    #[ORM\Column(name: 'creator_details', type: 'text', length: 255, nullable: true)]
    private ?string $creatorDetails = null;

    #[ORM\Column(name: 'activity_id', type: 'integer', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $activityId = null;

    #[ORM\Column(name: '`lock`', type: 'boolean')]
    private bool $lock = false;

    /**
     * @var Collection<int, Equipment>
     */
    #[ORM\JoinTable(name: 'activity_equipment')]
    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'equipmentid', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Equipment::class, inversedBy: 'activity')]
    private Collection $equipment;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\JoinTable(name: 'activity_tag')]
    #[ORM\JoinColumn(name: 'activityid', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tagid', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'activity')]
    private Collection $tag;

    #[ORM\OneToOne(mappedBy: 'activity', targetEntity: Trackdata::class)]
    private ?Trackdata $trackdata = null;

    #[ORM\OneToOne(mappedBy: 'activity', targetEntity: Swimdata::class)]
    private ?Swimdata $swimdata = null;

    #[ORM\OneToOne(mappedBy: 'activity', targetEntity: Hrv::class)]
    private ?Hrv $hrv = null;

    #[ORM\OneToOne(mappedBy: 'activity', targetEntity: Raceresult::class)]
    private ?Raceresult $raceresult = null;

    private ?ActivityAdapter $Adapter = null;

    public function __construct()
    {
        $this->equipment = new ArrayCollection();
        $this->tag = new ArrayCollection();
        $this->splits = new RoundCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function hasSport(): bool
    {
        return isset($this->sport);
    }

    public function setSport(Sport $sport): self
    {
        $this->sport = $sport;

        return $this;
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }

    public function setType(?Type $type = null): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @param int $time [timestamp]
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return int [timestamp]
     */
    public function getTime(): int
    {
        return $this->time;
    }

    public function getDateTime(): LocalTime
    {
        return new LocalTime($this->time);
    }

    /**
     * @param ?int $timezoneOffset [min]
     */
    public function setTimezoneOffset(?int $timezoneOffset): self
    {
        $this->timezoneOffset = $timezoneOffset;

        return $this;
    }

    /**
     * @return ?int [min]
     */
    public function getTimezoneOffset(): ?int
    {
        return $this->timezoneOffset;
    }

    /**
     * @param ?int $created [timestamp]
     */
    public function setCreated(?int $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return ?int [timestamp]
     */
    public function getCreated(): ?int
    {
        return $this->created;
    }

    #[ORM\PrePersist]
    public function setCreatedToNow(): void
    {
        $this->setCreated(time());
    }

    /**
     * @param ?int $edited [timestamp]
     */
    public function setEdited(?int $edited): self
    {
        $this->edited = $edited;

        return $this;
    }

    /**
     * @return ?int [timestamp]
     */
    public function getEdited(): ?int
    {
        return $this->edited;
    }

    #[ORM\PreUpdate]
    public function setEditedToNow(): void
    {
        $this->setEdited(time());
    }

    public function setPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function setIsPublic(bool $isPublic): self
    {
        return $this->setPublic($isPublic);
    }

    public function togglePrivacy(): self
    {
        $this->isPublic = ! $this->isPublic;

        return $this;
    }

    public function isPublic(): bool
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

    public function isTrack(): bool
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
     * @return ?float [s]
     */
    public function getS(): ?float
    {
        return $this->s;
    }

    /**
     * @param null|float $elapsedTime [s]
     */
    public function setElapsedTime(?float $elapsedTime): self
    {
        $this->elapsedTime = $elapsedTime !== null ? (int) $elapsedTime : null;

        return $this;
    }

    /**
     * @return ?int [s]
     */
    public function getElapsedTime(): ?int
    {
        return $this->elapsedTime;
    }

    /**
     * @param ?int $elevation [m]
     */
    public function setElevation(?int $elevation): self
    {
        $this->elevation = $elevation ?? null;

        return $this;
    }

    /**
     * @return ?int [m]
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
     * @param ?int $kcal [kcal]
     */
    public function setKcal(?int $kcal): self
    {
        $this->kcal = $kcal;

        return $this;
    }

    /**
     * @return ?int [kcal]
     */
    public function getKcal(): ?int
    {
        return $this->kcal;
    }

    /**
     * @return ?int [kcal]
     */
    public function getKcalInRest(): ?int
    {
        return $this->kcalInRest;
    }

    /**
     * @param ?int $kcalInRest [kcal]
     */
    public function setKcalInRest(?int $kcalInRest): self
    {
        $this->kcalInRest = $kcalInRest;

        return $this;
    }

    /**
     * @param ?int $pulseAvg [bpm]
     */
    public function setPulseAvg(?int $pulseAvg): self
    {
        $this->pulseAvg = $pulseAvg;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getPulseAvg(): ?int
    {
        return $this->pulseAvg;
    }

    /**
     * @param ?int $pulseMax [bpm]
     */
    public function setPulseMax(?int $pulseMax): self
    {
        $this->pulseMax = $pulseMax;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getPulseMax(): ?int
    {
        return $this->pulseMax;
    }

    /**
     * @param ?int $pulseAvgActive [bpm]
     */
    public function setPulseAvgActive(?int $pulseAvgActive): self
    {
        $this->pulseAvgActive = $pulseAvgActive;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getPulseAvgActive(): ?int
    {
        return $this->pulseAvgActive;
    }

    /**
     * @param ?int $maxHrUser [bpm]
     */
    public function setMaxHrUser(?int $maxHrUser): self
    {
        $this->maxHrUser = $maxHrUser;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getMaxHrUser(): ?int
    {
        return $this->maxHrUser;
    }

    /**
     * @param null|array $hrZoneBounds [bpm]
     */
    public function setHrZoneBounds(?array $hrZoneBounds): self
    {
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

    /**
     * @param null|float $vo2max [ml/kg/min]
     */
    public function setVO2max(?float $vo2max): self
    {
        $this->vo2max = $vo2max;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2max(): ?float
    {
        return $this->vo2max;
    }

    /**
     * @param null|float $vo2maxByTime [ml/kg/min]
     */
    public function setVO2maxByTime(?float $vo2maxByTime): self
    {
        $this->vo2maxByTime = $vo2maxByTime;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2maxByTime(): ?float
    {
        return $this->vo2maxByTime;
    }

    /**
     * @param null|float $vo2maxWithElevation [ml/kg/min]
     */
    public function setVO2maxWithElevation(?float $vo2maxWithElevation): self
    {
        $this->vo2maxWithElevation = $vo2maxWithElevation;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getVO2maxWithElevation(): ?float
    {
        return $this->vo2maxWithElevation;
    }

    public function setUseVO2max(bool $useVO2max): self
    {
        $this->useVO2max = $useVO2max;

        return $this;
    }

    public function getUseVO2max(): bool
    {
        return $this->useVO2max;
    }

    /**
     * @param null|float $fitVO2maxEstimate [ml/kg/min]
     */
    public function setFitVO2maxEstimate(?float $fitVO2maxEstimate): self
    {
        $this->fitVO2maxEstimate = $fitVO2maxEstimate;

        return $this;
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function getFitVO2maxEstimate(): ?float
    {
        return $this->fitVO2maxEstimate;
    }

    /**
     * @param ?int $fitRecoveryTime [min]
     */
    public function setFitRecoveryTime(?int $fitRecoveryTime): self
    {
        $this->fitRecoveryTime = $fitRecoveryTime;

        return $this;
    }

    /**
     * @return ?int [min]
     */
    public function getFitRecoveryTime(): ?int
    {
        return $this->fitRecoveryTime;
    }

    public function setFitHrvAnalysis(?int $fitHrvAnalysis): self
    {
        $this->fitHrvAnalysis = $fitHrvAnalysis;

        return $this;
    }

    public function getFitHrvAnalysis(): ?int
    {
        return $this->fitHrvAnalysis;
    }

    public function setFitTrainingEffectBenefit(?int $fitTrainingEffectBenefit): self
    {
        $this->fitTrainingEffectBenefit = $fitTrainingEffectBenefit;

        return $this;
    }

    public function getFitTrainingEffectBenefit(): ?int
    {
        return $this->fitTrainingEffectBenefit;
    }

    public function setFitTrainingEffect(?float $fitTrainingEffect): self
    {
        $this->fitTrainingEffect = $fitTrainingEffect;

        return $this;
    }

    public function getFitTrainingEffect(): ?float
    {
        return $this->fitTrainingEffect;
    }

    public function setFitAnaerobicTrainingEffect(?float $fitAnaerobicTrainingEffect): self
    {
        $this->fitAnaerobicTrainingEffect = $fitAnaerobicTrainingEffect;

        return $this;
    }

    public function getFitAnaerobicTrainingEffect(): ?float
    {
        return $this->fitAnaerobicTrainingEffect;
    }

    public function setFitPerformanceCondition(?int $fitPerformanceCondition): self
    {
        $this->fitPerformanceCondition = $fitPerformanceCondition;

        return $this;
    }

    public function getFitPerformanceCondition(): ?int
    {
        return $this->fitPerformanceCondition;
    }

    public function setFitPerformanceConditionEnd(?int $fitPerformanceConditionEnd): self
    {
        $this->fitPerformanceConditionEnd = $fitPerformanceConditionEnd;

        return $this;
    }

    public function getFitPerformanceConditionEnd(): ?int
    {
        return $this->fitPerformanceConditionEnd;
    }

    public function setFitLactateThresholdHR(?int $fitLactateThresholdHR): self
    {
        $this->fitLactateThresholdHR = $fitLactateThresholdHR;

        return $this;
    }

    /**
     * @return ?int [bpm]
     */
    public function getFitLactateThresholdHR(): ?int
    {
        return $this->fitLactateThresholdHR;
    }

    /**
     * @return null|float [m/s]
     */
    public function getFitLactateThresholdPace(): ?float
    {
        return $this->fitLactateThresholdPace;
    }

    public function setFitLactateThresholdPace(?float $fitLactateThresholdPace): self
    {
        $this->fitLactateThresholdPace = $fitLactateThresholdPace;

        return $this;
    }

    public function setFitTotalAscent(?int $fitTotalAscent): self
    {
        $this->fitTotalAscent = $fitTotalAscent;

        return $this;
    }

    public function getFitTotalAscent(): ?int
    {
        return $this->fitTotalAscent;
    }

    public function setFitTotalDescent(?int $fitTotalDescent): self
    {
        $this->fitTotalDescent = $fitTotalDescent;

        return $this;
    }

    public function getFitTotalDescent(): ?int
    {
        return $this->fitTotalDescent;
    }

    public function getFitSelfEvaluationFeeling(): ?int
    {
        return $this->fitSelfEvaluationFeeling;
    }

    public function setFitSelfEvaluationFeeling(?int $fitSelfEvaluationFeeling): self
    {
        $this->fitSelfEvaluationFeeling = $fitSelfEvaluationFeeling;

        return $this;
    }

    public function getFitSelfEvaluationPerceivedEffort(): ?int
    {
        return $this->fitSelfEvaluationPerceivedEffort;
    }

    public function setFitSelfEvaluationPerceivedEffort(?int $fitSelfEvaluationPerceivedEffort): self
    {
        $this->fitSelfEvaluationPerceivedEffort = $fitSelfEvaluationPerceivedEffort;

        return $this;
    }

    public function getFitLoadPeak(): ?int
    {
        return $this->fitLoadPeak;
    }

    public function setFitLoadPeak(?int $fitLoadPeak): self
    {
        $this->fitLoadPeak = $fitLoadPeak;

        return $this;
    }

    /**
     * @return ?int [s]
     */
    public function getFitRunTime(): ?int
    {
        return $this->fitRunTime;
    }

    /**
     * @param ?int $fitRunTime [s]
     */
    public function setFitRunTime(?int $fitRunTime): self
    {
        $this->fitRunTime = $fitRunTime;

        return $this;
    }

    /**
     * @return ?int [s]
     */
    public function getFitWalkTime(): ?int
    {
        return $this->fitWalkTime;
    }

    /**
     * @param ?int $fitWalkTime [s]
     */
    public function setFitWalkTime(?int $fitWalkTime): self
    {
        $this->fitWalkTime = $fitWalkTime;

        return $this;
    }

    /**
     * @return ?int [s]
     */
    public function getFitStandTime(): ?int
    {
        return $this->fitStandTime;
    }

    /**
     * @param ?int $fitStandTime [s]
     */
    public function setFitStandTime(?int $fitStandTime): self
    {
        $this->fitStandTime = $fitStandTime;

        return $this;
    }

    /**
     * @param null|array $fitSecondsInHrZones [bpm]
     */
    public function setFitSecondsInHrZones(?array $fitSecondsInHrZones): self
    {
        $this->fitSecondsInHrZones = $fitSecondsInHrZones;

        return $this;
    }

    /**
     * @return null|array [bpm]
     */
    public function getFitSecondsInHrZones(): ?array
    {
        return $this->fitSecondsInHrZones;
    }

    /**
     * @return ?int [ml]
     */
    public function getFitSweatLoss(): ?int
    {
        return $this->fitSweatLoss;
    }

    /**
     * @param ?int $fitSweatLoss [ml]
     */
    public function setFitSweatLoss(?int $fitSweatLoss): self
    {
        $this->fitSweatLoss = $fitSweatLoss;

        return $this;
    }

    public function getAvgRespirationRate(): ?int
    {
        return $this->avgRespirationRate;
    }

    public function setAvgRespirationRate(?int $avgRespirationRate): self
    {
        $this->avgRespirationRate = $avgRespirationRate;

        return $this;
    }

    public function getMaxRespirationRate(): ?int
    {
        return $this->maxRespirationRate;
    }

    public function setMaxRespirationRate(?int $maxRespirationRate): self
    {
        $this->maxRespirationRate = $maxRespirationRate;

        return $this;
    }

    public function setRpe(?int $rpe): self
    {
        $this->rpe = $rpe;

        return $this;
    }

    public function getRpe(): ?int
    {
        return $this->rpe;
    }

    public function setTrimp(?int $trimp): self
    {
        $this->trimp = $trimp;

        return $this;
    }

    public function getTrimp(): ?int
    {
        return $this->trimp;
    }

    /**
     * @param ?int $cadence [rpm]
     */
    public function setCadence(?int $cadence): self
    {
        $this->cadence = $cadence;

        return $this;
    }

    /**
     * @return ?int [rpm]
     */
    public function getCadence(): ?int
    {
        return $this->cadence;
    }

    /**
     * @param ?int $power [W]
     */
    public function setPower(?int $power): self
    {
        $this->power = $power;

        return $this;
    }

    /**
     * @return ?int [W]
     */
    public function getPower(): ?int
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

    public function isPowerCalculated(): ?bool
    {
        return $this->isPowerCalculated;
    }

    public function setTotalStrokes(?int $totalStrokes): self
    {
        $this->totalStrokes = $totalStrokes;

        return $this;
    }

    public function getTotalStrokes(): ?int
    {
        return $this->totalStrokes;
    }

    public function setSwolf(?int $swolf): self
    {
        $this->swolf = $swolf;

        return $this;
    }

    public function getSwolf(): ?int
    {
        return $this->swolf;
    }

    /**
     * @param ?int $strideLength [cm]
     */
    public function setStrideLength(?int $strideLength): self
    {
        $this->strideLength = $strideLength;

        return $this;
    }

    /**
     * @return ?int [cm]
     */
    public function getStrideLength(): ?int
    {
        return $this->strideLength;
    }

    /**
     * @param ?int $totalCycles [stck]
     */
    public function setTotalCycles(?int $totalCycles): self
    {
        $this->totalCycles = $totalCycles;

        return $this;
    }

    /**
     * @return ?int [stck]
     */
    public function getTotalCycles(): ?int
    {
        return $this->totalCycles;
    }

    /**
     * @param ?int $groundcontact [ms]
     */
    public function setGroundcontact(?int $groundcontact): self
    {
        $this->groundcontact = $groundcontact;

        return $this;
    }

    /**
     * @return ?int [ms]
     */
    public function getGroundcontact(): ?int
    {
        return $this->groundcontact;
    }

    /**
     * @param ?int $groundcontactBalance [%ooL]
     */
    public function setGroundcontactBalance(?int $groundcontactBalance): self
    {
        $this->groundcontactBalance = $groundcontactBalance;

        return $this;
    }

    /**
     * @return ?int [%ooL]
     */
    public function getGroundcontactBalance(): ?int
    {
        return $this->groundcontactBalance;
    }

    /**
     * @param ?int $verticalOscillation [mm]
     */
    public function setVerticalOscillation(?int $verticalOscillation): self
    {
        $this->verticalOscillation = $verticalOscillation;

        return $this;
    }

    /**
     * @return ?int [mm]
     */
    public function getVerticalOscillation(): ?int
    {
        return $this->verticalOscillation;
    }

    /**
     * @param ?int $verticalRatio [%]
     */
    public function setVerticalRatio(?int $verticalRatio): self
    {
        $this->verticalRatio = $verticalRatio;

        return $this;
    }

    /**
     * @return ?int [%]
     */
    public function getVerticalRatio(): ?int
    {
        return $this->verticalRatio;
    }

    /**
     * @param null|float $avgImpactGsLeft [G]
     */
    public function setAvgImpactGsLeft(?float $avgImpactGsLeft): self
    {
        $this->avgImpactGsLeft = $avgImpactGsLeft;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgImpactGsLeft(): ?float
    {
        return $this->avgImpactGsLeft;
    }

    /**
     * @param null|float $avgImpactGsRight [G]
     */
    public function setAvgImpactGsRight(?float $avgImpactGsRight): self
    {
        $this->avgImpactGsRight = $avgImpactGsRight;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgImpactGsRight(): ?float
    {
        return $this->avgImpactGsRight;
    }

    /**
     * @param null|float $avgBrakingGsLeft [G]
     */
    public function setAvgBrakingGsLeft(?float $avgBrakingGsLeft): self
    {
        $this->avgBrakingGsLeft = $avgBrakingGsLeft;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgBrakingGsLeft(): ?float
    {
        return $this->avgBrakingGsLeft;
    }

    /**
     * @param null|float $avgBrakingGsRight [G]
     */
    public function setAvgBrakingGsRight(?float $avgBrakingGsRight): self
    {
        $this->avgBrakingGsRight = $avgBrakingGsRight;

        return $this;
    }

    /**
     * @return null|float [G]
     */
    public function getAvgBrakingGsRight(): ?float
    {
        return $this->avgBrakingGsRight;
    }

    /**
     * @param ?int $avgFootstrikeTypeLeft [°]
     */
    public function setAvgFootstrikeTypeLeft(?int $avgFootstrikeTypeLeft): self
    {
        $this->avgFootstrikeTypeLeft = $avgFootstrikeTypeLeft;

        return $this;
    }

    /**
     * @return ?int [°]
     */
    public function getAvgFootstrikeTypeLeft(): ?int
    {
        return $this->avgFootstrikeTypeLeft;
    }

    /**
     * @param ?int $avgFootstrikeTypeRight [°]
     */
    public function setAvgFootstrikeTypeRight(?int $avgFootstrikeTypeRight): self
    {
        $this->avgFootstrikeTypeRight = $avgFootstrikeTypeRight;

        return $this;
    }

    /**
     * @return ?int [°]
     */
    public function getAvgFootstrikeTypeRight(): ?int
    {
        return $this->avgFootstrikeTypeRight;
    }

    /**
     * @param null|float $avgPronationExcursionLeft [°]
     */
    public function setAvgPronationExcursionLeft(?float $avgPronationExcursionLeft): self
    {
        $this->avgPronationExcursionLeft = $avgPronationExcursionLeft;

        return $this;
    }

    /**
     * @return null|float [°]
     */
    public function getAvgPronationExcursionLeft(): ?float
    {
        return $this->avgPronationExcursionLeft;
    }

    /**
     * @param null|float $avgPronationExcursionRight [°]
     */
    public function setAvgPronationExcursionRight(?float $avgPronationExcursionRight): self
    {
        $this->avgPronationExcursionRight = $avgPronationExcursionRight;

        return $this;
    }

    /**
     * @return null|float [°]
     */
    public function getAvgPronationExcursionRight(): ?float
    {
        return $this->avgPronationExcursionRight;
    }

    /**
     * @param ?int $temperature [°C]
     */
    public function setTemperature($temperature): self
    {
        $this->temperature = $temperature === null ? null : (int) $temperature;

        return $this;
    }

    /**
     * @return ?int [°C]
     */
    public function getTemperature(): ?int
    {
        return $this->temperature;
    }

    /**
     * @param ?int $windSpeed [km/h]
     */
    public function setWindSpeed(?int $windSpeed): self
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    /**
     * @return ?int [km/h]
     */
    public function getWindSpeed(): ?int
    {
        return $this->windSpeed;
    }

    /**
     * @param ?int $windDeg [°]
     */
    public function setWindDeg(?int $windDeg): self
    {
        $this->windDeg = $windDeg !== null ? (($windDeg % 360) + 360) % 360 : null;

        return $this;
    }

    /**
     * @return ?int [°]
     */
    public function getWindDeg(): ?int
    {
        return $this->windDeg;
    }

    /**
     * @param ?int $humidity [%]
     */
    public function setHumidity(?int $humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    /**
     * @return ?int [%]
     */
    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    /**
     * @param ?int $pressure [hPa]
     */
    public function setPressure(?int $pressure): self
    {
        $this->pressure = $pressure;

        return $this;
    }

    /**
     * @return ?int [hPa]
     */
    public function getPressure(): ?int
    {
        return $this->pressure;
    }

    public function setNight(?bool $isNight): self
    {
        $this->isNight = $isNight;

        return $this;
    }

    public function isNight(): ?bool
    {
        return $this->isNight;
    }

    /**
     * @param int $weatherid enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function setWeatherid(int $weatherid): self
    {
        $this->weatherid = $weatherid;

        return $this;
    }

    /**
     * @return int enum, see \Runalyze\Profile\Weather\WeatherConditionProfile
     */
    public function getWeatherid(): int
    {
        return $this->weatherid;
    }

    /**
     * @param ?int $weatherSource
     */
    public function setWeatherSource($weatherSource): self
    {
        $this->weatherSource = $weatherSource === null ? null : (int) $weatherSource;

        return $this;
    }

    public function getWeatherSource(): ?int
    {
        return $this->weatherSource;
    }

    public function setRouteName(?string $routeName): self
    {
        $this->routeName = $routeName;

        if ($this->hasRoute()) {
            $this->getRoute()
                ->setName($routeName);
        }

        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRoute(?Route $route = null): self
    {
        $this->route = $route;

        if ($route instanceof Route && isset($this->account)) {
            $route->setAccount($this->account);
        }

        return $this;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function hasRoute(): bool
    {
        return $this->route instanceof Route;
    }

    public function setSplits(RoundCollection $splits): self
    {
        $this->splits = $splits;

        return $this;
    }

    public function setSplitsToClone(): void
    {
        $this->splits = clone $this->splits;
    }

    public function getSplits(): ?RoundCollection
    {
        return $this->splits;
    }

    public function setSplitsAdditional(?array $splitsAdditional): self
    {
        $this->splitsAdditional = $splitsAdditional;

        return $this;
    }

    public function getSplitsAdditional(): ?array
    {
        return $this->splitsAdditional;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setPartner(?string $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getPartner(): ?string
    {
        return $this->partner;
    }

    public function setPaceGoal(?array $paceGoal): self
    {
        $this->paceGoal = $paceGoal;

        return $this;
    }

    public function getPaceGoal(): ?array
    {
        return $this->paceGoal;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function hasAccount(): bool
    {
        return isset($this->account);
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

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreatorDetails(?string $creatorDetails): self
    {
        $this->creatorDetails = $creatorDetails;

        return $this;
    }

    public function getCreatorDetails(): ?string
    {
        return $this->creatorDetails;
    }

    public function setActivityId(?int $activityId): self
    {
        $this->activityId = $activityId ?? null;

        return $this;
    }

    public function getActivityId(): ?int
    {
        return $this->activityId;
    }

    public function setLock(bool $lock): self
    {
        $this->lock = $lock;

        return $this;
    }

    public function getLock(): bool
    {
        return $this->lock;
    }

    public function addEquipment(Equipment $equipment): self
    {
        $this->equipment[] = $equipment;

        return $this;
    }

    public function removeEquipment(Equipment $equipment): void
    {
        $this->equipment->removeElement($equipment);
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function addTag(Tag $tag): self
    {
        $this->tag[] = $tag;

        return $this;
    }

    public function removeTag(Tag $tag): void
    {
        $this->tag->removeElement($tag);
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTag(): Collection
    {
        return $this->tag;
    }

    public function setTrackdata(?Trackdata $trackData = null): void
    {
        $this->trackdata = $trackData;

        if ($trackData instanceof Trackdata) {
            $trackData->setActivity($this);

            if (isset($this->account)) {
                $trackData->setAccount($this->account);
            }
        }
    }

    public function getTrackdata(): ?Trackdata
    {
        return $this->trackdata;
    }

    public function hasTrackdata(): bool
    {
        return $this->trackdata instanceof Trackdata;
    }

    public function setSwimdata(?Swimdata $swimData = null): void
    {
        $this->swimdata = $swimData;

        if ($swimData instanceof Swimdata) {
            $swimData->setActivity($this);

            if (isset($this->account)) {
                $swimData->setAccount($this->account);
            }
        }
    }

    public function getSwimdata(): ?Swimdata
    {
        return $this->swimdata;
    }

    public function hasSwimdata(): bool
    {
        return $this->swimdata instanceof Swimdata;
    }

    public function setHrv(?Hrv $hrv = null): void
    {
        $this->hrv = $hrv;

        if ($hrv instanceof Hrv) {
            $hrv->setActivity($this);

            if (isset($this->account)) {
                $hrv->setAccount($this->account);
            }
        }
    }

    public function getHrv(): ?Hrv
    {
        return $this->hrv;
    }

    public function hasHrv(): bool
    {
        return $this->hrv instanceof Hrv;
    }

    public function setRaceresult(?Raceresult $raceResult = null): void
    {
        $this->raceresult = $raceResult;

        if ($raceResult instanceof Raceresult) {
            $raceResult->setActivity($this);

            if (isset($this->account)) {
                $raceResult->setAccount($this->account);
            }
        }
    }

    public function getRaceresult(): ?Raceresult
    {
        return $this->raceresult;
    }

    public function hasRaceresult(): bool
    {
        return $this->raceresult instanceof Raceresult;
    }

    public function getAdapter(): ActivityAdapter
    {
        $this->Adapter ??= new ActivityAdapter($this);

        return $this->Adapter;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSimpleCalculatedValues(): void
    {
        if ($this->distance == 0.0) {
            $this->distance = null;
        }

        $this->getAdapter()
            ->updateSimpleCalculatedValues();
    }

    #[ORM\PrePersist]
    public function useElevationFromRouteIfEmpty(): void
    {
        if ($this->elevation === null || $this->elevation === 0) {
            $this->getAdapter()
                ->useElevationFromRoute();
        }
    }
}

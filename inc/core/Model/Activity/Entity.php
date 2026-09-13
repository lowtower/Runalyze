<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Activity
 */

namespace Runalyze\Model\Activity;

use Runalyze\Data\Weather;
use Runalyze\Model;

/**
 * Activity entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity
 */
class Entity extends Model\EntityWithID
{
    /**
     * Key: timestamp
     * @var string
     */
    public const TIMESTAMP = 'time';

    /**
     * Key: timezone offset [minutes]
     * @var string
     */
    public const TIMEZONE_OFFSET = 'timezone_offset';

    /**
     * Key: timestamp created
     * @var string
     */
    public const TIMESTAMP_CREATED = 'created';

    /**
     * Key: timestamp last edit
     * @var string
     */
    public const TIMESTAMP_EDITED = 'edited';

    /**
     * Key: sportid
     * @var string
     */
    public const SPORTID = 'sportid';

    /**
     * Key: typeid
     * @var string
     */
    public const TYPEID = 'typeid';

    /**
     * Key: is public
     * @var string
     */
    public const IS_PUBLIC = 'is_public';

    /**
     * Key: is track
     * @var string
     */
    public const IS_TRACK = 'is_track';

    /**
     * Key: has training goal
     * @var string
     */
    public const WITH_GOAL = 'with_goal';

    /**
     * Key: distance
     * @var string
     */
    public const DISTANCE = 'distance';

    /**
     * Key: time in seconds
     * @var string
     */
    public const TIME_IN_SECONDS = 's';

    /**
     * Key: elapsed time
     * @var string
     */
    public const ELAPSED_TIME = 'elapsed_time';

    /**
     * Key: elevation
     * @var string
     */
    public const ELEVATION = 'elevation';

    /**
     * Key: climb score
     * @var string
     */
    public const CLIMB_SCORE = 'climb_score';

    /**
     * Key: percentage hilly
     * @var string
     */
    public const PERCENTAGE_HILLY = 'percentage_hilly';

    /**
     * Key: energy
     * @var string
     */
    public const ENERGY = 'kcal';

    /**
     * Key: energy in rest
     * @var string
     */
    public const ENERGY_REST = 'kcal_rest';

    /**
     * Key: average heart rate
     * @var string
     */
    public const HR_AVG = 'pulse_avg';

    /**
     * Key: maximal heart rate
     * @var string
     */
    public const HR_MAX = 'pulse_max';

    /**
     * Key: average heart rate active rounds
     * @var string
     */
    public const HR_AVG_ACTIVE = 'pulse_avg_active';

    /**
     * Key: max heart rate for the user while the activity is done (the general max HR, not the activity one)
     * @var string
     */
    public const MAX_HR_USER = 'max_hr_user';

    /**
     * Key: lower bounds of the hr zones while the activity is done
     * @var string
     */
    public const HR_ZONE_BOUNDS = 'hr_zone_bounds';

    /**
     * Key: vo2max
     * @var string
     */
    public const VO2MAX = 'vo2max';

    /**
     * Key: vo2max by time
     * @var string
     */
    public const VO2MAX_BY_TIME = 'vo2max_by_time';

    /**
     * Key: vo2max with elevation
     * @var string
     */
    public const VO2MAX_WITH_ELEVATION = 'vo2max_with_elevation';

    /**
     * Key: use vo2max
     * @var string
     */
    public const USE_VO2MAX = 'use_vo2max';

    /**
     * Key: vo2max estimate from fit file
     * @var string
     */
    public const FIT_VO2MAX_ESTIMATE = 'fit_vo2max_estimate';

    /**
     * Key: recovery time from fit file
     * @var string
     */
    public const FIT_RECOVERY_TIME = 'fit_recovery_time';

    /**
     * Key: hrv analysis score from fit file
     * @var string
     */
    public const FIT_HRV_ANALYSIS = 'fit_hrv_analysis';

    /**
     * Key: total training effect benefit from fit file
     * @var string
     */
    public const FIT_TRAINING_EFFECT_BENEFIT = 'fit_training_effect_benefit';

    /**
     * Key: total training effect from fit file
     * @var string
     */
    public const FIT_TRAINING_EFFECT = 'fit_training_effect';

    /**
     * Key: total anaerobic training effect from fit file
     * @var string
     */
    public const FIT_ANAEROBIC_TRAINING_EFFECT = 'fit_anaerobic_training_effect';

    /**
     * Key: performance condition from fit file
     * @var string
     */
    public const FIT_PERFORMANCE_CONDITION = 'fit_performance_condition';

    /**
     * Key: performance condition from fit file
     * @var string
     */
    public const FIT_PERFORMANCE_CONDITION_END = 'fit_performance_condition_end';

    /**
     * Key: lactate threshold heart rate file
     * @var string
     */
    public const FIT_LACTATE_THRESHOLD_HR = 'fit_lactate_threshold_hr';

    /**
     * Key: lactate threshold pace (in m/s) file
     * @var string
     */
    public const FIT_LACTATE_THRESHOLD_PACE = 'fit_lactate_threshold_pace_ms';

    /**
     * Key: fit total ascent
     * @var string
     */
    public const FIT_TOTAL_ASCENT = 'fit_total_ascent';

    /**
     * Key: fit total descent
     * @var string
     */
    public const FIT_TOTAL_DESCENT = 'fit_total_descent';

    /**
     * Key: fit_self_evaluation_feeling
     * @var string
     */
    public const FIT_SELF_ELEVATION_FEELING = 'fit_self_evaluation_feeling';

    /**
     * Key: fit_self_evaluation_perceived_effort
     * @var string
     */
    public const FIT_SELF_ELEVATION_PERCEIVED_EFFORT = 'fit_self_evaluation_perceived_effort';

    /**
     * Key: fit_load_peak
     * @var string
     */
    public const FIT_LOAD_PEAK = 'fit_load_peak';

    /**
     * Key: fit_run_time
     * @var string
     */
    public const FIT_RUN_TIME = 'fit_run_time';

    /**
     * Key: fit_walk_time
     * @var string
     */
    public const FIT_WALK_TIME = 'fit_walk_time';

    /**
     * Key: fit_stand_time
     * @var string
     */
    public const FIT_STAND_TIME = 'fit_stand_time';

    /**
     * Key: for every HR_ZONE_BOUNDS the times in seconds
     * @var string
     */
    public const FIT_SECONDS_HR_ZONES = 'fit_seconds_hr_zones';

    /**
     * Key: sweat loss in ml
     * @var string
     */
    public const FIT_SWEAT_LOSS = 'fit_sweat_loss';

    /**
     * Key: avg_respiration_rate
     * @var string
     */
    public const AVG_RESPIRATION_RATE = 'avg_respiration_rate';

    /**
     * Key: max_respiration_rate
     * @var string
     */
    public const MAX_RESPIRATION_RATE = 'max_respiration_rate';

    /**
     * Key: RPE
     * @var string
     */
    public const RPE = 'rpe';

    /**
     * Key: trimp
     * @var string
     */
    public const TRIMP = 'trimp';

    /**
     * Key: cadence
     * @var string
     */
    public const CADENCE = 'cadence';

    /**
     * Key: power
     * @var string
     */
    public const POWER = 'power';

    /**
     * Key: power
     * @var string
     */
    public const IS_POWER_CALCULATED = 'is_power_calculated';

    /**
     * Key: stride length
     * @var string
     */
    public const STRIDE_LENGTH = 'stride_length';

    /**
     * Key: total_strokes
     * @var string
     */
    public const TOTAL_STROKES = 'total_strokes';

    /**
     * Key: SWOLF
     * @var string
     */
    public const SWOLF = 'swolf';

    /**
     * Key: total_cycles
     * @var string
     */
    public const TOTAL_CYCLES = 'total_cycles';

    /**
     * Key: ground contact time
     * @var string
     */
    public const GROUNDCONTACT = 'groundcontact';

    /**
     * Key: vertical oscillation
     * @var string
     */
    public const VERTICAL_OSCILLATION = 'vertical_oscillation';

    /**
     * Key: ground contact time balance
     * @var string
     */
    public const GROUNDCONTACT_BALANCE = 'groundcontact_balance';

    /**
     * Key: vertical ratio
     * @var string
     */
    public const VERTICAL_RATIO = 'vertical_ratio';

    /**
     * @var string
     */
    public const AVG_IMPACT_GS_LEFT = 'avg_impact_gs_left';

    /**
     * @var string
     */
    public const AVG_IMPACT_GS_RIGHT = 'avg_impact_gs_right';

    /**
     * @var string
     */
    public const AVG_BRAKING_GS_LEFT = 'avg_braking_gs_left';

    /**
     * @var string
     */
    public const AVG_BRAKING_GS_RIGHT = 'avg_braking_gs_right';

    /**
     * @var string
     */
    public const AVG_FOOTSTRIKE_TYPE_LEFT = 'avg_footstrike_type_left';

    /**
     * @var string
     */
    public const AVG_FOOTSTRIKE_TYPE_RIGHT = 'avg_footstrike_type_right';

    /**
     * @var string
     */
    public const AVG_PRONATION_EXCURSION_LEFT = 'avg_pronation_excursion_left';

    /**
     * @var string
     */
    public const AVG_PRONATION_EXCURSION_RIGHT = 'avg_pronation_excursion_right';

    /**
     * Key: temperature
     * @var string
     */
    public const TEMPERATURE = 'temperature';

    /**
     * Key: wind speed
     * @var string
     */
    public const WINDSPEED = 'wind_speed';

    /**
     * Key: wind degree
     * @var string
     */
    public const WINDDEG = 'wind_deg';

    /**
     * Key: humidity
     * @var string
     */
    public const HUMIDITY = 'humidity';

    /**
     * Key: pressure
     * @var string
     */
    public const PRESSURE = 'pressure';

    /**
     * Key: weather id
     * @var string
     */
    public const WEATHERID = 'weatherid';

    /**
     * Key: weather source
     * @var string
     */
    public const WEATHER_SOURCE = 'weather_source';

    /**
     * Key: is night
     * @var string
     */
    public const IS_NIGHT = 'is_night';

    /**
     * Key: route id
     * @var string
     */
    public const ROUTEID = 'routeid';

    /**
     * Key: route
     * @var string
     * @deprecated
     */
    public const ROUTE = 'route';

    /**
     * Key: splits
     * @var string
     */
    public const SPLITS = 'splits';

    /**
     * Key: splits_additional
     * @var string
     */
    public const SPLITS_ADDITIONAL = 'splits_additional';

    /**
     * Key: pace_goal
     * @var string
     */
    public const PACE_GOAL = 'pace_goal';

    /**
     * Key: title
     * @var string
     */
    public const TITLE = 'title';

    /**
     * Key: partner
     * @var string
     */
    public const PARTNER = 'partner';

    /**
     * Key: notes
     * @var string
     */
    public const NOTES = 'notes';

    /**
     * Key: creator
     * @var string
     */
    public const CREATOR = 'creator';

    /**
     * Key: creator details
     * @var string
     */
    public const CREATOR_DETAILS = 'creator_details';

    /**
     * Key: activity id
     * @var string
     */
    public const ACTIVITY_ID = 'activity_id';

    /**
     * Weather
     */
    protected ?\Runalyze\Data\Weather $Weather = null;

    /**
     * Splits
     */
    protected ?\Runalyze\Model\Activity\Splits\Entity $Splits = null;

    /**
     * Splits-additional
     */
    protected array $SplitsAdditional;

    /**
     * Partner
     */
    protected ?\Runalyze\Model\Activity\Partner $Partner = null;

    /**
     * Pace-Goal
     */
    protected array $PaceGoal;

    /**
     * Clone object
     */
    public function __clone()
    {
        $this->cloneInternalObjects();
    }

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::TIMESTAMP,
            self::TIMEZONE_OFFSET,
            self::TIMESTAMP_CREATED,
            self::TIMESTAMP_EDITED,
            self::SPORTID,
            self::TYPEID,
            self::IS_PUBLIC,
            self::IS_TRACK,
            self::WITH_GOAL,
            self::DISTANCE,
            self::TIME_IN_SECONDS,
            self::ELAPSED_TIME,
            self::ELEVATION,
            self::CLIMB_SCORE,
            self::PERCENTAGE_HILLY,
            self::ENERGY,
            self::ENERGY_REST,
            self::HR_AVG,
            self::HR_MAX,
            self::HR_AVG_ACTIVE,
            self::MAX_HR_USER,
            self::HR_ZONE_BOUNDS,
            self::VO2MAX,
            self::VO2MAX_BY_TIME,
            self::VO2MAX_WITH_ELEVATION,
            self::USE_VO2MAX,
            self::FIT_VO2MAX_ESTIMATE,
            self::FIT_RECOVERY_TIME,
            self::FIT_HRV_ANALYSIS,
            self::FIT_TRAINING_EFFECT_BENEFIT,
            self::FIT_TRAINING_EFFECT,
            self::FIT_ANAEROBIC_TRAINING_EFFECT,
            self::FIT_PERFORMANCE_CONDITION,
            self::FIT_PERFORMANCE_CONDITION_END,
            self::FIT_LACTATE_THRESHOLD_HR,
            self::FIT_LACTATE_THRESHOLD_PACE,
            self::FIT_TOTAL_ASCENT,
            self::FIT_TOTAL_DESCENT,
            self::FIT_SELF_ELEVATION_FEELING,
            self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT,
            self::FIT_LOAD_PEAK,
            self::FIT_RUN_TIME,
            self::FIT_WALK_TIME,
            self::FIT_STAND_TIME,
            self::FIT_SECONDS_HR_ZONES,
            self::FIT_SWEAT_LOSS,
            self::AVG_RESPIRATION_RATE,
            self::MAX_RESPIRATION_RATE,
            self::RPE,
            self::TRIMP,
            self::CADENCE,
            self::POWER,
            self::IS_POWER_CALCULATED,
            self::STRIDE_LENGTH,
            self::SWOLF,
            self::TOTAL_STROKES,
            self::TOTAL_CYCLES,
            self::GROUNDCONTACT,
            self::VERTICAL_OSCILLATION,
            self::GROUNDCONTACT_BALANCE,
            self::VERTICAL_RATIO,
            self::AVG_IMPACT_GS_LEFT,
            self::AVG_IMPACT_GS_RIGHT,
            self::AVG_BRAKING_GS_LEFT,
            self::AVG_BRAKING_GS_RIGHT,
            self::AVG_FOOTSTRIKE_TYPE_LEFT,
            self::AVG_FOOTSTRIKE_TYPE_RIGHT,
            self::AVG_PRONATION_EXCURSION_LEFT,
            self::AVG_PRONATION_EXCURSION_RIGHT,
            self::TEMPERATURE,
            self::WINDSPEED,
            self::WINDDEG,
            self::HUMIDITY,
            self::PRESSURE,
            self::WEATHERID,
            self::WEATHER_SOURCE,
            self::IS_NIGHT,
            self::ROUTEID,
            self::ROUTE,
            self::SPLITS,
            self::SPLITS_ADDITIONAL,
            self::TITLE,
            self::PARTNER,
            self::NOTES,
            self::CREATOR,
            self::CREATOR_DETAILS,
            self::ACTIVITY_ID,
            self::PACE_GOAL,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return static::allDatabaseProperties();
    }

    /**
     * Is the property an array?
     */
    public function isArray(string $key): bool
    {
        return $key === self::HR_ZONE_BOUNDS || $key === self::FIT_SECONDS_HR_ZONES;
    }

    /**
     * Can set key?
     */
    protected function canSet(string $key): bool
    {
        switch ($key) {
            case self::TEMPERATURE:
            case self::WINDSPEED:
            case self::WINDDEG:
            case self::HUMIDITY:
            case self::PRESSURE:
            case self::WEATHERID:
            case self::WEATHER_SOURCE:
            case self::PARTNER:
            case self::SPLITS:
                return false;
        }

        return true;
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        switch ($key) {
            case self::TYPEID:
            case self::TIMESTAMP_CREATED:
            case self::TIMESTAMP_EDITED:
            case self::TIMEZONE_OFFSET:
            case self::DISTANCE:
            case self::ELAPSED_TIME:
            case self::ELEVATION:
            case self::CLIMB_SCORE:
            case self::PERCENTAGE_HILLY:
            case self::ENERGY:
            case self::ENERGY_REST:
            case self::HR_AVG:
            case self::HR_MAX:
            case self::HR_AVG_ACTIVE:
            case self::MAX_HR_USER:
            case self::HR_ZONE_BOUNDS:
            case self::VO2MAX:
            case self::VO2MAX_BY_TIME:
            case self::VO2MAX_WITH_ELEVATION:
            case self::FIT_VO2MAX_ESTIMATE:
            case self::FIT_RECOVERY_TIME:
            case self::FIT_HRV_ANALYSIS:
            case self::FIT_TRAINING_EFFECT_BENEFIT:
            case self::FIT_TRAINING_EFFECT:
            case self::FIT_ANAEROBIC_TRAINING_EFFECT:
            case self::FIT_PERFORMANCE_CONDITION:
            case self::FIT_PERFORMANCE_CONDITION_END:
            case self::FIT_LACTATE_THRESHOLD_HR:
            case self::FIT_LACTATE_THRESHOLD_PACE:
            case self::FIT_TOTAL_ASCENT:
            case self::FIT_TOTAL_DESCENT:
            case self::FIT_SELF_ELEVATION_FEELING:
            case self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT:
            case self::FIT_LOAD_PEAK:
            case self::FIT_RUN_TIME:
            case self::FIT_WALK_TIME:
            case self::FIT_STAND_TIME:
            case self::FIT_SECONDS_HR_ZONES:
            case self::FIT_SWEAT_LOSS:
            case self::AVG_RESPIRATION_RATE:
            case self::MAX_RESPIRATION_RATE:
            case self::RPE:
            case self::TRIMP:
            case self::CADENCE:
            case self::POWER:
            case self::IS_POWER_CALCULATED:
            case self::TOTAL_STROKES:
            case self::SWOLF:
            case self::STRIDE_LENGTH:
            case self::TOTAL_CYCLES:
            case self::GROUNDCONTACT:
            case self::GROUNDCONTACT_BALANCE:
            case self::VERTICAL_OSCILLATION:
            case self::VERTICAL_RATIO:
            case self::AVG_IMPACT_GS_LEFT:
            case self::AVG_IMPACT_GS_RIGHT:
            case self::AVG_BRAKING_GS_LEFT:
            case self::AVG_BRAKING_GS_RIGHT:
            case self::AVG_FOOTSTRIKE_TYPE_LEFT:
            case self::AVG_FOOTSTRIKE_TYPE_RIGHT:
            case self::AVG_PRONATION_EXCURSION_LEFT:
            case self::AVG_PRONATION_EXCURSION_RIGHT:
            case self::TEMPERATURE:
            case self::WINDSPEED:
            case self::WINDDEG:
            case self::HUMIDITY:
            case self::PRESSURE:
            case self::WEATHER_SOURCE:
            case self::IS_NIGHT:
            case self::NOTES:
            case self::CREATOR_DETAILS:
            case self::ROUTEID:
            case self::ACTIVITY_ID:
            case self::PACE_GOAL:
                return true;
        }

        return false;
    }

    public function synchronize(): void
    {
        parent::synchronize();

        $this->ensureNullIfEmpty(self::ACTIVITY_ID);
        $this->ensureNullIfEmpty(self::IS_NIGHT, true);
        $this->ensureNullIfEmpty(self::CLIMB_SCORE, true);
        $this->ensureNullIfEmpty(self::PERCENTAGE_HILLY, true);
        $this->ensureAllNullOrNumericValues();
        $this->ensureAllNumericValues();
        $this->synchronizeObjects();
    }

    protected function ensureAllNullOrNumericValues()
    {
        $this->ensureNullIfEmpty([
            self::TYPEID,
            self::TIMESTAMP_CREATED,
            self::TIMESTAMP_EDITED,
            self::TIMEZONE_OFFSET,
            self::DISTANCE,
            self::ELAPSED_TIME,
            self::ELEVATION,
            self::ENERGY,
            self::ENERGY_REST,
            self::HR_AVG,
            self::HR_MAX,
            self::HR_AVG_ACTIVE,
            self::MAX_HR_USER,
            self::HR_ZONE_BOUNDS,
            self::VO2MAX,
            self::VO2MAX_BY_TIME,
            self::VO2MAX_WITH_ELEVATION,
            self::FIT_VO2MAX_ESTIMATE,
            self::FIT_RECOVERY_TIME,
            self::FIT_HRV_ANALYSIS,
            self::FIT_TRAINING_EFFECT_BENEFIT,
            self::FIT_TRAINING_EFFECT,
            self::FIT_ANAEROBIC_TRAINING_EFFECT,
            self::FIT_PERFORMANCE_CONDITION,
            self::FIT_PERFORMANCE_CONDITION_END,
            self::FIT_LACTATE_THRESHOLD_HR,
            self::FIT_LACTATE_THRESHOLD_PACE,
            self::FIT_TOTAL_ASCENT,
            self::FIT_TOTAL_DESCENT,
            self::FIT_SELF_ELEVATION_FEELING,
            self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT,
            self::FIT_LOAD_PEAK,
            self::FIT_SECONDS_HR_ZONES,
            self::FIT_SWEAT_LOSS,
            self::AVG_RESPIRATION_RATE,
            self::MAX_RESPIRATION_RATE,
            self::RPE,
            self::TRIMP,
            self::CADENCE,
            self::POWER,
            self::IS_POWER_CALCULATED,
            self::TOTAL_STROKES,
            self::SWOLF,
            self::STRIDE_LENGTH,
            self::TOTAL_CYCLES,
            self::GROUNDCONTACT,
            self::GROUNDCONTACT_BALANCE,
            self::VERTICAL_OSCILLATION,
            self::VERTICAL_RATIO,
            self::AVG_IMPACT_GS_LEFT,
            self::AVG_IMPACT_GS_RIGHT,
            self::AVG_BRAKING_GS_LEFT,
            self::AVG_BRAKING_GS_RIGHT,
            self::AVG_FOOTSTRIKE_TYPE_LEFT,
            self::AVG_FOOTSTRIKE_TYPE_RIGHT,
            self::AVG_PRONATION_EXCURSION_LEFT,
            self::AVG_PRONATION_EXCURSION_RIGHT,
            self::ROUTEID,
        ], true, true);
    }

    /**
     * Ensure that numeric fields get numeric values
     */
    protected function ensureAllNumericValues()
    {
        $this->ensureNumericValue([
            self::SPORTID,
            self::TIMESTAMP,
            self::IS_PUBLIC,
            self::IS_TRACK,
            self::TIME_IN_SECONDS,
            self::USE_VO2MAX,
        ]);
    }

    protected function synchronizeObjects()
    {
        $this->Data[self::TEMPERATURE] = $this->weather()->temperature()->value();
        $this->Data[self::WINDSPEED] = $this->weather()->windSpeed()->value();
        $this->Data[self::WINDDEG] = $this->weather()->windDegree()->value();
        $this->Data[self::HUMIDITY] = $this->weather()->humidity()->value();
        $this->Data[self::PRESSURE] = $this->weather()->pressure()->value();
        $this->Data[self::WEATHERID] = $this->weather()->condition()->id();
        $this->Data[self::WEATHER_SOURCE] = $this->weather()->source();
        $this->Data[self::SPLITS] = $this->splits()->asString();
        $this->Data[self::PARTNER] = $this->partner()->asString();
    }

    public function timestamp(): int
    {
        return (int) $this->Data[self::TIMESTAMP];
    }

    public function knowsTimezoneOffset(): bool
    {
        return $this->Data[self::TIMEZONE_OFFSET] !== null;
    }

    /**
     * @return int|null offset in minutes or null if unknown
     */
    public function timezoneOffset(): ?int
    {
        if ($this->Data[self::TIMEZONE_OFFSET] === null) {
            return null;
        }

        return (int) $this->Data[self::TIMEZONE_OFFSET];
    }

    public function sportid(): int
    {
        return (int) $this->Data[self::SPORTID];
    }

    public function typeid(): ?int
    {
        return (int) $this->Data[self::TYPEID];
    }

    /**
     * Is public?
     */
    public function isPublic(): bool
    {
        return $this->Data[self::IS_PUBLIC] == 1;
    }

    /**
     * On track?
     */
    public function isTrack(): bool
    {
        return $this->Data[self::IS_TRACK] == 1;
    }

    /**
     * has training goal
     */
    public function withGoal(): bool
    {
        return $this->Data[self::WITH_GOAL] == 1;
    }

    /**
     * @return null|float [km]
     */
    public function distance(): ?float
    {
        if ($this->Data[self::DISTANCE] === null) {
            return null;
        }

        return (float) $this->Data[self::DISTANCE];
    }

    /**
     * Time in seconds
     * @return int [s]
     */
    public function duration(): int
    {
        return (int) $this->Data[self::TIME_IN_SECONDS];
    }

    /**
     * @return null|int [s]
     */
    public function elapsedTime(): ?int
    {
        if ($this->Data[self::ELAPSED_TIME] === null) {
            return null;
        }

        return (int) $this->Data[self::ELAPSED_TIME];
    }

    /**
     * @return null|int [m]
     */
    public function elevation(): ?int
    {
        if ($this->Data[self::ELEVATION] === null) {
            return null;
        }

        return (int) $this->Data[self::ELEVATION];
    }

    /**
     * @return null|float [0.0 .. 1.0]
     */
    public function climbScore(): ?float
    {
        if ($this->Data[self::CLIMB_SCORE] === null) {
            return null;
        }

        return (float) $this->Data[self::CLIMB_SCORE];
    }

    /**
     * @return null|float [0.00 .. 1.00]
     */
    public function percentageHilly(): ?float
    {
        if ($this->Data[self::PERCENTAGE_HILLY] === null) {
            return null;
        }

        return (float) $this->Data[self::PERCENTAGE_HILLY];
    }

    /**
     * @return null|int [kcal]
     */
    public function energy(): ?int
    {
        return (int) $this->Data[self::ENERGY];
    }

    /**
     * #TSC
     * @return null|int [kcal]
     */
    public function energyInRest(): ?int
    {
        return (int) $this->Data[self::ENERGY_REST];
    }

    /**
     * Average heart rate
     * @return null|int [bpm]
     */
    public function hrAvg(): ?int
    {
        return (int) $this->Data[self::HR_AVG];
    }

    /**
     * Maximal heart rate
     * @return null|int [bpm]
     */
    public function hrMax(): ?int
    {
        return (int) $this->Data[self::HR_MAX];
    }

    /**
     * Average heart rate active rounds
     * @return null|int [bpm]
     */
    public function hrAvgActive(): ?int
    {
        return (int) $this->Data[self::HR_AVG_ACTIVE];
    }

    /**
     * Average max heart rate of the user (general, not while this activity)
     * @return null|int [bpm]
     */
    public function maxHrUser(): ?int
    {
        return (int) $this->Data[self::MAX_HR_USER];
    }

    /**
     * Average lower bounds of the hr zones
     * @return null|array [bpm]
     */
    public function hrZoneBounds()
    {
        return $this->Data[self::HR_ZONE_BOUNDS];
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function vo2maxByHeartRate(): ?float
    {
        return (float) $this->Data[self::VO2MAX];
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function vo2maxByTime(): ?float
    {
        return (float) $this->Data[self::VO2MAX_BY_TIME];
    }

    /**
     * @return null|float [ml/kg/min]
     */
    public function vo2maxWithElevation(): ?float
    {
        return (float) $this->Data[self::VO2MAX_WITH_ELEVATION];
    }

    public function usesVO2max(): bool
    {
        return $this->Data[self::USE_VO2MAX] == 1;
    }

    /**
     * VO2max estimate from fit file
     * @return null|float [ml/kg/min]
     */
    public function fitVO2maxEstimate(): ?float
    {
        return (float) $this->Data[self::FIT_VO2MAX_ESTIMATE];
    }

    /**
     * Recovery time advisor from fit file
     * @return null|int [min]
     */
    public function fitRecoveryTime(): ?int
    {
        return (int) $this->Data[self::FIT_RECOVERY_TIME];
    }

    /**
     * HRV analysis score from fit file
     */
    public function fitHRVscore(): ?int
    {
        return (int) $this->Data[self::FIT_HRV_ANALYSIS];
    }

    /**
     * Total training effect benefit
     * 1=Recovery, 2=Base, 3=Tempo, 4=Threshold, 5=VO2 Max, 6=Anaerobic, 7=Sprint
     */
    public function fitTrainingEffectBenefit(): ?int
    {
        return (int) $this->Data[self::FIT_TRAINING_EFFECT_BENEFIT];
    }

    /**
     * Total training effect
     */
    public function fitTrainingEffect(): ?float
    {
        return (float) $this->Data[self::FIT_TRAINING_EFFECT];
    }

    /**
     * Total anaerobic training effect
     */
    public function fitAnaerobicTrainingEffect(): ?float
    {
        return (float) $this->Data[self::FIT_ANAEROBIC_TRAINING_EFFECT];
    }

    /**
     * Performance condition
     */
    public function fitPerformanceCondition(): ?int
    {
        return (int) $this->Data[self::FIT_PERFORMANCE_CONDITION];
    }

    /**
     * Ending performance condition
     */
    public function fitPerformanceConditionEnd(): ?int
    {
        return (int) $this->Data[self::FIT_PERFORMANCE_CONDITION_END];
    }

    /**
     * Lactate threshold HR
     */
    public function fitLactateThresholdHR(): ?int
    {
        return (int) $this->Data[self::FIT_LACTATE_THRESHOLD_HR];
    }

    /**
     * Lactate threshold pace (in m/s)
     */
    public function fitLactateThresholdPace(): ?float
    {
        return (float) $this->Data[self::FIT_LACTATE_THRESHOLD_PACE];
    }

    public function fitTotalAscent(): ?int
    {
        return (int) $this->Data[self::FIT_TOTAL_ASCENT];
    }

    public function fitTotalDescent(): ?int
    {
        return (int) $this->Data[self::FIT_TOTAL_DESCENT];
    }

    /**
     * fit fitSelfEvaluationFeeling
     */
    public function fitSelfEvaluationFeeling(): ?int
    {
        return (int) $this->Data[self::FIT_SELF_ELEVATION_FEELING];
    }

    /**
     * fit fitSelfEvaluationPerceivedEffort
     */
    public function fitSelfEvaluationPerceivedEffort(): ?int
    {
        return (int) $this->Data[self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT];
    }

    /**
     * fit fitLoadPeak
     */
    public function fitLoadPeak(): ?int
    {
        return (int) $this->Data[self::FIT_LOAD_PEAK];
    }

    /**
     * fit RWD run time #TSC
     * @return null|int [s]
     */
    public function fitRunTime(): ?int
    {
        return (int) $this->Data[self::FIT_RUN_TIME];
    }

    /**
     * fit RWD walk time #TSC
     * @return null|int [s]
     */
    public function fitWalkTime(): ?int
    {
        return (int) $this->Data[self::FIT_WALK_TIME];
    }

    /**
     * fit RWD run time #TSC
     * @return null|int [s]
     */
    public function fitStandTime(): ?int
    {
        return (int) $this->Data[self::FIT_STAND_TIME];
    }

    /**
     * Average seconds in the HR zones of hrZoneBounds()
     * @return null|array [bpm]
     */
    public function fitSecondsInHrZones()
    {
        return $this->Data[self::FIT_SECONDS_HR_ZONES];
    }

    /**
     * sweat loss (Schweissverbrauch in ml)
     */
    public function fitSweetLoss(): ?int
    {
        return (int) $this->Data[self::FIT_SWEAT_LOSS];
    }

    public function avgRespirationRate(): ?int
    {
        return (int) $this->Data[self::AVG_RESPIRATION_RATE];
    }

    public function maxRespirationRate(): ?int
    {
        return (int) $this->Data[self::MAX_RESPIRATION_RATE];
    }

    public function rpe(): ?int
    {
        return (int) $this->Data[self::RPE];
    }

    public function trimp(): ?int
    {
        return (int) $this->Data[self::TRIMP];
    }

    /**
     * @return null|int [rpm]
     */
    public function cadence(): ?int
    {
        return (int) $this->Data[self::CADENCE];
    }

    /**
     * @return null|int [W]
     */
    public function power(): ?int
    {
        return (int) $this->Data[self::POWER];
    }

    public function isPowerCalculated(): ?bool
    {
        return $this->Data[self::IS_POWER_CALCULATED] === null ? null : $this->Data[self::IS_POWER_CALCULATED] == 1;
    }

    /**
     * @return null|int [cm]
     */
    public function strideLength(): ?int
    {
        return (int) $this->Data[self::STRIDE_LENGTH];
    }

    public function totalStrokes(): ?int
    {
        return (int) $this->Data[self::TOTAL_STROKES];
    }

    public function swolf(): ?int
    {
        return (int) $this->Data[self::SWOLF];
    }

    public function totalCycles(): ?int
    {
        return (int) $this->Data[self::TOTAL_CYCLES];
    }

    /**
     * @return null|int [ms]
     */
    public function groundcontact(): ?int
    {
        return (int) $this->Data[self::GROUNDCONTACT];
    }

    /**
     * @return null|int [mm]
     */
    public function verticalOscillation(): ?int
    {
        return (int) $this->Data[self::VERTICAL_OSCILLATION];
    }

    /**
     * Ground contact time balance
     * @return null|int [%oo]
     */
    public function groundContactBalance(): ?int
    {
        return (int) $this->Data[self::GROUNDCONTACT_BALANCE];
    }

    /**
     * Ground contact time balance
     * @return null|int [%]
     */
    public function groundContactBalanceLeft(): ?int
    {
        return (int) $this->Data[self::GROUNDCONTACT_BALANCE];
    }

    /**
     * Ground contact time balance
     * @return null|int [%]
     */
    public function groundContactBalanceRight(): ?int
    {
        if ($this->Data[self::GROUNDCONTACT_BALANCE] === null) {
            return null;
        }

        return 10000 - (int) $this->Data[self::GROUNDCONTACT_BALANCE];
    }

    /**
     * @return null|int [%o]
     */
    public function verticalRatio(): ?int
    {
        return (int) $this->Data[self::VERTICAL_RATIO];
    }

    /**
     * @return float|null [ms] can be negative for walking
     */
    public function flightTime(): ?float
    {
        if ($this->Data[self::CADENCE] > 0 && $this->Data[self::GROUNDCONTACT] > 0) {
            return 30000.0 / (float) $this->Data[self::CADENCE] - (float) $this->Data[self::GROUNDCONTACT];
        }

        return null;
    }

    /**
     * @return float|null [%] can be negative for walking
     */
    public function flightRatio(): ?float
    {
        if ($this->Data[self::CADENCE] > 0 && $this->Data[self::GROUNDCONTACT] > 0) {
            return 1.0 - (float) $this->Data[self::CADENCE] * (float) $this->Data[self::GROUNDCONTACT] / 30000.0;
        }

        return null;
    }

    /**
     * @return null|float [G]
     */
    public function impactGsLeft(): ?float
    {
        return (float) $this->Data[self::AVG_IMPACT_GS_LEFT];
    }

    /**
     * @return null|float [G]
     */
    public function impactGsRight(): ?float
    {
        return (float) $this->Data[self::AVG_IMPACT_GS_RIGHT];
    }

    /**
     * @return null|float [G]
     */
    public function brakingGsLeft(): ?float
    {
        return (float) $this->Data[self::AVG_BRAKING_GS_LEFT];
    }

    /**
     * @return null|float [G]
     */
    public function brakingGsRight(): ?float
    {
        return (float) $this->Data[self::AVG_BRAKING_GS_RIGHT];
    }

    /**
     * @return null|int [°]
     */
    public function footstrikeTypeLeft(): ?int
    {
        return (int) $this->Data[self::AVG_FOOTSTRIKE_TYPE_LEFT];
    }

    /**
     * @return null|int [°]
     */
    public function footstrikeTypeRight(): ?int
    {
        return (int) $this->Data[self::AVG_FOOTSTRIKE_TYPE_RIGHT];
    }

    /**
     * @return null|float [°]
     */
    public function pronationExcursionLeft(): ?float
    {
        return (float) $this->Data[self::AVG_PRONATION_EXCURSION_LEFT];
    }

    /**
     * @return null|float [°]
     */
    public function pronationExcursionRight(): ?float
    {
        return (float) $this->Data[self::AVG_PRONATION_EXCURSION_RIGHT];
    }

    public function weather(): ?\Runalyze\Data\Weather
    {
        if (! $this->Weather instanceof \Runalyze\Data\Weather) {
            $this->Weather = new Weather(
                new Weather\Temperature($this->Data[self::TEMPERATURE]),
                new Weather\Condition((int) $this->Data[self::WEATHERID]),
                new Weather\WindSpeed($this->Data[self::WINDSPEED]),
                new Weather\WindDegree($this->Data[self::WINDDEG]),
                new Weather\Humidity($this->Data[self::HUMIDITY]),
                new Weather\Pressure($this->Data[self::PRESSURE])
            );
            $this->Weather->setSource($this->Data[self::WEATHER_SOURCE]);
        }

        return $this->Weather;
    }

    public function isNight(): bool
    {
        return $this->Data[self::IS_NIGHT] == 1;
    }

    public function knowsIfItIsNight(): bool
    {
        return $this->Data[self::IS_NIGHT] !== null;
    }

    /**
     * Splits
     */
    public function splits(): ?\Runalyze\Model\Activity\Splits\Entity
    {
        if (! $this->Splits instanceof \Runalyze\Model\Activity\Splits\Entity) {
            $this->Splits = new Splits\Entity($this->Data[self::SPLITS]);
        }

        return $this->Splits;
    }

    /**
     * @return array<string, mixed>
     */
    public function splitsAdditional(): mixed
    {
        // #TSC the column is defined as ORM-JSON and should be a array, but it isn't; so make it here
        return json_decode($this->Data[self::SPLITS_ADDITIONAL], true);
    }

    public function title(): string
    {
        return $this->Data[self::TITLE];
    }

    public function partner(): ?\Runalyze\Model\Activity\Partner
    {
        if (! $this->Partner instanceof \Runalyze\Model\Activity\Partner) {
            $this->Partner = new Partner($this->Data[self::PARTNER]);
        }

        return $this->Partner;
    }

    /**
     * PaceGoal #TSC
     * @return array<string, mixed>
     */
    public function paceGoal(): mixed
    {
        // #TSC the column is defined as ORM-JSON and should be a array, but it isn't; so make it here
        return json_decode($this->Data[self::PACE_GOAL], true);
    }

    /**
     * Notes
     */
    public function notes(): ?string
    {
        return $this->Data[self::NOTES];
    }

    public function unsetRunningValues(): void
    {
        $this->set(self::VO2MAX_BY_TIME, null);
        $this->set(self::VO2MAX, null);
        $this->set(self::VO2MAX_WITH_ELEVATION, null);
    }

    public function creator(): string
    {
        return $this->Data[self::CREATOR];
    }

    /**
     * creatorDetails
     */
    public function creatorDetails(): ?string
    {
        return $this->Data[self::CREATOR_DETAILS];
    }
}

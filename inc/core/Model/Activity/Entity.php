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
     */
    public const string TIMESTAMP = 'time';

    /**
     * Key: timezone offset [minutes]
     */
    public const string TIMEZONE_OFFSET = 'timezone_offset';

    /**
     * Key: timestamp created
     */
    public const string TIMESTAMP_CREATED = 'created';

    /**
     * Key: timestamp last edit
     */
    public const string TIMESTAMP_EDITED = 'edited';

    /**
     * Key: sportid
     */
    public const string SPORTID = 'sportid';

    /**
     * Key: typeid
     */
    public const string TYPEID = 'typeid';

    /**
     * Key: is public
     */
    public const string IS_PUBLIC = 'is_public';

    /**
     * Key: is track
     */
    public const string IS_TRACK = 'is_track';

    /**
     * Key: has training goal
     */
    public const string WITH_GOAL = 'with_goal';

    /**
     * Key: distance
     */
    public const string DISTANCE = 'distance';

    /**
     * Key: time in seconds
     */
    public const string TIME_IN_SECONDS = 's';

    /**
     * Key: elapsed time
     */
    public const string ELAPSED_TIME = 'elapsed_time';

    /**
     * Key: elevation
     */
    public const string ELEVATION = 'elevation';

    /**
     * Key: climb score
     */
    public const string CLIMB_SCORE = 'climb_score';

    /**
     * Key: percentage hilly
     */
    public const string PERCENTAGE_HILLY = 'percentage_hilly';

    /**
     * Key: energy
     */
    public const string ENERGY = 'kcal';

    /**
     * Key: energy in rest
     */
    public const string ENERGY_REST = 'kcal_rest';

    /**
     * Key: average heart rate
     */
    public const string HR_AVG = 'pulse_avg';

    /**
     * Key: maximal heart rate
     */
    public const string HR_MAX = 'pulse_max';

    /**
     * Key: average heart rate active rounds
     */
    public const string HR_AVG_ACTIVE = 'pulse_avg_active';

    /**
     * Key: max heart rate for the user while the activity is done (the general max HR, not the activity one)
     */
    public const string MAX_HR_USER = 'max_hr_user';

    /**
     * Key: lower bounds of the hr zones while the activity is done
     */
    public const string HR_ZONE_BOUNDS = 'hr_zone_bounds';

    /**
     * Key: vo2max
     */
    public const string VO2MAX = 'vo2max';

    /**
     * Key: vo2max by time
     */
    public const string VO2MAX_BY_TIME = 'vo2max_by_time';

    /**
     * Key: vo2max with elevation
     */
    public const string VO2MAX_WITH_ELEVATION = 'vo2max_with_elevation';

    /**
     * Key: use vo2max
     */
    public const string USE_VO2MAX = 'use_vo2max';

    /**
     * Key: vo2max estimate from fit file
     */
    public const string FIT_VO2MAX_ESTIMATE = 'fit_vo2max_estimate';

    /**
     * Key: recovery time from fit file
     */
    public const string FIT_RECOVERY_TIME = 'fit_recovery_time';

    /**
     * Key: hrv analysis score from fit file
     */
    public const string FIT_HRV_ANALYSIS = 'fit_hrv_analysis';

    /**
     * Key: total training effect benefit from fit file
     */
    public const string FIT_TRAINING_EFFECT_BENEFIT = 'fit_training_effect_benefit';

    /**
     * Key: total training effect from fit file
     */
    public const string FIT_TRAINING_EFFECT = 'fit_training_effect';

    /**
     * Key: total anaerobic training effect from fit file
     */
    public const string FIT_ANAEROBIC_TRAINING_EFFECT = 'fit_anaerobic_training_effect';

    /**
     * Key: performance condition from fit file
     */
    public const string FIT_PERFORMANCE_CONDITION = 'fit_performance_condition';

    /**
     * Key: performance condition from fit file
     */
    public const string FIT_PERFORMANCE_CONDITION_END = 'fit_performance_condition_end';

    /**
     * Key: lactate threshold heart rate file
     */
    public const string FIT_LACTATE_THRESHOLD_HR = 'fit_lactate_threshold_hr';

    /**
     * Key: lactate threshold pace (in m/s) file
     */
    public const string FIT_LACTATE_THRESHOLD_PACE = 'fit_lactate_threshold_pace_ms';

    /**
     * Key: fit total ascent
     */
    public const string FIT_TOTAL_ASCENT = 'fit_total_ascent';

    /**
     * Key: fit total descent
     */
    public const string FIT_TOTAL_DESCENT = 'fit_total_descent';

    /**
     * Key: fit_self_evaluation_feeling
     */
    public const string FIT_SELF_ELEVATION_FEELING = 'fit_self_evaluation_feeling';

    /**
     * Key: fit_self_evaluation_perceived_effort
     */
    public const string FIT_SELF_ELEVATION_PERCEIVED_EFFORT = 'fit_self_evaluation_perceived_effort';

    /**
     * Key: fit_load_peak
     */
    public const string FIT_LOAD_PEAK = 'fit_load_peak';

    /**
     * Key: fit_run_time
     */
    public const string FIT_RUN_TIME = 'fit_run_time';

    /**
     * Key: fit_walk_time
     */
    public const string FIT_WALK_TIME = 'fit_walk_time';

    /**
     * Key: fit_stand_time
     */
    public const string FIT_STAND_TIME = 'fit_stand_time';

    /**
     * Key: for every HR_ZONE_BOUNDS the times in seconds
     */
    public const string FIT_SECONDS_HR_ZONES = 'fit_seconds_hr_zones';

    /**
     * Key: sweat loss in ml
     */
    public const string FIT_SWEAT_LOSS = 'fit_sweat_loss';

    /**
     * Key: avg_respiration_rate
     */
    public const string AVG_RESPIRATION_RATE = 'avg_respiration_rate';

    /**
     * Key: max_respiration_rate
     */
    public const string MAX_RESPIRATION_RATE = 'max_respiration_rate';

    /**
     * Key: RPE
     */
    public const string RPE = 'rpe';

    /**
     * Key: trimp
     */
    public const string TRIMP = 'trimp';

    /**
     * Key: cadence
     */
    public const string CADENCE = 'cadence';

    /**
     * Key: power
     */
    public const string POWER = 'power';

    /**
     * Key: power
     */
    public const string IS_POWER_CALCULATED = 'is_power_calculated';

    /**
     * Key: stride length
     */
    public const string STRIDE_LENGTH = 'stride_length';

    /**
     * Key: total_strokes
     */
    public const string TOTAL_STROKES = 'total_strokes';

    /**
     * Key: SWOLF
     */
    public const string SWOLF = 'swolf';

    /**
     * Key: total_cycles
     */
    public const string TOTAL_CYCLES = 'total_cycles';

    /**
     * Key: ground contact time
     */
    public const string GROUNDCONTACT = 'groundcontact';

    /**
     * Key: vertical oscillation
     */
    public const string VERTICAL_OSCILLATION = 'vertical_oscillation';

    /**
     * Key: ground contact time balance
     */
    public const string GROUNDCONTACT_BALANCE = 'groundcontact_balance';

    /**
     * Key: vertical ratio
     */
    public const string VERTICAL_RATIO = 'vertical_ratio';

    public const string AVG_IMPACT_GS_LEFT = 'avg_impact_gs_left';

    public const string AVG_IMPACT_GS_RIGHT = 'avg_impact_gs_right';

    public const string AVG_BRAKING_GS_LEFT = 'avg_braking_gs_left';

    public const string AVG_BRAKING_GS_RIGHT = 'avg_braking_gs_right';

    public const string AVG_FOOTSTRIKE_TYPE_LEFT = 'avg_footstrike_type_left';

    public const string AVG_FOOTSTRIKE_TYPE_RIGHT = 'avg_footstrike_type_right';

    public const string AVG_PRONATION_EXCURSION_LEFT = 'avg_pronation_excursion_left';

    public const string AVG_PRONATION_EXCURSION_RIGHT = 'avg_pronation_excursion_right';

    /**
     * Key: temperature
     */
    public const string TEMPERATURE = 'temperature';

    /**
     * Key: wind speed
     */
    public const string WINDSPEED = 'wind_speed';

    /**
     * Key: wind degree
     */
    public const string WINDDEG = 'wind_deg';

    /**
     * Key: humidity
     */
    public const string HUMIDITY = 'humidity';

    /**
     * Key: pressure
     */
    public const string PRESSURE = 'pressure';

    /**
     * Key: weather id
     */
    public const string WEATHERID = 'weatherid';

    /**
     * Key: weather source
     */
    public const string WEATHER_SOURCE = 'weather_source';

    /**
     * Key: is night
     */
    public const string IS_NIGHT = 'is_night';

    /**
     * Key: route id
     */
    public const string ROUTEID = 'routeid';

    /**
     * Key: route
     * @deprecated
     */
    public const string ROUTE = 'route';

    /**
     * Key: splits
     */
    public const string SPLITS = 'splits';

    /**
     * Key: splits_additional
     */
    public const string SPLITS_ADDITIONAL = 'splits_additional';

    /**
     * Key: pace_goal
     */
    public const string PACE_GOAL = 'pace_goal';

    /**
     * Key: title
     */
    public const string TITLE = 'title';

    /**
     * Key: partner
     */
    public const string PARTNER = 'partner';

    /**
     * Key: notes
     */
    public const string NOTES = 'notes';

    /**
     * Key: creator
     */
    public const string CREATOR = 'creator';

    /**
     * Key: creator details
     */
    public const string CREATOR_DETAILS = 'creator_details';

    /**
     * Key: activity id
     */
    public const string ACTIVITY_ID = 'activity_id';

    protected ?\Runalyze\Data\Weather $Weather = null;

    protected ?\Runalyze\Model\Activity\Splits\Entity $Splits = null;

    protected array $SplitsAdditional;

    protected ?Partner $Partner = null;

    protected array $PaceGoal;

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

    public function properties(): array
    {
        return self::allDatabaseProperties();
    }

    public function isArray(string $key): bool
    {
        return $key === self::HR_ZONE_BOUNDS || $key === self::FIT_SECONDS_HR_ZONES;
    }

    protected function canSet(string $key): bool
    {
        return match ($key) {
            self::TEMPERATURE, self::WINDSPEED, self::WINDDEG, self::HUMIDITY, self::PRESSURE, self::WEATHERID, self::WEATHER_SOURCE, self::PARTNER, self::SPLITS => false,
            default => true,
        };
    }

    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::TYPEID, self::TIMESTAMP_CREATED, self::TIMESTAMP_EDITED, self::TIMEZONE_OFFSET, self::DISTANCE, self::ELAPSED_TIME, self::ELEVATION, self::CLIMB_SCORE, self::PERCENTAGE_HILLY, self::ENERGY, self::ENERGY_REST, self::HR_AVG, self::HR_MAX, self::HR_AVG_ACTIVE, self::MAX_HR_USER, self::HR_ZONE_BOUNDS, self::VO2MAX, self::VO2MAX_BY_TIME, self::VO2MAX_WITH_ELEVATION, self::FIT_VO2MAX_ESTIMATE, self::FIT_RECOVERY_TIME, self::FIT_HRV_ANALYSIS, self::FIT_TRAINING_EFFECT_BENEFIT, self::FIT_TRAINING_EFFECT, self::FIT_ANAEROBIC_TRAINING_EFFECT, self::FIT_PERFORMANCE_CONDITION, self::FIT_PERFORMANCE_CONDITION_END, self::FIT_LACTATE_THRESHOLD_HR, self::FIT_LACTATE_THRESHOLD_PACE, self::FIT_TOTAL_ASCENT, self::FIT_TOTAL_DESCENT, self::FIT_SELF_ELEVATION_FEELING, self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT, self::FIT_LOAD_PEAK, self::FIT_RUN_TIME, self::FIT_WALK_TIME, self::FIT_STAND_TIME, self::FIT_SECONDS_HR_ZONES, self::FIT_SWEAT_LOSS, self::AVG_RESPIRATION_RATE, self::MAX_RESPIRATION_RATE, self::RPE, self::TRIMP, self::CADENCE, self::POWER, self::IS_POWER_CALCULATED, self::TOTAL_STROKES, self::SWOLF, self::STRIDE_LENGTH, self::TOTAL_CYCLES, self::GROUNDCONTACT, self::GROUNDCONTACT_BALANCE, self::VERTICAL_OSCILLATION, self::VERTICAL_RATIO, self::AVG_IMPACT_GS_LEFT, self::AVG_IMPACT_GS_RIGHT, self::AVG_BRAKING_GS_LEFT, self::AVG_BRAKING_GS_RIGHT, self::AVG_FOOTSTRIKE_TYPE_LEFT, self::AVG_FOOTSTRIKE_TYPE_RIGHT, self::AVG_PRONATION_EXCURSION_LEFT, self::AVG_PRONATION_EXCURSION_RIGHT, self::TEMPERATURE, self::WINDSPEED, self::WINDDEG, self::HUMIDITY, self::PRESSURE, self::WEATHER_SOURCE, self::IS_NIGHT, self::NOTES, self::CREATOR_DETAILS, self::ROUTEID, self::ACTIVITY_ID, self::PACE_GOAL => true,
            default => false,
        };
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

    private function nullableInt(string $key): ?int
    {
        return $this->Data[$key] === null ? null : (int) $this->Data[$key];
    }

    private function nullableFloat(string $key): ?float
    {
        return $this->Data[$key] === null ? null : (float) $this->Data[$key];
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
        return $this->nullableInt(self::TYPEID);
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
     * @return ?float [km]
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
     * @return ?int [s]
     */
    public function elapsedTime(): ?int
    {
        if ($this->Data[self::ELAPSED_TIME] === null) {
            return null;
        }

        return (int) $this->Data[self::ELAPSED_TIME];
    }

    /**
     * @return ?int [m]
     */
    public function elevation(): ?int
    {
        if ($this->Data[self::ELEVATION] === null) {
            return null;
        }

        return (int) $this->Data[self::ELEVATION];
    }

    /**
     * @return ?float [0.0 .. 1.0]
     */
    public function climbScore(): ?float
    {
        if ($this->Data[self::CLIMB_SCORE] === null) {
            return null;
        }

        return (float) $this->Data[self::CLIMB_SCORE];
    }

    /**
     * @return ?float [0.00 .. 1.00]
     */
    public function percentageHilly(): ?float
    {
        if ($this->Data[self::PERCENTAGE_HILLY] === null) {
            return null;
        }

        return (float) $this->Data[self::PERCENTAGE_HILLY];
    }

    /**
     * @return ?int [kcal]
     */
    public function energy(): ?int
    {
        return $this->nullableInt(self::ENERGY);
    }

    /**
     * #TSC
     * @return ?int [kcal]
     */
    public function energyInRest(): ?int
    {
        return $this->nullableInt(self::ENERGY_REST);
    }

    /**
     * Average heart rate
     * @return ?int [bpm]
     */
    public function hrAvg(): ?int
    {
        return $this->nullableInt(self::HR_AVG);
    }

    /**
     * Maximal heart rate
     * @return ?int [bpm]
     */
    public function hrMax(): ?int
    {
        return $this->nullableInt(self::HR_MAX);
    }

    /**
     * Average heart rate active rounds
     * @return ?int [bpm]
     */
    public function hrAvgActive(): ?int
    {
        return $this->nullableInt(self::HR_AVG_ACTIVE);
    }

    /**
     * Average max heart rate of the user (general, not while this activity)
     * @return ?int [bpm]
     */
    public function maxHrUser(): ?int
    {
        return $this->nullableInt(self::MAX_HR_USER);
    }

    /**
     * Average lower bounds of the hr zones
     * @return null|array [bpm]
     */
    public function hrZoneBounds(): ?array
    {
        return $this->Data[self::HR_ZONE_BOUNDS];
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function vo2maxByHeartRate(): ?float
    {
        return $this->nullableFloat(self::VO2MAX);
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function vo2maxByTime(): ?float
    {
        return $this->nullableFloat(self::VO2MAX_BY_TIME);
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function vo2maxWithElevation(): ?float
    {
        return $this->nullableFloat(self::VO2MAX_WITH_ELEVATION);
    }

    public function usesVO2max(): bool
    {
        return $this->Data[self::USE_VO2MAX] == 1;
    }

    /**
     * VO2max estimate from fit file
     * @return ?float [ml/kg/min]
     */
    public function fitVO2maxEstimate(): ?float
    {
        return $this->nullableFloat(self::FIT_VO2MAX_ESTIMATE);
    }

    /**
     * Recovery time advisor from fit file
     * @return ?int [min]
     */
    public function fitRecoveryTime(): ?int
    {
        return $this->nullableInt(self::FIT_RECOVERY_TIME);
    }

    /**
     * HRV analysis score from fit file
     */
    public function fitHRVscore(): ?int
    {
        return $this->nullableInt(self::FIT_HRV_ANALYSIS);
    }

    /**
     * Total training effect benefit
     * 1=Recovery, 2=Base, 3=Tempo, 4=Threshold, 5=VO2 Max, 6=Anaerobic, 7=Sprint
     */
    public function fitTrainingEffectBenefit(): ?int
    {
        return $this->nullableInt(self::FIT_TRAINING_EFFECT_BENEFIT);
    }

    /**
     * Total training effect
     */
    public function fitTrainingEffect(): ?float
    {
        return $this->nullableFloat(self::FIT_TRAINING_EFFECT);
    }

    /**
     * Total anaerobic training effect
     */
    public function fitAnaerobicTrainingEffect(): ?float
    {
        return $this->nullableFloat(self::FIT_ANAEROBIC_TRAINING_EFFECT);
    }

    /**
     * Performance condition
     */
    public function fitPerformanceCondition(): ?int
    {
        return $this->nullableInt(self::FIT_PERFORMANCE_CONDITION);
    }

    /**
     * Ending performance condition
     */
    public function fitPerformanceConditionEnd(): ?int
    {
        return $this->nullableInt(self::FIT_PERFORMANCE_CONDITION_END);
    }

    /**
     * Lactate threshold HR
     */
    public function fitLactateThresholdHR(): ?int
    {
        return $this->nullableInt(self::FIT_LACTATE_THRESHOLD_HR);
    }

    /**
     * Lactate threshold pace (in m/s)
     */
    public function fitLactateThresholdPace(): ?float
    {
        return $this->nullableFloat(self::FIT_LACTATE_THRESHOLD_PACE);
    }

    public function fitTotalAscent(): ?int
    {
        return $this->nullableInt(self::FIT_TOTAL_ASCENT);
    }

    public function fitTotalDescent(): ?int
    {
        return $this->nullableInt(self::FIT_TOTAL_DESCENT);
    }

    /**
     * fit fitSelfEvaluationFeeling
     */
    public function fitSelfEvaluationFeeling(): ?int
    {
        return $this->nullableInt(self::FIT_SELF_ELEVATION_FEELING);
    }

    /**
     * fit fitSelfEvaluationPerceivedEffort
     */
    public function fitSelfEvaluationPerceivedEffort(): ?int
    {
        return $this->nullableInt(self::FIT_SELF_ELEVATION_PERCEIVED_EFFORT);
    }

    /**
     * fit fitLoadPeak
     */
    public function fitLoadPeak(): ?int
    {
        return $this->nullableInt(self::FIT_LOAD_PEAK);
    }

    /**
     * fit RWD run time #TSC
     * @return ?int [s]
     */
    public function fitRunTime(): ?int
    {
        return $this->nullableInt(self::FIT_RUN_TIME);
    }

    /**
     * fit RWD walk time #TSC
     * @return ?int [s]
     */
    public function fitWalkTime(): ?int
    {
        return $this->nullableInt(self::FIT_WALK_TIME);
    }

    /**
     * fit RWD run time #TSC
     * @return ?int [s]
     */
    public function fitStandTime(): ?int
    {
        return $this->nullableInt(self::FIT_STAND_TIME);
    }

    /**
     * Average seconds in the HR zones of hrZoneBounds()
     * @return null|array [bpm]
     */
    public function fitSecondsInHrZones(): ?array
    {
        return $this->Data[self::FIT_SECONDS_HR_ZONES];
    }

    /**
     * sweat loss (Schweissverbrauch in ml)
     */
    public function fitSweatLoss(): ?int
    {
        return $this->nullableInt(self::FIT_SWEAT_LOSS);
    }

    public function avgRespirationRate(): ?int
    {
        return $this->nullableInt(self::AVG_RESPIRATION_RATE);
    }

    public function maxRespirationRate(): ?int
    {
        return $this->nullableInt(self::MAX_RESPIRATION_RATE);
    }

    public function rpe(): ?int
    {
        return $this->nullableInt(self::RPE);
    }

    public function trimp(): ?int
    {
        return $this->nullableInt(self::TRIMP);
    }

    /**
     * @return ?int [rpm]
     */
    public function cadence(): ?int
    {
        return $this->nullableInt(self::CADENCE);
    }

    /**
     * @return ?int [W]
     */
    public function power(): ?int
    {
        return $this->nullableInt(self::POWER);
    }

    public function isPowerCalculated(): ?bool
    {
        return $this->Data[self::IS_POWER_CALCULATED] === null ? null : $this->Data[self::IS_POWER_CALCULATED] == 1;
    }

    /**
     * @return ?int [cm]
     */
    public function strideLength(): ?int
    {
        return $this->nullableInt(self::STRIDE_LENGTH);
    }

    public function totalStrokes(): ?int
    {
        return $this->nullableInt(self::TOTAL_STROKES);
    }

    public function swolf(): ?int
    {
        return $this->nullableInt(self::SWOLF);
    }

    public function totalCycles(): ?int
    {
        return $this->nullableInt(self::TOTAL_CYCLES);
    }

    /**
     * @return ?int [ms]
     */
    public function groundcontact(): ?int
    {
        return $this->nullableInt(self::GROUNDCONTACT);
    }

    /**
     * @return ?int [mm]
     */
    public function verticalOscillation(): ?int
    {
        return $this->nullableInt(self::VERTICAL_OSCILLATION);
    }

    /**
     * Ground contact time balance
     * @return ?int [%oo]
     */
    public function groundContactBalance(): ?int
    {
        return $this->nullableInt(self::GROUNDCONTACT_BALANCE);
    }

    /**
     * Ground contact time balance
     * @return ?int [%]
     */
    public function groundContactBalanceLeft(): ?int
    {
        return $this->nullableInt(self::GROUNDCONTACT_BALANCE);
    }

    /**
     * Ground contact time balance
     * @return ?int [%]
     */
    public function groundContactBalanceRight(): ?int
    {
        $balance = $this->nullableInt(self::GROUNDCONTACT_BALANCE);

        if ($balance === null) {
            return null;
        }

        return 10000 - $balance;
    }

    /**
     * @return ?int [%o]
     */
    public function verticalRatio(): ?int
    {
        return $this->nullableInt(self::VERTICAL_RATIO);
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
     * @return ?float [G]
     */
    public function impactGsLeft(): ?float
    {
        return $this->nullableFloat(self::AVG_IMPACT_GS_LEFT);
    }

    /**
     * @return ?float [G]
     */
    public function impactGsRight(): ?float
    {
        return $this->nullableFloat(self::AVG_IMPACT_GS_RIGHT);
    }

    /**
     * @return ?float [G]
     */
    public function brakingGsLeft(): ?float
    {
        return $this->nullableFloat(self::AVG_BRAKING_GS_LEFT);
    }

    /**
     * @return ?float [G]
     */
    public function brakingGsRight(): ?float
    {
        return $this->nullableFloat(self::AVG_BRAKING_GS_RIGHT);
    }

    /**
     * @return ?int [°]
     */
    public function footstrikeTypeLeft(): ?int
    {
        return $this->nullableInt(self::AVG_FOOTSTRIKE_TYPE_LEFT);
    }

    /**
     * @return ?int [°]
     */
    public function footstrikeTypeRight(): ?int
    {
        return $this->nullableInt(self::AVG_FOOTSTRIKE_TYPE_RIGHT);
    }

    /**
     * @return ?float [°]
     */
    public function pronationExcursionLeft(): ?float
    {
        return $this->nullableFloat(self::AVG_PRONATION_EXCURSION_LEFT);
    }

    /**
     * @return ?float [°]
     */
    public function pronationExcursionRight(): ?float
    {
        return $this->nullableFloat(self::AVG_PRONATION_EXCURSION_RIGHT);
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

    public function partner(): ?Partner
    {
        if (! $this->Partner instanceof Partner) {
            $this->Partner = new Partner($this->Data[self::PARTNER]);
        }

        return $this->Partner;
    }

    /**
     * PaceGoal #TSC
     * @return ?array<string, mixed>
     */
    public function paceGoal(): mixed
    {
        if ($this->Data[self::PACE_GOAL] === null) {
            return null;
        }

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

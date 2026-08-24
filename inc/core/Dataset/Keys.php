<?php

/**
 * This file contains class::Keys
 * @package Runalyze
 */

namespace Runalyze\Dataset;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for dataset keys and their internal ids
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset
 */
final class Keys extends AbstractEnum
{
    /**
     * @var int
     */
    public const SPORT = 1;

    /**
     * @var int
     */
    public const TYPE = 2;

    /**
     * @var int
     */
    public const DAYTIME = 3;

    /**
     * @var int
     */
    public const SHARED_LINK = 4;

    /**
     * @var int
     */
    public const DISTANCE = 5;

    /**
     * @var int
     */
    public const DURATION = 6;

    /**
     * @var int
     */
    public const PACE = 7;

    /**
     * @var int
     */
    public const ELAPSED_TIME = 8;

    /**
     * @var int
     */
    public const ELEVATION = 9;

    /**
     * @var int
     */
    public const ENERGY = 10;

    /**
     * @var int
     */
    public const HEARTRATE_AVG = 11;

    /**
     * @var int
     */
    public const HEARTRATE_MAX = 12;

    /**
     * @var int
     */
    public const VO2MAX_ICON = 13;

    /**
     * @var int
     */
    public const VO2MAX_VALUE = 14;

    /**
     * @var int
     */
    public const FIT_VO2MAX_ESTIMATE = 15;

    /**
     * @var int
     */
    public const FIT_RECOVERY_TIME = 16;

    /**
     * @var int
     */
    public const FIT_HRV_ANALYSIS = 17;

    /**
     * @var int
     */
    public const TRIMP = 19;

    /**
     * @var int
     */
    public const CADENCE = 20;

    /**
     * @var int
     */
    public const POWER = 21;

    /**
     * @var int
     */
    public const SWOLF = 22;

    /**
     * @var int
     */
    public const STRIDE_LENGTH = 23;

    /**
     * @var int
     */
    public const GROUNDCONTACT = 24;

    /**
     * @var int
     */
    public const VERTICAL_OSCILLATION = 25;

    /**
     * @var int
     */
    public const TEMPERATURE = 26;

    /**
     * @var int
     */
    public const WEATHER = 27;

    /**
     * @var int
     */
    public const ROUTE = 28;

    /**
     * @var int
     */
    public const SPLITS = 29;

    /**
     * @var int
     */
    public const TITLE = 30;

    /**
     * @var int
     */
    public const TRAININGPARTNER = 31;

    /**
     * @var int
     */
    public const TOTAL_STROKES = 32;

    /**
     * @var int
     */
    public const VERTICAL_RATIO = 33;

    /**
     * @var int
     */
    public const GROUNDCONTACT_BALANCE = 34;

    /**
     * @var int
     */
    public const TAGS = 35;

    /**
     * @var int
     */
    public const COMPLETE_EQUIPMENT = 36;

    /**
     * @var int
     */
    public const MAIN_EQUIPMENT = 37;

    /**
     * @var int
     */
    public const WIND = 38;

    /**
     * @var int
     */
    public const HUMIDITY = 39;

    /**
     * @var int
     */
    public const AIR_PRESSURE = 40;

    /**
     * @var int
     */
    public const WIND_CHILL = 41;

    /**
     * @var int
     */
    public const SETTING = 42;

    /**
     * @var int
     */
    public const FIT_TRAINING_EFFECT = 43;

    /**
     * @var int
     */
    public const RPE = 44;

    /**
     * @var int
     */
    public const GRADIENT = 45;

    /**
     * @var int
     */
    public const FIT_PERFORMANCE_CONDITION = 46;

    /**
     * @var int
     */
    public const RACE_RESULT = 47;

    /**
     * @var int
     */
    public const HEAT_INDEX = 48;

    /**
     * @var int
     */
    public const FIT_PERFORMANCE_CONDITION_START = 49;

    /**
     * @var int
     */
    public const FIT_PERFORMANCE_CONDITION_END = 50;

    /**
     * @var int
     */
    public const CLIMB_SCORE = 51;

    /**
     * @var int
     */
    public const PERCENTAGE_HILLY = 52;

    /**
     * @var int
     */
    public const TOOLS = 53;

    /**
     * @var int
     */
    public const FLIGHT_TIME = 54;

    /**
     * @var int
     */
    public const FLIGHT_RATIO = 55;

    /**
     * @var int
     */
    public const AVG_IMPACT_GS = 56;

    /**
     * @var int
     */
    public const AVG_BRAKING_GS = 57;

    /**
     * @var int
     */
    public const AVG_FOOTSTRIKE_TYPE = 58;

    /**
     * @var int
     */
    public const AVG_PRONATION_EXCURSION = 59;

    /**
     * @var int
     */
    public const FIT_ANAEROBIC_TRAINING_EFFECT = 60;

    /**
     * @var int
     */
    public const AVG_RESPIRATION_RATE = 61;

    /**
     * @var int
     */
    public const HEARTRATE_AVG_ACTIVE = 62;

    /**
     * @var int
     */
    public const TOTAL_CYCLES = 63;

    /**
     * @var int
     */
    public const FIT_LOAD_PEAK = 64;

    /**
     * @var int
     */
    public const OPEN_TAB = 65;

    /**
     * @var int
     */
    public const FIT_TRAINING_EFFECT_BENEFIT = 66;

    /**
     * @var int
     */
    public const WITH_GOAL = 67;

    /**
     * @var array|null
     */
    private static $ClassNames;

    /**
     * @var array instances of key objects
     */
    private static $Instances = [];

    /**
     * @var bool
     */
    private static $KeepInstances = false;

    /**
     * @param bool $flag
     */
    public static function keepInstances($flag = true)
    {
        self::$KeepInstances = $flag;
    }

    /**
     * Get key
     * @param int $keyid int from internal enum
     * @return \Runalyze\Dataset\Keys\AbstractKey
     * @throws \InvalidArgumentException
     */
    public static function get($keyid)
    {
        if (self::$ClassNames === null) {
            self::generateClassNamesArray();
        }

        if (! isset(self::$ClassNames[$keyid])) {
            throw new \InvalidArgumentException('Invalid keyid "' . $keyid . '".');
        }

        $className = 'Runalyze\\Dataset\\Keys\\' . self::$ClassNames[$keyid];

        if (self::$KeepInstances) {
            if (! isset(self::$Instances[$keyid])) {
                self::$Instances[$keyid] = new $className();
            }

            return self::$Instances[$keyid];
        }

        return new $className();
    }

    /**
     * Generate static array with class names
     */
    private static function generateClassNamesArray()
    {
        self::$ClassNames = [
            self::SPORT => 'Sport',
            self::TYPE => 'Type',
            self::DAYTIME => 'Daytime',
            self::SHARED_LINK => 'SharedLink',
            self::DISTANCE => 'Distance',
            self::DURATION => 'Duration',
            self::PACE => 'Pace',
            self::ELAPSED_TIME => 'ElapsedTime',
            self::ELEVATION => 'Elevation',
            self::ENERGY => 'Energy',
            self::HEARTRATE_AVG => 'HeartrateAverage',
            self::HEARTRATE_MAX => 'HeartrateMaximum',
            self::VO2MAX_ICON => 'VO2maxIcon',
            self::VO2MAX_VALUE => 'VO2maxValue',
            self::FIT_VO2MAX_ESTIMATE => 'FitVO2maxEstimate',
            self::FIT_RECOVERY_TIME => 'FitRecoveryTime',
            self::FIT_HRV_ANALYSIS => 'FitHrvAnalysis',
            self::TRIMP => 'Trimp',
            self::CADENCE => 'Cadence',
            self::POWER => 'Power',
            self::SWOLF => 'Swolf',
            self::STRIDE_LENGTH => 'StrideLength',
            self::GROUNDCONTACT => 'Groundcontact',
            self::VERTICAL_OSCILLATION => 'VerticalOscillation',
            self::TEMPERATURE => 'Temperature',
            self::WEATHER => 'Weather',
            self::ROUTE => 'Route',
            self::SPLITS => 'Splits',
            self::TITLE => 'Title',
            self::TRAININGPARTNER => 'TrainingPartner',
            self::TOTAL_STROKES => 'TotalStrokes',
            self::VERTICAL_RATIO => 'VerticalRatio',
            self::GROUNDCONTACT_BALANCE => 'GroundcontactBalance',
            self::TAGS => 'Tags',
            self::COMPLETE_EQUIPMENT => 'CompleteEquipment',
            self::MAIN_EQUIPMENT => 'MainEquipment',
            self::WIND => 'Wind',
            self::HUMIDITY => 'Humidity',
            self::AIR_PRESSURE => 'AirPressure',
            self::WIND_CHILL => 'WindChill',
            self::SETTING => 'Setting',
            self::FIT_TRAINING_EFFECT => 'FitTrainingEffect',
            self::RPE => 'RPE',
            self::GRADIENT => 'Gradient',
            self::FIT_PERFORMANCE_CONDITION => 'FitPerformanceCondition',
            self::RACE_RESULT => 'RaceResult',
            self::HEAT_INDEX => 'HeatIndex',
            self::FIT_PERFORMANCE_CONDITION_START => 'FitPerformanceConditionStart',
            self::FIT_PERFORMANCE_CONDITION_END => 'FitPerformanceConditionEnd',
            self::CLIMB_SCORE => 'ClimbScore',
            self::PERCENTAGE_HILLY => 'PercentageHilly',
            self::TOOLS => 'Tools',
            self::FLIGHT_TIME => 'FlightTime',
            self::FLIGHT_RATIO => 'FlightRatio',
            self::AVG_IMPACT_GS => 'AverageImpactGs',
            self::AVG_BRAKING_GS => 'AverageBrakingGs',
            self::AVG_FOOTSTRIKE_TYPE => 'AverageFootstrikeType',
            self::AVG_PRONATION_EXCURSION => 'AveragePronationExcursion',
            self::FIT_ANAEROBIC_TRAINING_EFFECT => 'FitAnaerobicTrainingEffect',
            self::AVG_RESPIRATION_RATE => 'AverageRespirationRate',
            self::HEARTRATE_AVG_ACTIVE => 'HeartrateAverageActive',
            self::TOTAL_CYCLES => 'TotalCycles',
            self::FIT_LOAD_PEAK => 'FitLoadPeak',
            self::OPEN_TAB => 'OpenTab',
            self::FIT_TRAINING_EFFECT_BENEFIT => 'FitTrainingEffectBenefit',
            self::WITH_GOAL => 'WithGoal',
        ];
    }
}

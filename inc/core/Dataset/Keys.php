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
    public const int SPORT = 1;

    public const int TYPE = 2;

    public const int DAYTIME = 3;

    public const int SHARED_LINK = 4;

    public const int DISTANCE = 5;

    public const int DURATION = 6;

    public const int PACE = 7;

    public const int ELAPSED_TIME = 8;

    public const int ELEVATION = 9;

    public const int ENERGY = 10;

    public const int HEARTRATE_AVG = 11;

    public const int HEARTRATE_MAX = 12;

    public const int VO2MAX_ICON = 13;

    public const int VO2MAX_VALUE = 14;

    public const int FIT_VO2MAX_ESTIMATE = 15;

    public const int FIT_RECOVERY_TIME = 16;

    public const int FIT_HRV_ANALYSIS = 17;

    public const int TRIMP = 19;

    public const int CADENCE = 20;

    public const int POWER = 21;

    public const int SWOLF = 22;

    public const int STRIDE_LENGTH = 23;

    public const int GROUNDCONTACT = 24;

    public const int VERTICAL_OSCILLATION = 25;

    public const int TEMPERATURE = 26;

    public const int WEATHER = 27;

    public const int ROUTE = 28;

    public const int SPLITS = 29;

    public const int TITLE = 30;

    public const int TRAININGPARTNER = 31;

    public const int TOTAL_STROKES = 32;

    public const int VERTICAL_RATIO = 33;

    public const int GROUNDCONTACT_BALANCE = 34;

    public const int TAGS = 35;

    public const int COMPLETE_EQUIPMENT = 36;

    public const int MAIN_EQUIPMENT = 37;

    public const int WIND = 38;

    public const int HUMIDITY = 39;

    public const int AIR_PRESSURE = 40;

    public const int WIND_CHILL = 41;

    public const int SETTING = 42;

    public const int FIT_TRAINING_EFFECT = 43;

    public const int RPE = 44;

    public const int GRADIENT = 45;

    public const int FIT_PERFORMANCE_CONDITION = 46;

    public const int RACE_RESULT = 47;

    public const int HEAT_INDEX = 48;

    public const int FIT_PERFORMANCE_CONDITION_START = 49;

    public const int FIT_PERFORMANCE_CONDITION_END = 50;

    public const int CLIMB_SCORE = 51;

    public const int PERCENTAGE_HILLY = 52;

    public const int TOOLS = 53;

    public const int FLIGHT_TIME = 54;

    public const int FLIGHT_RATIO = 55;

    public const int AVG_IMPACT_GS = 56;

    public const int AVG_BRAKING_GS = 57;

    public const int AVG_FOOTSTRIKE_TYPE = 58;

    public const int AVG_PRONATION_EXCURSION = 59;

    public const int FIT_ANAEROBIC_TRAINING_EFFECT = 60;

    public const int AVG_RESPIRATION_RATE = 61;

    public const int HEARTRATE_AVG_ACTIVE = 62;

    public const int TOTAL_CYCLES = 63;

    public const int FIT_LOAD_PEAK = 64;

    public const int OPEN_TAB = 65;

    public const int FIT_TRAINING_EFFECT_BENEFIT = 66;

    public const int WITH_GOAL = 67;

    private static ?array $ClassNames = null;

    /**
     * @var array instances of key objects
     */
    private static array $Instances = [];

    /**
     * @var bool
     */
    private static $KeepInstances = false;

    /**
     * @param bool $flag
     */
    public static function keepInstances($flag = true): void
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
            self::$Instances[$keyid] ??= new $className();
            return self::$Instances[$keyid];
        }

        return new $className();
    }

    /**
     * Generate static array with class names
     */
    private static function generateClassNamesArray(): void
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

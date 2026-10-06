<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

final class QueryValues extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    public const string PACE = 'pace';

    public const string DISTANCE = 'distance';

    public const string DURATION = 'duration';

    public const string HEART_RATE_AVERAGE = 'heart_rate_average';

    public const string HEART_RATE_MAXIMUM = 'heart_rate_maximum';

    public const string TRIMP = 'trimp';

    public const string RPE = 'rpe';

    public const string POWER = 'power';

    public const string CADENCE = 'cadence';

    public const string VO2MAX = 'vo2max';

    public const string CLIMB_SCORE = 'climb_score';

    public const string PERCENTAGE_HILLY = 'percentage_hilly';

    public const string VO2MAX_WITH_ELEVATION = 'vo2max_with_elevation';

    public const string GROUND_CONTACT_TIME = 'ground_contact_time';

    public const string GROUND_CONTACT_BALANCE = 'ground_contact_balance';

    public const string VERTICAL_OSCILLATION = 'vertical_oscillation';

    public const string FLIGHT_TIME = 'flight_time';

    public const string FLIGHT_RATIO = 'flight_ratio';

    public const string IMPACT_GS_LEFT = 'impact_gs_left';

    public const string IMPACT_GS_RIGHT = 'impact_gs_right';

    public const string BRAKING_GS_LEFT = 'braking_gs_left';

    public const string BRAKING_GS_RIGHT = 'braking_gs_right';

    public const string FOOTSTRIKE_TYPE_LEFT = 'footstrike_type_left';

    public const string FOOTSTRIKE_TYPE_RIGHT = 'footstrike_type_right';

    public const string PRONATION_EXCURSION_LEFT = 'pronation_excursion_left';

    public const string PRONATION_EXCURSION_RIGHT = 'pronation_excursion_right';

    public const string FIT_HRV_ANALYSIS = 'fit_hrv_analysis';

    public const string FIT_PERFORMANCE_CONDITION_START = 'fit_performance_condition_start';

    public const string FIT_PERFORMANCE_CONDITION_END = 'fit_performance_condition_end';

    public const string FIT_RECOVERY_TIME = 'fit_recovery_time';

    public const string FIT_TRAINING_EFFECT = 'fit_training_effect';

    public const string FIT_VO2MAX_ESTIMATE = 'fit_vo2max_estimate';

    public const string WEATHER_TEMPERATURE = 'temperature';

    public const string WEATHER_HUMIDITY = 'humidity';

    public const string WEATHER_PRESSURE = 'pressure';

    public const string WEATHER_WIND_SPEED = 'wind_speed';
}

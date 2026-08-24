<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\Common\Enum\AbstractEnumFactoryTrait;

final class QueryValues extends AbstractEnum
{
    use AbstractEnumFactoryTrait;

    /**
     * @var string
     */
    public const PACE = 'pace';

    /**
     * @var string
     */
    public const DISTANCE = 'distance';

    /**
     * @var string
     */
    public const DURATION = 'duration';

    /**
     * @var string
     */
    public const HEART_RATE_AVERAGE = 'heart_rate_average';

    /**
     * @var string
     */
    public const HEART_RATE_MAXIMUM = 'heart_rate_maximum';

    /**
     * @var string
     */
    public const TRIMP = 'trimp';

    /**
     * @var string
     */
    public const RPE = 'rpe';

    /**
     * @var string
     */
    public const POWER = 'power';

    /**
     * @var string
     */
    public const CADENCE = 'cadence';

    /**
     * @var string
     */
    public const VO2MAX = 'vo2max';

    /**
     * @var string
     */
    public const CLIMB_SCORE = 'climb_score';

    /**
     * @var string
     */
    public const PERCENTAGE_HILLY = 'percentage_hilly';

    /**
     * @var string
     */
    public const VO2MAX_WITH_ELEVATION = 'vo2max_with_elevation';

    /**
     * @var string
     */
    public const GROUND_CONTACT_TIME = 'ground_contact_time';

    /**
     * @var string
     */
    public const GROUND_CONTACT_BALANCE = 'ground_contact_balance';

    /**
     * @var string
     */
    public const VERTICAL_OSCILLATION = 'vertical_oscillation';

    /**
     * @var string
     */
    public const FLIGHT_TIME = 'flight_time';

    /**
     * @var string
     */
    public const FLIGHT_RATIO = 'flight_ratio';

    /**
     * @var string
     */
    public const IMPACT_GS_LEFT = 'impact_gs_left';

    /**
     * @var string
     */
    public const IMPACT_GS_RIGHT = 'impact_gs_right';

    /**
     * @var string
     */
    public const BRAKING_GS_LEFT = 'braking_gs_left';

    /**
     * @var string
     */
    public const BRAKING_GS_RIGHT = 'braking_gs_right';

    /**
     * @var string
     */
    public const FOOTSTRIKE_TYPE_LEFT = 'footstrike_type_left';

    /**
     * @var string
     */
    public const FOOTSTRIKE_TYPE_RIGHT = 'footstrike_type_right';

    /**
     * @var string
     */
    public const PRONATION_EXCURSION_LEFT = 'pronation_excursion_left';

    /**
     * @var string
     */
    public const PRONATION_EXCURSION_RIGHT = 'pronation_excursion_right';

    /**
     * @var string
     */
    public const FIT_HRV_ANALYSIS = 'fit_hrv_analysis';

    /**
     * @var string
     */
    public const FIT_PERFORMANCE_CONDITION_START = 'fit_performance_condition_start';

    /**
     * @var string
     */
    public const FIT_PERFORMANCE_CONDITION_END = 'fit_performance_condition_end';

    /**
     * @var string
     */
    public const FIT_RECOVERY_TIME = 'fit_recovery_time';

    /**
     * @var string
     */
    public const FIT_TRAINING_EFFECT = 'fit_training_effect';

    /**
     * @var string
     */
    public const FIT_VO2MAX_ESTIMATE = 'fit_vo2max_estimate';

    /**
     * @var string
     */
    public const WEATHER_TEMPERATURE = 'temperature';

    /**
     * @var string
     */
    public const WEATHER_HUMIDITY = 'humidity';

    /**
     * @var string
     */
    public const WEATHER_PRESSURE = 'pressure';

    /**
     * @var string
     */
    public const WEATHER_WIND_SPEED = 'wind_speed';
}

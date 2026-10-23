<?php

namespace Runalyze\Metrics\HeartRate\Unit;

use Runalyze\Common\Enum\AbstractEnum;

class HeartRateEnum extends AbstractEnum
{
    public const int BEATS_PER_MINUTE = 0;

    public const int PERCENT_MAXIMUM = 1;

    public const int PERCENT_RESERVE = 2;

    /**
     * @param int $enum from internal enum
     *
     * @throws \InvalidArgumentException
     */
    public static function get(int $enum, int $maximalHeartRate, int $restingHeartRate): AbstractHeartRateUnit
    {
        if ($enum === self::BEATS_PER_MINUTE) {
            return new BeatsPerMinute();
        }

        if ($enum === self::PERCENT_MAXIMUM) {
            return new PercentMaximum($maximalHeartRate);
        }

        if ($enum === self::PERCENT_RESERVE) {
            return new PercentReserve($maximalHeartRate, $restingHeartRate);
        }

        throw new \InvalidArgumentException('Unknown heart rate unit enum "' . $enum . '".');
    }
}

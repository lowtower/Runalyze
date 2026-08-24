<?php

namespace Runalyze\Metrics\HeartRate\Unit;

use Runalyze\Common\Enum\AbstractEnum;

class HeartRateEnum extends AbstractEnum
{
    /**
     * @var int
     */
    public const BEATS_PER_MINUTE = 0;

    /**
     * @var int
     */
    public const PERCENT_MAXIMUM = 1;

    /**
     * @var int
     */
    public const PERCENT_RESERVE = 2;

    /**
     * @param int $enum from internal enum
     * @param int $maximalHeartRate
     * @param int $restingHeartRate
     * @return AbstractHeartRateUnit
     *
     * @throws \InvalidArgumentException
     */
    public static function get($enum, $maximalHeartRate, $restingHeartRate)
    {
        if ($enum == self::BEATS_PER_MINUTE) {
            return new BeatsPerMinute();
        }

        if ($enum == self::PERCENT_MAXIMUM) {
            return new PercentMaximum($maximalHeartRate);
        }

        if ($enum == self::PERCENT_RESERVE) {
            return new PercentReserve($maximalHeartRate, $restingHeartRate);
        }

        throw new \InvalidArgumentException('Unknown heart rate unit enum "' . $enum . '".');
    }
}

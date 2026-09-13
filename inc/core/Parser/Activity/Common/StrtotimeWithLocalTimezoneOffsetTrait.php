<?php

namespace Runalyze\Parser\Activity\Common;

use Runalyze\Util\LocalTime;

trait StrtotimeWithLocalTimezoneOffsetTrait
{
    /**
     * Timestamps are given in UTC but local timezone offset has to be considered!
     */
    protected function strtotime(string $string): int
    {
        if (substr($string, -1) == 'Z') {
            return LocalTime::fromServerTime(strtotime(substr($string, 0, -1) . ' UTC'))->getTimestamp();
        }

        return LocalTime::fromString($string)->getTimestamp();
    }
}

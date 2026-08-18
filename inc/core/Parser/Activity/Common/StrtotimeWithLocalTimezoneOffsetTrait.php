<?php

namespace Runalyze\Parser\Activity\Common;

use Runalyze\Util\LocalTime;

trait StrtotimeWithLocalTimezoneOffsetTrait
{
    /**
     * Timestamps are given in UTC but local timezone offset has to be considered!
     *
     * @param string $string
     */
    protected function strtotime($string): int
    {
        if (substr($string, -1) == 'Z') {
            return LocalTime::fromServerTime(strtotime(substr($string, 0, -1).' UTC'))->getTimestamp();
        }

        return LocalTime::fromString($string)->getTimestamp();
    }
}

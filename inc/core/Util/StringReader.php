<?php

/**
 * This file contains class::StringReader
 * @package Runalyze\Util
 */

namespace Runalyze\Util;

use Runalyze\Activity\Duration;

/**
 * Read specific information from string
 *
 * @author Hannes Christiansen
 * @package Runalyze\Util
 */
class StringReader
{
    protected string $String;

    public function __construct(string $string = '')
    {
        $this->setString($string);
    }

    public function setString(string $string): self
    {
        $this->String = $string;

        return $this;
    }

    /**
     * Find a pace goal within a string
     *
     * This method can be used to extract the demanded pace within a description,
     * e.g. to find '3:20' within '6x1000m in 3:20, 400m pauses'.
     * It will look for a time string directly after the given search pattern.
     * The time string will be interpreted as time per kilometer.
     *
     * @param string $searchPattern [optional] String that must occur directly before the pace
     * @return float Pace in s/km, 0 if nothing found
     */
    public function findDemandedPace(string $searchPattern = ' in ', string $timePattern = '(\d+[:\d+]*)'): float
    {
        $matches = [];
        preg_match('/' . $searchPattern . $timePattern . '/', $this->String, $matches);

        if (isset($matches[1])) {
            return new Duration($matches[1])->seconds();
        }

        return 0.0;
    }
}

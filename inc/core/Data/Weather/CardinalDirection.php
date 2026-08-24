<?php

/**
 * This file contains class::Temperature
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

class CardinalDirection
{
    /**
     * Direction in degree
     * @var float
     */
    protected $Value;

    /**
     * Construct wind degree object
     * @param float $degrees
     */
    public function __construct($degrees)
    {
        $this->setDegree($degrees);
    }

    /**
     * @param float $degrees
     * @throws \InvalidArgumentException
     * @return \Runalyze\Data\Weather\CardinalDirection $this-reference
     */
    public function setDegree($degrees): self
    {
        if (! is_numeric($degrees)) {
            throw new \InvalidArgumentException('Value must be numeric.');
        }

        $this->Value = $degrees;

        return $this;
    }

    /**
     * @return float
     */
    public function value()
    {
        return $this->Value;
    }

    public function string(): string
    {
        return self::getDirection($this->Value);
    }

    /**
     * Get cardinal direction
     * @param float $bearing
     */
    public static function getDirection($bearing): string
    {
        if (! is_numeric($bearing)) {
            throw new \InvalidArgumentException('Argument must be numeric');
        }

        if ($bearing >= 337.5 || $bearing < 22.5) {
            return __('N');
        }

        $cardinalDirections = [
            __('N') => [337.5, 22.5],
            __('NE') => [22.5, 67.5],
            __('E') => [67.5, 112.5],
            __('SE') => [112.5, 157.5],
            __('S') => [157.5, 202.5],
            __('SW') => [202.5, 247.5],
            __('W') => [247.5, 292.5],
            __('NW') => [292.5, 337.5],
        ];

        foreach ($cardinalDirections as $dir => $angles) {
            if ($bearing >= $angles[0] && $bearing < $angles[1]) {
                return $dir;
            }
        }

        return '?';
    }
}

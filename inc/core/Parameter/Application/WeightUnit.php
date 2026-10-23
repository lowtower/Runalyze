<?php

declare(strict_types=1);

/**
 * This file contains class::WeightUnit
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

/**
 * Weight Unit
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\Parameter\Application
 */
class WeightUnit extends \Runalyze\Parameter\Select
{
    /**
     * KG
     */
    public const string KG = 'kg';

    /**
     * Pound
     */
    public const string POUNDS = 'lbs';

    /**
     * Stone
     */
    public const string STONES = 'st';

    /**
     * Construct
     */
    public function __construct(string $default = self::KG)
    {
        parent::__construct($default, [
            'options' => [
                self::KG => __('kilograms'),
                self::POUNDS => __('pounds'),
                self::STONES => __('stones'),
            ],
        ]);
    }

    /**
     * Is kg?
     */
    public function isKG(): bool
    {
        return $this->value() == self::KG;
    }

    /**
     * Is pounds?
     */
    public function isPounds(): bool
    {
        return $this->value() == self::POUNDS;
    }

    /**
     * Is stones?
     */
    public function isStones(): bool
    {
        return $this->value() == self::STONES;
    }

    /**
     * Get current user weight unit
     * @return string
     */
    public function unit(): mixed
    {
        return $this->value();
    }
}

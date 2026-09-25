<?php

declare(strict_types=1);

/**
 * This file contains class::EnergyUnit
 * @package Runalyze\Parameter\Application
 */

namespace Runalyze\Parameter\Application;

use Runalyze\Activity\Energy;

/**
 * Energy Unit
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Parameter\Application
 */
class EnergyUnit extends \Runalyze\Parameter\Select
{
    /**
     * Kilocalories
     * @var string
     */
    public const KCAL = 'kcal';

    /**
     * Kilojoule
     * @var string
     */
    public const KJ = 'kJ';

    /**
     * Construct
     */
    public function __construct(string $default = self::KCAL)
    {
        parent::__construct($default, [
            'options' => [
                self::KCAL => __('Kilocalories'),
                self::KJ => __('Kilojoules'),
            ],
        ]);
    }

    /**
     * Is kilocalories?
     */
    public function isKCAL(): bool
    {
        return $this->value() == self::KCAL;
    }

    /**
     * Is kilojoules?
     */
    public function isKJ(): bool
    {
        return $this->value() == self::KJ;
    }

    /**
     * Get current user energy unit
     * @return string
     */
    public function unit(): mixed
    {
        return $this->value();
    }

    public function factorFromKcalToUnit(): float
    {
        if ($this->isKJ()) {
            return Energy::KJ_MULTIPLIER;
        }

        return 1.0;
    }
}

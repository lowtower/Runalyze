<?php

declare(strict_types=1);

/**
 * This file contains class::Factor
 * @package Runalyze\Calculation\Trimp
 */

namespace Runalyze\Calculation\Trimp;

use Runalyze\Profile\Athlete\Gender;

/**
 * Based on experiments there are different constants for men (1.92)
 * and women (1.67). The base factor (in general 0.64) is adapted for women
 * and unknown gender to get a normalized value which has the same range
 * for everyone.
 *
 * For an unknown gender, the mean of men's and women's factor is used.
 *
 * @see http://fellrnr.com/wiki/TRIMP
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Trimp
 */
class Factor
{
    /**
     * Construct
     */
    public function __construct(
        protected int $Gender
    ) {
    }

    /**
     * Base factor
     */
    public function A(): float
    {
        if ($this->Gender === Gender::MALE) {
            return 0.64;
        }

        if ($this->Gender === GENDER::FEMALE) {
            return 0.821776;
        }

        return 0.725215;
    }

    /**
     * Exponent factor
     */
    public function B(): float
    {
        if ($this->Gender === Gender::MALE) {
            return 1.92;
        }

        if ($this->Gender === Gender::FEMALE) {
            return 1.67;
        }

        return 1.795;
    }
}

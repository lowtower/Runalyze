<?php

/**
 * This file contains class::MinPerKilometer
 * @package Runalyze\Activity\PaceUnit
 */

namespace Runalyze\Activity\PaceUnit;

/**
 * Pace unit: min/km
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MinPerKilometer extends AbstractTimeUnit
{
    /**
     * Unit
     * @codeCoverageIgnore
     */
    public function unit(): int
	{
		return \Runalyze\Parameter\Application\PaceUnit::MIN_PER_KM;
	}

    /**
     * Appendix
     * @codeCoverageIgnore
     */
    public function appendix(): string
	{
		return '/km';
	}

    /**
     * Factor to transform 's/km' to current unit
     *
     * This should only be used for time formats.
     * 's/km' * factorForUnit() = [time in seconds per distance]
     *
     * @throws \RuntimeException
     */
    public function factorForUnit(): int
	{
		return 1;
	}
}

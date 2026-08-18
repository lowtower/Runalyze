<?php

declare(strict_types=1);

/**
 * This file contains class::KmPerHour
 * @package Runalyze\Activity\PaceUnit
 */
namespace Runalyze\Activity\PaceUnit;

/**
 * Pace unit: km/h
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class KmPerHour extends AbstractDecimalUnit
{
    /**
     * Unit
     * @codeCoverageIgnore
     */
    public function unit(): int
	{
		return \Runalyze\Parameter\Application\PaceUnit::KM_PER_H;
	}

    /**
     * Appendix
     * @codeCoverageIgnore
     */
    public function appendix(): string
	{
		return '&nbsp;km/h';
	}

    /**
     * Dividend to transform 's/km' to current unit
     *
     * This should only be used for decimal formats.
     * dividendForUnit() / 's/km' = [decimal pace value]
     *
     * @throws \RuntimeException
     */
    public function dividendForUnit(): int
	{
		return 3600;
	}
}

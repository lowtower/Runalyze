<?php

declare(strict_types=1);

/**
 * This file contains class::MeterPerSecond
 * @package Runalyze\Activity\PaceUnit
 */
namespace Runalyze\Activity\PaceUnit;

/**
 * Pace unit: m/s
 * @author Hannes Christiansen
 * @package Runalyze\Activity\PaceUnit
 */
class MeterPerSecond extends AbstractDecimalUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function unit(): int
	{
		return \Runalyze\Parameter\Application\PaceUnit::M_PER_S;
	}

    /**
     * @codeCoverageIgnore
     */
    public function appendix(): string
	{
		return '&nbsp;m/s';
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
		return 1000;
	}
}

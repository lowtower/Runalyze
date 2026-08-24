<?php

declare(strict_types=1);

/**
 * This file contains class::CompletorForTime
 * @package Runalyze\Model\Activity\Splits
 */

namespace Runalyze\Model\Activity\Splits;

/**
 * Complete splits: fill missing times
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
class CompletorForTime extends Completor
{
    public function mode(): string {
		return parent::MODE_TIME;
	}
}

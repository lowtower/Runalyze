<?php

declare(strict_types=1);

/**
 * This file contains class::CompletorForDistance
 * @package Runalyze\Model\Activity\Splits
 */
namespace Runalyze\Model\Activity\Splits;

/**
 * Complete splits: fill missing distances
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
class CompletorForDistance extends Completor
{
    public function mode(): string {
		return parent::MODE_DISTANCE;
	}
}

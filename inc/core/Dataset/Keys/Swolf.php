<?php

declare(strict_types=1);

/**
 * This file contains class::Swolf
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: Swolf
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Swolf extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::SWOLF;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'swolf';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Swolf');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->activity()
		    ->swolf();
	}

	/**
	 * @return int see \Runalyze\Dataset\SummaryMode for enum
	 */
	public function summaryMode(): int
	{
		return SummaryMode::AVG;
	}
}

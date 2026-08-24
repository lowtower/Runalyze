<?php

declare(strict_types=1);

/**
 * This file contains class::AverageRespirationRate
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: AverageRespirationRate
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class AverageRespirationRate extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::AVG_RESPIRATION_RATE;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'avg_respiration_rate';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Avg respiration rate') . ' ' . __('(by file)');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Avg respiration rate');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'Die Atemfrequenz ist ein Messwert für die Anzahl der Atemzüge pro Minute (brpm) während des Tages. ' .
			'Ein Atemzug umfasst sowohl das Einatmen als auch das Ausatmen.'
		);
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->activity()
		    ->avgRespirationRate();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}

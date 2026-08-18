<?php

/**
 * This file contains class::Sport
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Sport
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Sport extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::SPORT;
	}

    /**
     * Is this key always shown?
     * @codeCoverageIgnore
     */
    public function mustBeShown(): bool
	{
		return true;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'sportid';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Sport');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return '';
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('The sports icon is shown.');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if ($context->hasSport()) {
			return $context->sport()
			    ->icon()
			    ->code();
		}

		return '';
	}
}

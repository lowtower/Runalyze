<?php

/**
 * This file contains class::Title
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Title
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Title extends AbstractKey
{
	/**
	 * @var int
	 */
	const DEFAULT_CUT = 20;

    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::TITLE;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'title';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Title');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return sprintf(
		    __('Titles are automatically cut after %u characters.'),
		    self::DEFAULT_CUT
		);
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return \Helper::Cut(
		    $context->activity()
		        ->title(),
		    20
		);
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small l';
	}
}

<?php

declare(strict_types=1);

/**
 * This file contains class::Type
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Type extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::TYPE;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'typeid';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Activity type');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Type');
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'The activity type is shown with its abbreviation. ' .
			'It is bold if the type is marked as quality session.'
		);
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		if ($context->hasType()) {
			if ($context->type()->isQualitySession()) {
				return '<strong>' . $context->type()->abbreviation() . '</strong>';
			}

			return $context->type()
			    ->abbreviation();
		}

		return '';
	}
}

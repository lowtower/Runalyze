<?php

declare(strict_types=1);

/**
 * This file contains class::Daytime
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Profile\View\DatasetPrivacyProfile;

/**
 * Dataset key: Daytime
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Daytime extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::DAYTIME;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'time';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Daytime');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return __('Time');
	}

    /**
     * @codeCoverageIgnore
     */
    public function defaultPrivacy(): int {
        return DatasetPrivacyProfile::PRIVATE_KEY;
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Exact daytime of the activity. (the date itself is always shown)');
	}

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
	{
		return $context->dataview()
		    ->daytime();
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'c';
	}
}

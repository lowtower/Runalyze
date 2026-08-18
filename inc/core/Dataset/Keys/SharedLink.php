<?php

/**
 * This file contains class::SharedLink
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Icon;
use Runalyze\Dataset\Context;

/**
 * Dataset key: SharedLink
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class SharedLink extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::SHARED_LINK;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'is_public';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Public link');
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
		return __(
		    'You can decide for each activity whether it is visible for '.
			'everybody or not. If it is public a linked icon is shown.'
		);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		if ($context->activity()->isPublic()) {
			return '<a href="'.$context->linker()->publicUrl().'" target="_blank">'.Icon::$ATTACH.'</a>';
		}

		return '';
	}
}

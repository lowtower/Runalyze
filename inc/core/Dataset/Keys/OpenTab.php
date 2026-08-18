<?php

/**
 * This file contains class::StrideLength
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: OpenTab
 * Open the activity in a new Browser tab/window
 *
 * @author TSC
 * @package Runalyze\Dataset\Keys
 */
class OpenTab extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::OPEN_TAB;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return '';
	}

    public function isInDatabase(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Open new tab');
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
    public function hasPrivacyOption(): bool {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __(
		    'Open the activity in a new browser tab/window'
		);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		$id = $context->activity()
		    ->id();
        if ($id > 0) {
            return '<a href="/dashboard?id=' . $id . '#statistics-scrolltag" target="_blank" onclick="event.stopPropagation()">' . $this->getCodeForIcon() .'</a>';
        }

        return '';
	}

    public function stringForExample(Context $context): string
    {
        return '<span class="link">' . $this->getCodeForIcon() . '</span>';
    }

    protected function getCodeForIcon(): string
    {
        return '<i class="fa fa-fw fa-external-link" style="font-weight: 900;"></i>';
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}

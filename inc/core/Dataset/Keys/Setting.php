<?php

declare(strict_types=1);

/**
 * This file contains class::Route
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Setting
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class Setting extends AbstractKey
{
	/** if true, this Settings also include the links from Tools. */
	const INCLUDE_TOOLS = true;

    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::SETTING;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return 'is_public';
	}

	public function isInDatabase(): bool
	{
		return true;
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Setting');
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
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if (! \Request::isOnSharedPage() && $context->activity()->id() > 0) {
            return $this->getCodeForDropdown($this->inlineDropdownWithRealLinks($context));
        }

        return '';
    }

    public function stringForExample(Context $context): string
    {
        return $this->getCodeForDropdown($this->inlineDropdownWithFakeLinks($context));
    }

    /**
     * @param string $menu
     */
    protected function getCodeForDropdown($menu): string
    {
        return '<div class="inline-menu"><ul><li class="with-submenu">'.
            '<span class="link"><i class="fa fa-fw fa-wrench"></i></span>'.
            '<ul class="submenu">'.$menu.'</ul>'.
            '</li></ul></div>';
    }

    /**
     * @codeCoverageIgnore
     */
    protected function inlineDropdownWithRealLinks(Context $context): string
	{
		$id = $context->activity()
		    ->id();

		$html = '<li>'.\Ajax::window('<a href="'.$context->linker()->editUrl().'">'.\Icon::$EDIT.' '.__('Edit').'</a> ', 'small').'</li>';
		$html .= '<li><span class="link" data-action="privacy" data-activityid="'.$id.'">'.$this->privacyIconAndLabel($context).'</span></li>';
		$html .= '<li><span class="link" data-action="delete" data-activityid="'.$id.'" data-confirm="'.__('Do you really want to delete this activity?').'"><i class="fa fa-fw fa-trash"></i> '.__('Delete activity').'</span></li>';
		$html .= ($context->activity()->isPublic())
			? '<li><a href="'.$context->linker()->publicUrl().'" target="_blank" onclick="(arguments[0] || window.event).stopPropagation();">'.\Icon::$ATTACH.' '.__('Public link').'</a></li>'
			: '';

		// #TSC also include the tools
		$html .= '<li class="separator"></li>';

		return $html . Tools::inlineDropdownWithRealLinks($context);
	}

    /**
     * @codeCoverageIgnore
     */
    protected function inlineDropdownWithFakeLinks(Context $context): string
	{
		$html = '<li><span class="link">'.\Icon::$EDIT.' '.__('Edit').'</span></li>';
		$html .= '<li><span class="link">'.$this->privacyIconAndLabel($context).'</span></li>';
		$html .= '<li><span class="link"><i class="fa fa-fw fa-trash"></i> '.__('Delete activity').'</span></li>';
		$html .= ($context->activity()->isPublic())
			? '<li><span class="link">'.\Icon::$ATTACH.' '.__('Public link').'</span></li>'
			: '';

		// #TSC also include the tools
		$html .= Tools::inlineDropdownWithFakeLinks($context);

		return $html;
	}

	protected function privacyIconAndLabel(Context $context): string
	{
		if ($context->activity()->isPublic()) {
			$privacyLabel = __('Make private');
			$privacyIcon = 'fa-lock';
		} else {
			$privacyLabel = __('Make public');
			$privacyIcon = 'fa-unlock';
		}

		return '<i class="fa fa-fw '.$privacyIcon.'"></i> '.$privacyLabel;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small l';
	}
}

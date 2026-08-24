<?php

declare(strict_types=1);

/**
 * This file contains class::Tools
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Tools
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class Tools extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::TOOLS;
    }

    /**
     * Database key
     */
    public function column(): array
    {
        return ['elevation', 'splits'];
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
        return __('Tools');
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
        return '<div class="inline-menu"><ul><li class="with-submenu">' .
            '<span class="link"><i class="fa fa-fw fa-magic"></i></span>' .
            '<ul class="submenu">' . $menu . '</ul>' .
            '</li></ul></div>';
    }

    /**
     * @codeCoverageIgnore
     */
    public static function inlineDropdownWithRealLinks(Context $context): string
    {
        $id = $context->activity()
            ->id();
        $html = '';

        if (! $context->activity()->splits()->isEmpty()) {
            $html .= '<li><a class="window" data-size="big" href="activity/' . $id . '/splits-info"><i class="fa fa-fw fa-bar-chart"></i> ' . __('Analyze splits') . '</a></li>';
        }

        if ($context->activity()->elevation() > 0) {
            $html .= '<li><a class="window" href="activity/' . $id . '/elevation-info"><i class="fa fa-fw fa-area-chart"></i> ' . __('Elevation info') . '</a></li>';
            $html .= '<li><a class="window" href="activity/' . $id . '/climb-score"><i class="fa fa-fw fa-area-chart"></i> ' . __('Climb score') . '</a></li>';
        }

        $html .= '<li><a class="window" href="activity/' . $id . '/time-series-info"><i class="fa fa-fw fa-line-chart"></i> ' . __('Time series') . '</a></li>';

        return $html . ('<li><a class="window" href="activity/' . $id . '/sub-segments-info"><i class="fa fa-fw fa-bar-chart"></i> ' . __('Sub segments') . '</a></li>');
    }

    /**
     * @codeCoverageIgnore
     */
    public static function inlineDropdownWithFakeLinks(Context $context): string
    {
        $html = '<li><span class="link"><i class="fa fa-fw fa-bar-chart"></i> ' . __('Analyze splits') . '</span></li>';
        $html .= '<li><span class="link"><i class="fa fa-fw fa-area-chart"></i> ' . __('Elevation info') . '</span></li>';
        $html .= '<li><span class="link"><i class="fa fa-fw fa-area-chart"></i> ' . __('Climb score') . '</span></li>';
        $html .= '<li><span class="link"><i class="fa fa-fw fa-line-chart"></i> ' . __('Time series') . '</span></li>';

        return $html . ('<li><span class="link"><i class="fa fa-fw fa-bar-chart"></i> ' . __('Sub segments') . '</span></li>');
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small l';
    }
}

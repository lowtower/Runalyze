<?php

/**
 * This file contains class::Table
 * @package Runalyze\View\Dataset
 */

namespace Runalyze\View\Dataset;

use Runalyze\Dataset\Configuration;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\Keys;
use Runalyze\View\Tooltip;

/**
 * Dataset table
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset
 */
class Table
{
    protected array $ActiveKeys = [];

    /**
     * Boolean flag to automatically set distance comparison
     */
    protected bool $AutomaticDistanceComparison = false;

    /**
     * @var float [km]
     */
    protected float $LastDistance = 0.0;

    public function __construct(
        protected Configuration $Configuration
    ) {
        $this->ActiveKeys = $this->Configuration->activeKeys();

        Keys::keepInstances();
    }

    /**
     * This does only work, if <code>codeForColumns()</code> is called in
     * respective order.
     */
    public function activateAutomaticDistanceComparison(): void
    {
        $this->AutomaticDistanceComparison = true;
    }

    public function numColumns(): int
    {
        return count($this->ActiveKeys);
    }

    /**
     * @param string|bool $icon optional icon instead of text
     */
    public function codeForColumnLabels($icon = false): string
    {
        $Tooltip = new Tooltip('');
        $Code = '';
        foreach ($this->ActiveKeys as $keyid) {
            $Key = Keys::get($keyid);
            $Header = $icon ?: $Key->shortLabel();

            $Tooltip->setText($Key->label());
            $Tooltip->wrapAround($Header);

            $Code .= '<td>' . $Header . '</td>';
        }

        return $Code;
    }

    public function codeForColumns(Context $context, array $hiddenKeys = []): string
    {
        $Code = '';

        if ($this->AutomaticDistanceComparison) {
            $context->setData(Keys\Distance::KEY_DISTANCE_COMPARISON, $this->LastDistance);
            $this->LastDistance = $context->activity()
                ->distance();
        }

        foreach ($this->ActiveKeys as $keyid) {
            if (in_array($keyid, $hiddenKeys)) {
                $Code .= '<td></td>';
            } else {
                $Key = Keys::get($keyid);

                // css style via class
                $class = ($Key->cssClass() != '') ? $Key->cssClass() : '';
                // css style from the configuration
                $style = ($this->Configuration->getStyle($keyid) != '') ? $this->Configuration->getStyle($keyid) : '';
                // css class&style depending on values
                $valueClassStyle = $Key->valueDependendCssStyle($context);
                if ($valueClassStyle !== null) {
                    $class = $class . ' ' . $valueClassStyle['class'];
                    $style .= $valueClassStyle['style'];
                }

                $class = ($class !== '') ? ' class="' . $class . '"' : '';
                $style = ($style !== '') ? ' style="' . $style . '"' : '';

                $Code .= '<td' . $class . $style . '>' . $Key->stringFor($context) . '</td>';
            }
        }

        return $Code;
    }

    public function codeForPublicIcon(Context $context): string
    {
        if ($context->activity()->isPublic()) {
            return \HTML::td(\Icon::$ADD_SMALL_GREEN, 'link');
        }

        return '<td></td>';
    }

    public function codeForShortLink(Context $context): string
    {
        return $context->linker()
            ->codeWithSportIcon(Tooltip::POSITION_RIGHT);
    }
}

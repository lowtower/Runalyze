<?php

/**
 * This file contains class::Icon
 * @package Runalyze\View
 */

namespace Runalyze\View;

/**
 * Example usage:
 * <pre>
 * $Icon = new Icon( Icon::INFO );
 * $Icon->setTooltip('That\'s so cool!');
 * $Icon->display();
 * </pre>
 *
 * @author Hannes Christiansen
 * @package Runalyze\View
 */
class Icon
{
    /**
     * @var string
     */
    public const INFO = 'fa-info-circle';

    /**
     * @var string
     */
    public const CLOCK = 'fa-clock-o';

    /**
     * @var string
     */
    public const CLOCK_GRAY = 'fa-clock-o unimportant';

    /**
     * @var string
     */
    public const HEART = 'fa-heart';

    /**
     * @var string
     */
    public const MAP_ARROW = 'fa-location-arrow';

    /**
     * Key to identfiy FontAwesome icon
     * @see http://fontawesome.io/icons/
     * @var string
     */
    protected $FontAwesomeName;

    /**
     * Text for tooltip
     * @var string
     */
    protected $Tooltip;

    /**
     * Construct new icon
     * @param string $fontAwesomeName [optional] class const
     */
    public function __construct($fontAwesomeName)
    {
        $this->FontAwesomeName = $fontAwesomeName;
    }

    /**
     * @param string $tooltip
     */
    public function setTooltip($tooltip)
    {
        $this->Tooltip = $tooltip;
    }

    final public function display()
    {
        echo $this->code();
    }

    /**
     * Get code
     */
    public function code(): string
    {
        return '<i class="fa fa-fw ' . $this->FontAwesomeName . '"' . $this->tooltipAttributes() . '></i>';
    }

    protected function tooltipAttributes(): string
    {
        if (! empty($this->Tooltip)) {
            $Tooltip = new Tooltip($this->Tooltip);
            return ' ' . $Tooltip->attributes();
        }

        return '';
    }

    /**
     * @param string $code
     */
    protected function addTooltipToCode(&$code)
    {
        if (! empty($this->Tooltip)) {
            $Tooltip = new Tooltip($this->Tooltip);
            $Tooltip->wrapAround($code);
        }
    }
}

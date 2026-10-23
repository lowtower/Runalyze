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
    public const string INFO = 'fa-info-circle';

    public const string CLOCK = 'far fa-clock';

    public const string CLOCK_GRAY = 'far fa-clock unimportant';

    public const string HEART = 'fa-heart';

    public const string MAP_ARROW = 'fa-location-arrow';

    /**
     * Text for tooltip
     * @var string
     */
    protected $Tooltip;

    /**
     * Construct new icon
     * @param string $FontAwesomeName [optional] class const
     */
    public function __construct(
        /**
         * Key to identfiy FontAwesome icon
         * @see http://fontawesome.io/icons/
         */
        protected $FontAwesomeName
    ) {}

    /**
     * @param string $tooltip
     */
    public function setTooltip($tooltip): void
    {
        $this->Tooltip = $tooltip;
    }

    final public function display(): void
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

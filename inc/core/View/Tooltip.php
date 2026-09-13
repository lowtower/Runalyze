<?php

/**
 * This file contains class::Tooltip
 * @package Runalyze\View
 */

namespace Runalyze\View;

/**
 * Example usage:
 * <pre>
 * $Tooltip = new Tooltip( 'I\'m a tooltip!' );
 * $Tooltip->setPosition( Tooltip::POSITION_LEFT );
 * $Tooltip->wrapAround( $code );
 * </pre>
 *
 * @author Hannes Christiansen
 * @package Runalyze\View
 */
class Tooltip
{
    /**
     * @var string
     */
    public const POSITION_LEFT = 'atLeft';

    /**
     * @var string
     */
    public const POSITION_RIGHT = 'atRight';

    /**
     * @var string
     */
    public const ATTRIBUTE_REL = 'tooltip';

    /**
     * Attribute to be used for the content
     * @var string
     */
    public const ATTRIBUTE = 'title';

    /**
     * Position
     * @var string
     */
    protected $position = '';

    /**
     * Construct new tooltip
     * @param string $text
     */
    public function __construct(
        /**
         * Text for tooltip
         */
        protected $text
    ) {
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @param string $code
     */
    public function wrapAround(&$code)
    {
        $code = '<span ' . $this->attributes() . '>' . $code . '</span>';
    }

    /**
     * Get attributes
     */
    public function attributes(): string
    {
        $Attributes = [];
        $Attributes[] = 'rel="' . self::ATTRIBUTE_REL . '"';
        $Attributes[] = self::ATTRIBUTE . '="' . str_replace('&amp;', '&', htmlspecialchars($this->text)) . '"';

        if (! empty($this->position)) {
            $Attributes[] = 'class="' . $this->position . '"';
        }

        return implode(' ', $Attributes);
    }
}

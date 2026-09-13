<?php

declare(strict_types=1);

/**
 * This file contains class::SportIcon
 * @package Runalyze\View\Icon
 */

namespace Runalyze\View\Icon;

/**
 * Sport icon
 * @author Hannes Christiansen
 * @package Runalyze\View\Icon
 */
class SportIcon extends \Runalyze\View\Icon
{
    /**
     * Sport icon
     * @param string $Filename
     */
    public function __construct(
        /**
         * Filename
         */
        protected $Filename
    ) {
        parent::__construct('');
    }

    /**
     * Display
     */
    public function code(): string
    {
        return '<i class="' . $this->Filename . '"' . $this->tooltipAttributes() . '></i>';
    }
}

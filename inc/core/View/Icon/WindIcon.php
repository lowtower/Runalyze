<?php

declare(strict_types=1);

/**
 * This file contains class::WindIcon
 * @package Runalyze\View\Icon
 */

namespace Runalyze\View\Icon;

use Runalyze\Data\Weather\BeaufortScale;
use Runalyze\Data\Weather\WindDegree;
use Runalyze\Data\Weather\WindSpeed;

/**
 * Wind icon
 * @author Hannes Christiansen
 * @package Runalyze\View\Icon
 */
class WindIcon extends \Runalyze\View\Icon
{
    public const string BASE_CLASS = 'windicon';

    public const string DIRECTION_CLASS = 'windicon-direction';

    protected BeaufortScale $BeaufortScale;

    public function __construct(
        protected WindSpeed $WindSpeed,
        protected WindDegree $WindDegree
    ) {
        $this->BeaufortScale = new BeaufortScale($this->WindSpeed);

        $this->setDefaultTooltip();
    }

    public function setDefaultTooltip(): void
    {
        $strings = [];

        if (! $this->WindSpeed->isUnknown()) {
            $strings[] = $this->WindSpeed->string();
            $strings[] = $this->BeaufortScale->shortString();
        }

        if (! $this->WindDegree->isUnknown()) {
            $strings[] = $this->WindDegree->string();
        }

        if ($strings !== []) {
            $this->setTooltip(__('Wind') . ': ' . implode(', ', $strings));
        }
    }

    /**
     * Display
     */
    public function code(): string
    {
        if ($this->WindSpeed->isUnknown() && $this->WindDegree->isUnknown()) {
            return '';
        }

        $code = '<span class="' . self::BASE_CLASS . '"' . $this->tooltipAttributes() . '>';

        if (! $this->WindDegree->isUnknown()) {
            $code .= '<span class="' . self::DIRECTION_CLASS . '" style="transform:rotate(' . $this->WindDegree->value() . 'deg);"></span> ';
        }

        $code .= $this->WindSpeed->isUnknown() ? '?' : $this->BeaufortScale->string(false);

        return $code . '</span>';
    }
}

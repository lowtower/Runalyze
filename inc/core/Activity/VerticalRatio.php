<?php

/**
 * This file contains class::VerticalRatio
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * Vertical ratio
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class VerticalRatio implements ValueInterface
{
    /**
     * Value [%o]
     */
    protected int $Permille;

    /**
     * Format vertical ratio
     */
    public static function format(int $permille, bool $withUnit = true): string
    {
        return (new self($permille))->string($withUnit);
    }

    /**
     * @param int $permille [%o]
     */
    public function __construct(int $permille = 0)
    {
        $this->Permille = $permille;
    }

    /**
     * Label for stride length
     */
    public function label(): string
    {
        return __('Vertical ratio');
    }

    public function unit(): string
    {
        return '%';
    }

    /**
     * Set vertical ratio
     * @param int $permille [%o]
     * @return \Runalyze\Activity\VerticalRatio $this-reference
     */
    public function set($permille): self
    {
        $this->Permille = $permille;

        return $this;
    }

    /**
     * Set vertical ratio in percent
     * @param float $percent [%]
     * @return \Runalyze\Activity\VerticalRatio $this-reference
     */
    public function setPercent(float $percent): self
    {
        $this->Permille = (int) (10 * $percent);

        return $this;
    }

    /**
     * Get vertical ratio
     * @return int [%o]
     */
    public function value(): int
    {
        return $this->Permille;
    }

    /**
     * Get vertical ratio
     * @return float [%]
     */
    public function inPercent(): float
    {
        return $this->Permille / 10;
    }

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->Permille > 0) {
            return number_format($this->Permille / 10, 1, '.', '') . ($withUnit ? '&nbsp;%' : '');
        }

        return '';
    }
}

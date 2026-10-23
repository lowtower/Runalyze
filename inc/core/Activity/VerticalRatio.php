<?php

/**
 * This file contains class::VerticalRatio
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class VerticalRatio implements ValueInterface
{
    public static function format(int $permille, bool $withUnit = true): string
    {
        return new self($permille)->string($withUnit);
    }

    /**
     * @param int $Permille [%o]
     */
    public function __construct(
        protected int $Permille = 0
    ) {}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Vertical ratio');
    }

    /**
     * @codeCoverageIgnore
     */
    public function unit(): string
    {
        return '%';
    }

    public function set(mixed $permille): self
    {
        $this->Permille = (int) $permille;

        return $this;
    }

    /**
     * @param float $percent [%]
     */
    public function setPercent(float $percent): self
    {
        $this->Permille = (int) round(10 * $percent);

        return $this;
    }

    /**
     * @return int [%o]
     */
    public function value(): int
    {
        return $this->Permille;
    }

    /**
     * @return float [%]
     */
    public function inPercent(): float
    {
        return $this->Permille / 10;
    }

    public function string(bool $withUnit = true): string
    {
        if ($this->Permille > 0) {
            return number_format($this->Permille / 10, 1, '.', '') . ($withUnit ? '&nbsp;%' : '');
        }

        return '';
    }
}

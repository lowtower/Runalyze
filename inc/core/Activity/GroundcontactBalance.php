<?php

/**
 * This file contains class::GroundcontactBalance
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * Groundcontact balance
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class GroundcontactBalance implements ValueInterface
{
    /**
     * Format groundcontact balance
     */
    public static function format(int $basispoint, bool $withUnit = true): string
    {
        return new self($basispoint)->string($withUnit);
    }

    /**
     * @param int $BasisPoint [%oo L]
     */
    public function __construct(
        protected int $BasisPoint = 0
    ) {}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Groundcontact balance');
    }

    /**
     * @codeCoverageIgnore
     */
    public function unit(): string
    {
        return '%';
    }

    /**
     * Set groundcontact balance
     * @param int $basispoint [%oo L]
     * @return \Runalyze\Activity\GroundcontactBalance $this-reference
     */
    public function set($basispoint): self
    {
        $this->BasisPoint = $basispoint;

        return $this;
    }

    /**
     * Set groundcontact balance in percent
     * @param float $percent [% L]
     * @return \Runalyze\Activity\GroundcontactBalance $this-reference
     */
    public function setPercent(float $percent): self
    {
        $this->BasisPoint = (int) (100 * $percent);

        return $this;
    }

    /**
     * Get groundcontact balance
     * @return int [%oo]
     */
    public function value(): int
    {
        return $this->BasisPoint;
    }

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->isKnown()) {
            $string = $this->leftInPercent() . 'L/' . $this->rightInPercent() . 'R';
        } else {
            $string = '-/-';
        }

        if ($withUnit) {
            return $string . '&nbsp;%';
        }

        return $string;
    }

    public function leftInPercent(): string
    {
        return number_format($this->BasisPoint / 100, 1);
    }

    public function rightInPercent(): string
    {
        return number_format(100 - $this->BasisPoint / 100, 1);
    }

    public function isKnown(): bool
    {
        return $this->BasisPoint > 0;
    }
}

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
	 * Value [%oo L]
	 * @var int
	 */
	protected $BasisPoint;

    /**
     * Format groundcontact balance
     * @param int $basispoint
     * @param bool $withUnit
     */
    public static function format($basispoint, $withUnit = true): string
	{
		return (new self($basispoint))->string($withUnit);
	}

	/**
	 * @param int $basispoint [%oo L]
	 */
	public function __construct($basispoint = 0)
	{
		$this->BasisPoint = $basispoint;
	}

    /**
     * Label for stride length
     */
    public function label(): string
	{
		return __('Groundcontact balance');
	}

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
	public function setPercent($percent): self
	{
		$this->BasisPoint = 100 * $percent;

		return $this;
	}

	/**
	 * Get groundcontact balance
	 * @return int [%oo]
	 */
	public function value()
	{
		return $this->BasisPoint;
	}

    /**
     * Format value as string
     * @param bool $withUnit
     */
    public function string($withUnit = true): string
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

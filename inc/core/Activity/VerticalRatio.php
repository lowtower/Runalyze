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
	 * @var int
	 */
	protected $Permille;

    /**
     * Format vertical ratio
     * @param int $permille
     * @param bool $withUnit
     */
    public static function format($permille, $withUnit = true): string
	{
		return (new self($permille))->string($withUnit);
	}

	/**
	 * @param int $permille [%o]
	 */
	public function __construct($permille = 0)
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

    /**
     * Unit
     */
    public function unit(): string
	{
		return '%';
	}

	/**
	 * Set vertical ratio
	 * @param int $permille [%o]
	 * @return \Runalyze\Activity\VerticalRatio $this-reference
	 */
	public function set($permille)
	{
		$this->Permille = $permille;

		return $this;
	}

	/**
	 * Set vertical ratio in percent
	 * @param float $percent [%]
	 * @return \Runalyze\Activity\VerticalRatio $this-reference
	 */
	public function setPercent($percent)
	{
		$this->Permille = 10*$percent;

		return $this;
	}

	/**
	 * Get vertical ratio
	 * @return int [%o]
	 */
	public function value()
	{
		return $this->Permille;
	}

	/**
	 * Get vertical ratio
	 * @return float [%]
	 */
	public function inPercent()
	{
		return $this->Permille/10;
	}

    /**
     * Format value as string
     * @param bool $withUnit
     */
    public function string($withUnit = true): string
	{
		if ($this->Permille > 0) {
			return number_format($this->Permille/10, 1, '.', '').($withUnit ? '&nbsp;%' : '');
		}

		return '';
	}
}

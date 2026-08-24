<?php

/**
 * This file contains class::Calculator
 * @package Runalyze\Calculation\Elevation
 */

namespace Runalyze\Calculation\Elevation;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\ElevationMethod;

/**
 * Elevation calculator
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Elevation
 */
class Calculator
{
	/**
	 * Elevation points
	 * @var array
	 */
	protected $ElevationPoints = [];

	/**
	 * Elevation method
	 * @var \Runalyze\Parameter\Application\ElevationMethod
	 */
	protected $Method;

	/**
	 * Threshold
	 * @var int
	 */
	protected $Threshold;

	/**
	 * Smoothing strategy
	 * @var \Runalyze\Calculation\Elevation\Strategy\AbstractStrategy
	 */
	protected $Strategy;

	/**
	 * Up/down points
	 * @var array
	 */
	protected $UpDownPoints = [];

	/**
	 * Elevation up
	 * @var int
	 */
	protected $Up = 0;

	/**
	 * Elevation down
	 * @var int
	 */
	protected $Down = 0;

    /**
     * If no options are set, the current configuration settings are used.
     *
     * @param \Runalyze\Parameter\Application\ElevationMethod|null $Method [optional]
     * @param int|null $Threshold [optional]
     */
    public function __construct(array $ElevationPoints, ?ElevationMethod $Method = null, $Threshold = null)
	{
		$this->ElevationPoints = $ElevationPoints;
		$this->Method = $Method instanceof \Runalyze\Parameter\Application\ElevationMethod ? $Method : Configuration::ActivityView()->elevationMethod();
		$this->Threshold = $Threshold !== null ? $Threshold : Configuration::ActivityView()->elevationMinDiff();
	}

	/**
	 * @param int $threshold
	 */
	public function setThreshold($threshold)
	{
		$this->Threshold = $threshold;
	}

    public function setMethod(ElevationMethod $Method)
	{
		$this->Method = $Method;
	}

    /**
     * Get elevation
     */
    public function totalElevation(): int
	{
		return max($this->Up, $this->Down);
	}

	/**
	 * Get elevation up
	 * @return int
	 */
	public function elevationUp()
	{
		return $this->Up;
	}

	/**
	 * Get elevation up
	 * @return int
	 */
	public function elevationDown()
	{
		return $this->Down;
	}

	public function calculate()
	{
		$this->chooseStrategy();

		if ($this->ElevationPoints !== []) {
			$this->runStrategy();

			$this->Up = array_sum( array_filter($this->UpDownPoints, function($value): bool{ return $value > 0; }) );
			$this->Down = -1 * array_sum( array_filter($this->UpDownPoints, function($value): bool{ return $value < 0; }) );
		}
	}

	protected function chooseStrategy()
	{
		if ($this->Method->usesThreshold()) {
			$this->Strategy = new Strategy\Threshold($this->ElevationPoints, $this->Threshold);
		} elseif ($this->Method->usesDouglasPeucker()) {
			$this->Strategy = new Strategy\DouglasPeucker($this->ElevationPoints, $this->Threshold);
		} elseif ($this->Method->usesReumannWitkam()) {
			$this->Strategy = new Strategy\ReumannWitkam($this->ElevationPoints);
		} else {
			$this->Strategy = new Strategy\NoSmoothing($this->ElevationPoints);
		}
	}

	protected function runStrategy()
	{
		if ($this->Strategy instanceof Strategy\AbstractStrategy) {
			$this->Strategy->runSmoothing();
			$smoothedData = $this->Strategy->smoothedData();
		} else {
			$smoothedData = $this->ElevationPoints;
		}

		$num = count($smoothedData);
		$this->UpDownPoints = [];

		for ($i = 1; $i < $num; ++$i) {
			$this->UpDownPoints[$i - 1] = $smoothedData[$i] - $smoothedData[$i - 1];
		}
	}

	/**
	 * Smoothing strategy
	 * @return \Runalyze\Calculation\Elevation\Strategy\AbstractStrategy
	 */
	public function strategy()
	{
		return $this->Strategy;
	}
}

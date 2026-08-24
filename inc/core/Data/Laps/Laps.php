<?php

/**
 * This file contains class::Laps
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Model\Activity\Splits;
use Runalyze\Model\Route;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Trackdata;

/**
 * Object of laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Laps
{
	/**
	 * @var \Runalyze\Data\Laps\Lap[]
	 */
	protected $Objects;

	/**
	 * @var bool
	 */
	protected $CalculateAdditionalValues = false;

	/**
	 * @param bool $flag
	 */
	public function enableCalculationOfAdditionalValues($flag = true) {
		$this->CalculateAdditionalValues = $flag;
	}

    /**
     * @param \Runalyze\Model\Route\Entity $route
     * @param \Runalyze\Model\Swimdata\Entity $trackdata
     */
    public function calculateFrom(array $distances, Trackdata\Entity $trackdata, ?Route\Entity $route = null) {
		$Calculator = new Calculator($this);
		$Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
		$Calculator->setDistances($distances);
		$Calculator->calculateFrom($trackdata, $route);
	}

    /**
     * @param \Runalyze\Model\Route\Entity $route
     * @param \Runalyze\Model\Swimdata\Entity $swimdata
     */
    public function calculateFromTimes(array $times, Trackdata\Entity $trackdata, ?Route\Entity $route = null, ?Swimdata\Entity $swimdata = null) {
		$Calculator = new Calculator($this);
		$Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
		$Calculator->setTimes($times);
		$Calculator->calculateFrom($trackdata, $route, ($swimdata != null && $swimdata->num() > 0 ? $swimdata : null));
	}

	public function readFrom(Splits\Entity $splits) {
		$SplitsReader = new SplitsReader($this);
		$SplitsReader->readFrom($splits);
	}

	public function add(Lap $object) {
		$this->Objects[] = $object;
	}

	/**
	 * @param int $index
	 * @return \Runalyze\Data\Laps\Lap
	 * @throws \InvalidArgumentException
	 */
	public function at($index) {
		if (! is_numeric($index) || $index < 0 || $index > $this->num()) {
			throw new \InvalidArgumentException('Index out of bounds.');
		}

		return $this->Objects[$index];
	}

	/**
	 * @return \Runalyze\Data\Laps\Lap[]
	 */
	public function objects() {
		return $this->Objects;
	}

	public function num(): int {
		return count($this->Objects);
	}

    /**
     * true if there other laps with mode not ACTIVE and not RESTING.
     * #TSC
     */
    public function hasMoreThanActiveOrRestLaps(): bool {
		foreach ($this->Objects as $lap) {
			if (! $lap->getMode()->isActive() && ! $lap->getMode()->isRest()) {
				return true;
			}
		}

		return false;
	}
}

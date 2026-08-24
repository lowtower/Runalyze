<?php

/**
 * This file contains class::Loop
 * @package Runalyze\Model\Trackdata
 */

namespace Runalyze\Model\Trackdata;

use Runalyze\Configuration;

/**
 * Loop through trackdata object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Trackdata
 */
class Loop extends \Runalyze\Model\Loop
{
	/**
	 * Object
	 * @var \Runalyze\Model\Trackdata\Entity
	 */
	protected $Object;

    /**
     * Construct
     */
    public function __construct(Entity $object) {
		parent::__construct($object);
	}

	/**
	 * Current time
	 * @return int
	 */
	public function time() {
		return $this->current(Entity::TIME);
	}

	/**
	 * Current distance
	 * @return float
	 */
	public function distance() {
		return $this->current(Entity::DISTANCE);
	}

	/**
	 * Move Distance
	 * @return bool
	 */
	public function nextDistance() {
		if (Configuration::General()->distanceUnitSystem()->isImperial()) {
			return $this->nextMile();
		}

		return $this->nextKilometer();
	}

	/**
	 * Alias for <code>moveDistance(1.0)</code>
	 * @return bool
	 */
	public function nextKilometer() {
		$this->moveDistance(1.0);

		return $this->isAtEnd();
	}

	/**
	 * Alias for <code>moveDistance(1.60934)</code>
	 * @return bool
	 */
	public function nextMile() {
		$this->moveDistance(1.60934);

		return $this->isAtEnd();
	}

	/**
	 * Move for time
	 * @param int $seconds
	 * @throws \RuntimeException for negative values or if time is empty
	 */
	public function moveTime($seconds) {
		$this->move(Entity::TIME, $seconds);
	}

	/**
	 * @param int $seconds
	 * @throws \RuntimeException for negative values or if time is empty
	 */
	public function moveToTime($seconds) {
		$this->moveTo(Entity::TIME, $seconds);
	}

	/**
	 * Move for distance
	 * @param float $kilometer
	 * @throws \RuntimeException for negative values or if distance is empty
	 */
	public function moveDistance($kilometer) {
		$this->move(Entity::DISTANCE, $kilometer);
	}

	/**
	 * @param float $kilometer
	 * @throws \RuntimeException for negative values or if distance is empty
	 */
	public function moveToDistance($kilometer) {
		$this->moveTo(Entity::DISTANCE, $kilometer);
	}

    /**
     * @param string $key
     * @return float|int
     */
    public function average($key)
    {
        if ($this->LastIndex < $this->Index && $this->Object->has(Entity::TIME) && $this->Object->has($key)) {
            if ($key == Entity::PACE) {
                return $this->Object->hasTheoreticalPace() ? $this->averageTheoreticalPace() : $this->averagePace();
            }

            $lastTime = $this->Object->at($this->LastIndex, Entity::TIME);
            $totalTime = 0;
            $sum = null;

            for ($i = $this->LastIndex + 1; $i <= $this->Index; ++$i) {
                $currentTime = $this->Object->at($i, Entity::TIME);

                // #TSC check if we have a value -> if not don't summarize/avg and set not the total time
                // this functionality builds the avg of an timerange for the plot. if we have no "full" values for the given timerange, we must
                // build the avg with a "limited" totalTime; the goal is to show the plot-line only for "not null values".
                $val = $this->Object->at($i, $key);
                if ($val !== null && is_numeric($val)) {
                    $sum += $val * ($currentTime - $lastTime);
                    $totalTime += $currentTime - $lastTime;
                }

                $lastTime = $currentTime;
            }

            if ($sum !== null) {
                if ($totalTime == 0) {
                    return $sum / ($this->Index - $this->LastIndex);
                }

                return $sum / $totalTime;
            }

            // for the considered timerange we have no values, return null instead of a wrong 0 - which leads in a 0-line wrong plot
            return null;
        }

        return parent::average($key);
    }

    /**
     * @return float|int
     */
    public function averagePace()
    {
        $totalTime = $this->difference(Entity::TIME);
        $totalDistance = $this->difference(Entity::DISTANCE);

        return $totalDistance > 0 ? $totalTime / $totalDistance : 0;
    }

    /**
     * @return float|int
     */
    public function averageTheoreticalPace()
    {
        $lastTime = $this->Object->at($this->LastIndex, Entity::TIME);
        $totalTime = 0;
        $totalDistance = 0;

        for ($i = $this->LastIndex + 1; $i <= $this->Index; ++$i) {
            $currentPace = $this->Object->at($i, Entity::PACE);
            $currentTime = $this->Object->at($i, Entity::TIME);
            $totalTime += $currentTime - $lastTime;

            if ($currentPace > 0) {
                $totalDistance += ($currentTime - $lastTime) / $currentPace;
            }

            $lastTime = $currentTime;
        }

        return $totalDistance > 0 ? $totalTime / $totalDistance : 0;
    }

	protected function createNewObject(array $data): \Runalyze\Model\Trackdata\Entity {
		return new Entity($data);
	}

    /**
     * @param int|float $seconds
     */
    public function moveToTimeBeforeOrAt($seconds)
    {
        $this->moveTo(Entity::TIME, $seconds, self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE);
    }

    /**
     * @param int|float $kilometer
     */
    public function moveToDistanceBeforeOrAt($kilometer)
    {
        $this->moveTo(Entity::DISTANCE, $kilometer, self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE);
    }

	/**
	 * Move to the recorded time closest to the target.
	 *
	 * @param int|float $seconds
	 */
	public function moveToTimeNearest($seconds)
	{
		$this->moveTo(Entity::TIME, $seconds, self::BOUNDARY_NEAREST);
	}

	/**
	 * Move to the recorded distance closest to the target.
	 *
	 * @param int|float $kilometer
	 */
	public function moveToDistanceNearest($kilometer)
	{
		$this->moveTo(Entity::DISTANCE, $kilometer, self::BOUNDARY_NEAREST);
	}
}

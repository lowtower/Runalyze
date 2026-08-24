<?php

/**
 * This file contains class::Calculator
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Activity\Duration;
use Runalyze\Calculation;
use Runalyze\Configuration;
use Runalyze\Model\Activity;
use Runalyze\Model\Route;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Trackdata;

/**
 * Calculate laps from trackdata/route
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Calculator
{
	/**
	 * @var \Runalyze\Data\Laps\Laps
	 */
	protected $Laps;

	/**
	 * @var array
	 */
	protected $Distances = [];

	/**
	 * @var array
	 */
	protected $Times = [];

	/**
	 * @var \Runalyze\Model\Trackdata\Loop
	 */
	protected $TrackdataLoop;

	/**
	 * @var \Runalyze\Model\Route\Loop
	 */
	protected $RouteLoop;

	/**
	 * @var \Runalyze\Model\Swimdata\Loop
	 */
	protected $SwimdataLoop;

	/**
	 * @var bool
	 */
	protected $CalculateAdditionalValues = false;

	/**
	 * @var bool
	 */
	protected $UsePreviousTrackpointForTimeLaps = false;

	public function __construct(Laps $object)
	{
		$this->Laps = $object;
	}

	/**
	 * @param bool $flag
	 */
	public function calculateAdditionalValues($flag = true) {
		$this->CalculateAdditionalValues = $flag;
	}

	/**
	 * @param array $lapDistances [km]
	 */
	public function setDistances(array $lapDistances)
	{
		if (! self::isSorted($lapDistances)) {
			throw new \InvalidArgumentException('Calculator needs sorted array of distances');
		}

		$this->Distances = $lapDistances;
		$this->Times = [];
	}

	/**
	 * @param array $lapTimes [s]
	 */
	public function setTimes(array $lapTimes)
	{
		if (! self::isSorted($lapTimes)) {
			throw new \InvalidArgumentException('Calculator needs sorted array of times');
		}

		$this->Distances = [];
		$this->Times = $lapTimes;
	}

    /**
     * @param \Runalyze\Model\Route\Entity $route
     * @param \Runalyze\Model\Swimdata\Entity $swimdata
     */
    public function calculateFrom(Trackdata\Entity $trackdata, ?Route\Entity $route = null, ?Swimdata\Entity $swimdata = null)
	{
		$this->TrackdataLoop = new Trackdata\Loop($trackdata);
		$this->RouteLoop = $route instanceof \Runalyze\Model\Route\Entity ? new Route\Loop($route) : null;
		$this->SwimdataLoop = $swimdata instanceof \Runalyze\Model\Swimdata\Entity ? new Swimdata\Loop($swimdata) : null;

		if ($this->Distances !== []) {
			$this->readLapsFromDistances();
		} else {
			$this->readLapsFromTimes();
		}

		if (! $this->TrackdataLoop->isAtEnd()) {
			$this->finish();
		}
	}

	/**
	 * Read laps from given distances
	 */
	protected function readLapsFromDistances()
	{
		foreach ($this->Distances as $i => $kilometer) {
			// Ignore empty splits
			if ($i == 0 && $kilometer > 0 || $this->Distances[$i - 1] < $kilometer) {
				$this->moveToDistance($kilometer);
				$this->readLap();
			}
		}
	}

	/**
	 * Read laps from given times
	 */
	protected function readLapsFromTimes()
	{
		$len = count($this->Times) - 1;

		foreach ($this->Times as $i => $seconds) {
			if($i == $len) {
				// #TSC in some cases we are at the last split-lap, but the "move" "has a further" (1 sec-) lap; so go NOW to the end
				$this->goToEnd();
			} else {
				$this->moveToTime($seconds);

			}

			$this->readLap();
		}
	}

	/**
	 * Convert distance comma-separated string to array
	 * + at the beginning means treat as intervals
	 * @return array
	 */
	public static function getDistancesFromString($distanceStr)
	{
		if (substr($distanceStr, 0, 1) == '+') {
			$distanceStr = substr($distanceStr, 1);
			$distanceArr = explode(',', $distanceStr);
			$distSum = 0;
			foreach ($distanceArr as $k => $v) {
				$distSum += $v;
				$distanceArr[$k] = $distSum;
			}
		} else {
			$distanceArr = explode(',', $distanceStr);
		}

		if (! self::isSorted($distanceArr)) return [];

		return $distanceArr;
	}

	/**
	 * Convert times from comma-separated string to array
	 * + at the beginning means treat as intervals
	 * ' means minutes
	 *
	 * @param string $string
	 * @return array
	 */
	public static function getTimesFromString($string)
	{
		$string = str_replace("'", ':00', $string);

		if (substr($string, 0, 1) == '+') {
			$times = self::explodeTimeStrings(substr($string, 1));
			$sum = 0;

			foreach ($times as $i => $time) {
				$sum += $time;
				$times[$i] = $sum;
			}
		} else {
			$times = self::explodeTimeStrings($string);
		}

		if (! self::isSorted($times))
		{
			return [];
		}

		return $times;
	}

    /**
     * @param string $commaSeparatedString
     */
    private static function explodeTimeStrings($commaSeparatedString): array
	{
		$timeStrings = explode(',', $commaSeparatedString);

		return array_map(function ($string) {
			$Time = new Duration($string);
			return $Time->seconds();
		}, $timeStrings);
	}

	protected function readLap()
	{
		$Lap = new Lap(
		    $this->TrackdataLoop->difference(Trackdata\Entity::TIME),
		    $this->TrackdataLoop->difference(Trackdata\Entity::DISTANCE)
		);

		$Lap->setTrackDuration($this->TrackdataLoop->time());
		$Lap->setTrackDistance($this->TrackdataLoop->distance());
		$Lap->setHR($this->TrackdataLoop->average(Trackdata\Entity::HEARTRATE), $this->TrackdataLoop->max(Trackdata\Entity::HEARTRATE));
		$this->addElevationFor($Lap);
		$this->calculateAdditionalValuesFor($Lap);

		$this->Laps->add($Lap);
	}

	protected function addElevationFor(Lap $Lap)
	{
		if ($this->RouteLoop === null) {
			return;
		}

		$Calculator = new Calculation\Elevation\Calculator($this->RouteLoop->sliceElevation());
		$Calculator->calculate();

		$Lap->setElevation($Calculator->elevationUp(), $Calculator->elevationDown());
	}

	protected function calculateAdditionalValuesFor(Lap $Lap)
	{
		if (! $this->CalculateAdditionalValues) {
			return;
		}

		$AdditionalData = [];
		$SlicedTrackdata = $this->TrackdataLoop->sliceObject();
		$this->addTrackdataAveragesToDataFrom($SlicedTrackdata, $AdditionalData);

		if($this->SwimdataLoop !== null) {
			$SlicedSwimdata = $this->SwimdataLoop->sliceObject();
			$this->addSwimmdataAveragesToDataFrom($SlicedSwimdata, $AdditionalData);
		}

		$this->addVO2maxToDataFrom($Lap, $AdditionalData);

		$Lap->setAdditionalValues($AdditionalData);
	}

    /**
     * @param array<string, mixed> $AdditionalData
     */
    protected function addTrackdataAveragesToDataFrom(Trackdata\Entity $Object, array &$AdditionalData) {
		$KeysToAverage = [
		    Activity\Entity::CADENCE => Trackdata\Entity::CADENCE,
		    Activity\Entity::STRIDE_LENGTH => Trackdata\Entity::STRIDE_LENGTH,
		    Activity\Entity::GROUNDCONTACT => Trackdata\Entity::GROUNDCONTACT,
		    Activity\Entity::GROUNDCONTACT_BALANCE => Trackdata\Entity::GROUNDCONTACT_BALANCE,
		    Activity\Entity::VERTICAL_OSCILLATION => Trackdata\Entity::VERTICAL_OSCILLATION,
		    Activity\Entity::VERTICAL_RATIO => Trackdata\Entity::VERTICAL_RATIO,
		    Activity\Entity::POWER => Trackdata\Entity::POWER,
		];

		$NewLoop = new Trackdata\Loop($Object);
		$NewLoop->goToEnd();

		foreach ($KeysToAverage as $objectKey => $trackdataKey) {
			if ($Object->has($trackdataKey)) {
				$AdditionalData[$objectKey] = $NewLoop->average($trackdataKey);
			}
		}
	}

    /**
     * #TSC add swim-data for the laps-popup.
     * @param array<string, mixed> $AdditionalData
     */
    protected function addSwimmdataAveragesToDataFrom(Swimdata\Entity $Object, array &$AdditionalData) {
		$KeysToAverage = [
		    Swimdata\Entity::STROKE => Swimdata\Entity::STROKE,
		    Swimdata\Entity::SWOLF => Swimdata\Entity::SWOLF,
		    Swimdata\Entity::SWOLFCYCLES => Swimdata\Entity::SWOLFCYCLES,
		];

		$NewLoop = new Swimdata\Loop($Object);

		// have we stroke-type and therefore rest lanes?
		if ($Object->has(Swimdata\Entity::STROKETYPE)) {
			// first determine the lanes
			$st = 0;
			while(! $NewLoop->isAtEnd()) {
				if($NewLoop->current(Swimdata\Entity::STROKETYPE) != \Runalyze\Profile\FitSdk\StrokeTypeProfile::BREAK) {
					// number of lanes of a lap
					$st++;
				}

				$NewLoop->nextStep();
			}

			if($NewLoop->current(Swimdata\Entity::STROKETYPE) != \Runalyze\Profile\FitSdk\StrokeTypeProfile::BREAK) {
				$st++;
			}

			$AdditionalData['lanes'] = $st; // number of lanes

			$NewLoop->reset();
			$NewLoop->goToEnd();

			// calculate stroke...swolf
			if ($st > 0) {
				foreach ($KeysToAverage as $objectKey => $dataKey) {
					if ($Object->has($dataKey)) {
						$AdditionalData[$objectKey] = round($NewLoop->sum($dataKey) / $st);
					}
				}
			}
		} else {
			// no rest lanes can be determine...so calculate without rest determination
			$NewLoop->goToEnd();

			// first determine the lanes
			if ($Object->has(Swimdata\Entity::STROKE)) {
				$AdditionalData['lanes'] = $NewLoop->num(Swimdata\Entity::STROKE);
			}

			// calculate stroke...swolf
			foreach ($KeysToAverage as $objectKey => $dataKey) {
				if ($Object->has($dataKey)) {
					$AdditionalData[$objectKey] = $NewLoop->average($dataKey);
				}
			}
		}

		if ($Object->has(Swimdata\Entity::STROKE)) {
			$AdditionalData[Activity\Entity::TOTAL_STROKES] = $NewLoop->sum(Swimdata\Entity::STROKE);
		}
	}

    /**
     * @param array<string, mixed> $AdditionalData
     */
    protected function addVO2maxToDataFrom(Lap $Lap, array &$AdditionalData) {
		if (Configuration::VO2max()->useElevationCorrection() && $Lap->hasElevation()) {
			$distance = (new Calculation\Elevation\DistanceModifier(
			    $Lap->distance()
			        ->kilometer(),
			    $Lap->elevationUp(),
			    $Lap->elevationDown(),
			    Configuration::VO2max()
			))->correctedDistance();
		} else {
			$distance = $Lap->distance()
			    ->kilometer();
		}

		$vo2max = new Calculation\JD\LegacyEffectiveVO2max();
        $vo2max->fromPaceAndHR(
            $distance,
            $Lap->duration()
                ->seconds(),
            $Lap->HRavg()
                ->inHRmax() / 100
        );

		if ($vo2max->value() > 0) {
			$AdditionalData[Activity\Entity::VO2MAX] = $vo2max->value();
		}
	}

	/**
	 * @param float $kilometer
	 */
	protected function moveToDistance($kilometer)
	{
		$this->TrackdataLoop->moveToDistance($kilometer);

		if ($this->RouteLoop !== null) {
			$this->RouteLoop->goToIndex($this->TrackdataLoop->index());
		}

		if ($this->SwimdataLoop !== null) {
			$this->SwimdataLoop->goToIndex($this->TrackdataLoop->index());
		}
	}

	/**
	 * @param int $seconds
	 */
	protected function moveToTime($seconds)
	{
		if ($this->UsePreviousTrackpointForTimeLaps) {
			$this->TrackdataLoop->moveToTimeBeforeOrAt($seconds);
		} else {
			$this->TrackdataLoop->moveToTime($seconds);
		}

		if ($this->RouteLoop !== null) {
			$this->RouteLoop->goToIndex($this->TrackdataLoop->index());
		}

		if ($this->SwimdataLoop !== null) {
			$this->SwimdataLoop->goToIndex($this->TrackdataLoop->index());
		}
	}

	protected function goToEnd()
	{
		$this->TrackdataLoop->goToEnd();

		if ($this->RouteLoop !== null) {
			$this->RouteLoop->goToEnd();
		}

		if ($this->SwimdataLoop !== null) {
			$this->SwimdataLoop->goToEnd();
		}
	}

	/**
	 * Go to end and read last lap
	 */
	protected function finish()
	{
		$this->goToEnd();
		$this->readLap();
	}

    /**
     * Is the given array sorted?
     * @param array<int, mixed> $data
     * @return bool true for e.g. [1, 2.5, 3], false for e.g. [1, 2, 1.5]
     */
    protected static function isSorted(array $data): bool
	{
		$num = count($data);

		for ($i = 1; $i < $num; ++$i) {
			if ($data[$i] < $data[$i - 1]) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Use previous trackpoints for sparse time-based lap boundaries.
	 *
	 * Intended for swimming data where trackpoints commonly represent
	 * complete pool lengths rather than one-second samples.
	 *
	 * @param bool $flag
	 */
	public function usePreviousTrackpointForTimeLaps($flag = true)
	{
		$this->UsePreviousTrackpointForTimeLaps = $flag;
	}
}

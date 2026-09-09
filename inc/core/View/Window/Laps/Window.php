<?php

/**
 * This file contains class::Window
 * @package Runalyze\View\Window\Laps
 */

namespace Runalyze\View\Window\Laps;

use Request;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Configuration;
use Runalyze\Data\Laps\Calculator;
use Runalyze\Data\Laps\Lap;
use Runalyze\Data\Laps\Laps;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Parameter\Application\PaceUnit;
use Runalyze\View\Activity\Context;

/**
 * Window for detailed laps info
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Window\Laps
 */
class Window
{
    /**
     * @var float
     */
    public const HANDMADE_LIMIT_TIME = 10.0;

    /**
     * @var float
     */
    public const HANDMADE_LIMIT_DISTANCE = 0.2;

    protected Context $Context;

    protected Laps $Laps;

    /**
     * Lap distance
     * @var mixed
     */
    protected $LapDistance = 0;

    /**
     * Lap time
     * @var mixed
     */
    protected $LapTime = 0;

    /**
     * Demanded time
     */
    protected Duration $DemandedTime;

    /**
     * Demanded pace
     */
    protected Pace $DemandedPace;

    /**
     * Manual distances
     */
    protected array $ManualDistances = [];

    /**
     * Manual times
     */
    protected array $ManualTimes = [];

    public function __construct(Context $context)
    {
        $this->Context = $context;

        $this->prepareProperties();
        $this->readPropertiesFromRequest();
        $this->constructLaps();
    }

    /**
     * Prepare internal properties
     */
    protected function prepareProperties()
    {
        $this->DemandedTime = new Duration();
        $this->DemandedPace = new Pace(0, 1);
        $this->DemandedPace->setLegacyUnitEnum(PaceUnit::MIN_PER_KM);

        if (isset($_POST['distance'])) {
            $_POST['distance'] = str_replace(',', '.', $_POST['distance']);
        } elseif ($this->Context->activity()->splits()->isEmpty()) {
            $_POST['distance'] = 1;
        }
    }

    /**
     * @return float [km]
     */
    protected function distanceToKm(float $valueInPreferredUnit): float
    {
        if (Configuration::General()->distanceUnitSystem()->isImperial()) {
            return $valueInPreferredUnit / DistanceUnitSystem::MILE_MULTIPLIER;
        }

        return $valueInPreferredUnit;
    }

    /**
     * Read request
     */
    protected function readPropertiesFromRequest()
    {
        if ((float) Request::param('distance') > 0) {
            $this->LapDistance = min($this->Context->trackdata()->totalDistance(), $this->distanceToKm((float) Request::param('distance')));
        }

        if ((string) Request::param('time') !== '') {
            $Time = new Duration(Request::param('time'));
            $this->LapTime = min($this->Context->trackdata()->totalTime(), $Time->seconds());
        }

        if ((string) Request::param('demanded-time') !== '') {
            $this->DemandedTime->fromString(Request::param('demanded-time'));

            if ($this->LapDistance > 0) {
                $this->DemandedPace->setTime($this->DemandedTime->seconds() / $this->LapDistance);
            }
        } elseif ($this->LapDistance > 0) {
            $this->DemandedTime->fromSeconds($this->Context->trackdata()->totalTime() / $this->Context->trackdata()->totalDistance() * $this->LapDistance);
            $this->DemandedPace->setTime($this->DemandedTime->seconds() / $this->LapDistance);
        }

        if ((string) Request::param('demanded-pace') !== '') {
            $this->DemandedPace->fromMinPerKm(Request::param('demanded-pace'));
            $this->DemandedTime->fromSeconds($this->LapDistance * $this->DemandedPace->secondsPerKm());
        } elseif ($this->DemandedPace->secondsPerKm() == 0) {
            $this->DemandedPace = $this->Context->dataview()
                ->pace();
        }

        if ((string) Request::param('manual-distances') !== '') {
            $this->ManualDistances = Calculator::getDistancesFromString(Request::param('manual-distances'));

            if (Configuration::General()->distanceUnitSystem()->isImperial()) {
                $this->ManualDistances = array_map([$this, 'distanceToKm'], $this->ManualDistances);
            }
        }

        if ((string) Request::param('manual-times') !== '') {
            $this->ManualTimes = Calculator::getTimesFromString(Request::param('manual-times'));
        }
    }

    protected function handmadeLapsDiffer(): bool
    {
        if ($this->Context->activity()->splits()->isEmpty()) {
            return false;
        }

        return $this->handmadeLapsTimeDiffers() && $this->handmadeLapsDistanceDiffers();
    }

    protected function handmadeLapsTimeDiffers(): bool
    {
        return abs($this->Context->trackdata()->totalTime() - $this->Context->activity()->splits()->totalTime()) > self::HANDMADE_LIMIT_TIME;
    }

    protected function handmadeLapsDistanceDiffers(): bool
    {
        return abs($this->Context->trackdata()->totalDistance() - $this->Context->activity()->splits()->totalDistance()) > self::HANDMADE_LIMIT_DISTANCE;
    }

    /**
     * Calculate values
     */
    protected function constructLaps()
    {
        $this->Laps = new Laps();
        $this->Laps->enableCalculationOfAdditionalValues();

        if (
            $this->ManualDistances === [] && $this->LapDistance == 0 &&
            $this->ManualTimes === [] && $this->LapTime == 0 &&
            ! $this->Context->activity()
                ->splits()
                ->isEmpty()
        ) {
            $this->constructLapsFromSplits();
        } else {
            $this->constructLapsFromTrackdata();
        }
    }

    /**
     * Construct laps from splits object
     */
    protected function constructLapsFromSplits()
    {
        if ($this->handmadeLapsDiffer() && ! Request::param('calculate-for-splits')) {
            $this->Laps->readFrom($this->Context->activity()->splits());
            return;
        }

        if ($this->handmadeLapsTimeDiffers()) {
            $this->constructLapsFromSplitsDistances();
        } else {
            $this->constructLapsFromSplitsTimes();
        }

        $num = $this->Laps->num();

        foreach ($this->Context->activity()->splits()->asArray() as $i => $split) {
            if ($i < $num) {
                $this->Laps->at($i)
                    ->setMode($split->getIntensity());
            }
        }
    }

    /**
     * Construct laps from splits object by distances
     */
    protected function constructLapsFromSplitsDistances()
    {
        $Distances = [];
        $sum = 0;

        foreach ($this->Context->activity()->splits()->asArray() as $split) {
            $Distances[] = $split->distance() + $sum;
            $sum += $split->distance();
        }

        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->route());
    }

    /**
     * Construct laps from splits object by times
     */
    protected function constructLapsFromSplitsTimes()
    {
        $Times = [];
        $sum = 0;

        foreach ($this->Context->activity()->splits()->asArray() as $split) {
            $Times[] = $split->time() + $sum;
            $sum += $split->time();
        }

        $this->Laps->calculateFromTimes($Times, $this->Context->trackdata(), $this->Context->route(), $this->Context->swimdata());
    }

    protected function constructLapsFromTrackdata()
    {
        if ($this->ManualTimes !== [] || $this->LapTime != 0) {
            $this->constructLapsFromTrackdataByTimes();
        } else {
            $this->constructLapsFromTrackdataByDistances();
        }
    }

    protected function constructLapsFromTrackdataByTimes()
    {
        $Times = $this->ManualTimes;

        if ($Times === []) {
            if ($this->LapTime == 0) {
                $this->LapTime = 300;
            }

            $LastStep = $this->LapTime * floor($this->Context->trackdata()->totalTime() / $this->LapTime);

            if ($LastStep <= $this->LapTime) {
                $Times = [$this->LapTime];
            } else {
                $Times = range($this->LapTime, $LastStep, $this->LapTime);
            }
        }

        $this->Laps->calculateFromTimes($Times, $this->Context->trackdata(), $this->Context->route());
    }

    protected function constructLapsFromTrackdataByDistances()
    {
        $Distances = $this->ManualDistances;

        if ($Distances === []) {
            if ($this->LapDistance == 0) {
                $this->LapDistance = 1;
            }

            $LastStep = $this->LapDistance * floor($this->Context->trackdata()->totalDistance() / $this->LapDistance);

            if ($LastStep <= $this->LapDistance) {
                $Distances = [$this->LapDistance];
            } else {
                $Distances = range($this->LapDistance, $LastStep, $this->LapDistance);
            }
        }

        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->route());
    }

    public function display()
    {
        echo '<div class="panel-heading">';
        echo '<h1>' . $this->Context->dataview()->titleWithComment() . '</h1>';
        echo '</div>';

        // #TSC allow scrollbar if width is to small
        echo '<div class="panel-content" style="overflow-x: auto;">';
        $this->displayForm();
        $this->displayTable();
        echo '</div>';
    }

    /**
     * Display formular
     */
    protected function displayForm()
    {
        $Form = new Form();

        if (! $this->Context->activity()->splits()->isEmpty()) {
            if ($this->handmadeLapsDiffer()) {
                $Form->activateHandmadeDifference();
            }

            $Form->activateHandmadeInfo();
        }

        $Form->display();
    }

    /**
     * Display rounds
     */
    protected function displayTable()
    {
        $this->DemandedPace->setUnit($this->Context->sport()->legacyPaceUnit());

        $Table = new Table(
            $this->Laps,
            $this->DemandedTime,
            $this->DemandedPace,
            $this->Context->sport()
                ->getInternalProfileEnum(),
            $this->Context->activity()
                ->splitsAdditional()
        );

        if ($this->LapDistance > 0) {
            $Table->setAverage(
                $this->Context->dataview()
                    ->pace(),
                new Duration($this->Context->trackdata()->totalTime() / $this->Context->trackdata()->totalDistance() * $this->LapDistance)
            );
        } else {
            $Table->setAverage($this->Context->dataview()->pace());
        }

        $Table->display();
    }
}

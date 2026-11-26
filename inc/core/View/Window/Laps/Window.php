<?php

/**
 * This file contains class::Window
 * @package Runalyze\View\Window\Laps
 */

namespace Runalyze\View\Window\Laps;

use Request;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Configuration;
use Runalyze\Data\Laps\Calculator;
use Runalyze\Data\Laps\Lap;
use Runalyze\Data\Laps\Laps;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\View\Activity\Context;

/**
 * Window for detailed laps info
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Window\Laps
 */
class Window
{
    public const float HANDMADE_LIMIT_TIME = 10.0;

    public const float HANDMADE_LIMIT_DISTANCE = 0.2;

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
    protected float $demandedSecondsPerKilometer = 0.0;

    /**
     * Manual distances
     */
    protected array $ManualDistances = [];

    /**
     * Manual times
     */
    protected array $ManualTimes = [];

    public function __construct(
        protected Context $Context
    ) {
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

        if (Request::param('time') !== '') {
            $Time = new Duration(Request::param('time'));
            $this->LapTime = min($this->Context->trackdata()->totalTime(), $Time->seconds());
        }

        if (Request::param('demanded-time') !== '') {
            $this->DemandedTime->fromString(Request::param('demanded-time'));

            if ($this->LapDistance > 0) {
                $this->demandedSecondsPerKilometer = $this->DemandedTime->seconds() / $this->LapDistance;
            }
        } elseif ($this->LapDistance > 0) {
            $this->DemandedTime->fromSeconds($this->Context->trackdata()->totalTime() / $this->Context->trackdata()->totalDistance() * $this->LapDistance);
            $this->demandedSecondsPerKilometer = $this->DemandedTime->seconds() / $this->LapDistance;
        }

        if (Request::param('demanded-pace') !== '') {
            $this->demandedSecondsPerKilometer = new Duration(Request::param('demanded-pace'))->seconds();
            $this->DemandedTime->fromSeconds($this->LapDistance * round($this->demandedSecondsPerKilometer));
        } elseif (round($this->demandedSecondsPerKilometer) === 0.0) {
            $activity = $this->Context->activity();
            $this->demandedSecondsPerKilometer = $activity->distance() > 0.0 ? $activity->duration() / $activity->distance() : 0.0;
        }

        if (Request::param('manual-distances') !== '') {
            $this->ManualDistances = Calculator::getDistancesFromString(Request::param('manual-distances'));

            if (Configuration::General()->distanceUnitSystem()->isImperial()) {
                $this->ManualDistances = array_map($this->distanceToKm(...), $this->ManualDistances);
            }
        }

        if (Request::param('manual-times') !== '') {
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
            $this->ManualDistances === [] && $this->LapDistance == 0
            && $this->ManualTimes === [] && $this->LapTime == 0
            && ! $this->Context->activity()
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

        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->hasRoute() ? $this->Context->route() : null);
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

        $this->Laps->calculateFromTimes($Times, $this->Context->trackdata(), $this->Context->hasRoute() ? $this->Context->route() : null, $this->Context->swimdata());
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

        $this->Laps->calculateFromTimes($Times, $this->Context->trackdata(), $this->Context->hasRoute() ? $this->Context->route() : null);
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

        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->hasRoute() ? $this->Context->route() : null);
    }

    public function display(): void
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
        $Table = new Table(
            $this->Laps,
            $this->DemandedTime,
            $this->demandedSecondsPerKilometer,
            $this->Context->sport()->getSpeedUnit(),
            $this->Context->sport()
                ->getInternalProfileEnum(),
            $this->Context->activity()
                ->splitsAdditional()
        );

        $activity = $this->Context->activity();
        $Table->setAverage(
            PaceFormatter::secondsPerKilometer($activity->duration(), $activity->distance() ?? 0.0),
            $this->LapDistance > 0
                ? new Duration($this->Context->trackdata()->totalTime() / $this->Context->trackdata()->totalDistance() * $this->LapDistance)
                : null
        );

        $Table->display();
    }
}

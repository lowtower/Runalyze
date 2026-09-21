<?php

/**
 * This file contains class::Activity
 * @package Runalyze\View\Leaflet
 */

namespace Runalyze\View\Leaflet;

use League\Geotools\Geohash\Geohash;
use League\Geotools\Geotools;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Configuration;
use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;
use Runalyze\View\Leaflet\Route as LeafletRoute;

/**
 * Leaflet route for an activity
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Leaflet
 */
class Activity extends LeafletRoute
{
    /**
     * Route
     */
    protected \Runalyze\Model\Route\Entity $Route;

    /**
     * Route loop
     */
    protected \Runalyze\Model\Route\Loop $RouteLoop;

    /**
     * Trackdata loop
     */
    protected ?\Runalyze\Model\Trackdata\Loop $TrackdataLoop = null;

    /**
     * Path
     */
    protected array $Path = [];

    /**
     * Info
     */
    protected array $Info = [];

    /**
     * Distance
     * @var int km or miles
     */
    protected int $Dist = 1;

    /**
     * Time
     */
    protected int $Time = 0;

    /**
     * Pause limit
     * @var float distance in km
     */
    protected float $PauseLimit = 0.05;

    protected int $PauseIndex = 0;

    protected bool $PathShouldBreak = false;

    /**
     * Construct new route
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $id,
        Route\Entity $route,
        protected ?\Runalyze\Model\Trackdata\Entity $Trackdata = null,
        protected bool $addIconsAndInfo = true
    ) {
        parent::__construct($id);

        if (! $route->hasPositionData()) {
            throw new \InvalidArgumentException('Route needs position data.');
        }

        $this->Route = $route;

        $this->addOption('options', [
            'color' => '#FF5500',
        ]);

        $this->createRoute();
    }

    protected function createRoute()
    {
        $this->prepareLoops();
        $this->findLimitForPauses();
        $this->fillCurrentSegment();

        while ($this->nextStep()) {
            $this->checkForPause();
            $this->fillCurrentSegment();
            $this->checkForDistanceMarker();

            if ($this->PathShouldBreak) {
                $this->addCurrentSegment();
                $this->PathShouldBreak = false;
            }
        }

        $this->addCurrentSegment();
        $this->addStartAndEndIcon();
        $this->defineInfoLabels();
    }

    protected function prepareLoops()
    {
        $stepSize = (int) Configuration::ActivityView()->routePrecision()->value();

        $this->RouteLoop = new Route\Loop($this->Route);
        $this->RouteLoop->setStepSize($stepSize);

        if (
            $this->Trackdata instanceof \Runalyze\Model\Trackdata\Entity
            && ($this->Route->num() === $this->Trackdata->num())
            && $this->Trackdata->has(Trackdata\Entity::TIME)
        ) {
            $this->TrackdataLoop = new Trackdata\Loop($this->Trackdata);
            $this->TrackdataLoop->setStepSize($stepSize);
        }
    }

    /**
     * Has trackdata loop?
     */
    protected function hasTrackdataLoop(): bool
    {
        return $this->TrackdataLoop instanceof \Runalyze\Model\Trackdata\Loop;
    }

    protected function nextStep(): bool
    {
        if ($this->hasTrackdataLoop()) {
            $this->TrackdataLoop->nextStep();
        }

        return $this->RouteLoop->nextStep();
    }

    protected function goToIndex(int $index)
    {
        if ($this->hasTrackdataLoop()) {
            $this->TrackdataLoop->goToIndex($index);
        }

        $this->RouteLoop->goToIndex($index);
    }

    protected function fillCurrentSegment()
    {
        /** @var Geohash $geohash */
        $geohash = (new Geotools())->geohash()
            ->decode($this->RouteLoop->geohash());
        if ($this->RouteLoop->geohash() === '7zzzzzzzzzzz') {
            return;
        }

        $this->Path[] = [(float) $geohash->getCoordinate()->getLatitude(), (float) $geohash->getCoordinate()->getLongitude()];

        if ($this->addIconsAndInfo && $this->hasTrackdataLoop()) {
            $this->Info[] = [
                $this->TrackdataLoop->distance(),
                $this->TrackdataLoop->time(),
            ];
        }
    }

    /**
     * Define labels
     */
    protected function defineInfoLabels()
    {
        if ($this->addIconsAndInfo && $this->hasTrackdataLoop()) {
            $this->InfoLabels = [
                __('Distance'),
                __('Time'),
            ];
            $this->InfoFunctions = [
                'function(v){return (v*' . Configuration::General()->distanceUnitSystem()->distanceToPreferredUnitFactor() . ').toFixed(2)+"&nbsp;' . Configuration::General()->distanceUnitSystem()->distanceUnit() . '";}',
                'function(v){return (new Date(v * 1000)).toUTCString().match(/(\d\d:\d\d:\d\d)/)[0];}',
            ];
        }
    }

    protected function checkForDistanceMarker()
    {
        if (! $this->addIconsAndInfo || ! $this->hasTrackdataLoop()) {
            return;
        }

        if (round((new Distance($this->TrackdataLoop->distance()))->valueInPreferredUnit(), 2) >= $this->Dist) {
            $Pace = new Pace($this->TrackdataLoop->time() - $this->Time);

            $Tooltip = sprintf(__('<strong>%s. %s</strong> in %s'), $this->Dist, Configuration::General()->distanceUnitSystem()->distanceUnit(), $Pace->asMinPerKm()) . '<br>';
            $Tooltip .= sprintf(__('<strong>Time:</strong> %s'), Duration::format($this->TrackdataLoop->time()));

            $this->addMarkerGeohash(
                $this->RouteLoop->geohash(),
                $this->distIcon($this->Dist),
                $Tooltip
            );

            ++$this->Dist;
            $this->Time = $this->TrackdataLoop->time();
        }
    }

    protected function addStartAndEndIcon()
    {
        if (! $this->addIconsAndInfo) {
            return;
        }

        $this->addMarker(
            $this->Paths[0][0][0],
            $this->Paths[0][0][1],
            $this->startIcon(),
            __('Start')
        );

        if ($this->hasTrackdataLoop()) {
            $Tooltip = sprintf(__('<strong>Total:</strong> %s'), Distance::format($this->TrackdataLoop->distance()));
            $Tooltip .= '<br>' . sprintf(__('<strong>Time:</strong> %s'), Duration::format($this->TrackdataLoop->time()));
        } else {
            $Tooltip = '';
        }

        $this->addMarkerGeohash(
            $this->RouteLoop->geohash(),
            $this->endIcon(),
            $Tooltip
        );
    }

    protected function addCurrentSegment()
    {
        $this->addSegment($this->Path, $this->Info);

        $this->Path = [];
        $this->Info = [];
    }

    protected function findLimitForPauses()
    {
        if ($this->Trackdata instanceof \Runalyze\Model\Trackdata\Entity && $this->Trackdata->has(Trackdata\Entity::DISTANCE)) {
            $SecondsForDist = (int) Configuration::ActivityView()->routeBreak()->value();
            $AveragePace = $this->Trackdata->totalDistance() > 0 ? $this->Trackdata->totalTime() / $this->Trackdata->totalDistance() : 0;

            if ($AveragePace > 0) {
                $this->PauseLimit = max($this->PauseLimit, $SecondsForDist / $AveragePace);
            }
        }

        $this->PauseLimit *= (int) Configuration::ActivityView()->routePrecision()->value();
    }

    protected function checkForPause()
    {
        if ($this->Trackdata instanceof \Runalyze\Model\Trackdata\Entity && $this->Trackdata->hasPauses() && $this->hasTrackdataLoop()) {
            if (
                $this->PauseIndex < $this->Trackdata->pauses()
                    ->num() &&
                $this->Trackdata->pauses()
                    ->at($this->PauseIndex)
                    ->time() <= $this->Trackdata->at($this->TrackdataLoop->index(), Trackdata\Entity::TIME)
            ) {
                if ($this->Trackdata->pauses()->at($this->PauseIndex)->time() < $this->Trackdata->at($this->TrackdataLoop->index(), Trackdata\Entity::TIME)) {
                    $this->handleExactPausePosition();
                } else {
                    $this->addCurrentPauseIcon();
                    $this->PathShouldBreak = true;
                }

                $this->PauseIndex++;
            }
        } elseif (! Configuration::ActivityView()->routeBreak()->never() && $this->RouteLoop->calculatedStepDistance() > $this->PauseLimit) {
            $this->addCurrentSegment();
        }
    }

    protected function handleExactPausePosition()
    {
        $this->goToIndex($this->RouteLoop->index() - $this->RouteLoop->currentStepSize() + 1);

        while ($this->Trackdata->pauses()->at($this->PauseIndex)->time() > $this->Trackdata->at($this->TrackdataLoop->index(), Trackdata\Entity::TIME)) {
            $this->goToIndex($this->RouteLoop->index() + 1);
        }

        $this->addCurrentPauseIcon();
        $this->fillCurrentSegment();
        $this->addCurrentSegment();

        $this->goToIndex($this->RouteLoop->index() + 1);
    }

    /**
     * Add icon for current pause
     */
    protected function addCurrentPauseIcon()
    {
        $Pause = $this->Trackdata->pauses()
            ->at($this->PauseIndex);
        $Index = $this->RouteLoop->index();

        // #TSC: add also pause-index to the tooltip
        $Tooltip = sprintf('<strong>%d.</strong> ' . __('<strong>Pause</strong> of %s'), $this->PauseIndex + 1, Duration::format($Pause->duration()));
        $Tooltip .= '<br>' . sprintf(__('<strong>Distance:</strong> %s'), Distance::format($this->Trackdata->at($Index, Trackdata\Entity::DISTANCE)));
        $Tooltip .= '<br>' . sprintf(__('<strong>Time:</strong> %s'), Duration::format($this->Trackdata->at($Index, Trackdata\Entity::TIME)));

        if ($Pause->hasHeartRateInfo()) {
            $Tooltip .= '<br>' . sprintf(__('<strong>Heart rate:</strong>') . ' ' . __('%s to %s'), $Pause->hrStart(), $Pause->hrEnd() . ' bpm');
        }

        $this->addMarkerGeohash(
            $this->Route->at($Index, Route\Entity::GEOHASHES),
            $this->pauseIcon(),
            $Tooltip
        );
    }
}

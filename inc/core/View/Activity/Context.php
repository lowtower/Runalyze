<?php

/**
 * This file contains class::Context
 * @package Runalyze\View\Activity
 */

namespace Runalyze\View\Activity;

use Request;
use Runalyze\Configuration;
use Runalyze\Model;

/**
 * Activity context
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity
 */
class Context
{
    protected Model\Activity\Entity $Activity;

    protected Model\Trackdata\Entity $Trackdata;

    protected ?Model\Swimdata\Entity $Swimdata = null;

    protected ?Model\Route\Entity $Route = null;

    protected Model\HRV\Entity $HRV;

    protected Model\Sport\Entity $Sport;

    protected Model\RaceResult\Entity $RaceResult;

    protected \Runalyze\View\Activity\Dataview $Dataview;

    public function __construct(int $activityID, int $accountID)
    {
        $Factory = new Model\Factory($accountID);

        $this->Activity = $Factory->activity($activityID);
        $this->Trackdata = $Factory->trackdata($activityID);
        $this->Swimdata = $Factory->swimdata($activityID);
        $this->RaceResult = $Factory->raceResult($activityID);
        $this->Route = $this->Activity->get(Model\Activity\Entity::ROUTEID) ? $Factory->route($this->Activity->get(Model\Activity\Entity::ROUTEID)) : null;
        $this->HRV = $Factory->hrv($activityID);
        $this->Sport = $Factory->sport($this->Activity->sportid());

        $this->Swimdata->fillDistanceArray($this->Trackdata);
        $this->Swimdata->fillSwolfArray($this->Trackdata);

        $this->Dataview = new Dataview($this->Activity);
    }

    public function __clone()
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (is_object($value)) {
                $this->{$property} = clone $value;
            }
        }
    }

    public function activity(): Model\Activity\Entity
    {
        return $this->Activity;
    }

    public function trackdata(): Model\Trackdata\Entity
    {
        return $this->Trackdata;
    }

    public function swimdata(): Model\Swimdata\Entity
    {
        return $this->Swimdata;
    }

    public function hrv(): Model\HRV\Entity
    {
        return $this->HRV;
    }

    public function sport(): Model\Sport\Entity
    {
        return $this->Sport;
    }

    public function route(): Model\Route\Entity
    {
        return $this->Route;
    }

    public function raceResult(): Model\RaceResult\Entity
    {
        return $this->RaceResult;
    }

    public function dataview(): \Runalyze\View\Activity\Dataview
    {
        return $this->Dataview;
    }

    public function hasTrackdata(): bool
    {
        return ! $this->Trackdata->isEmpty();
    }

    public function hasRoute(): bool
    {
        return $this->Route instanceof Model\Route\Entity;
    }

    public function hasSwimdata(): bool
    {
        return $this->Swimdata instanceof Model\Swimdata\Entity;
    }

    public function hasHRV(): bool
    {
        return ! $this->HRV->isEmpty();
    }

    public function hasRaceResult(): bool
    {
        return ! $this->RaceResult->isEmpty();
    }

    public function hideMap(): bool
    {
        if (! Request::isOnSharedPage()) {
            return false;
        }

        $RoutePrivacy = Configuration::Privacy()->RoutePrivacy();

        if ($RoutePrivacy->showAlways()) {
            return false;
        }

        if ($RoutePrivacy->showRace()) {
            return ! $this->hasRaceResult();
        }

        return true;
    }
}

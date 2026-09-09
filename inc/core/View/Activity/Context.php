<?php

/**
 * This file contains class::Context
 * @package Runalyze\View\Activity
 */

namespace Runalyze\View\Activity;

use Request;
use Runalyze\Configuration;
use Runalyze\Model\Activity;
use Runalyze\Model\Factory;

/**
 * Activity context
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity
 */
class Context
{
    /**
     * @var \Runalyze\Model\Activity\Entity
     */
    protected $Activity;

    /**
     * @var \Runalyze\Model\Trackdata\Entity
     */
    protected $Trackdata;

    /**
     * @var \Runalyze\Model\Swimdata\Entity
     */
    protected $Swimdata;

    /**
     * @var \Runalyze\Model\Route\Entity
     */
    protected $Route;

    /**
     * @var \Runalyze\Model\HRV\Entity
     */
    protected $HRV;

    /**
     * @var \Runalyze\Model\Sport\Entity
     */
    protected $Sport;

    /**
     * @var \Runalyze\Model\RaceResult\Entity
     */
    protected $RaceResult;

    /**
     * @var \Runalyze\View\Activity\Dataview
     */
    protected $Dataview;

    /**
     * Construct context
     */
    public function __construct(int $activityID, int $accountID)
    {
        $Factory = new Factory($accountID);

        $this->Activity = $Factory->activity($activityID);
        $this->Trackdata = $Factory->trackdata($activityID);
        $this->Swimdata = $Factory->swimdata($activityID);
        $this->RaceResult = $Factory->raceResult($activityID);
        $this->Route = $this->Activity->get(Activity\Entity::ROUTEID) ? $Factory->route($this->Activity->get(Activity\Entity::ROUTEID)) : null;
        $this->HRV = $Factory->hrv($activityID);
        $this->Sport = $Factory->sport($this->Activity->sportid());

        $this->Swimdata->fillDistanceArray($this->Trackdata);
        $this->Swimdata->fillSwolfArray($this->Trackdata);

        $this->Dataview = new Dataview($this->Activity);
    }

    /**
     * Clone object
     */
    public function __clone()
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (is_object($value)) {
                $this->{$property} = clone $value;
            }
        }
    }

    /**
     * @return \Runalyze\Model\Activity\Entity
     */
    public function activity()
    {
        return $this->Activity;
    }

    /**
     * @return \Runalyze\Model\Trackdata\Entity
     */
    public function trackdata()
    {
        return $this->Trackdata;
    }

    /**
     * @return \Runalyze\Model\Swimdata\Entity
     */
    public function swimdata()
    {
        return $this->Swimdata;
    }

    /**
     * @return \Runalyze\Model\HRV\Entity
     */
    public function hrv()
    {
        return $this->HRV;
    }

    /**
     * @return \Runalyze\Model\Sport\Entity
     */
    public function sport()
    {
        return $this->Sport;
    }

    /**
     * @return \Runalyze\Model\Route\Entity
     */
    public function route()
    {
        return $this->Route;
    }

    /**
     * @return \Runalyze\Model\RaceResult\Entity
     */
    public function raceResult()
    {
        return $this->RaceResult;
    }

    /**
     * @return \Runalyze\View\Activity\Dataview
     */
    public function dataview()
    {
        return $this->Dataview;
    }

    public function hasTrackdata(): bool
    {
        return ! $this->Trackdata->isEmpty();
    }

    public function hasRoute(): bool
    {
        return $this->Route !== null;
    }

    public function hasSwimdata(): bool
    {
        return $this->Swimdata !== null;
    }

    public function hasHRV(): bool
    {
        return ! $this->HRV->isEmpty();
    }

    public function hasRaceResult(): bool
    {
        return ! $this->RaceResult->isEmpty();
    }

    /**
     * @return bool
     */
    public function hideMap()
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

<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class ActivityContext
{
    /** @var Training */
    protected $Activity;

    /** @var Trackdata|null */
    protected $Trackdata;

    /** @var Swimdata|null */
    protected $Swimdata;

    /** @var Route|null */
    protected $Route;

    /** @var Hrv|null */
    protected $HRV;

    /** @var Raceresult|null */
    protected $RaceResult;

    /** @var ActivityDecorator */
    protected $Decorator;

    public function __construct(
        Training $activity,
        ?Trackdata $trackdata = null,
        ?Swimdata $swimdata = null,
        ?Route $route = null,
        ?Hrv $hrv = null,
        ?Raceresult $raceResult = null
    )
    {
        $this->Activity = $activity;
        $this->Trackdata = $trackdata;
        $this->Swimdata = $swimdata;
        $this->Route = $route;
        $this->HRV = $hrv;
        $this->RaceResult = $raceResult;

        $this->Decorator = new ActivityDecorator($this);
    }

    /**
     * @return \Runalyze\Bundle\CoreBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->Activity->getAccount();
    }

    /**
     * @return Training
     */
    public function getActivity()
    {
        return $this->Activity;
    }

    /**
     * @return Trackdata|null
     */
    public function getTrackdata()
    {
        return $this->Trackdata;
    }

    /**
     * @return Swimdata|null
     */
    public function getSwimdata()
    {
        return $this->Swimdata;
    }

    /**
     * @return Hrv|null
     */
    public function getHrv()
    {
        return $this->HRV;
    }

    /**
     * @return Sport
     */
    public function getSport()
    {
        return $this->Activity->getSport();
    }

    /**
     * @return Route|null
     */
    public function getRoute()
    {
        return $this->Route;
    }

    /**
     * @return Raceresult|null
     */
    public function getRaceResult()
    {
        return $this->RaceResult;
    }

    public function hasTrackdata(): bool {
        return null !== $this->Trackdata;
    }

    public function hasRoute(): bool
    {
        return null !== $this->Route;
    }

    public function hasSwimdata(): bool
    {
        return null !== $this->Swimdata;
    }

    public function hasHRV(): bool
    {
        return null !== $this->HRV;
    }

    public function hasRaceResult(): bool
    {
        return null !== $this->RaceResult;
    }

    /**
     * @return ActivityDecorator
     */
    public function getDecorator()
    {
        return $this->Decorator;
    }

    public function canShowMap(): bool
    {
        return $this->hasRoute() && $this->Route->hasGeohashes();
    }
}

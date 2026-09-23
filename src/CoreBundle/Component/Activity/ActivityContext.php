<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Entity;

class ActivityContext
{
    protected ActivityDecorator $Decorator;

    public function __construct(
        protected Entity\Training $Activity,
        protected ?Entity\Trackdata $Trackdata = null,
        protected ?Entity\Swimdata $Swimdata = null,
        protected ?Entity\Route $Route = null,
        protected ?Entity\Hrv $HRV = null,
        protected ?Entity\Raceresult $RaceResult = null
    ) {
        $this->Decorator = new ActivityDecorator($this);
    }

    public function getAccount(): Entity\Account
    {
        return $this->Activity->getAccount();
    }

    public function getActivity(): Entity\Training
    {
        return $this->Activity;
    }

    public function getTrackdata(): ?Entity\Trackdata
    {
        return $this->Trackdata;
    }

    public function getSwimdata(): ?Entity\Swimdata
    {
        return $this->Swimdata;
    }

    public function getHrv(): ?Entity\Hrv
    {
        return $this->HRV;
    }

    public function getSport(): Entity\Sport
    {
        return $this->Activity->getSport();
    }

    public function getRoute(): ?Entity\Route
    {
        return $this->Route;
    }

    public function getRaceResult(): ?Entity\Raceresult
    {
        return $this->RaceResult;
    }

    public function hasTrackdata(): bool
    {
        return $this->Trackdata instanceof Entity\Trackdata;
    }

    public function hasRoute(): bool
    {
        return $this->Route instanceof Entity\Route;
    }

    public function hasSwimdata(): bool
    {
        return $this->Swimdata instanceof Entity\Swimdata;
    }

    public function hasHRV(): bool
    {
        return $this->HRV instanceof Entity\Hrv;
    }

    public function hasRaceResult(): bool
    {
        return $this->RaceResult instanceof Entity\Raceresult;
    }

    public function getDecorator(): ActivityDecorator
    {
        return $this->Decorator;
    }

    public function canShowMap(): bool
    {
        return $this->hasRoute() && $this->Route->hasGeohashes();
    }
}

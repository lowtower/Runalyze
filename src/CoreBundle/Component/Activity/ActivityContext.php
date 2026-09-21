<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

class ActivityContext
{
    protected \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator $Decorator;

    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Entity\Training $Activity,
        protected ?\Runalyze\Bundle\CoreBundle\Entity\Trackdata $Trackdata = null,
        protected ?\Runalyze\Bundle\CoreBundle\Entity\Swimdata $Swimdata = null,
        protected ?\Runalyze\Bundle\CoreBundle\Entity\Route $Route = null,
        protected ?\Runalyze\Bundle\CoreBundle\Entity\Hrv $HRV = null,
        protected ?\Runalyze\Bundle\CoreBundle\Entity\Raceresult $RaceResult = null
    ) {
        $this->Decorator = new ActivityDecorator($this);
    }

    public function getAccount(): \Runalyze\Bundle\CoreBundle\Entity\Account
    {
        return $this->Activity->getAccount();
    }

    public function getActivity(): \Runalyze\Bundle\CoreBundle\Entity\Training
    {
        return $this->Activity;
    }

    public function getTrackdata(): ?\Runalyze\Bundle\CoreBundle\Entity\Trackdata
    {
        return $this->Trackdata;
    }

    public function getSwimdata(): ?\Runalyze\Bundle\CoreBundle\Entity\Swimdata
    {
        return $this->Swimdata;
    }

    public function getHrv(): ?\Runalyze\Bundle\CoreBundle\Entity\Hrv
    {
        return $this->HRV;
    }

    public function getSport(): \Runalyze\Bundle\CoreBundle\Entity\Sport
    {
        return $this->Activity->getSport();
    }

    public function getRoute(): ?\Runalyze\Bundle\CoreBundle\Entity\Route
    {
        return $this->Route;
    }

    public function getRaceResult(): ?\Runalyze\Bundle\CoreBundle\Entity\Raceresult
    {
        return $this->RaceResult;
    }

    public function hasTrackdata(): bool
    {
        return $this->Trackdata instanceof \Runalyze\Bundle\CoreBundle\Entity\Trackdata;
    }

    public function hasRoute(): bool
    {
        return $this->Route instanceof \Runalyze\Bundle\CoreBundle\Entity\Route;
    }

    public function hasSwimdata(): bool
    {
        return $this->Swimdata instanceof \Runalyze\Bundle\CoreBundle\Entity\Swimdata;
    }

    public function hasHRV(): bool
    {
        return $this->HRV instanceof \Runalyze\Bundle\CoreBundle\Entity\Hrv;
    }

    public function hasRaceResult(): bool
    {
        return $this->RaceResult instanceof \Runalyze\Bundle\CoreBundle\Entity\Raceresult;
    }

    public function getDecorator(): \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator
    {
        return $this->Decorator;
    }

    public function canShowMap(): bool
    {
        return $this->hasRoute() && $this->Route->hasGeohashes();
    }
}

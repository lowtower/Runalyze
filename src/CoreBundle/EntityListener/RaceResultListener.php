<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;

class RaceResultListener
{
    public function __construct(
        protected RecalculationManager $RecalculationManager
    ) {
    }

    public function postPersist(Raceresult $race, \Doctrine\ORM\Event\PostPersistEventArgs $args): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    public function postUpdate(Raceresult $race, \Doctrine\ORM\Event\PostUpdateEventArgs $args): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    public function postRemove(Raceresult $race, \Doctrine\ORM\Event\PostRemoveEventArgs $args): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    protected function scheduleCorrectionFactorRecalculationIfRequired(Raceresult $race)
    {
        $activity = $race->getActivity();

        if (
            $activity->getSport()
                ->getInternalSport()
                ->isRunning() &&
            $activity->getUseVO2max() &&
            $activity->getVO2max() > 0
        ) {
            $this->RecalculationManager->scheduleEffectiveVO2maxCorrectionFactorCalculation($race->getAccount());
        }
    }
}

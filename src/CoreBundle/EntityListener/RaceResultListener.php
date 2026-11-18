<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;

#[AsEntityListener(event: 'postPersist', lazy: true, entity: Raceresult::class)]
#[AsEntityListener(event: 'postUpdate', lazy: true, entity: Raceresult::class)]
#[AsEntityListener(event: 'postRemove', lazy: true, entity: Raceresult::class)]
class RaceResultListener
{
    public function __construct(
        protected RecalculationManager $RecalculationManager
    ) {}

    public function postPersist(Raceresult $race): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    public function postUpdate(Raceresult $race): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    public function postRemove(Raceresult $race): void
    {
        $this->scheduleCorrectionFactorRecalculationIfRequired($race);
    }

    protected function scheduleCorrectionFactorRecalculationIfRequired(Raceresult $race)
    {
        $activity = $race->getActivity();

        if (
            $activity->getSport()
                ->getInternalSport()
                ->isRunning()
            && $activity->getUseVO2max()
            && $activity->getVO2max() > 0
        ) {
            $this->RecalculationManager->scheduleEffectiveVO2maxCorrectionFactorCalculation($race->getAccount());
        }
    }
}

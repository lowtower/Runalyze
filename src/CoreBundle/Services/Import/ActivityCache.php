<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class ActivityCache
{
    public function __construct(
        protected AdapterInterface $Cache
    ) {
    }

    public function save(Training $activity): string
    {
        $hash = $this->getRandomHash($activity->hasAccount() ? $activity->getAccount() : null);
        $item = $this->Cache->getItem($hash);
        $item->set($activity);

        $this->Cache->save($item);

        return $hash;
    }

    public function get(?string $hash, ?Training $activityToMerge = null, bool $removerAfterwards = false): ?Training
    {
        if ($hash === null || $hash === '') {
            return $activityToMerge;
        }

        $activity = $this->Cache->getItem($hash)
            ->get();

        if ($removerAfterwards) {
            $this->remove($hash);
        }

        if (! ($activity instanceof Training)) {
            return $activityToMerge;
        }

        if ($activityToMerge instanceof Training) {
            return $this->merge($activity, $activityToMerge);
        }

        return $activity;
    }

    public function remove(string $hash): void
    {
        $this->Cache->deleteItem($hash);
    }

    public function merge(Training $activityFromCache, Training $activityToMerge): Training
    {
        $this->mergeActivityDataFromParserThatIsNotInForm($activityFromCache, $activityToMerge);

        $activityToMerge->setRoute($activityFromCache->getRoute());
        $activityToMerge->setTrackdata($activityFromCache->getTrackdata());
        $activityToMerge->setSwimdata($activityFromCache->getSwimdata());
        $activityToMerge->setHrv($activityFromCache->getHrv());
        $activityToMerge->setRaceresult($activityFromCache->getRaceresult());

        $activityToMerge->getAdapter()
            ->setAccountToRelatedEntities();

        return $activityToMerge;
    }

    protected function mergeActivityDataFromParserThatIsNotInForm(Training $activityFromCache, Training $activityToMerge): void
    {
        $activityToMerge->setTimezoneOffset($activityFromCache->getTimezoneOffset());
        $activityToMerge->setElapsedTime($activityFromCache->getElapsedTime());
        $activityToMerge->setFitVO2maxEstimate($activityFromCache->getFitVO2maxEstimate());
        $activityToMerge->setFitHrvAnalysis($activityFromCache->getFitHrvAnalysis());
        $activityToMerge->setFitRecoveryTime($activityFromCache->getFitRecoveryTime());
        $activityToMerge->setKcalInRest($activityFromCache->getKcalInRest()); // #TSC transfer calories in rest
        $activityToMerge->setFitTrainingEffectBenefit($activityFromCache->getFitTrainingEffectBenefit()); // #TSC transfer benefit
        $activityToMerge->setFitTrainingEffect($activityFromCache->getFitTrainingEffect());
        $activityToMerge->setFitAnaerobicTrainingEffect($activityFromCache->getFitAnaerobicTrainingEffect()); // #TSC: transfer anaerobic eff
        $activityToMerge->setFitPerformanceCondition($activityFromCache->getFitPerformanceCondition());
        $activityToMerge->setFitPerformanceConditionEnd($activityFromCache->getFitPerformanceConditionEnd());
        $activityToMerge->setFitLactateThresholdHR($activityFromCache->getFitLactateThresholdHR()); // #TSC: transfer LTHR
        $activityToMerge->setFitLactateThresholdPace($activityFromCache->getFitLactateThresholdPace()); // #TSC: transfer LTP
        $activityToMerge->setFitTotalAscent($activityFromCache->getFitTotalAscent()); // #TSC: transfer ascent
        $activityToMerge->setFitTotalDescent($activityFromCache->getFitTotalDescent()); // #TSC: transfer descent
        $activityToMerge->setFitSelfEvaluationFeeling($activityFromCache->getFitSelfEvaluationFeeling()); // #TSC: transfer...
        $activityToMerge->setFitSelfEvaluationPerceivedEffort($activityFromCache->getFitSelfEvaluationPerceivedEffort()); // #TSC: transfer...
        $activityToMerge->setFitLoadPeak($activityFromCache->getFitLoadPeak()); // #TSC: transfer...
        $activityToMerge->setFitRunTime($activityFromCache->getFitRunTime()); // #TSC: transfer...
        $activityToMerge->setFitWalkTime($activityFromCache->getFitWalkTime()); // #TSC: transfer...
        $activityToMerge->setFitStandTime($activityFromCache->getFitStandTime()); // #TSC: transfer...
        $activityToMerge->setFitSecondsInHrZones($activityFromCache->getFitSecondsInHrZones()); // #TSC: transfer...
        $activityToMerge->setFitSweatLoss($activityFromCache->getFitSweatLoss()); // #TSC: transfer...
        $activityToMerge->setAvgRespirationRate($activityFromCache->getAvgRespirationRate()); // #TSC: transfer...
        $activityToMerge->setMaxRespirationRate($activityFromCache->getMaxRespirationRate()); // #TSC: transfer...
        $activityToMerge->setPowerCalculated($activityFromCache->isPowerCalculated());
        $activityToMerge->setGroundcontact($activityFromCache->getGroundcontact());
        $activityToMerge->setGroundcontactBalance($activityFromCache->getGroundcontactBalance());
        $activityToMerge->setVerticalOscillation($activityFromCache->getVerticalOscillation());
        $activityToMerge->setAvgImpactGsLeft($activityFromCache->getAvgImpactGsLeft());
        $activityToMerge->setAvgImpactGsRight($activityFromCache->getAvgImpactGsRight());
        $activityToMerge->setAvgBrakingGsLeft($activityFromCache->getAvgBrakingGsLeft());
        $activityToMerge->setAvgBrakingGsRight($activityFromCache->getAvgBrakingGsRight());
        $activityToMerge->setAvgFootstrikeTypeLeft($activityFromCache->getAvgFootstrikeTypeLeft());
        $activityToMerge->setAvgFootstrikeTypeRight($activityFromCache->getAvgFootstrikeTypeRight());
        $activityToMerge->setAvgPronationExcursionLeft($activityFromCache->getAvgPronationExcursionLeft());
        $activityToMerge->setAvgPronationExcursionRight($activityFromCache->getAvgPronationExcursionRight());
        $activityToMerge->setCreator($activityFromCache->getCreator());
        $activityToMerge->setCreatorDetails($activityFromCache->getCreatorDetails());
        $activityToMerge->setActivityId($activityFromCache->getActivityId());
        $activityToMerge->setPulseAvgActive($activityFromCache->getPulseAvgActive()); // #TSC: transfer hr
        $activityToMerge->setMaxHrUser($activityFromCache->getMaxHrUser()); // #TSC: transfer hr
        $activityToMerge->setHrZoneBounds($activityFromCache->getHrZoneBounds()); // #TSC: transfer hr
        $activityToMerge->setTotalCycles($activityFromCache->getTotalCycles()); // #TSC: transfer
        $activityToMerge->setSplitsAdditional($activityFromCache->getSplitsAdditional()); // #TSC: transfer
        $activityToMerge->setPaceGoal($activityFromCache->getPaceGoal()); // #TSC: transfer
        $activityToMerge->setWithGoal($activityFromCache->isWithGoal()); // #TSC: transfer

        if ($activityFromCache->isPowerCalculated() !== null) {
            $activityToMerge->setPower($activityFromCache->getPower());
        }
    }

    protected function getRandomHash(?Account $account = null): string
    {
        return hash('sha256', ($account instanceof Account ? $account->getName() : '') . uniqid());
    }
}

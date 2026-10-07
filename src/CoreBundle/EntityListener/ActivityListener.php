<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Util\LocalTime;

class ActivityListener
{
    public function __construct(
        protected RecalculationManager $RecalculationManager,
        protected ConfigurationManager $ConfigurationManager,
        protected SportRepository $SportRepository,
        protected EquipmentRepository $EquipmentRepository,
        protected TimezoneLookup $TimezoneLookup
    ) {
        $this->TimezoneLookup->silentExceptions();
    }

    protected function getNumberOfDaysToConsiderForEffectiveVO2maxShape(Account $account): int
    {
        return $this->ConfigurationManager->getList($account)
            ->getVO2max()
            ->getNumberOfDaysToConsider();
    }

    protected function getNumberOfDaysToConsiderForMarathonShape(Account $account): int
    {
        return $this->ConfigurationManager->getList($account)
            ->getBasicEndurance()
            ->getDaysToConsider();
    }

    public function prePersist(Training $activity, PrePersistEventArgs $args): void
    {
        $this->setActivityIdIfEmpty($activity);
        $this->checkRelatedEntitiesForConsistency($activity);
        $this->removeWeatherIfInside($activity);
        $this->calculateEnergyConsumptionIfEmpty($activity);
        $this->calculateTrimp($activity);
        $this->calculateVO2max($activity);
        $this->calculatePower($activity);
        $this->calculateIfActivityWasAtNight($activity);
        $this->calculateClimbScore($activity);
        $this->calculateValuesForSwimming($activity);
        $this->guessTimezoneBasedOnCoordinates($activity);
    }

    public function postPersist(Training $activity, PostPersistEventArgs $args): void
    {
        $this->addStatisticsToEquipment($activity);
        $this->scheduleRunningRelatedRecalculationsIfRequired($activity);
        $this->addStartTimeCheck($activity->getAccount(), $activity->getTime(), false);
    }

    public function preUpdate(Training $activity, PreUpdateEventArgs $args): void
    {
        $this->checkRelatedEntitiesForConsistency($activity, $args);

        if ($args->hasChangedField('sport')) {
            $this->removeWeatherIfInside($activity);
            $this->calculatePower($activity);
        }

        if ($this->updateRequiresTrimpCalculation($activity, $args)) {
            $this->calculateTrimp($activity);
        }

        if ($this->updateRequiresVO2maxCalculation($activity, $args)) {
            $this->calculateVO2max($activity);
        }

        if ($args->hasChangedField('time') || $args->hasChangedField('s')) {
            $this->calculateIfActivityWasAtNight($activity);
        }

        $this->recomputeOwnChangeSet($activity, $args);
        $this->scheduleRunningRelatedRecalculationsIfRequiredForUpdate($activity, $args);

        if ($args->hasChangedField('time')) {
            if ($args->getOldValue('time') < $args->getNewValue('time')) {
                $this->addStartTimeCheck($activity->getAccount(), $args->getOldValue('time'), true);
            } else {
                $this->addStartTimeCheck($activity->getAccount(), $args->getNewValue('time'), false);
            }
        }
    }

    public function postRemove(Training $activity, PostRemoveEventArgs $args): void
    {
        $this->removeStatisticsFromEquipment($activity);

        $this->scheduleRunningRelatedRecalculationsIfRequired($activity);

        $this->addStartTimeCheck($activity->getAccount(), $activity->getTime(), true);
    }

    protected function setActivityIdIfEmpty(Training $activity)
    {
        $activity->getAdapter()
            ->setActivityIdIfEmpty();
    }

    protected function checkRelatedEntitiesForConsistency(Training $activity, ?PreUpdateEventArgs $args = null)
    {
        if (! $args instanceof PreUpdateEventArgs || $args->hasChangedField('sport')) {
            $this->setSportIfEmpty($activity);
        }

        if (! $args instanceof PreUpdateEventArgs || $args->hasChangedField('sport') || $args->hasChangedField('type')) {
            $this->removeTypeIfInvalidForSport($activity);
        }
    }

    protected function setSportIfEmpty(Training $activity)
    {
        if (! $activity->hasSport()) {
            /** @var Sport $mainSport */
            $mainSport = $this->SportRepository->findThisOrAny(
                $this->ConfigurationManager->getList($activity->getAccount())
                    ->getGeneral()
                    ->getMainSport(),
                $activity->getAccount()
            );
            $activity->setSport($mainSport);
        }
    }

    protected function removeTypeIfInvalidForSport(Training $activity)
    {
        if ($activity->getType() instanceof Type && $activity->getType()->getSport()->getId() != $activity->getSport()->getId()) {
            $activity->setType();
        }
    }

    public function removeWeatherIfInside(Training $activity): void
    {
        if (! $activity->getSport()->getOutside()) {
            $activity->getAdapter()
                ->removeWeather();
        }
    }

    protected function calculateEnergyConsumptionIfEmpty(Training $activity)
    {
        $activity->getAdapter()
            ->calculateEnergyConsumptionIfEmpty();
    }

    protected function calculateTrimp(Training $activity)
    {
        $dataConfiguration = $this->ConfigurationManager->getList($activity->getAccount())
            ->getData();

        $activity->getAdapter()
            ->calculateTrimp(
                $activity->getAccount()
                    ->getGender(),
                $dataConfiguration->getMaximalHeartRate(),
                $dataConfiguration->getRestingHeartRate()
            );
    }

    protected function calculateVO2max(Training $activity)
    {
        if (! $activity->getAdapter()->isRunning()) {
            $activity->getAdapter()
                ->removeEffectiveVO2max();

            return;
        }

        $configuration = $this->ConfigurationManager->getList($activity->getAccount());
        $dataConfiguration = $configuration->getData();
        $vo2maxConfiguration = $configuration->getVO2max();

        $activity->getAdapter()
            ->calculateEffectiveVO2max(
                $dataConfiguration->getMaximalHeartRate(),
                $vo2maxConfiguration->getAdditionalDistancePerPositiveElevation(),
                $vo2maxConfiguration->getAdditionalDistancePerNegativeElevation()
            );
    }

    protected function calculatePower(Training $activity)
    {
        $activity->getAdapter()
            ->calculatePower();
    }

    protected function calculateIfActivityWasAtNight(Training $activity)
    {
        $activity->getAdapter()
            ->calculateIfActivityWasAtNight();
    }

    protected function calculateClimbScore(Training $activity)
    {
        $activity->getAdapter()
            ->calculateClimbScore();
    }

    protected function calculateValuesForSwimming(Training $activity)
    {
        $activity->getAdapter()
            ->calculateValuesForSwimming();
    }

    protected function guessTimezoneBasedOnCoordinates(Training $activity)
    {
        $activity->getAdapter()
            ->guessTimezoneBasedOnCoordinates($this->TimezoneLookup);
    }

    protected function addStartTimeCheck(Account $account, int $timestamp, bool $isRemoved)
    {
        $this->RecalculationManager->addStartTimeCheck($account, $timestamp, $isRemoved);
    }

    protected function scheduleRunningRelatedRecalculationsIfRequired(Training $activity)
    {
        if ($activity->getAdapter()->isRunning()) {
            if ($activity->getAdapter()->isRelevantForCurrentEffectiveVO2maxShape($this->getNumberOfDaysToConsiderForEffectiveVO2maxShape($activity->getAccount()))) {
                $this->RecalculationManager->scheduleEffectiveVO2maxShapeCalculation($activity->getAccount());
            }

            if ($activity->getAdapter()->isRelevantForCurrentMarathonShape($this->getNumberOfDaysToConsiderForMarathonShape($activity->getAccount()))) {
                $this->RecalculationManager->scheduleMarathonShapeCalculation($activity->getAccount());
            }
        }
    }

    protected function scheduleRunningRelatedRecalculationsIfRequiredForUpdate(Training $activity, PreUpdateEventArgs $args)
    {
        if ($this->updateRequiresEffectiveVO2maxShapeCalculation($activity, $args)) {
            $this->RecalculationManager->scheduleEffectiveVO2maxShapeCalculation($activity->getAccount());
        }

        if ($this->updateRequiresMarathonShapeCalculation($activity, $args)) {
            $this->RecalculationManager->scheduleMarathonShapeCalculation($activity->getAccount());
        }

        if ($this->updateRequiresEffectiveVO2maxCorrectionFactorCalculation($activity, $args)) {
            $this->RecalculationManager->scheduleEffectiveVO2maxCorrectionFactorCalculation($activity->getAccount());
        }
    }

    protected function updateRequiresEffectiveVO2maxShapeCalculation(Training $activity, PreUpdateEventArgs $args): bool
    {
        $days = $this->getNumberOfDaysToConsiderForEffectiveVO2maxShape($activity->getAccount());

        return (
            (
                $args->hasChangedField('useVO2max') ||
                $args->hasChangedField('vo2max') ||
                $args->hasChangedField('vo2maxWithElevation')
            ) && (
                $activity->getAdapter()
                    ->isNotOlderThanXDays($days) ||
                $this->timestampEnteredOrLeftPeriodOfLastXDays($days, $args)
            )
        ) || (
            $this->timestampEnteredOrLeftPeriodOfLastXDays($days, $args) && (
                $activity->getUseVO2max() &&
                $activity->getVO2max() > 0.0
            )
        );
    }

    protected function updateRequiresMarathonShapeCalculation(Training $activity, PreUpdateEventArgs $args): bool
    {
        $daysToConsider = $this->getNumberOfDaysToConsiderForMarathonShape($activity->getAccount());

        return (
            $activity->getAdapter()
                ->isRunning() && (
                    $args->hasChangedField('distance') ||
                $this->timestampEnteredOrLeftPeriodOfLastXDays($daysToConsider, $args)
                )
        ) || (
            $args->hasChangedField('sport') &&
            $activity->getAdapter()
                ->isNotOlderThanXDays($daysToConsider)
        );
    }

    protected function updateRequiresEffectiveVO2maxCorrectionFactorCalculation(Training $activity, PreUpdateEventArgs $args): bool
    {
        return
            $activity->hasRaceresult() &&
            $args->hasChangedField('vo2max') ||
            $args->hasChangedField('vo2maxByTime')
        ;
    }

    protected function updateRequiresTrimpCalculation(Training $activity, PreUpdateEventArgs $args): bool
    {
        return
            (! $activity->hasTrackdata() || ! $activity->getTrackdata()->hasHeartrate()) && (
                $args->hasChangedField('pulseAvg') ||
                $args->hasChangedField('s') ||
                (
                    $activity->getPulseAvg() === null && (
                        $args->hasChangedField('sport') ||
                        $args->hasChangedField('type')
                    )
                )
            )
        ;
    }

    protected function updateRequiresVO2maxCalculation(Training $activity, PreUpdateEventArgs $args): bool
    {
        return (
            $activity->getAdapter()
                ->isRunning() && (
                    $args->hasChangedField('pulseAvg') ||
                $args->hasChangedField('s') ||
                $args->hasChangedField('distance')
                )
        ) || (
            $args->hasChangedField('sport')
        );
    }

    protected function timestampEnteredOrLeftPeriodOfLastXDays(int $numberOfDays, PreUpdateEventArgs $args): bool
    {
        if (! $args->hasChangedField('time')) {
            return false;
        }

        $now = new LocalTime();
        $ageOfOldDate = new LocalTime($args->getOldValue('time'))->diff($now, true)
            ->days;
        $ageOfNewDate = new LocalTime($args->getNewValue('time'))->diff($now, true)
            ->days;

        return
            ($ageOfOldDate <= $numberOfDays && $ageOfNewDate > $numberOfDays) ||
            ($ageOfOldDate > $numberOfDays && $ageOfNewDate <= $numberOfDays)
        ;
    }

    protected function addStatisticsToEquipment(Training $activity)
    {
        $this->EquipmentRepository->updateEquipment(
            $activity->getEquipment()
                ->toArray(),
            (int) $activity->getS(),
            $activity->getDistance()
        );
    }

    protected function removeStatisticsFromEquipment(Training $activity)
    {
        $this->EquipmentRepository->updateEquipment(
            $activity->getEquipment()
                ->toArray(),
            -1 * (int) $activity->getS(),
            -1 * $activity->getDistance()
        );
    }

    protected function recomputeOwnChangeSet(Training $activity, PreUpdateEventArgs $args)
    {
        $objectManager = $args->getObjectManager();

        if (! $objectManager instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \LogicException('Expected Doctrine ORM EntityManagerInterface.');
        }

        $metaData = $objectManager->getClassMetadata(Training::class);
        $unitOfWork = $objectManager->getUnitOfWork();
        $unitOfWork->recomputeSingleEntityChangeSet($metaData, $activity);
    }
}

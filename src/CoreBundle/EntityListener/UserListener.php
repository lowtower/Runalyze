<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;

class UserListener
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater $ConfigurationUpdater,
        protected \Runalyze\Bundle\CoreBundle\Entity\UserRepository $UserRepository
    ) {
    }

    public function postPersist(User $user, \Doctrine\ORM\Event\PostPersistEventArgs $args): void
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postUpdate(User $user, \Doctrine\ORM\Event\PostUpdateEventArgs $args): void
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postRemove(User $user, \Doctrine\ORM\Event\PostRemoveEventArgs $args): void
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    protected function updateHeartRateStatsInDataConfiguration(\Doctrine\Persistence\Event\LifecycleEventArgs $args, Account $account)
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $args->getObjectManager();

        if ($em->getUnitOfWork()->isScheduledForDelete($account)) {
            return;
        }

        $restingHeartRate = $this->UserRepository->getCurrentRestingHeartRate($account) ?: 60;
        $maximalHeartRate = $this->UserRepository->getCurrentMaximalHeartRate($account) ?: 200;

        $this->ConfigurationUpdater->updateRestingHeartRate($account, $restingHeartRate);
        $this->ConfigurationUpdater->updateMaximalHeartRate($account, $maximalHeartRate);
    }
}

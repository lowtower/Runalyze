<?php

namespace Runalyze\Bundle\CoreBundle\EntityListener;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\UserRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;

class UserListener
{
    /**
     * @var ConfigurationUpdater
     */
    protected $ConfigurationUpdater;

    /**
     * @var UserRepository
     */
    protected $UserRepository;

    public function __construct(ConfigurationUpdater $updater, UserRepository $repository)
    {
        $this->ConfigurationUpdater = $updater;
        $this->UserRepository = $repository;
    }

    public function postPersist(User $user, \Doctrine\ORM\Event\PostPersistEventArgs $args)
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postUpdate(User $user, \Doctrine\ORM\Event\PostUpdateEventArgs $args)
    {
        $this->updateHeartRateStatsInDataConfiguration($args, $user->getAccount());
    }

    public function postRemove(User $user, \Doctrine\ORM\Event\PostRemoveEventArgs $args)
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

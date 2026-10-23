<?php

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class ActivityContextFactory
{
    public function __construct(
        protected EntityManager $EntityManager
    ) {}

    public function getContext(Training $activity): ActivityContext
    {
        return new ActivityContext(
            $activity,
            $activity->getTrackdata(),
            $activity->getSwimdata(),
            $activity->getRoute(),
            $activity->getHrv(),
            $activity->getRaceresult()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getContextById(int $activityId, int $accountId): ActivityContext
    {
        $activity = $this->EntityManager->getRepository(Training::class)
            ->findForAccount($activityId, $accountId);

        if ($activity === null) {
            throw new InvalidArgumentException('Unknown activity (id = ' . $activityId . ').');
        }

        return $this->getContext($activity);
    }
}

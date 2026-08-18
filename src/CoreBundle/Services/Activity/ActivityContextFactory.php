<?php

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Doctrine\ORM\EntityManager;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class ActivityContextFactory
{
    /** @var EntityManager */
    protected $EntityManager;

    public function __construct(EntityManager $em)
    {
        $this->EntityManager = $em;
    }

    public function getContext(Training $activity): \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext
    {
        $activityId = $activity->getId();

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
     * @param int $activityId
     * @param int $accountId
     *
     * @throws \InvalidArgumentException
     */
    public function getContextById($activityId, $accountId): \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext
    {
        $activity = $this->EntityManager->getRepository('CoreBundle:Training')
            ->findForAccount($activityId, $accountId);

        if (null === $activity) {
            throw new \InvalidArgumentException('Unknown activity (id = '.$activityId.').');
        }

        return $this->getContext($activity);
    }
}

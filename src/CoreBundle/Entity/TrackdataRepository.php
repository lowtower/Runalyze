<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Trackdata>
 */
class TrackdataRepository extends EntityRepository
{
    public function findForActivity(int $activityId, Account $account): ?Trackdata
    {
        return $this->findOneBy([
            'activity' => $activityId,
            'account' => $account,
        ]);
    }

    public function save(Trackdata $trackdata): void
    {
        $this->getEntityManager()->persist($trackdata);
        $this->getEntityManager()->flush();
    }
}

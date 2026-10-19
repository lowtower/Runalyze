<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;

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

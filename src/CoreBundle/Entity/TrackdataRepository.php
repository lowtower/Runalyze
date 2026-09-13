<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Trackdata>
 */
class TrackdataRepository extends EntityRepository
{
    /**
     * @param int $activityId
     * @return null|Trackdata
     */
    public function findByActivity($activityId, Account $account)
    {
        return $this->findOneBy([
            'activity' => $activityId,
            'account' => $account,
        ]);
    }

    public function save(Trackdata $trackdata): void
    {
        $this->_em->persist($trackdata);
        $this->_em->flush();
    }
}

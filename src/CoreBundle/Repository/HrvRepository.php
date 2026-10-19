<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;

/**
 * @extends EntityRepository<Hrv>
 */
class HrvRepository extends EntityRepository
{
    public function findForActivity(int $activityId): ?Hrv
    {
        return $this->findOneBy([
            'activity' => $activityId,
        ]);
    }
}

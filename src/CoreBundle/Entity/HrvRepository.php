<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

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

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;

/**
 * @extends EntityRepository<Swimdata>
 */
class SwimdataRepository extends EntityRepository
{
    public function findForActivity(int $activityId): ?Swimdata
    {
        return $this->findOneBy([
            'activity' => $activityId,
        ]);
    }
}

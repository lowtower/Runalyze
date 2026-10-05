<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

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

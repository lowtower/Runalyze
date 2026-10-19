<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Dataset;

/**
 * @extends EntityRepository<Dataset>
 */
class DatasetRepository extends EntityRepository
{
    /**
     * @return Dataset[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy(
            [
                'account' => $account->getId(),
            ],
            [
                'position' => 'ASC',
            ]
        );
    }

    public function save(Dataset $dataset): void
    {
        $this->getEntityManager()->persist($dataset);
        $this->getEntityManager()->flush();
    }

    public function remove(Dataset $dataset): void
    {
        $this->getEntityManager()->remove($dataset);
        $this->getEntityManager()->flush();
    }
}

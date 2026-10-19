<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;

/**
 * @extends EntityRepository<Tag>
 */
class TagRepository extends EntityRepository
{
    /**
     * @return Tag[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy(
            [
                'account' => $account->getId(),
            ],
            [
                'tag' => 'ASC',
            ]
        );
    }

    public function save(Tag $tag): void
    {
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
    }

    public function remove(Tag $tag): void
    {
        $this->getEntityManager()->remove($tag);
        $this->getEntityManager()->flush();
    }
}

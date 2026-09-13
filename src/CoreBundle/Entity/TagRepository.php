<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Tag>
 */
class TagRepository extends EntityRepository
{
    /**
     * @return Tag[]
     */
    public function findAllFor(Account $account)
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

    public function save(Tag $tag)
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    public function remove(Tag $tag)
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }
}

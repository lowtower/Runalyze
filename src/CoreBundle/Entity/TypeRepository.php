<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Type>
 */
class TypeRepository extends EntityRepository
{
    /**
     * @return Type[]
     */
    public function findAllFor(Account $account, ?Sport $sport = null)
    {
        if ($sport instanceof \Runalyze\Bundle\CoreBundle\Entity\Sport) {
            return $this->findBy([
                'account' => $account->getId(),
                'sport' => $sport->getId(),
            ]);
        }

        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    /**
     * @param string $typeName
     * @return null|Type
     */
    public function findByNameFor($typeName, Account $account, ?Sport $sport = null)
    {
        // #TSC add $sport as parameter - only with sport the type can be find unique
        return $this->findOneBy([
            'account' => $account->getId(),
            'name' => (string) $typeName,
            'sport' => $sport->getId(),
        ]);
    }

    /**
     * #TSC add search for shurt-cut.
     *
     * @param string $shortCut
     * @return null|Type
     */
    public function findByShortCutFor($shortCut, Account $account, ?Sport $sport = null)
    {
        return $this->findOneBy([
            'account' => $account->getId(),
            'abbr' => (string) $shortCut,
            'sport' => $sport->getId(),
        ]);
    }

    public function save(Type $type): void
    {
        $this->_em->persist($type);
        $this->_em->flush();
    }
}

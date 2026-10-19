<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;

/**
 * @extends EntityRepository<Type>
 */
class TypeRepository extends EntityRepository
{
    /**
     * @return Type[]
     */
    public function findAllFor(Account $account, ?Sport $sport = null): array
    {
        if ($sport instanceof Sport) {
            return $this->findBy([
                'account' => $account->getId(),
                'sport' => $sport->getId(),
            ]);
        }

        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    public function findForNameAndAccount(string $typeName, Account $account, ?Sport $sport = null): ?Type
    {
        // #TSC add $sport as parameter - only with sport the type can be find unique
        return $this->findOneBy([
            'account' => $account->getId(),
            'name' => $typeName,
            'sport' => $sport->getId(),
        ]);
    }

    /**
     * #TSC add search for shurt-cut.
     */
    public function findForShortCutAndAccount(string $shortCut, Account $account, ?Sport $sport = null): ?Type
    {
        return $this->findOneBy([
            'account' => $account->getId(),
            'abbr' => $shortCut,
            'sport' => $sport->getId(),
        ]);
    }

    public function save(Type $type): void
    {
        $this->getEntityManager()->persist($type);
        $this->getEntityManager()->flush();
    }
}

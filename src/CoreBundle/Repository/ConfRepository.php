<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;

/**
 * @extends EntityRepository<Conf>
 */
class ConfRepository extends EntityRepository
{
    /**
     * @return Conf[]
     */
    public function findForAccount(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    public function findForAccountAndKey(Account $account, string $key): ?Conf
    {
        $results = $this->findBy([
            'account' => $account->getId(),
            'key' => $key,
        ]);

        return $results === [] ? null : $results[0];
    }

    public function updateOrInsert(Account $account, string $category, string $key, string $value): Conf
    {
        if (! ($conf = $this->findForAccountAndKey($account, $key)) instanceof Conf) {
            $conf = new Conf();
            $conf->setCategory($category);
            $conf->setKey($key);
            $conf->setAccount($account);
        }

        $conf->setValue($value);

        $this->save($conf);

        return $conf;
    }

    public function save(Conf $conf): void
    {
        $this->getEntityManager()->persist($conf);
        $this->getEntityManager()->flush();
    }
}

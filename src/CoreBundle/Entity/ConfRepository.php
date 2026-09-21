<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Conf>
 */
class ConfRepository extends EntityRepository
{
    /**
     * @return Conf[]
     */
    public function findByAccount(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    public function findByAccountAndKey(Account $account, string $key): ?Conf
    {
        $results = $this->findBy([
            'account' => $account->getId(),
            'key' => $key,
        ]);

        return empty($results) ? null : $results[0];
    }

    /**
     * @param string $key
     * @return Conf
     */
    public function updateOrInsert(Account $account, string $category, $key, string $value)
    {
        if (! ($conf = $this->findByAccountAndKey($account, $key)) instanceof Conf) {
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
        $this->_em->persist($conf);
        $this->_em->flush();
    }
}

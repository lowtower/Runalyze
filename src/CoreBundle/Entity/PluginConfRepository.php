<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @extends EntityRepository<PluginConf>
 */
class PluginConfRepository extends EntityRepository
{
    /**
     * @return int[]
     */
    public function getAllActivityIdsOfFunRaces(Account $account): array
    {
        try {
            $result = $this->createQueryBuilder('c')
                ->innerJoin('c.plugin', 'p')
                ->select('c.value')
                ->where('p.account = :account')
                ->andWhere('p.key = :plugin')
                ->andWhere('c.config = :config')
                ->setParameter('account', $account)
                ->setParameter('plugin', 'RunalyzePluginStat_Wettkampf')
                ->setParameter('config', 'fun_ids')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            return [];
        }

        return array_filter(
            array_map(fn ($id): int => (int) trim($id), explode(',', $result))
        );
    }
}

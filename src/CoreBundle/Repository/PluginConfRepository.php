<?php

namespace Runalyze\\Bundle\\CoreBundle\\Repository;

use Doctrine\\ORM\\EntityRepository;
use Doctrine\\ORM\\NonUniqueResultException;
use Doctrine\\ORM\\NoResultException;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;
use Runalyze\\Bundle\\CoreBundle\\Entity\\PluginConf;

/**
 * @extends EntityRepository<PluginConf>
 */
class PluginConfRepository extends EntityRepository
{
    /**
     * @param int[] $pluginIds
     * @return array<int, array<string, string>>
     */
    public function findValuesForPlugins(array $pluginIds): array
    {
        if ($pluginIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('configuration')
            ->select('IDENTITY(configuration.plugin) AS pluginId', 'configuration.config', 'configuration.value')
            ->where('IDENTITY(configuration.plugin) IN (:pluginIds)')
            ->setParameter('pluginIds', $pluginIds)
            ->getQuery()
            ->getArrayResult();

        $values = [];
        foreach ($rows as $row) {
            $values[(int) $row['pluginId']][$row['config']] = $row['value'];
        }

        return $values;
    }

    public function findForPluginAndKey(int $pluginId, string $key): ?PluginConf
    {
        return $this->findOneBy([
            'plugin' => $pluginId,
            'config' => $key,
        ]);
    }

    public function save(PluginConf $configuration): void
    {
        $this->getEntityManager()->persist($configuration);
        $this->getEntityManager()->flush();
    }

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

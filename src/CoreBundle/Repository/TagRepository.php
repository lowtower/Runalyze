<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

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

    /**
     * @return list<array{id: int, tag: string, usage_count: int}>
     */
    public function findUsageForSportSince(Account $account, int $sportId, int $since): array
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('tg.id AS id, tg.tag AS tag, COUNT(t.id) AS usage_count')
            ->from(Training::class, 't')
            ->innerJoin('t.tag', 'tg')
            ->where('t.account = :account')
            ->andWhere('tg.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time >= :since')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->setParameter('since', $since)
            ->groupBy('tg.id')
            ->addGroupBy('tg.tag')
            ->orderBy('usage_count', 'DESC')
            ->addOrderBy('tg.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'tag' => (string) $row['tag'],
            'usage_count' => (int) $row['usage_count'],
        ], $results);
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

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use SortDirection;

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
            ->orderBy('usage_count', SortDirection::Descending)
            ->addOrderBy('tg.id', SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'tag' => (string) $row['tag'],
            'usage_count' => (int) $row['usage_count'],
        ], $results);
    }

    /**
     * @return array<int, array<int, int>> Counts indexed by year and month
     */
    public function findMonthlyUsageFor(Account $account, int $tagId, ?int $sport, ?int $from, ?int $until): array
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('YEAR(FROM_UNIXTIME(t.time)) AS year, MONTH(FROM_UNIXTIME(t.time)) AS month, COUNT(t.id) AS usage_count')
            ->from(Training::class, 't')
            ->innerJoin('t.tag', 'tg')
            ->where('t.account = :account')
            ->andWhere('tg.account = :account')
            ->andWhere('tg.id = :tag')
            ->setParameter('account', $account)
            ->setParameter('tag', $tagId)
            ->groupBy('year')
            ->addGroupBy('month')
            ->orderBy('year', SortDirection::Descending)
            ->addOrderBy('month', SortDirection::Ascending);

        if ($sport !== null) {
            $query->andWhere('t.sport = :sport')->setParameter('sport', $sport);
        }

        if ($from !== null) {
            $query->andWhere('t.time >= :from')->setParameter('from', $from);
        }

        if ($until !== null) {
            $query->andWhere('t.time < :until')->setParameter('until', $until);
        }

        $counts = [];
        foreach ($query->getQuery()->getArrayResult() as $row) {
            $counts[(int) $row['year']][(int) $row['month']] = (int) $row['usage_count'];
        }

        return $counts;
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

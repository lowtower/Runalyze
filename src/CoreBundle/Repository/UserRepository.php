<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ], [
            'time' => 'DESC',
        ]);
    }

    /**
     * @return ?int [bpm]
     */
    public function getCurrentRestingHeartRate(Account $account): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.pulseRest')
            ->setMaxResults(1)
            ->where('u.account = :accountid')
            ->andWhere('u.pulseRest > 0')
            ->setParameter('accountid', $account->getId())
            ->orderBy('u.time', \SortDirection::Descending)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        return $result ? (int) $result['pulseRest'] : null;
    }

    /**
     * @return ?int [bpm]
     */
    public function getCurrentMaximalHeartRate(Account $account): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.pulseMax')
            ->setMaxResults(1)
            ->where('u.account = :accountid')
            ->andWhere('u.pulseMax > 0')
            ->setParameter('accountid', $account->getId())
            ->orderBy('u.time', \SortDirection::Descending)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        return $result ? (int) $result['pulseMax'] : null;
    }

    public function save(User $user, Account $account): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function getLatestEntryFor(Account $account): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.account = :account')
            ->setParameter('account', $account->getId())
            ->addOrderBy('u.time', \SortDirection::Descending)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<array{time: int, weight: ?float, pulseRest: ?int}>
     */
    public function findWeightHistoryFor(Account $account, int $limit): array
    {
        $rows = $this->createHistoryQueryBuilder(
            $account,
            'u.time AS time, u.weight AS weight, u.pulseRest AS pulseRest',
            $limit,
            null
        )->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'time' => (int) $row['time'],
            'weight' => null === $row['weight'] ? null : (float) $row['weight'],
            'pulseRest' => null === $row['pulseRest'] ? null : (int) $row['pulseRest'],
        ], $rows);
    }

    /**
     * @return array<array{time: int, weight: ?float, pulseRest: ?int}>
     */
    public function findWeightHistorySince(Account $account, int $timestamp): array
    {
        $rows = $this->createHistoryQueryBuilder(
            $account,
            'u.time AS time, u.weight AS weight, u.pulseRest AS pulseRest',
            null,
            $timestamp
        )->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'time' => (int) $row['time'],
            'weight' => null === $row['weight'] ? null : (float) $row['weight'],
            'pulseRest' => null === $row['pulseRest'] ? null : (int) $row['pulseRest'],
        ], $rows);
    }

    /**
     * @return array<array{time: int, fat: ?float, water: ?float, muscles: ?float}>
     */
    public function findBodyCompositionHistoryFor(Account $account, int $limit): array
    {
        $rows = $this->createHistoryQueryBuilder(
            $account,
            'u.time AS time, u.fat AS fat, u.water AS water, u.muscles AS muscles',
            $limit,
            null
        )->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'time' => (int) $row['time'],
            'fat' => null === $row['fat'] ? null : (float) $row['fat'],
            'water' => null === $row['water'] ? null : (float) $row['water'],
            'muscles' => null === $row['muscles'] ? null : (float) $row['muscles'],
        ], $rows);
    }

    /**
     * @return array<array{time: int, fat: ?float, water: ?float, muscles: ?float}>
     */
    public function findBodyCompositionHistorySince(Account $account, int $timestamp): array
    {
        $rows = $this->createHistoryQueryBuilder(
            $account,
            'u.time AS time, u.fat AS fat, u.water AS water, u.muscles AS muscles',
            null,
            $timestamp
        )->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'time' => (int) $row['time'],
            'fat' => null === $row['fat'] ? null : (float) $row['fat'],
            'water' => null === $row['water'] ? null : (float) $row['water'],
            'muscles' => null === $row['muscles'] ? null : (float) $row['muscles'],
        ], $rows);
    }

    private function createHistoryQueryBuilder(
        Account $account,
        string $select,
        ?int $limit,
        ?int $timestamp
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select($select)
            ->where('u.account = :account')
            ->setParameter('account', $account)
            ->orderBy('u.time', 'DESC');

        if (null !== $timestamp) {
            return $queryBuilder
                ->andWhere('u.time > :timestamp')
                ->setParameter('timestamp', $timestamp);
        }

        return $queryBuilder->setMaxResults($limit);
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}

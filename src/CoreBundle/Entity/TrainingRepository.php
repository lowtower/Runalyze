<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\VO2max;
use Runalyze\Bundle\CoreBundle\Model\Account\AccountStatistics;
use Runalyze\Sports\Running\MarathonShape;
use Runalyze\Util\Time;

/**
 * @extends EntityRepository<Training>
 */
class TrainingRepository extends EntityRepository
{
    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getAmountOfLoggedKilometers(bool $cache = true): bool|float|int|string|null
    {
        $query = $this->createQueryBuilder('t')
            ->select('SUM(t.distance)')
            ->getQuery()
            ->useResultCache($cache);
        if ($cache) {
            $query->setResultCacheLifetime(120);
        }

        return $query->getSingleScalarResult();
    }

    /**
     * @see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSpeedUnitFor(int $activityid, int $accountid): mixed
    {
        return $this->_em->createQueryBuilder()
            ->select('s.speed')
            ->from(Training::class, 't')
            ->join('t.sport', 's')
            ->where('t.id = :id AND t.account = :account')
            ->setParameter('id', $activityid)
            ->setParameter('account', $accountid)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function isPossibleDuplicate(Training $activity): bool
    {
        if (! $activity->hasAccount() || ! is_numeric($activity->getAccount()->getId()) || $activity->getActivityId() === null) {
            return false;
        }

        return $this->createQueryBuilder('t')
            ->select('1')
            ->where('t.account = :account AND t.activityId = :id')
            ->setParameter('account', $activity->getAccount())
            ->setParameter('id', $activity->getActivityId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function getIdOfNextActivity(Training $activity): ?int
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('(t.time > :time AND t.id != :id)')
            ->orWhere('(t.time = :time AND t.id > :id)')
            ->andWhere('t.account = :account')
            ->setParameter('account', $activity->getAccount())
            ->setParameter('id', $activity->getId())
            ->setParameter('time', $activity->getTime())
            ->orderBy('t.time', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    public function getIdOfPreviousActivity(Training $activity): ?int
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('(t.time < :time AND t.id != :id)')
            ->orWhere('(t.time = :time AND t.id < :id)')
            ->andWhere('t.account = :account')
            ->setParameter('account', $activity->getAccount())
            ->setParameter('id', $activity->getId())
            ->setParameter('time', $activity->getTime())
            ->orderBy('t.time', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    public function getActiveYearsFor(Account $account, ?int $sportid = null, ?int $minimumActivities = null): array
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('YEAR(FROM_UNIXTIME(t.time)) AS year')
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->setParameter('account', $account->getId())
            ->addGroupBy('year');

        if ($sportid !== null) {
            $queryBuilder
                ->andWhere('t.sport = :sportid')
                ->setParameter('sportid', $sportid);
        }

        if ($minimumActivities !== null) {
            $queryBuilder
                ->having('COUNT(IDENTITY(t.sport)) >= :minimum')
                ->setParameter('minimum', $minimumActivities);
        }

        return $queryBuilder->getQuery()
            ->getResult('COLUMN_HYDRATOR');
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumberOfActivitiesFor(Account $account, ?int $year = null, ?int $sportid = null): int
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('COUNT(1) as num')
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->setParameter('account', $account->getId());

        if ($year !== null) {
            $queryBuilder
                ->andWhere('t.time BETWEEN :startTime and :endTime')
                ->setParameter('startTime', mktime(0, 0, 0, 1, 1, $year))
                ->setParameter('endTime', mktime(23, 59, 59, 12, 31, $year));
        }

        if ($sportid !== null) {
            $queryBuilder
                ->andWhere('t.sport = :sportid')
                ->setParameter('sportid', $sportid);
        }

        return (int) $queryBuilder->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumberOfActivitiesWithActivityType(Type $type): bool|float|int|string|null
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('COUNT(1) as num')
            ->from(Training::class, 't')
            ->where('t.type = :typeid')
            ->setParameter('typeid', $type->getId());

        return $queryBuilder->getQuery()
            ->getSingleScalarResult();
    }

    public function getTypesWithTraining(Account $account): array
    {
        $qb = $this->_em->createQueryBuilder();
        $queryBuilder = $qb
            ->select('IDENTITY(t.type)')
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->andWhere($qb->expr()->isNotNull('t.type'))
            ->addGroupBy('t.type')
            ->setParameter('account', $account->getId());

        return $queryBuilder->getQuery()
            ->getResult('COLUMN_HYDRATOR');
    }

    public function getSportsWithTraining(Account $account): array
    {
        $qb = $this->_em->createQueryBuilder();
        $queryBuilder = $qb
            ->select('IDENTITY(t.sport)')
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->andWhere($qb->expr()->isNotNull('t.sport'))
            ->addGroupBy('t.sport')
            ->setParameter('account', $account->getId());

        return $queryBuilder->getQuery()
            ->getResult('COLUMN_HYDRATOR');
    }

    public function getMonthlyStatsFor(Account $account, ?string $column = null, ?int $sportid = null): array
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select(
                'YEAR(FROM_UNIXTIME(t.time)) AS year',
                'MONTH(FROM_UNIXTIME(t.time)) AS month'
            )
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->setParameter('account', $account->getId())
            ->addGroupBy('year')
            ->addGroupBy('month');

        if ($column !== null) {
            $queryBuilder->addSelect('SUM(t.' . $column . ') AS value');
        } else {
            $queryBuilder->addSelect('SUM(1) AS value');
        }

        if ($sportid !== null) {
            $queryBuilder
                ->andWhere('t.sport = :sportid')
                ->setParameter('sportid', $sportid);
        }

        return $queryBuilder->getQuery()
            ->getArrayResult();
    }

    public function findForAccount(int $activityId, int $accountId): ?Training
    {
        return $this->findOneBy([
            'id' => $activityId,
            'account' => $accountId,
        ]);
    }

    public function getAccountStatistics(Account $account): AccountStatistics
    {
        $statistics = new AccountStatistics();

        $dataForAccount = $this->_em->createQueryBuilder()
            ->select(
                'COUNT(1) as num',
                'SUM(t.distance) as distance',
                'SUM(t.s) as duration'
            )
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->setParameter('account', $account->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if ($dataForAccount !== null) {
            $statistics->setNumberOfActivities((int) $dataForAccount['num']);
            $statistics->setTotalDistance((float) $dataForAccount['distance']);
            $statistics->setTotalDuration((float) $dataForAccount['duration']);
        }

        return $statistics;
    }

    public function getQueryForJsonPosterData(Account $account, Sport $sport, int $year): Query
    {
        return $this->_em->createQueryBuilder()
            ->select(
                't.s',
                't.time',
                't.distance',
                'r.geohashes'
            )
            ->from(Training::class, 't')
            ->join('t.route', 'r')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.distance > 0')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->setParameters([
                ':account' => $account->getId(),
                ':sport' => $sport->getId(),
                ':startTime' => mktime(0, 0, 0, 1, 1, $year),
                ':endTime' => mktime(23, 59, 59, 12, 31, $year),
            ])
            ->getQuery();
    }

    public function getStatsForPoster(Account $account, Sport $sport, int $year): Query
    {
        return $this->_em->createQueryBuilder()
            ->select(
                'COUNT(t.id) as num',
                'SUM(t.distance) as total_distance',
                'MIN(t.distance) as min_distance',
                'MAX(t.distance) as max_distance'
            )
            ->from(Training::class, 't')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->setParameters([
                ':account' => $account->getId(),
                ':sport' => $sport->getId(),
                ':startTime' => mktime(0, 0, 0, 1, 1, $year),
                ':endTime' => mktime(23, 59, 59, 12, 31, $year),
            ])
            ->getQuery();
    }

    public function accountHasLockedTrainings(Account $account): bool
    {
        return $this->createQueryBuilder('t')
            ->select('t.id')
            ->setMaxResults(1)
            ->where('t.account = :accountid AND t.lock = 1')
            ->setParameter('accountid', $account->getId())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR) !== null;
    }

    /**
     * @return Training[]
     */
    public function getLatestActivities(Account $account, int $limit = 20, bool $onlyPublic = false): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.account = :accountid')
            ->setParameter('accountid', $account->getId())
            ->orderBy('t.time', 'DESC')
            ->setMaxResults($limit);

        if ($onlyPublic) {
            $queryBuilder->andWhere('t.isPublic = 1');
        }

        return $queryBuilder->getQuery()
            ->getResult();
    }

    /**
     * @return Training[]
     */
    public function getLatestPublicActivities(Account $account, int $limit = 20): array
    {
        return $this->getLatestActivities($account, $limit, true);
    }

    /**
     * @param int[] $ids
     * @return Training[]
     */
    public function getPartialEntitiesForPreview(array $ids, ?Account $account = null, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('partial t.{id, time, s, distance, pulseAvg, splits, route, title, sport}');

        if ($ids === []) {
            if (! $account instanceof Account) {
                return [];
            }

            $queryBuilder->where('t.account = :accountid')
                ->setParameter('accountid', $account->getId())
                ->orderBy('t.time', 'DESC')
                ->setMaxResults($limit);
        } else {
            $queryBuilder->where('t.id IN(:ids)')
                ->setParameter('ids', $ids);
        }

        return $queryBuilder->getQuery()
            ->getResult();
    }

    public function getStartTime(Account $account): ?int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MIN(t.time)')
            ->where('t.account = :account')
            ->setParameter('account', $account->getId())
            ->groupBy('t.account')
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $result === null ? $result : (int) $result;
    }

    public function calculateVO2maxShape(Account $account, VO2max $configuration, int $sportId, int $timestamp): float
    {
        $vo2maxColumn = $configuration->useCorrectionForElevation() ? 'vo2maxWithElevation' : 'vo2max';

        $result = $this->createQueryBuilder('t')
            ->select([
                'SUM(t.s * t.useVO2max * t.' . $vo2maxColumn . ') as value',
                'SUM(CASE WHEN t.' . $vo2maxColumn . ' > 0 THEN t.s * t.useVO2max ELSE 0 END) as ssum',
            ])
            ->where('t.account = :account')
            ->andWhere('t.time BETWEEN :timeStart AND :timeEnd')
            ->andWhere('t.sport = :sport')
            ->setParameter('account', $account->getId())
            ->setParameter('timeStart', $timestamp - $configuration->getNumberOfDaysToConsider() * 86400)
            ->setParameter('timeEnd', $timestamp)
            ->setParameter('sport', $sportId)
            ->groupBy('t.account')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        if ($result === null || (float) $result['ssum'] === 0.0) {
            return 0.0;
        }

        return round((float) $result['value'] / (float) $result['ssum'], 5);
    }

    public function calculateMarathonShape(Account $account, BasicEndurance $configuration, float $effectiveVO2max, int $sportId, int $timestamp, ?int $timestampOfFirstActivity = null): int
    {
        $startTimeForLongJogs = $timestamp - $configuration->getDaysToConsiderForLongJogs() * 86400;
        $startTimeForWeeklyMileage = $timestamp - $configuration->getDaysToConsiderForWeeklyMileage() * 86400;
        $numberOfDaysSinceFirstActivity = $timestampOfFirstActivity === null
            ? $configuration->getDaysToConsiderForLongJogs()
            : Time::diffInDays($timestampOfFirstActivity, $timestamp);
        $marathonShape = new MarathonShape($effectiveVO2max, $configuration);

        $result = $this->createQueryBuilder('t')
            ->select([
                'SUM(CASE WHEN t.time >= :timeStartWeek THEN t.distance ELSE 0 END) as km',
                'SUM(
                    CASE WHEN
                        t.distance > :minLongJog AND t.time >= :timeStartLongJog
                    THEN
                        (2 - :weight * ROUND((:timeEnd - t.time) / 86400 - 0.5) )
                        * ((t.distance - :minLongJog) / :longJogTarget)
                        * ((t.distance - :minLongJog) / :longJogTarget)
                    ELSE 0
                    END
                ) as points',
            ])
            ->where('t.account = :account')
            ->andWhere('t.time BETWEEN :timeStart AND :timeEnd')
            ->andWhere('t.sport = :sport')
            ->setParameter('account', $account->getId())
            ->setParameter('timeStart', min($startTimeForLongJogs, $startTimeForWeeklyMileage))
            ->setParameter('timeStartWeek', $startTimeForWeeklyMileage)
            ->setParameter('timeStartLongJog', $startTimeForLongJogs)
            ->setParameter('timeEnd', $timestamp)
            ->setParameter('weight', 2.0 / $configuration->getDaysToConsiderForLongJogs())
            ->setParameter('minLongJog', $configuration->getMinimalDistanceForLongJog())
            ->setParameter('longJogTarget', $marathonShape->getTargetForLongJogEachWeek() - $configuration->getMinimalDistanceForLongJog())
            ->setParameter('sport', $sportId)
            ->groupBy('t.account')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        if ($result === null) {
            return 0;
        }

        $totalDistanceInTimePeriod = (float) ($result['km'] ?? 0.0);
        $totalPoints = (float) ($result['points'] ?? 0.0);

        return $marathonShape->getShapeFor($totalDistanceInTimePeriod, $totalPoints, (int) $numberOfDaysSinceFirstActivity);
    }

    /**
     * @return int activity id
     */
    public function save(Training $training, bool $ensureThatSportAndTypeAreManaged = false): int
    {
        if ($ensureThatSportAndTypeAreManaged) {
            $this->ensureThatSportAndTypeAreManaged($training);
        }

        if ($training->getRoute() instanceof Route) {
            $this->_em->persist($training->getRoute());
        }

        if ($training->getTrackdata() instanceof Trackdata) {
            $this->_em->persist($training->getTrackdata());
        }

        if ($training->getSwimdata() instanceof Swimdata) {
            $this->_em->persist($training->getSwimdata());
        }

        if ($training->getHrv() instanceof Hrv) {
            $this->_em->persist($training->getHrv());
        }

        if ($training->getRaceresult() instanceof Raceresult) {
            $this->_em->persist($training->getRaceresult());
        }

        $this->_em->persist($training);
        $this->_em->flush();

        return $training->getId();
    }

    public function ensureThatSportAndTypeAreManaged(Training $activity): void
    {
        $sport = $activity->getSport();
        $type = $activity->getType();

        if ($sport->getId() && ! $this->_em->getUnitOfWork()->isInIdentityMap($sport)) {
            $activity->setSport(
                $this->_em->find(Sport::class, $sport->getId())
            );
        }

        if ($type instanceof Type && $type->getId() && ! $this->_em->getUnitOfWork()->isInIdentityMap($type)) {
            $activity->setType(
                $this->_em->find(Type::class, $type->getId())
            );
        }
    }

    public function remove(Training $activity): void
    {
        if ($activity->getRoute() instanceof Route) {
            $this->_em->remove($activity->getRoute());
        }

        if ($activity->getTrackdata() instanceof Trackdata) {
            $this->_em->remove($activity->getTrackdata());
        }

        if ($activity->getSwimdata() instanceof Swimdata) {
            $this->_em->remove($activity->getSwimdata());
        }

        if ($activity->getHrv() instanceof Hrv) {
            $this->_em->remove($activity->getHrv());
        }

        if ($activity->getRaceresult() instanceof Raceresult) {
            $this->_em->remove($activity->getRaceresult());
        }

        // #TSC: hacky request the lazy equipments here, so that while removing via EventListener the collection is filled
        $activity->getEquipment()
            ->count();

        $this->_em->remove($activity);
        $this->_em->flush();
    }
}

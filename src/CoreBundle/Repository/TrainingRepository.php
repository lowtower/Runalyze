<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\VO2max;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Model\Account\AccountStatistics;
use Runalyze\Sports\Running\MarathonShape;
use Runalyze\Util\Time;

/**
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getAmountOfLoggedKilometers(bool $cache = true): bool|float|int|string|null
    {
        $query = $this->createQueryBuilder('t')
            ->select('SUM(t.distance)')
            ->getQuery();

        if ($cache) {
            $query->enableResultCache(120);
        } else {
            $query->disableResultCache();
        }

        return $query->getSingleScalarResult();
    }

    /**
     * Aggregate running activity counts and distances for one account since a
     * timestamp in the storage's timezone-neutral format.
     *
     * @return array{activity_count: int, distance_sum: float}
     */
    public function findTrainingTotalsSince(Account $account, int $sportId, int $fromTimestamp): array
    {
        $totals = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS activity_count, SUM(t.distance) AS distance_sum')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time >= :fromTimestamp')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->setParameter('fromTimestamp', $fromTimestamp)
            ->getQuery()
            ->getSingleResult();

        return [
            'activity_count' => (int) $totals['activity_count'],
            'distance_sum' => (float) ($totals['distance_sum'] ?? 0.0),
        ];
    }

    /**
     * Return daily TRIMP totals for an account. Training timestamps are stored
     * as timezone-neutral UTC values, so UTC dates match the legacy grouping.
     *
     * @return list<array{day: string, trimp: float}>
     */
    public function findDailyTrimpForAccount(Account $account, ?int $from = null, ?int $to = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t.time AS time, t.trimp AS trimp')
            ->where('t.account = :account')
            ->setParameter('account', $account)
            ->orderBy('t.time', \SortDirection::Ascending);

        if ($from !== null) {
            $queryBuilder->andWhere('t.time >= :from')->setParameter('from', $from);
        }

        if ($to !== null) {
            $queryBuilder->andWhere('t.time <= :to')->setParameter('to', $to);
        }

        $totals = [];

        foreach ($queryBuilder->getQuery()->toIterable() as $row) {
            $day = gmdate('Y-m-d', (int) $row['time']);
            $totals[$day] = ($totals[$day] ?? 0.0) + (float) ($row['trimp'] ?? 0);
        }

        $result = [];

        foreach ($totals as $day => $trimp) {
            $result[] = [
                'day' => $day,
                'trimp' => $trimp,
            ];
        }

        return $result;
    }

    /**
     * Fetch bounded shape-plot inputs for one account, preserving the legacy
     * duration-weighted VO2max calculation and including TRIMP from all sports.
     *
     * @return list<array{
     *     day: string,
     *     trimp: float,
     *     distance: float,
     *     vo2max_weighted: float,
     *     vo2max_sum_time: float,
     *     is_running: bool
     * }>
     */
    public function findShapePlotTrainingData(
        Account $account,
        int $runningSportId,
        int $fromTimestamp,
        int $toTimestamp,
        bool $withElevation
    ): array {
        $vo2maxColumn = $withElevation ? 'vo2maxWithElevation' : 'vo2max';

        $rows = $this->createQueryBuilder('t')
            ->select('t.time AS time, t.trimp AS trimp, t.distance AS distance')
            ->addSelect('t.s AS duration, t.useVO2max AS useVO2max')
            ->addSelect('t.' . $vo2maxColumn . ' AS vo2max')
            ->addSelect('IDENTITY(t.sport) AS sport_id')
            ->where('t.account = :account')
            ->andWhere('t.time BETWEEN :fromTimestamp AND :toTimestamp')
            ->setParameter('account', $account)
            ->setParameter('fromTimestamp', $fromTimestamp)
            ->setParameter('toTimestamp', $toTimestamp)
            ->orderBy('t.time', \SortDirection::Ascending)
            ->getQuery()
            ->toIterable();

        $result = [];

        foreach ($rows as $row) {
            $isRunning = (int) $row['sport_id'] === $runningSportId;
            $duration = (float) ($row['duration'] ?? 0);
            $vo2max = (float) ($row['vo2max'] ?? 0);
            $useVO2max = (bool) $row['useVO2max'];

            $result[] = [
                'day' => gmdate('Y-m-d', (int) $row['time']),
                'trimp' => (float) ($row['trimp'] ?? 0),
                'distance' => (float) ($row['distance'] ?? 0),
                'vo2max_weighted' => $isRunning && $useVO2max ? $vo2max * $duration : 0.0,
                'vo2max_sum_time' => $isRunning && $useVO2max && $vo2max > 0 ? $duration : 0.0,
                'is_running' => $isRunning,
            ];
        }

        return $result;
    }

    /**
     * Return training rows for the prognosis trend, preserving the legacy
     * VO2max duration weighting and ordering by activity date.
     *
     * @return list<array{
     *     date_age: int,
     *     date: string,
     *     distance: float,
     *     vo2max_weighted: float,
     *     vo2max_sum_time: float
     * }>
     */
    public function findPrognosisTrendHistory(Account $account, int $sportId, bool $withElevation): array
    {
        $vo2maxColumn = $withElevation ? 'vo2maxWithElevation' : 'vo2max';

        $rows = $this->createQueryBuilder('t')
            ->select('t.time AS time')
            ->addSelect('t.distance AS distance')
            ->addSelect('t.s AS duration')
            ->addSelect('t.useVO2max AS useVO2max')
            ->addSelect('t.' . $vo2maxColumn . ' AS vo2max')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->orderBy('t.time', \SortDirection::Ascending)
            ->getQuery()
            ->toIterable();

        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $history = [];

        foreach ($rows as $row) {
            $date = gmdate('Y-m-d', (int) $row['time']);
            $day = new DateTimeImmutable($date, new DateTimeZone('UTC'));
            $duration = (float) $row['duration'];
            $vo2max = (float) ($row['vo2max'] ?? 0);
            $useVO2max = (bool) $row['useVO2max'];

            $history[] = [
                'date_age' => (int) $today->diff($day)->format('%r%a'),
                'date' => $date,
                'distance' => (float) ($row['distance'] ?? 0),
                'vo2max_weighted' => $duration * (int) $useVO2max * $vo2max,
                'vo2max_sum_time' => $vo2max > 0 && $useVO2max ? $duration : 0.0,
            ];
        }

        return $history;
    }

    /**
     * Get the best activities according to `vo2max_by_time`, at most one per distance.
     *
     * Replaces the raw SQL query of the deprecated
     * `Runalyze\Calculation\Prognosis\TopResults`, which was not compatible
     * with `ONLY_FULL_GROUP_BY`.
     *
     * @param int $sportId id of the running sport
     * @param int $numberOfResults number of distances to return
     * @param float $minimalDistanceRequired in [km]
     * @return array<int, array<string, int|float|null>> rows with keys `time`, `distance`, `s`, `vo2max_by_time`
     */
    public function findTopResults(Account $account, int $sportId, int $numberOfResults = 1, float $minimalDistanceRequired = 3.0): array
    {
        $allResults = $this->createQueryBuilder('t')
            ->select('t.time, t.distance, t.s, t.vo2maxByTime as vo2max_by_time')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.distance >= :minimalDistance')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->setParameter('minimalDistance', $minimalDistanceRequired)
            ->orderBy('t.vo2maxByTime', \SortDirection::Descending)
            ->setMaxResults(20)
            ->getQuery()
            ->getArrayResult();

        $bestResultPerDistance = [];

        foreach ($allResults as $result) {
            $bestResultPerDistance[(string) $result['distance']] ??= $result;
        }

        return \array_slice(\array_values($bestResultPerDistance), 0, $numberOfResults);
    }

    /**
     * @return Training[]
     */
    public function findVO2maxAnalysisRaces(Account $account, int $sportId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.raceresult', 'r')
            ->where('t.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.pulseAvg != 0')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->orderBy('t.time', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }

    /**
     * @see \Runalyze\Metrics\Velocity\Unit\PaceEnum
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSpeedUnitFor(int $activityid, int $accountid): mixed
    {
        return $this->getEntityManager()->createQueryBuilder()
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
            ->orderBy('t.time', \SortDirection::Ascending)
            ->addOrderBy('t.id', \SortDirection::Ascending)
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
            ->orderBy('t.time', \SortDirection::Descending)
            ->addOrderBy('t.id', \SortDirection::Descending)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    public function getActiveYearsFor(Account $account, ?int $sportid = null, ?int $minimumActivities = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
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
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
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
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(1) as num')
            ->from(Training::class, 't')
            ->where('t.type = :typeid')
            ->setParameter('typeid', $type->getId());

        return $queryBuilder->getQuery()
            ->getSingleScalarResult();
    }

    public function getTypesWithTraining(Account $account): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
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
        $qb = $this->getEntityManager()->createQueryBuilder();
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
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
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

        $dataForAccount = $this->getEntityManager()->createQueryBuilder()
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
        return $this->getEntityManager()->createQueryBuilder()
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
            ->setParameter('account', $account->getId())
            ->setParameter('sport', $sport->getId())
            ->setParameter('startTime', mktime(0, 0, 0, 1, 1, $year))
            ->setParameter('endTime', mktime(23, 59, 59, 12, 31, $year))
            ->getQuery();
    }

    public function getStatsForPoster(Account $account, Sport $sport, int $year): Query
    {
        return $this->getEntityManager()->createQueryBuilder()
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
            ->setParameter('account', $account->getId())
            ->setParameter('sport', $sport->getId())
            ->setParameter('startTime', mktime(0, 0, 0, 1, 1, $year))
            ->setParameter('endTime', mktime(23, 59, 59, 12, 31, $year))
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
            ->orderBy('t.time', \SortDirection::Descending)
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
                ->orderBy('t.time', \SortDirection::Descending)
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
            $this->getEntityManager()->persist($training->getRoute());
        }

        if ($training->getTrackdata() instanceof Trackdata) {
            $this->getEntityManager()->persist($training->getTrackdata());
        }

        if ($training->getSwimdata() instanceof Swimdata) {
            $this->getEntityManager()->persist($training->getSwimdata());
        }

        if ($training->getHrv() instanceof Hrv) {
            $this->getEntityManager()->persist($training->getHrv());
        }

        if ($training->getRaceresult() instanceof Raceresult) {
            $this->getEntityManager()->persist($training->getRaceresult());
        }

        $this->getEntityManager()->persist($training);
        $this->getEntityManager()->flush();

        return $training->getId();
    }

    public function ensureThatSportAndTypeAreManaged(Training $activity): void
    {
        $sport = $activity->getSport();
        $type = $activity->getType();

        if ($sport->getId() && ! $this->getEntityManager()->getUnitOfWork()->isInIdentityMap($sport)) {
            $activity->setSport(
                $this->getEntityManager()->find(Sport::class, $sport->getId())
            );
        }

        if ($type instanceof Type && $type->getId() && ! $this->getEntityManager()->getUnitOfWork()->isInIdentityMap($type)) {
            $activity->setType(
                $this->getEntityManager()->find(Type::class, $type->getId())
            );
        }
    }

    public function remove(Training $activity): void
    {
        if ($activity->getRoute() instanceof Route) {
            $this->getEntityManager()->remove($activity->getRoute());
        }

        if ($activity->getTrackdata() instanceof Trackdata) {
            $this->getEntityManager()->remove($activity->getTrackdata());
        }

        if ($activity->getSwimdata() instanceof Swimdata) {
            $this->getEntityManager()->remove($activity->getSwimdata());
        }

        if ($activity->getHrv() instanceof Hrv) {
            $this->getEntityManager()->remove($activity->getHrv());
        }

        if ($activity->getRaceresult() instanceof Raceresult) {
            $this->getEntityManager()->remove($activity->getRaceresult());
        }

        // #TSC: hacky request the lazy equipments here, so that while removing via EventListener the collection is filled
        $activity->getEquipment()
            ->count();

        $this->getEntityManager()->remove($activity);
        $this->getEntityManager()->flush();
    }
}

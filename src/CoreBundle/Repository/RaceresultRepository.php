<?php

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Sport;

/**
 * @extends ServiceEntityRepository<Raceresult>
 * @phpstan-type RaceStatisticsRow array{
 *     officialTime: float, officialDistance: ?float, name: string,
 *     placeTotal: ?int, placeGender: ?int, placeAgeclass: ?int,
 *     participantsTotal: ?int, participantsGender: ?int, participantsAgeclass: ?int,
 *     id: int, time: int, isTrack: bool, pulseAvg: ?int, pulseMax: ?int,
 *     weatherid: int, temperature: ?int, isNight: ?bool
 * }
 */
class RaceresultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Raceresult::class);
    }

    public const int NUMBER_OF_RACES_TO_CONSIDER_FOR_CORRECTION_FACTOR = 3;

    public function findForActivity(int $activityId): ?Raceresult
    {
        return $this->findOneBy([
            'activity' => $activityId,
        ]);
    }

    public function findForAccount(int $activityId, int $accountId): ?Raceresult
    {
        return $this->findOneBy([
            'activity' => $activityId,
            'account' => $accountId,
        ]);
    }

    /**
     * @param array<int|string, mixed> $distances
     * @return array<string, array{activity_id: int, seconds: float}>
     */
    public function findPersonalBestDetailsForDistances(Account $account, int $sportId, array $distances): array
    {
        $distances = array_values(array_unique(array_map(
            static fn (mixed $distance): float => (float) $distance,
            array_filter($distances, is_numeric(...))
        )));

        if ($distances === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('race')
            ->select('race.officialDistance AS distance')
            ->addSelect('race.officialTime AS seconds')
            ->addSelect('activity.id AS activityId')
            ->join('race.activity', 'activity')
            ->where('race.account = :account')
            ->andWhere('activity.sport = :sportId')
            ->andWhere('race.officialDistance IN (:distances)')
            ->setParameter('account', $account)
            ->setParameter('sportId', $sportId)
            ->setParameter('distances', $distances)
            ->orderBy('race.officialDistance', \SortDirection::Ascending)
            ->addOrderBy('race.officialTime', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult();

        $personalBests = [];

        foreach ($rows as $row) {
            $key = sprintf('%.2f', (float) $row['distance']);

            if (isset($personalBests[$key])) {
                continue;
            }

            $personalBests[$key] = [
                'activity_id' => (int) $row['activityId'],
                'seconds' => (float) $row['seconds'],
            ];
        }

        return $personalBests;
    }

    /**
     * @return list<array{time: int, id: int, official_time: float}>
     */
    public function findTrendResultsForDistance(Account $account, int $sportId, float $distance): array
    {
        $rows = $this->createQueryBuilder('race')
            ->select('activity.time AS time')
            ->addSelect('activity.id AS id')
            ->addSelect('race.officialTime AS officialTime')
            ->join('race.activity', 'activity')
            ->where('race.account = :account')
            ->andWhere('activity.sport = :sport')
            ->andWhere('race.officialDistance = :distance')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->setParameter('distance', $distance)
            ->orderBy('activity.time', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'time' => (int) $row['time'],
                'id' => (int) $row['id'],
                'official_time' => (float) $row['officialTime'],
            ],
            $rows
        );
    }

    /**
     * @return list<RaceStatisticsRow>
     */
    public function findForStatistics(Account $account, int $sportId): array
    {
        return $this->createQueryBuilder('race')
            ->select(
                'race.officialTime AS officialTime',
                'race.officialDistance AS officialDistance',
                'race.name AS name',
                'race.placeTotal AS placeTotal',
                'race.placeGender AS placeGender',
                'race.placeAgeclass AS placeAgeclass',
                'race.participantsTotal AS participantsTotal',
                'race.participantsGender AS participantsGender',
                'race.participantsAgeclass AS participantsAgeclass',
                'activity.id AS id',
                'activity.time AS time',
                'activity.isTrack AS isTrack',
                'activity.pulseAvg AS pulseAvg',
                'activity.pulseMax AS pulseMax',
                'activity.weatherid AS weatherid',
                'activity.temperature AS temperature',
                'activity.isNight AS isNight'
            )
            ->join('race.activity', 'activity')
            ->where('race.account = :account')
            ->andWhere('activity.account = :account')
            ->andWhere('activity.sport = :sport')
            ->setParameter('account', $account)
            ->setParameter('sport', $sportId)
            ->orderBy('activity.time', \SortDirection::Descending)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array<int, int>
     */
    public function countWeatherConditionsForStatistics(Account $account): array
    {
        $rows = $this->createQueryBuilder('race')
            ->select('activity.weatherid AS condition', 'COUNT(activity.id) AS total')
            ->join('race.activity', 'activity')
            ->where('race.account = :account')
            ->andWhere('activity.account = :account')
            ->andWhere('activity.weatherid <> :unknown')
            ->setParameter('account', $account)
            ->setParameter('unknown', \Runalyze\Profile\Weather\WeatherConditionProfile::UNKNOWN)
            ->groupBy('activity.weatherid')
            ->orderBy('activity.weatherid', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'total', 'condition');
    }

    public function hasRaceForActivity(Account $account, int $activityId): bool
    {
        return (bool) $this->createQueryBuilder('race')
            ->select('COUNT(activity.id)')
            ->join('race.activity', 'activity')
            ->where('race.account = :account')
            ->andWhere('activity.account = :account')
            ->andWhere('activity.id = :activity')
            ->setParameter('account', $account)
            ->setParameter('activity', $activityId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForSportAndYear(Account $account, Sport $sport, int $year): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select(
                'r',
                't.time'
            )
            ->from(Raceresult::class, 'r')
            ->join('r.activity', 't')
            ->where('r.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->setParameter('account', $account->getId())
            ->setParameter('sport', $sport->getId())
            ->setParameter('startTime', mktime(0, 0, 0, 1, 1, $year))
            ->setParameter('endTime', mktime(23, 59, 59, 12, 31, $year))
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return Raceresult[]
     */
    public function findAllWithActivityStats(Account $account): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->addSelect('partial a.{id, time, sport, vo2max, vo2maxByTime, vo2maxWithElevation}')
            ->join('r.activity', 'a')
            ->where('r.account = :account')
            ->setParameters(new \Doctrine\Common\Collections\ArrayCollection([new \Doctrine\ORM\Query\Parameter(':account', $account)]))
            ->getQuery()
            ->getResult();
    }

    public function getEffectiveVO2maxCorrectionFactor(Account $account, int $sportId): float
    {
        $result = $this->createQueryBuilder('r')
            ->join('r.activity', 't')
            ->select([
                't.vo2maxByTime * 1.0 / t.vo2max as factor',
            ])
            ->where('r.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.useVO2max = 1')
            ->andWhere('t.vo2max > 0')
            ->setParameter('account', $account->getId())
            ->setParameter('sport', $sportId)
            ->orderBy('t.vo2maxByTime', \SortDirection::Descending)
            ->setMaxResults(self::NUMBER_OF_RACES_TO_CONSIDER_FOR_CORRECTION_FACTOR)
            ->getQuery()
            ->getResult('COLUMN_HYDRATOR');

        if (empty($result)) {
            return 1.0;
        }

        return max(array_map(fn ($v): float => (float) $v, $result));
    }

    public function save(Raceresult $raceResult): void
    {
        $this->getEntityManager()->persist($raceResult);
        $this->getEntityManager()->flush();
    }

    public function delete(Raceresult $raceResult): void
    {
        $this->getEntityManager()->remove($raceResult);
        $this->getEntityManager()->flush();
    }
}

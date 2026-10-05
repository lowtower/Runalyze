<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Raceresult>
 */
class RaceresultRepository extends EntityRepository
{
    public const NUMBER_OF_RACES_TO_CONSIDER_FOR_CORRECTION_FACTOR = 3;

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

    public function findForSportAndYear(Account $account, Sport $sport, int $year): array
    {
        return $this->_em->createQueryBuilder()
            ->select(
                'r',
                't.time'
            )
            ->from(Raceresult::class, 'r')
            ->join('r.activity', 't')
            ->where('r.account = :account')
            ->andWhere('t.sport = :sport')
            ->andWhere('t.time BETWEEN :startTime and :endTime')
            ->setParameters([
                ':account' => $account->getId(),
                ':sport' => $sport->getId(),
                ':startTime' => mktime(0, 0, 0, 1, 1, $year),
                ':endTime' => mktime(23, 59, 59, 12, 31, $year),
            ])
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
            ->setParameters([
                ':account' => $account,
            ])
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
            ->orderBy('t.vo2maxByTime', 'DESC')
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
        $this->_em->persist($raceResult);
        $this->_em->flush();
    }

    public function delete(Raceresult $raceResult): void
    {
        $this->_em->remove($raceResult);
        $this->_em->flush();
    }
}

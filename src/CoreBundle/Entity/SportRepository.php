<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Model\Sport\SportStatistics;
use Runalyze\Profile\Sport\Running;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Util\LocalTime;

/**
 * @extends EntityRepository<Sport>
 */
class SportRepository extends EntityRepository
{
    /**
     * @return Sport[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    public function findInternalIdFor(int $internalTypeId, Account $account): ?Sport
    {
        return $this->findOneBy([
            'account' => $account->getId(),
            'internalSportId' => $internalTypeId,
        ]);
    }

    public function findRunningFor(Account $account, bool $returnNull = false): ?Sport
    {
        $sport = $this->findInternalIdFor(SportProfile::RUNNING, $account);

        if (! $sport instanceof Sport && ! $returnNull) {
            $sport = new Sport();
            $sport->setDataFrom(new Running());
        }

        return $sport;
    }

    /**
     * @return Sport[]
     */
    public function findWithDistancesFor(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
            'distances' => true,
        ]);
    }

    public function findThisOrAny(int $sportId, Account $account): ?Sport
    {
        /** @var null|Sport $requestedSport */
        $requestedSport = $this->find($sportId);

        if ($requestedSport !== null && $requestedSport->getAccount()->getId() != $account->getId()) {
            $requestedSport = null;
        }

        if ($requestedSport === null) {
            $results = $this->findBy(
                [
                    'account' => $account->getId(),
                ],
                null,
                1
            );

            if (count($results) === 1) {
                return $results[0];
            }
        }

        return $requestedSport;
    }

    public function findEquipmentCategoryIdsFor(array $sportIds): mixed
    {
        // TODO: this query results in two joins with one of them being useless
        // SELECT r0_.id AS id_0, r1_.id AS id_1 FROM runalyze_sport r0_ INNER JOIN runalyze_equipment_sport r2_ ON r0_.id = r2_.sportid INNER JOIN runalyze_equipment_type r1_ ON r1_.id = r2_.equipment_typeid WHERE r0_.id IN (?)

        return $this->createQueryBuilder('s')
            ->innerJoin('s.equipmentType', 'e')
            ->select('s.id as sport_id, e.id as equipment_type_id')
            ->where('s.id IN(:sports)')
            ->setParameter('sports', $sportIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array internal sport ids
     */
    public function getUsedInternalSportIdsFor(Account $account): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->select('s.internalSportId')
            ->where('s.account = :account')
            ->andWhere($queryBuilder->expr()->isNotNull('s.internalSportId'))
            ->setParameter('account', $account->getId());

        return $queryBuilder->getQuery()
            ->getResult('COLUMN_HYDRATOR');
    }

    public function isInternalTypeFree(int $internalTypeId, Account $account): bool
    {
        return ! $this->findInternalIdFor($internalTypeId, $account) instanceof Sport;
    }

    public function getFreeInternalTypes(Account $account): array
    {
        $allTypes = array_flip(SportProfile::getChoices());

        foreach ($this->getUsedInternalSportIdsFor($account) as $usedId) {
            if (isset($allTypes[$usedId])) {
                unset($allTypes[$usedId]);
            }
        }

        if (isset($allTypes[SportProfile::GENERIC])) {
            unset($allTypes[SportProfile::GENERIC]);
        }

        return $allTypes;
    }

    /**
     * @param bool $raw if enabled, raw array data is returned
     */
    public function getSportStatisticsSince(?int $timestamp, Account $account, bool $raw = false): SportStatistics|array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->addSelect('COUNT(t.id) as num')
            ->addSelect('SUM(t.distance) as distance')
            ->addSelect('SUM(t.s) as time_in_s')
            ->addSelect('SUM(CASE WHEN t.distance > 0 THEN 1 ELSE 0 END) as count_distance')
            ->from(Sport::class, 's')
            ->innerJoin('s.trainings', 't', 'WITH', 't.account = :account')
            ->where('s.account = :account')
            ->setParameter(':account', $account->getId())
            ->groupBy('s.id')
            ->orderBy('distance', \SortDirection::Descending)
            ->addOrderBy('time_in_s', \SortDirection::Descending);

        if ($timestamp !== null) {
            $queryBuilder->andWhere('t.time > :startTime');
            $queryBuilder->setParameter(':startTime', $timestamp);
        }

        if ($raw) {
            return $queryBuilder->getQuery()
                ->getResult();
        }

        return new SportStatistics(new LocalTime($timestamp)->toServerTime(), $queryBuilder->getQuery()->getResult());
    }

    public function save(Sport $sport): void
    {
        $this->getEntityManager()->persist($sport);
        $this->getEntityManager()->flush();
    }

    public function remove(Sport $sport): void
    {
        $this->getEntityManager()->remove($sport);
        $this->getEntityManager()->flush();
    }
}

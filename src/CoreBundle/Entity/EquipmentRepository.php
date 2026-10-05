<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Runalyze\Bundle\CoreBundle\Model\Equipment\EquipmentStatistics;

/**
 * @extends EntityRepository<Equipment>
 */
class EquipmentRepository extends EntityRepository
{
    /**
     * @return Equipment[]
     */
    public function findForTypeId(int $equipmentTypeId, Account $account): array
    {
        return $this->findBy([
            'type' => $equipmentTypeId,
            'account' => $account->getId(),
        ]);
    }

    /**
     * @return Equipment[]
     */
    public function findForName(string|array $equipmentName, Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
            'name' => $equipmentName,
        ]);
    }

    public function getStatisticsForType(int $typeId, Account $account): EquipmentStatistics
    {
        return new EquipmentStatistics(
            $this->createQueryBuilder('eq')
                ->select('eq')
                ->addSelect('SUM(1) as num')
                ->addSelect('MIN(t.s/t.distance) as max_pace')
                ->addSelect('MAX(t.distance) as max_distance')
                ->join('eq.activity', 't')
                ->where('eq.type = :typeid')
                ->andWhere('eq.account = :account')
                ->setParameter(':typeid', $typeId)
                ->setParameter(':account', $account->getId())
                ->addOrderBy('eq.distance', 'DESC')
                ->groupBy('eq.id')
                ->getQuery()
                ->getResult()
        );
    }

    /**
     * @param Equipment[] $equipment
     * @param ?int $additionalDuration [s]
     * @param ?float $additionalDistance [km]
     */
    public function updateEquipment(array $equipment, ?int $additionalDuration = null, ?float $additionalDistance = null, bool $flush = true): void
    {
        foreach ($equipment as $object) {
            $object->addTime($additionalDuration);
            $object->addDistance($additionalDistance);
            $this->_em->persist($object);
        }

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function save(Equipment $equipment): void
    {
        $this->_em->persist($equipment);
        $this->_em->flush();
    }

    public function remove(Equipment $equipment): void
    {
        $this->_em->remove($equipment);
        $this->_em->flush();
    }
}

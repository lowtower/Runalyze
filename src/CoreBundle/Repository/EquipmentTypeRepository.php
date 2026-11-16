<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Symfony\Bridge\Doctrine\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<EquipmentType>
 */
class EquipmentTypeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentType::class);
    }

    /**
     * @return EquipmentType[]
     */
    public function findAllFor(Account $account): array
    {
        return $this->findBy([
            'account' => $account->getId(),
        ]);
    }

    /**
     * @return EquipmentType[]
     */
    public function findSingleChoiceTypesFor(Account $account): array
    {
        return $this->findBy([
            'input' => EquipmentType::CHOICE_SINGLE,
            'account' => $account->getId(),
        ]);
    }

    public function save(EquipmentType $equipmentType): void
    {
        $this->getEntityManager()->persist($equipmentType);
        $this->getEntityManager()->flush();
    }

    public function remove(EquipmentType $equipmentType): void
    {
        $this->getEntityManager()->remove($equipmentType);
        $this->getEntityManager()->flush();
    }
}

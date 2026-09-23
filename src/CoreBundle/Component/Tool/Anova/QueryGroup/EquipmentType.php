<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class EquipmentType implements QueryGroupInterface
{
    public function __construct(
        protected int $EquipmentTypeId
    ) {
    }

    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as, string $sportAlias): void
    {
        $queryBuilder
            ->addSelect(sprintf('%s.id as %s', 'eq', $as))
            ->leftJoin(sprintf('%s.equipment', $alias), 'eq')
            ->andWhere('eq.type = :equipmentTypeId')
            ->setParameter(':equipmentTypeId', $this->EquipmentTypeId)
            ->distinct()
        ;
    }

    public function loadAllGroups(EntityManager $entityManager, Account $account, AnovaData $anovaData): array
    {
        $groups = [];

        foreach ($entityManager->getRepository(Equipment::class)->findByTypeId($this->EquipmentTypeId, $account) as $equipment) {
            $groups[$equipment->getId()] = $equipment->getName();
        }

        return $groups;
    }

    public function showEmptyGroups(): bool
    {
        return false;
    }
}

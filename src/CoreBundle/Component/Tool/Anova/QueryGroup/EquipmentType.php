<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class EquipmentType implements QueryGroupInterface
{
    /**
     * @var int
     */
    protected $EquipmentTypeId;

    public function __construct($id)
    {
        $this->EquipmentTypeId = (int) $id;
    }

    /**
     * @param string $alias
     * @param string $as
     * @param string $sportAlias
     */
    public function addSelectionToQuery(QueryBuilder $queryBuilder, $alias, $as, $sportAlias)
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

        foreach ($entityManager->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Equipment::class)->findByTypeId($this->EquipmentTypeId, $account) as $equipment) {
            $groups[$equipment->getId()] = $equipment->getName();
        }

        return $groups;
    }

    public function showEmptyGroups(): bool
    {
        return false;
    }
}

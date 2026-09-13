<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class Type implements QueryGroupInterface
{
    /**
     * @param string $alias
     * @param string $as
     * @param string $sportAlias
     */
    public function addSelectionToQuery(QueryBuilder $queryBuilder, $alias, $as, $sportAlias): void
    {
        $queryBuilder
            ->addSelect(sprintf('%s.id as %s', 'ty', $as))
            ->join(sprintf('%s.type', $alias), 'ty')
        ;
    }

    public function loadAllGroups(EntityManager $entityManager, Account $account, AnovaData $anovaData): array
    {
        $groups = [];
        $sports = $anovaData->getSport();

        foreach ($entityManager->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Type::class)->findAllFor($account) as $type) {
            foreach ($sports as $sport) {
                if ($sport->getId() == $type->getSport()->getId()) {
                    $groups[$type->getId()] = $type->getName();
                    break;
                }
            }
        }

        return $groups;
    }

    public function showEmptyGroups(): bool
    {
        return true;
    }
}

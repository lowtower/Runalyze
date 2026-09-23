<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class Sport implements QueryGroupInterface
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as, string $sportAlias): void
    {
        $queryBuilder->addSelect(sprintf('%s.id as %s', $sportAlias, $as));
    }

    public function loadAllGroups(EntityManager $entityManager, Account $account, AnovaData $anovaData): array
    {
        $groups = [];
        $sports = $anovaData->getSport();

        foreach ($entityManager->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Sport::class)->findAllFor($account) as $sport) {
            foreach ($sports as $singleSport) {
                if ($singleSport->getId() == $sport->getId()) {
                    $groups[$sport->getId()] = $sport->getName();
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

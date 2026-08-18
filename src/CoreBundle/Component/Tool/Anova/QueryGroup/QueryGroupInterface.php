<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

interface QueryGroupInterface
{
    /**
     * @param string $alias
     * @param string $as
     * @param string $sportAlias
     */
    public function addSelectionToQuery(QueryBuilder $queryBuilder, $alias, $as, $sportAlias);

    /**
     * @return array
     */
    public function loadAllGroups(EntityManager $entityManager, Account $account, AnovaData $anovaData);

    /**
     * @return bool
     */
    public function showEmptyGroups();
}

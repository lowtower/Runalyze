<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;

class Vo2max extends AbstractOneColumnValue
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, $alias, $as)
    {
        parent::addSelectionToQuery($queryBuilder, $alias, $as);

        $queryBuilder->andWhere(sprintf('%s.%s = 1', $alias, 'useVO2max'));
    }

    protected function getColumn(): string
    {
        return 'vo2max';
    }

    public function getValueUnit(UnitSystem $unitSystem): \Runalyze\Metrics\Common\Unit\Factorial
    {
        return $unitSystem->getVO2maxUnit();
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\Unit\Factorial;

class Vo2max extends AbstractOneColumnValue
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as): void
    {
        parent::addSelectionToQuery($queryBuilder, $alias, $as);

        $queryBuilder->andWhere(sprintf('%s.%s = 1', $alias, 'useVO2max'));
    }

    protected function getColumn(): string
    {
        return 'vo2max';
    }

    public function getValueUnit(UnitSystem $unitSystem): Factorial
    {
        return $unitSystem->getVO2maxUnit();
    }
}

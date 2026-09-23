<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;

class Pace implements QueryValueInterface
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as): void
    {
        $queryBuilder
            ->addSelect(sprintf('%s.s/%s.distance as %s', $alias, $alias, $as))
            ->andWhere(sprintf('%s.distance IS NOT NULL', $alias));
    }

    public function getValueUnit(UnitSystem $unitSystem): AbstractPaceUnit
    {
        return $unitSystem->getPaceUnit();
    }
}

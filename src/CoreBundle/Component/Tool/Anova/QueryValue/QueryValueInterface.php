<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Metrics\Common\UnitInterface;

interface QueryValueInterface
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as);

    public function getValueUnit(UnitSystem $unitSystem): UnitInterface;
}

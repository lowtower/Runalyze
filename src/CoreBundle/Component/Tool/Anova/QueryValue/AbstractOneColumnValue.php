<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractOneColumnValue implements QueryValueInterface
{
    public function addSelectionToQuery(QueryBuilder $queryBuilder, string $alias, string $as)
    {
        $queryBuilder
            ->addSelect(sprintf('%s.%s as %s', $alias, $this->getColumn(), $as))
            ->andWhere(sprintf('%s.%s IS NOT NULL', $alias, $this->getColumn()));
    }

    abstract protected function getColumn(): string;
}

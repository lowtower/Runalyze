<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class ColumnHydrator extends AbstractHydrator
{
    /**
     * @return list<mixed>
     */
    protected function hydrateAllData(): array
    {
        return $this->statement()->fetchFirstColumn();
    }
}

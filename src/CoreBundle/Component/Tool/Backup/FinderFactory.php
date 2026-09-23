<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

use Symfony\Component\Finder\Finder;

class FinderFactory implements FinderFactoryInterface
{
    public function create(): Finder
    {
        return new Finder();
    }
}

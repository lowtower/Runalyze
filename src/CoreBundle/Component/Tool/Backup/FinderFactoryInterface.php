<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

use Symfony\Component\Finder\Finder;

interface FinderFactoryInterface
{
    public function create(): Finder;
}

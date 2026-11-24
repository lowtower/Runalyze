<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Symfony\Component\HttpFoundation\InputBag;

interface ModernStatisticsRequestHandlerInterface
{
    public function handleRequest(Account $account, Plugin $installation, InputBag $query): void;
}

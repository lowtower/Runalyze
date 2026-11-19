<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Panel;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;

interface ModernPanelRequestHandlerInterface
{
    /**
     * @param array<string, mixed> $queryParameters
     */
    public function handleRequest(Account $account, Plugin $installation, array $queryParameters): void;
}

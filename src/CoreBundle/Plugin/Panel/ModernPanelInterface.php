<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Panel;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;

interface ModernPanelInterface
{
    public function getKey(): string;

    public function getTemplate(): string;

    /**
     * @return array<string, mixed>
     */
    public function getData(Account $account, Plugin $installation): array;
}

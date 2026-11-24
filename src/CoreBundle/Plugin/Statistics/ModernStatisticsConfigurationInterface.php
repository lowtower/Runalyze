<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;

interface ModernStatisticsConfigurationInterface
{
    public function getConfigurationTemplate(): string;

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationData(Account $account, Plugin $installation): array;

    /**
     * @param array<string, mixed> $data
     */
    public function updateConfiguration(Account $account, Plugin $installation, array $data): void;
}

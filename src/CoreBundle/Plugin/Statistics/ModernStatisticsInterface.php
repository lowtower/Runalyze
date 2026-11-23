<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Symfony\Component\HttpFoundation\InputBag;

interface ModernStatisticsInterface
{
    public function getKey(): string;

    public function getName(): string;

    public function getDescription(?Account $account = null): string;

    public function getTemplate(): string;

    /**
     * @return array<string, mixed>
     */
    public function present(Account $account, Plugin $installation, InputBag $query): array;
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Statistics;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;

final class StatisticsInstallation
{
    public static function requireId(Account $account, Plugin $installation, string $key): int
    {
        $id = $installation->getId();
        $accountId = $account->getId();

        if (
            $id === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== $key
            || ! $installation->isStatistic()
        ) {
            throw new InvalidArgumentException(sprintf('Statistics plugin "%s" is not installed for this account.', $key));
        }

        return $id;
    }
}

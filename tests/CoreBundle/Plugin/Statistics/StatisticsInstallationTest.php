<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Plugin\Statistics;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInstallation;

final class StatisticsInstallationTest extends TestCase
{
    private const string KEY = 'RunalyzePluginStat_Wetter';

    public function testAcceptsAnOwnedInactiveStatisticsInstallation(): void
    {
        $account = $this->accountWithId(4);
        $installation = new Plugin()
            ->setAccount($account)
            ->setKey(self::KEY)
            ->setActive(Plugin::STATE_INACTIVE);
        new \ReflectionProperty(Plugin::class, 'id')->setValue($installation, 7);

        $this->assertSame(7, StatisticsInstallation::requireId($this->accountWithId(4), $installation, self::KEY));
    }

    #[DataProvider('invalidInstallations')]
    public function testRejectsMismatchedInstallations(?int $id, ?int $accountId, string $type, string $key): void
    {
        $installation = new Plugin()
            ->setAccount($this->accountWithId(4))
            ->setKey($key)
            ->setType($type);
        new \ReflectionProperty(Plugin::class, 'id')->setValue($installation, $id);

        $this->expectException(InvalidArgumentException::class);
        StatisticsInstallation::requireId($this->accountWithId($accountId), $installation, self::KEY);
    }

    /**
     * @return iterable<string, array{?int, ?int, string, string}>
     */
    public static function invalidInstallations(): iterable
    {
        yield 'unsaved installation' => [null, 4, Plugin::TYPE_STAT, self::KEY];
        yield 'unsaved account' => [7, null, Plugin::TYPE_STAT, self::KEY];
        yield 'different account' => [7, 5, Plugin::TYPE_STAT, self::KEY];
        yield 'different plugin' => [7, 4, Plugin::TYPE_STAT, 'RunalyzePluginStat_Tag'];
        yield 'panel' => [7, 4, Plugin::TYPE_PANEL, self::KEY];
    }

    private function accountWithId(?int $id): Account
    {
        $account = new Account();
        new \ReflectionProperty(Account::class, 'id')->setValue($account, $id);

        return $account;
    }
}

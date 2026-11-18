<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Plugin;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Services\Plugin\SportlerPanelDataProvider;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use ReflectionProperty;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class SportlerPanelDataProviderTest extends TestCase
{
    public function testLoadsLatestValuesAndHistoriesUsingConfiguredPointLimit(): void
    {
        $account = $this->createAccount(7);
        $installation = $this->createInstallation($account, 17);
        $latest = (new User())
            ->setAccount($account)
            ->setTime(1234)
            ->setWeight(68.4)
            ->setPulseRest(48)
            ->setPulseMax(185)
            ->setFat(14.5)
            ->setWater(58.2)
            ->setMuscles(41.3);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('getLatestEntryFor')->with($account)->willReturn($latest);
        $repository->expects($this->once())->method('findWeightHistoryFor')->with($account, 12)->willReturn([
            ['time' => 200, 'weight' => 68.5, 'pulseRest' => 49],
            ['time' => 100, 'weight' => 68.4, 'pulseRest' => 48],
        ]);
        $repository->expects($this->once())->method('findBodyCompositionHistoryFor')->with($account, 12)->willReturn([
            ['time' => 200, 'fat' => 15.0, 'water' => 58.0, 'muscles' => 41.0],
            ['time' => 100, 'fat' => 14.5, 'water' => 58.2, 'muscles' => 41.3],
        ]);

        $provider = $this->createProvider($repository, [
            17 => [
                'wunschgewicht' => '72',
                'plot_points' => '12',
                'plot_timerange' => '0',
            ],
        ]);

        $data = $provider->getData($account, $installation);

        $this->assertSame([100, 200], array_column($data['weightHistory'], 'time'));
        $this->assertSame([100, 200], array_column($data['bodyCompositionHistory'], 'time'));
        $this->assertSame(17, $data['pluginId']);
        $this->assertFalse($data['isHidden']);
        $this->assertSame([
            'desiredWeight' => 72,
            'plotPoints' => 12,
            'plotTimerange' => 0,
        ], $data['configuration']);
        $this->assertSame([
            'weight' => 68.4,
            'pulseRest' => 48,
            'pulseMax' => 185,
            'fat' => 14.5,
            'water' => 58.2,
            'muscles' => 41.3,
        ], $data['latest']);
    }

    public function testLoadsHistoriesUsingConfiguredTimeRange(): void
    {
        $account = $this->createAccount(7);
        $installation = $this->createInstallation($account, 17);

        $repository = $this->createMock(UserRepository::class);
        $repository->method('getLatestEntryFor')->willReturn(null);
        $repository->expects($this->once())->method('findWeightHistorySince')->with($account, 2000000 - 30 * 86400)->willReturn([]);
        $repository->expects($this->once())->method('findBodyCompositionHistorySince')->with($account, 2000000 - 30 * 86400)->willReturn([]);

        $provider = $this->createProvider(
            $repository,
            [17 => ['plot_timerange' => '30']],
            2000000
        );

        $data = $provider->getData($account, $installation);

        $this->assertSame(30, $data['configuration']['plotTimerange']);
        $this->assertSame(20, $data['configuration']['plotPoints']);
    }

    public function testUsesLegacyDefaultValuesWhenNoBodyValueEntryExists(): void
    {
        $account = $this->createAccount(7);
        $installation = $this->createInstallation($account, 17);

        $repository = $this->createMock(UserRepository::class);
        $repository->method('getLatestEntryFor')->willReturn(null);
        $repository->method('findWeightHistoryFor')->willReturn([]);
        $repository->method('findBodyCompositionHistoryFor')->willReturn([]);

        $data = $this->createProvider($repository, [])->getData($account, $installation);

        $this->assertSame([
            'weight' => 0.0,
            'pulseRest' => 0,
            'pulseMax' => 0,
            'fat' => 0.0,
            'water' => 0.0,
            'muscles' => 0.0,
        ], $data['latest']);
        $this->assertSame([
            'desiredWeight' => 0,
            'plotPoints' => 20,
            'plotTimerange' => 0,
        ], $data['configuration']);
    }

    public function testRejectsInstallationOwnedByAnotherAccount(): void
    {
        $account = $this->createAccount(7);
        $otherAccount = $this->createAccount(8);
        $installation = $this->createInstallation($otherAccount, 17);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('getLatestEntryFor');

        $provider = $this->createProvider($repository, []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Sportler panel installation is not available for this account.');

        $provider->getData($account, $installation);
    }

    public function testRejectsInactiveInstallation(): void
    {
        $account = $this->createAccount(7);
        $installation = $this->createInstallation($account, 17, PluginInstallation::STATE_INACTIVE);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('getLatestEntryFor');

        $provider = $this->createProvider($repository, []);

        $this->expectException(InvalidArgumentException::class);

        $provider->getData($account, $installation);
    }

    /**
     * @param array<int, array<string, string>> $configuration
     */
    private function createProvider(
        UserRepository $repository,
        array $configuration,
        int $timestamp = 2000000
    ): SportlerPanelDataProvider {
        $configurationStorage = $this->createStub(PluginConfigurationStorageInterface::class);
        $configurationStorage->method('loadForPlugins')->willReturn($configuration);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('@' . $timestamp));

        return new SportlerPanelDataProvider($repository, $configurationStorage, $clock);
    }

    private function createAccount(int $id): Account
    {
        $account = new Account();
        new ReflectionProperty(Account::class, 'id')->setValue($account, $id);

        return $account;
    }

    private function createInstallation(
        Account $account,
        int $id,
        int $active = PluginInstallation::STATE_ACTIVE
    ): PluginInstallation {
        $installation = (new PluginInstallation())
            ->setAccount($account)
            ->setKey('RunalyzePluginPanel_Sportler')
            ->setType(PluginInstallation::TYPE_PANEL)
            ->setActive($active);
        new ReflectionProperty(PluginInstallation::class, 'id')->setValue($installation, $id);

        return $installation;
    }
}

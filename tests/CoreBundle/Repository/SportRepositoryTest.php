<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Profile\Sport\SportProfile;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class SportRepositoryTest extends AbstractRepositoryTestCase
{
    protected SportRepository $SportRepository;

    protected TrainingRepository $TrainingRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->SportRepository = $this->getEntityManager()
            ->getRepository(Sport::class);
        $this->TrainingRepository = $this->getEntityManager()
            ->getRepository(Training::class);
        $this->Account = $this->getDefaultAccount();
    }

    protected function insertActivityForDefaultAccount(
        ?int $timestamp = null,
        float $duration = 3600,
        ?float $distance = null,
        ?Sport $sport = null
    ): Training {
        $activity = $this->getActivityForDefaultAccount($timestamp, $duration, $distance, $sport);

        $this->TrainingRepository->save($activity);

        return $activity;
    }

    public function testEmptyAccount(): void
    {
        $account = $this->getEmptyAccount();

        $this->assertEmpty($this->SportRepository->findAllFor($account));
        $this->assertEmpty($this->SportRepository->findWithDistancesFor($account));
        $this->assertEmpty($this->SportRepository->getUsedInternalSportIdsFor($account));

        $this->assertTrue($this->SportRepository->isInternalTypeFree(SportProfile::RUNNING, $account));
        $this->assertTrue($this->SportRepository->isInternalTypeFree(SportProfile::CYCLING, $account));
        $this->assertTrue($this->SportRepository->isInternalTypeFree(SportProfile::SWIMMING, $account));

        $this->assertNull($this->SportRepository->findRunningFor($account, true));
        $this->assertNull($this->SportRepository->findRunningFor($account)->getId());

        $this->assertNull($this->SportRepository->findThisOrAny(1, $account));
    }

    public function testDefaultAccount(): void
    {
        $account = $this->getDefaultAccount();

        $this->assertFalse($this->SportRepository->isInternalTypeFree(SportProfile::RUNNING, $account));
        $this->assertFalse($this->SportRepository->isInternalTypeFree(SportProfile::CYCLING, $account));
        $this->assertFalse($this->SportRepository->isInternalTypeFree(SportProfile::SWIMMING, $account));

        $this->assertEquals($this->getDefaultAccountsRunningSport()->getId(), $this->SportRepository->findRunningFor($account)->getId());

        $this->assertEquals($this->getDefaultAccountsRunningSport(), $this->SportRepository->findThisOrAny($this->getDefaultAccountsRunningSport()->getId(), $account));
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Sport::class, $this->SportRepository->findThisOrAny(-123, $account));
    }

    public function testSportStatisticsWithoutData(): void
    {
        $statistics = $this->SportRepository->getSportStatisticsSince(null, $this->Account);

        $this->assertSame(0, $statistics->getCount());
        $this->assertEmpty($statistics->getStatistics());
    }

    public function testSportStatisticsWithData(): void
    {
        $this->insertActivityForDefaultAccount(1400000000, 3600, 10.0, $this->getDefaultAccountsRunningSport());
        $this->insertActivityForDefaultAccount(1500000000, 3400, 12.0, $this->getDefaultAccountsRunningSport());
        $this->insertActivityForDefaultAccount(1400000000, 7600, 63.5, $this->getDefaultAccountsCyclingSport());

        $allTimeStatistics = $this->SportRepository->getSportStatisticsSince(null, $this->Account);
        $allTimeStatisticsRunning = $allTimeStatistics->getStatisticFor($this->getDefaultAccountsRunningSport());
        $allTimeStatisticsCycling = $allTimeStatistics->getStatisticFor($this->getDefaultAccountsCyclingSport());

        $this->assertSame(2, $allTimeStatistics->getCount());

        $this->assertSame(7000.0, $allTimeStatisticsRunning->getTotalDuration());
        $this->assertSame(22.0, $allTimeStatisticsRunning->getTotalDistance());
        $this->assertSame(2, $allTimeStatisticsRunning->getNumberOfActivities());

        $this->assertSame(7600.0, $allTimeStatisticsCycling->getTotalDuration());
        $this->assertSame(63.5, $allTimeStatisticsCycling->getTotalDistance());
        $this->assertSame(1, $allTimeStatisticsCycling->getNumberOfActivities());
    }
}

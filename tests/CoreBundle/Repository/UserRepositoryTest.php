<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Repository\ConfRepository;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class UserRepositoryTest extends AbstractRepositoryTestCase
{
    protected UserRepository $UserRepository;

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->UserRepository = $this->EntityManager->getRepository(User::class);
        $this->Account = $this->getDefaultAccount();
    }

    public function testEmptyDatabase(): void
    {
        $this->assertNull($this->UserRepository->getCurrentRestingHeartRate(new Account()));
        $this->assertNull($this->UserRepository->getCurrentMaximalHeartRate(new Account()));
        $this->assertNull($this->UserRepository->getLatestEntryFor(new Account()));
        $this->assertEmpty($this->UserRepository->findAllFor(new Account()));
    }

    public function testFindBodyValueHistoryForAccountWithLimit(): void
    {
        $this->insertBodyValues($this->Account, 100, 65.2, 50, 15.1, 55.2, 40.3);
        $this->insertBodyValues($this->Account, 200, 66.1, 51, 15.2, 55.3, 40.4);
        $this->insertBodyValues($this->Account, 300, 67.0, 52, 15.3, 55.4, 40.5);
        $this->insertBodyValues($this->getEmptyAccount(), 400, 80.0, 60, 20.0, 50.0, 30.0);

        self::assertSame(
            [
                ['time' => 300, 'weight' => 67.0, 'pulseRest' => 52],
                ['time' => 200, 'weight' => 66.1, 'pulseRest' => 51],
            ],
            $this->UserRepository->findWeightHistoryFor($this->Account, 2)
        );

        self::assertSame(
            [
                ['time' => 300, 'fat' => 15.3, 'water' => 55.4, 'muscles' => 40.5],
                ['time' => 200, 'fat' => 15.2, 'water' => 55.3, 'muscles' => 40.4],
            ],
            $this->UserRepository->findBodyCompositionHistoryFor($this->Account, 2)
        );
    }

    public function testFindBodyValueHistorySinceTimestampForAccount(): void
    {
        $this->insertBodyValues($this->Account, 100, 65.2, 50, 15.1, 55.2, 40.3);
        $this->insertBodyValues($this->Account, 200, 66.1, 51, 15.2, 55.3, 40.4);
        $this->insertBodyValues($this->Account, 300, 67.0, 52, 15.3, 55.4, 40.5);
        $this->insertBodyValues($this->getEmptyAccount(), 400, 80.0, 60, 20.0, 50.0, 30.0);

        self::assertSame(
            [
                ['time' => 300, 'weight' => 67.0, 'pulseRest' => 52],
                ['time' => 200, 'weight' => 66.1, 'pulseRest' => 51],
            ],
            $this->UserRepository->findWeightHistorySince($this->Account, 100)
        );

        self::assertSame(
            [
                ['time' => 300, 'fat' => 15.3, 'water' => 55.4, 'muscles' => 40.5],
                ['time' => 200, 'fat' => 15.2, 'water' => 55.3, 'muscles' => 40.4],
            ],
            $this->UserRepository->findBodyCompositionHistorySince($this->Account, 100)
        );
    }

    public function testFindBodyValueHistoryReturnsEmptyForAccountWithoutEntries(): void
    {
        self::assertSame([], $this->UserRepository->findWeightHistoryFor($this->Account, 20));
        self::assertSame([], $this->UserRepository->findWeightHistorySince($this->Account, 0));
        self::assertSame([], $this->UserRepository->findBodyCompositionHistoryFor($this->Account, 20));
        self::assertSame([], $this->UserRepository->findBodyCompositionHistorySince($this->Account, 0));
    }

    private function insertBodyValues(
        Account $account,
        int $timestamp,
        ?float $weight,
        ?int $restingHeartRate,
        ?float $fat,
        ?float $water,
        ?float $muscles
    ): void {
        $user = new User()
            ->setTime($timestamp)
            ->setWeight($weight)
            ->setPulseRest($restingHeartRate)
            ->setFat($fat)
            ->setWater($water)
            ->setMuscles($muscles)
            ->setAccount($account);

        $this->UserRepository->save($user, $account);
    }

    protected function insertDataForDefaultAccount(int $heartRateMax, int $heartRateRest, ?int $timestamp = null): User
    {
        $user = new User()
            ->setPulseMax($heartRateMax)
            ->setPulseRest($heartRateRest)
            ->setTime($timestamp ?: time())
            ->setAccount($this->Account);

        $this->UserRepository->save($user, $this->Account);

        return $user;
    }

    public function testCurrentHeartRateStats(): void
    {
        $this->insertDataForDefaultAccount(197, 48);

        $this->assertSame(197, $this->UserRepository->getCurrentMaximalHeartRate($this->Account));
        $this->assertSame(48, $this->UserRepository->getCurrentRestingHeartRate($this->Account));

        /** @var ConfRepository $confRepository */
        $confRepository = $this->EntityManager->getRepository(Conf::class);
        $this->assertEquals('197', $confRepository->findForAccountAndKey($this->Account, 'HF_MAX')->getValue());
        $this->assertEquals('48', $confRepository->findForAccountAndKey($this->Account, 'HF_REST')->getValue());
    }

    public function testThatZeroesAreIgnored(): void
    {
        $this->insertDataForDefaultAccount(0, 0, time());
        $this->insertDataForDefaultAccount(195, 0, time() - 300);
        $this->insertDataForDefaultAccount(0, 53, time() - 600);
        $this->insertDataForDefaultAccount(200, 60, time() - 900);

        $this->assertSame(195, $this->UserRepository->getCurrentMaximalHeartRate($this->Account));
        $this->assertSame(53, $this->UserRepository->getCurrentRestingHeartRate($this->Account));

        $latestEntry = $this->UserRepository->getLatestEntryFor($this->Account);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\User::class, $latestEntry);
        $this->assertSame(0, $latestEntry->getPulseMax());
        $this->assertSame(0, $latestEntry->getPulseRest());
    }
}

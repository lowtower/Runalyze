<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\UserRepository;

/**
 * @group requiresDoctrine
 */
class UserRepositoryTest extends AbstractRepositoryTestCase
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

    protected function insertDataForDefaultAccount(int $heartRateMax, int $heartRateRest, ?int $timestamp = null): User
    {
        $user = (new User())
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

        $this->assertEquals(197, $this->UserRepository->getCurrentMaximalHeartRate($this->Account));
        $this->assertEquals(48, $this->UserRepository->getCurrentRestingHeartRate($this->Account));

        $this->assertEquals('197', $this->EntityManager->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Conf::class)->findByAccountAndKey($this->Account, 'HF_MAX')->getValue());
        $this->assertEquals('48', $this->EntityManager->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Conf::class)->findByAccountAndKey($this->Account, 'HF_REST')->getValue());
    }

    public function testThatZeroesAreIgnored(): void
    {
        $this->insertDataForDefaultAccount(0, 0, time());
        $this->insertDataForDefaultAccount(195, 0, time() - 300);
        $this->insertDataForDefaultAccount(0, 53, time() - 600);
        $this->insertDataForDefaultAccount(200, 60, time() - 900);

        $this->assertEquals(195, $this->UserRepository->getCurrentMaximalHeartRate($this->Account));
        $this->assertEquals(53, $this->UserRepository->getCurrentRestingHeartRate($this->Account));

        $latestEntry = $this->UserRepository->getLatestEntryFor($this->Account);
        $this->assertEquals(0, $latestEntry->getPulseMax());
        $this->assertEquals(0, $latestEntry->getPulseRest());
    }
}

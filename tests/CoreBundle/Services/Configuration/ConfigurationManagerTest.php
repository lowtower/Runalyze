<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\ConfRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ConfigurationManagerTest extends TestCase
{
    private function setAccountId(Account $account, int $id): void
    {
        $property = new \ReflectionProperty($account, 'id');
        $property->setValue($account, $id);
    }

    private function createLoggedInTokenStorage(Account $user): TokenStorage
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'credentials-not-used-in-these-tests', 'main'));

        return $tokenStorage;
    }

    public function testEmptyList(): void
    {
        /** @var ConfRepository&MockObject $repository */
        $repository = $this->getConfRepositoryMock([]);
        $manager = new ConfigurationManager($repository, new TokenStorage());
        $list = $manager->getList(new Account());

        $this->assertEquals('metric', $list->get('general.DISTANCE_UNIT_SYSTEM'));
    }

    public function testSimpleList(): void
    {
        $existingConf = new Conf();
        $existingConf->setCategory('general');
        $existingConf->setKey('DISTANCE_UNIT_SYSTEM');
        $existingConf->setValue('imperial');

        /** @var ConfRepository&MockObject $repository */
        $repository = $this->getConfRepositoryMock([$existingConf]);
        $manager = new ConfigurationManager($repository, new TokenStorage());
        $list = $manager->getList(new Account());

        $this->assertEquals('imperial', $list->get('general.DISTANCE_UNIT_SYSTEM'));
    }

    public function testGetListWithNullAccountAndNoLoggedInUserNeverQueriesRepository(): void
    {
        /** @var ConfRepository&MockObject $repository */
        $repository = $this->getConfRepositoryMockExpectingCalls([], 0);
        $manager = new ConfigurationManager($repository, new TokenStorage());

        $list = $manager->getList();

        $this->assertEquals('metric', $list->get('general.DISTANCE_UNIT_SYSTEM'));
    }

    public function testGetListForOwnAccountCachesResultAcrossCallsIncludingNullAccount(): void
    {
        $user = new Account();
        $this->setAccountId($user, 5);

        /** @var ConfRepository&MockObject $repository */
        $repository = $this->getConfRepositoryMockExpectingCalls([], 1);
        $manager = new ConfigurationManager($repository, $this->createLoggedInTokenStorage($user));

        $firstCall = $manager->getList($user);
        $secondCall = $manager->getList();

        self::assertSame($firstCall, $secondCall, 'both calls must resolve to the identical cached instance');
    }

    public function testGetListForDifferentAccountIsNeverCachedAndAlwaysQueriesRepository(): void
    {
        $loggedInUser = new Account();
        $this->setAccountId($loggedInUser, 5);

        $otherAccount = new Account();
        $this->setAccountId($otherAccount, 99);

        /** @var ConfRepository&MockObject $repository */
        $repository = $this->getConfRepositoryMockExpectingCalls([], 2);
        $manager = new ConfigurationManager($repository, $this->createLoggedInTokenStorage($loggedInUser));

        $firstCall = $manager->getList($otherAccount);
        $secondCall = $manager->getList($otherAccount);

        self::assertNotSame($firstCall, $secondCall, 'each call for a non-current-user account must construct a fresh instance');
    }

    /**
     * @param Conf[] $existingData
     */
    protected function getConfRepositoryMock(array $existingData = []): ConfRepository
    {
        /** @var ConfRepository&MockObject $repository */
        $repository = $this
            ->getMockBuilder(ConfRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByAccount'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findByAccount')
            ->will($this->returnValue($existingData));

        return $repository;
    }

    /**
     * @param Conf[] $existingData
     */
    protected function getConfRepositoryMockExpectingCalls(array $existingData, int $expectedCallCount): ConfRepository
    {
        /** @var ConfRepository&MockObject $repository */
        $repository = $this
            ->getMockBuilder(ConfRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByAccount'])
            ->getMock();

        $repository
            ->expects($this->exactly($expectedCallCount))
            ->method('findByAccount')
            ->will($this->returnValue($existingData));

        return $repository;
    }
}

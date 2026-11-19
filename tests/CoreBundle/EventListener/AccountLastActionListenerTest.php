<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\EventListener\AccountLastActionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[Group('default')]
final class AccountLastActionListenerTest extends TestCase
{
    public function testIgnoresSubrequests(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->never())->method('getToken');

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->never())->method('flush');

        new AccountLastActionListener($storage, $manager)->onCoreController($this->event(HttpKernelInterface::SUB_REQUEST));
    }

    public function testIgnoresRequestsWithoutAuthentication(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->never())->method('flush');

        new AccountLastActionListener($storage, $manager)->onCoreController($this->event(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testUpdatesStaleLastAction(): void
    {
        $account = new Account();
        $account->setLastAction(time() - 3600);

        $storage = $this->storageFor($account);
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->once())->method('flush');

        new AccountLastActionListener($storage, $manager)->onCoreController($this->event(HttpKernelInterface::MAIN_REQUEST));

        $this->assertGreaterThan(time() - 60, $account->getLastAction());
    }

    public function testKeepsRecentLastAction(): void
    {
        $account = new Account();
        $lastAction = time();
        $account->setLastAction($lastAction);

        $storage = $this->storageFor($account);
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->never())->method('flush');

        new AccountLastActionListener($storage, $manager)->onCoreController($this->event(HttpKernelInterface::MAIN_REQUEST));

        $this->assertSame($lastAction, $account->getLastAction());
    }

    private function storageFor(Account $account): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($account);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        return $storage;
    }

    private function event(int $requestType): ControllerEvent
    {
        return new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            static fn (): null => null,
            new Request(),
            $requestType
        );
    }
}

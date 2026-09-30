<?php

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use LogicException;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\EventListener\SwitchUserListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SwitchUserListenerTest extends TestCase
{
    private function createListener(TokenStorageInterface $tokenStorage): SwitchUserListener
    {
        return new SwitchUserListener(
            $tokenStorage,
            $this->createMock(UserProviderInterface::class),
            $this->createMock(UserCheckerInterface::class),
            'main',
            $this->createMock(AccessDecisionManagerInterface::class)
        );
    }

    private function createGetResponseEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testInvokeThrowsLogicExceptionWhenExitingWithoutAnOriginalToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $currentToken = $this->createMock(TokenInterface::class);
        $tokenStorage->method('getToken')
            ->willReturn($currentToken);

        $listener = $this->createListener($tokenStorage);
        $request = new Request([
            '_switch_user' => '_exit',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Switch User failed/');

        $listener($this->createGetResponseEvent($request));
    }

    public function testInvokeDoesNothingWhenUsernameParameterIsAbsent(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->never())
            ->method('getToken');
        $tokenStorage->expects($this->never())
            ->method('setToken');

        $listener = $this->createListener($tokenStorage);
        $event = $this->createGetResponseEvent(new Request());

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }
}

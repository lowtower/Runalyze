<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Runalyze\Bundle\CoreBundle\EventListener\SwitchUserListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Component\Security\Http\Firewall\FirewallListenerInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class SwitchUserListenerTest extends TestCase
{
    private function createListener(TokenStorageInterface $tokenStorage): SwitchUserListener
    {
        return new SwitchUserListener(
            $tokenStorage,
            $this->createStub(UserProviderInterface::class),
            $this->createStub(UserCheckerInterface::class),
            'main',
            $this->createStub(AccessDecisionManagerInterface::class)
        );
    }

    private function createGetResponseEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testInvokeThrowsLogicExceptionWhenExitingWithoutAnOriginalToken(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $currentToken = $this->createStub(TokenInterface::class);
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

    public function testListenerImplementsTheFirewallListenerInterface(): void
    {
        $listener = $this->createListener($this->createStub(TokenStorageInterface::class));

        $this->assertInstanceOf(FirewallListenerInterface::class, $listener);
    }

    public function testGetPriorityIsStaticAndReturnsAnInteger(): void
    {
        $this->assertTrue(new ReflectionMethod(SwitchUserListener::class, 'getPriority')->isStatic());
        $this->assertIsInt(SwitchUserListener::getPriority());
    }

    public function testGetPriorityMatchesTheDefaultPriorityOfSymfonyFirewallListeners(): void
    {
        $this->assertSame(AbstractListener::getPriority(), SwitchUserListener::getPriority());
    }

    public function testSupportsReturnsFalseWhenUsernameParameterIsAbsent(): void
    {
        $listener = $this->createListener($this->createStub(TokenStorageInterface::class));

        $this->assertFalse($listener->supports(new Request()));
    }

    public function testSupportsReturnsFalseWhenUsernameParameterIsEmpty(): void
    {
        $listener = $this->createListener($this->createStub(TokenStorageInterface::class));

        $this->assertFalse($listener->supports(new Request([
            '_switch_user' => '',
        ])));
    }

    public function testSupportsReturnsTrueWhenUsernameIsGivenInTheQuery(): void
    {
        $listener = $this->createListener($this->createStub(TokenStorageInterface::class));

        $this->assertTrue($listener->supports(new Request([
            '_switch_user' => 'someone',
        ])));
    }

    public function testSupportsReturnsTrueWhenUsernameIsGivenInTheRequestBody(): void
    {
        $listener = $this->createListener($this->createStub(TokenStorageInterface::class));

        $this->assertTrue($listener->supports(new Request([], [
            '_switch_user' => 'someone',
        ])));
    }

    public function testAuthenticateDoesNothingWhenUsernameParameterIsAbsent(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->never())
            ->method('setToken');

        $listener = $this->createListener($tokenStorage);
        $event = $this->createGetResponseEvent(new Request());

        $listener->authenticate($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testAuthenticateThrowsLogicExceptionWhenExitingWithoutAnOriginalToken(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')
            ->willReturn($this->createStub(TokenInterface::class));

        $listener = $this->createListener($tokenStorage);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Switch User failed/');

        $listener->authenticate($this->createGetResponseEvent(new Request([
            '_switch_user' => '_exit',
        ])));
    }
}

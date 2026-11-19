<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\EventListener\UserTimezoneListener;
use Runalyze\Parameter\Application\Timezone;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

#[Group('default')]
final class UserTimezoneListenerTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    public function testAppliesTimezoneOfAuthenticatedAccount(): void
    {
        $account = new Account()->setTimezone(Timezone::UTC);

        $this->listenerFor($account)->onKernelRequest();

        $this->assertSame(Timezone::getFullNameByEnum(Timezone::UTC), date_default_timezone_get());
    }

    public function testIgnoresInvalidTimezone(): void
    {
        $account = new Account()->setTimezone(PHP_INT_MAX);

        $this->listenerFor($account)->onKernelRequest();

        $this->assertSame('Europe/Berlin', date_default_timezone_get());
    }

    public function testIgnoresUnauthenticatedRequest(): void
    {
        $storage = $this->createStub(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn(null);

        new UserTimezoneListener($storage)->onKernelRequest();

        $this->assertSame('Europe/Berlin', date_default_timezone_get());
    }

    public function testIgnoresAuthenticatedUserWithoutAccount(): void
    {
        $this->listenerFor(new InMemoryUser('test', 'password'))->onKernelRequest();

        $this->assertSame('Europe/Berlin', date_default_timezone_get());
    }

    private function listenerFor(UserInterface $user): UserTimezoneListener
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = $this->createStub(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return new UserTimezoneListener($storage);
    }
}

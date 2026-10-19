<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\ArgumentResolver\AccountValueResolver;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AccountValueResolverTest extends TestCase
{
    private TokenStorage $tokenStorage;

    private AccountValueResolver $resolver;

    protected function setUp(): void
    {
        $this->tokenStorage = new TokenStorage();
        $this->resolver = new AccountValueResolver($this->tokenStorage);
    }

    private function createArgument(?string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('account', $type, false, false, null);
    }

    /**
     * @return array<int, mixed>
     */
    private function resolve(?string $type): array
    {
        return [...$this->resolver->resolve(new Request(), $this->createArgument($type))];
    }

    public function testResolvesLoggedInAccountForAccountArgument(): void
    {
        $account = new Account();
        $account->setUsername('johndoe');

        $this->tokenStorage->setToken(new UsernamePasswordToken($account, 'default', ['ROLE_USER']));

        $this->assertSame([$account], $this->resolve(Account::class));
    }

    public function testResolvesNothingWhenNobodyIsLoggedIn(): void
    {
        $this->assertSame([], $this->resolve(Account::class));
    }

    public function testResolvesNothingWhenLoggedInUserIsNoAccount(): void
    {
        $this->tokenStorage->setToken(
            new UsernamePasswordToken(new InMemoryUser('memory-user', 'password'), 'default', ['ROLE_USER'])
        );

        $this->assertSame([], $this->resolve(Account::class));
    }

    public function testResolvesNothingForArgumentsOfOtherTypes(): void
    {
        $account = new Account();
        $account->setUsername('johndoe');

        $this->tokenStorage->setToken(new UsernamePasswordToken($account, 'default', ['ROLE_USER']));

        $this->assertSame([], $this->resolve(stdClass::class));
    }

    public function testResolvesNothingForArgumentsWithoutType(): void
    {
        $account = new Account();
        $account->setUsername('johndoe');

        $this->tokenStorage->setToken(new UsernamePasswordToken($account, 'default', ['ROLE_USER']));

        $this->assertSame([], $this->resolve(null));
    }
}

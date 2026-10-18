<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;

#[Group('default')]
class AccountCheckerTest extends TestCase
{
    private AccountChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new AccountChecker();
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCheckPreAuthAllowsAccountWithoutActivationHash(): void
    {
        $account = new Account();

        $this->checker->checkPreAuth($account);
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCheckPreAuthAllowsAccountWithClearedActivationHash(): void
    {
        $account = new Account();
        $account->setNewActivationHash();
        $account->removeActivationHash();

        $this->checker->checkPreAuth($account);
    }

    public function testCheckPreAuthThrowsDisabledExceptionForAccountWithActivationHash(): void
    {
        $account = new Account();
        $account->setNewActivationHash();

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessageIsOrContains('Your account has not been activated yet.');

        $this->checker->checkPreAuth($account);
    }

    public function testCheckPreAuthDoesNothingForNonAccountUserImplementations(): void
    {
        $user = $this->createStub(UserInterface::class);

        $this->checker->checkPreAuth($user);

        $this->addToAssertionCount(1); // no exception was thrown
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCheckPostAuthIsANoOpForEnabledAccount(): void
    {
        $account = new Account();

        $this->checker->checkPostAuth($account);
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCheckPostAuthIsANoOpEvenForAccountWithActivationHash(): void
    {
        $account = new Account();
        $account->setNewActivationHash();

        $this->checker->checkPostAuth($account);
    }
}

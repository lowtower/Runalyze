<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class PluginControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();

        ob_start();
    }

    protected function tearDown(): void
    {
        gc_collect_cycles();

        ob_end_clean();

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('plugin_test_' . uniqid('', true))
            ->setMail('plugin_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function request(string $method, string $uri): void
    {
        $this->Client->request($method, $uri);
    }

    public function testPluginInstallActionRequiresAuthentication(): void
    {
        $this->request('GET', '/call/call.Plugin.install.php?key=SomethingIrrelevant');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginInstallActionRejectsAnUnrecognizedKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.install.php?key=ThisPluginDirectoryDoesNotExist');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testPluginInstallActionRejectsAMissingKey(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.install.php');

        $this->assertStatusCode(404, $this->Client);
    }

    public function testPluginConfigActionRequiresAuthentication(): void
    {
        $this->request('GET', '/call/call.Plugin.config.php');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginConfigActionShowsErrorMessageWhenIdIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Something went wrong', (string) $this->Client->getResponse()->getContent());
    }

    public function testPluginConfigActionShowsErrorMessageWhenIdIsNotNumeric(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/call/call.Plugin.config.php?id=not-a-number');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('Something went wrong', (string) $this->Client->getResponse()->getContent());
    }

    public function testPluginDisplayActionRequiresAuthentication(): void
    {
        $this->request('GET', '/my/plugin/999999999');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPluginDisplayActionShowsNotFoundMessageForAnUnknownPluginId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/my/plugin/999999999');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('could not be found', (string) $this->Client->getResponse()->getContent());
    }
}

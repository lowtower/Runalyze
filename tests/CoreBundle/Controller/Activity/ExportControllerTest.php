<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class ExportControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('export_test_' . uniqid('', true))
            ->setMail('export_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    public function testExporterExportActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/activity/1/export/social/1');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testFileExportActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/activity/1/export/file/1');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testExporterExportActionReturnsEmptyResponseForAnInvalidTypeId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/1/export/social/0');

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('', $this->Client->getResponse()->getContent());
        $this->assertStringNotContainsString(
            'Failed to start the session',
            $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testFileExportActionReturnsEmptyResponseForAnInvalidTypeId(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/1/export/file/0');

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame('', $this->Client->getResponse()->getContent());
        $this->assertStringNotContainsString(
            'Failed to start the session',
            $this->Client->getResponse()
                ->getContent()
        );
    }
}

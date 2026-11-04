<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\My;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class SearchControllerTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('search_test_' . uniqid('', true))
            ->setMail('search_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    public function testSearchActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/my/search');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testSearchActionRendersTheSearchFormForAuthenticatedUser(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/my/search');

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()
            ->getContent();

        $this->assertStringContainsString('Search for activities', $content);
        $this->assertStringContainsString('id="search"', $content);
        $this->assertStringContainsString('id="searchResult"', $content);
    }

    public function testSearchActionDisplaysOnlyResultsAfterSubmittingTheForm(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/my/search', [
            'resultsPerPage' => 10,
        ]);

        $this->assertStatusCode(200, $this->Client);

        $content = (string) $this->Client->getResponse()
            ->getContent();

        $this->assertStringContainsString('id="searchResult"', $content);
        $this->assertStringNotContainsString('panel-heading', $content);
        $this->assertStringNotContainsString('id="search"', $content);
    }
}

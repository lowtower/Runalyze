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
final class RequestIsolationTest extends AbstractFixturesAwareWebTestCase
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
            ->setUsername('isolation_test_' . uniqid('', true))
            ->setMail('isolation_test_' . uniqid('', true) . '@example.test');

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
        ob_start();
        $this->Client->request($method, $uri);
        ob_end_clean();
    }

    public function testArrayValueLeftBehindBySearchActionDoesNotLeakIntoAPlotRequest(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->request('GET', '/my/search?get=true&sportid[]=1&sportid[]=2');

        $this->assertIsArray(
            $_POST['sportid'] ?? null,
            'Test setup is invalid: expected the legacy search action to have left an array value in $_POST["sportid"].'
        );

        $this->request('GET', '/call/window.plotSumData.php');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'TypeError',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }

    public function testArrayValueLeftBehindBySearchActionDoesNotLeakIntoASharedAthletePlotRequest(): void
    {
        $this->request('GET', '/my/search?get=true&sportid[]=1&sportid[]=2');

        $this->assertIsArray(
            $_POST['sportid'] ?? null,
            'Test setup is invalid: expected the legacy search action to have left an array value in $_POST["sportid"].'
        );

        $this->request('GET', '/athlete/' . $this->Account->getUsername() . '?type=month');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringNotContainsString(
            'TypeError',
            (string) $this->Client->getResponse()
                ->getContent()
        );
    }
}

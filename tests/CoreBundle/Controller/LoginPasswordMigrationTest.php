<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class LoginPasswordMigrationTest extends AbstractFixturesAwareWebTestCase
{
    private const string LEGACY_PASSWORD = 'legacy-hash-migration-test-password';

    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerAccountWithLegacyHash();
    }

    /**
     * Simulates a pre-argon2id account: the password is stored as sha256(password . salt)
     * together with the per-account salt, exactly as the former Sha256SaltedEncoder did.
     */
    private function registerAccountWithLegacyHash(): Account
    {
        $account = new Account()
            ->setUsername('legacy_hash_' . uniqid('', true))
            ->setMail('legacy_hash_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordHasher);
        $registration->setAccount($account);

        $registeredAccount = $registration->registerAccount();

        $legacyHasher = new Sha256SaltedEncoder();
        $account->setPassword($legacyHasher->hash(self::LEGACY_PASSWORD, $account->getSalt()));

        $this->getEntityManager()
            ->flush();

        return $registeredAccount;
    }

    private function reloadAccount(int $id): ?Account
    {
        $this->getEntityManager()
            ->clear();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($id);
    }

    private function submitLoginForm(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', '/en/login');

        $form = $crawler->filter('form')
            ->form([
                '_username' => $this->Account->getUsername(),
                '_password' => self::LEGACY_PASSWORD,
            ]);

        $client->submit($form);
    }

    public function testLoginWithLegacySha256SaltedHashMigratesStoredHash(): void
    {
        $legacyHash = $this->Account->getPassword();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $legacyHash);

        $this->submitLoginForm($this->Client);

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));

        $migratedAccount = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(Account::class, $migratedAccount);

        $migratedHash = $migratedAccount->getPassword();
        $this->assertNotSame($legacyHash, $migratedHash);
        $this->assertTrue(
            str_starts_with($migratedHash, '$argon2id$') || str_starts_with($migratedHash, '$2y$'),
            sprintf('Expected the migrated hash to be argon2id (or bcrypt), got "%s".', $migratedHash)
        );
    }

    public function testSecondLoginWithMigratedHashSucceeds(): void
    {
        $this->submitLoginForm($this->Client);

        $this->assertStatusCode(302, $this->Client);
        $this->assertSame('http://localhost/', $this->Client->getResponse()->headers->get('Location'));

        $migratedAccount = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(Account::class, $migratedAccount);

        $migratedHash = $migratedAccount->getPassword();

        $client = $this->makeClient();
        $this->submitLoginForm($client);

        $this->assertStatusCode(302, $client);
        $this->assertSame('http://localhost/', $client->getResponse()->headers->get('Location'));

        // no further rehash churn: the migrated hash must be stable on the second login
        $accountAfterSecondLogin = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(Account::class, $accountAfterSecondLogin);
        $this->assertSame($migratedHash, $accountAfterSecondLogin->getPassword());
    }
}

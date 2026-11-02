<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use RuntimeException;
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
     *
     * @throws RuntimeException if the account could not be reloaded after registration
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
        $registration->registerAccount();

        // Registration::registerAccount() ends with em->clear(), so the account object
        // is detached from the entity manager: it has to be re-fetched before the stored
        // password can be replaced with a legacy hash.
        $managedAccount = $this->getEntityManager()->find(Account::class, $account->getId());

        if (! $managedAccount instanceof Account) {
            throw new RuntimeException('The account could not be reloaded after registration.');
        }

        $legacyHasher = new Sha256SaltedEncoder();
        $managedAccount->setPassword($legacyHasher->hash(self::LEGACY_PASSWORD, $managedAccount->getSalt()));

        $this->getEntityManager()
            ->flush();

        return $managedAccount;
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
        $legacyAccount = $this->reloadAccount($this->Account->getId());
        $this->assertInstanceOf(Account::class, $legacyAccount);

        // assert against the persisted hash: the database, not the in-memory object,
        // decides whether the login can succeed
        $legacyHash = $legacyAccount->getPassword();
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

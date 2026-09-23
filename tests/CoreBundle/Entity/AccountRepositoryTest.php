<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class AccountRepositoryTest extends AbstractRepositoryTestCase
{
    protected AccountRepository $AccountRepository;

    protected function setUp(): void
    {
        $this->FixtureClasses = [];

        parent::setUp();

        $this->AccountRepository = $this->getEntityManager()
            ->getRepository(Account::class);
    }

    protected function getNewAccount(string $username, string $mail = ''): Account
    {
        if ($mail === '') {
            $mail = $username . '@test.com';
        }

        return (new Account())
            ->setUsername($username)
            ->setMail($mail)
            ->setPassword('');
    }

    protected function persistAccounts(Account ...$accounts): void
    {
        foreach ($accounts as $account) {
            $this->EntityManager->persist($account);
        }

        $this->EntityManager->flush();
    }

    public function testLoadingUnknownUser(): void
    {
        $this->assertNull($this->AccountRepository->loadUserByUsername('foobar'));
    }

    public function testLoadingSimpleUser(): void
    {
        $this->persistAccounts($this->getNewAccount('foobar', 'foo@bar.com'));

        $accountInRepository = $this->AccountRepository->loadUserByUsername('foobar');

        $this->assertInstanceOf(Account::class, $accountInRepository);
        $this->assertEquals('foobar', $accountInRepository->getUsername());
        $this->assertEquals($accountInRepository, $this->AccountRepository->loadUserByUsername('foo@bar.com'));
    }

    public function testFindingByUsernameDoesNotMatchByMail(): void
    {
        $account = $this->getNewAccount('uniqueuser', 'unique-mail@test.com');
        $this->persistAccounts($account);

        $found = $this->AccountRepository->findByUsername('uniqueuser');
        $this->assertInstanceOf(Account::class, $found);
        $this->assertEquals($account->getId(), $found->getId());

        $this->assertNull($this->AccountRepository->findByUsername('unknownuser'));

        $this->assertNull($this->AccountRepository->findByUsername('unique-mail@test.com'));
    }

    public function testExistsOneWith(): void
    {
        $this->persistAccounts($this->getNewAccount('existsuser'));

        $this->assertTrue($this->AccountRepository->existsOneWith([
            'username' => 'existsuser',
        ]));
        $this->assertFalse($this->AccountRepository->existsOneWith([
            'username' => 'doesnotexist',
        ]));
    }

    public function testNumberOfActivatedUsers(): void
    {
        $activeAccounts = $this->AccountRepository->getAmountOfActivatedUsers(false);
        $activatedAccountNames = ['foo', 'bar', 'baz'];

        $accountsToPersist = array_map(
            fn (string $name): Account => $this->getNewAccount($name),
            $activatedAccountNames
        );
        $accountsToPersist[] = $this->getNewAccount('foobar')->setActivationHash(bin2hex(random_bytes(16)));

        $this->persistAccounts(...$accountsToPersist);

        $this->assertEquals($activeAccounts + 3, $this->AccountRepository->getAmountOfActivatedUsers(false));
    }

    public function testDeletingNonActivatedUsers(): void
    {
        $notActivatedAccountNames = ['foo', 'bar', 'baz'];
        $accountsToPersist = array_map(
            fn (string $name): Account => $this->getNewAccount($name)
                ->setActivationHash(bin2hex(random_bytes(16)))
                ->setRegisterdate(strtotime('today -30 days')),
            $notActivatedAccountNames
        );

        $accountsToPersist[] = $this->getNewAccount('foobar', 'foobar@test.com');
        $accountsToPersist[] = $this->getNewAccount('todays_user')
            ->setActivationHash(bin2hex(random_bytes(16)))
            ->setRegisterdate(time());

        $this->persistAccounts(...$accountsToPersist);

        $this->assertEquals(3, count($this->AccountRepository->deleteNotActivatedAccounts(7)));
        $this->assertNotNull($this->AccountRepository->loadUserByUsername('foobar'));
        $this->assertNotNull($this->AccountRepository->loadUserByUsername('todays_user'));
    }

    public function testDeletingByHash(): void
    {
        $deletionHash = bin2hex(random_bytes(16));
        $this->persistAccounts(
            $this->getNewAccount('foo')
                ->setDeletionHash($deletionHash),
            $this->getNewAccount('bar')
        );

        $this->assertFalse($this->AccountRepository->deleteByHash('unknownHash'));
        $this->assertTrue($this->AccountRepository->deleteByHash($deletionHash));
        $this->assertNull($this->AccountRepository->loadUserByUsername('foo'));
        $this->assertNotNull($this->AccountRepository->loadUserByUsername('bar'));
    }

    public function testActivatingByHash(): void
    {
        $fooAccount = $this->getNewAccount('foo')
            ->setNewActivationHash();
        $activationHash = $fooAccount->getActivationHash();

        $this->AccountRepository->save($fooAccount);

        $this->assertFalse($this->AccountRepository->activateByHash('unknownHash'));
        $this->assertTrue($this->AccountRepository->activateByHash($activationHash));
        $this->assertNull($fooAccount->getActivationHash());

        $this->getEntityManager()
            ->clear();
        $reloaded = $this->AccountRepository->find($fooAccount->getId());
        $this->assertNotNull($reloaded);
        $this->assertNull($reloaded->getActivationHash());
    }

    public function testFindingByLanguage(): void
    {
        $english1 = $this->getNewAccount('en1')
            ->setLanguage('en');
        $english2 = $this->getNewAccount('en2')
            ->setLanguage('en');
        $german = $this->getNewAccount('de')
            ->setLanguage('de');
        $swedish = $this->getNewAccount('sv')
            ->setLanguage('sv');

        $this->persistAccounts($english1, $english2, $german, $swedish);

        $this->assertEqualsCanonicalizing(
            [$english1->getId(), $english2->getId()],
            $this->AccountRepository->findAllByLanguage('en')
        );
        $this->assertEqualsCanonicalizing(
            [$english1->getId(), $english2->getId(), $german->getId()],
            $this->AccountRepository->findAllByLanguage(['en', 'de'])
        );
        $this->assertEqualsCanonicalizing(
            [$swedish->getId()],
            $this->AccountRepository->findAllByLanguage(['en', 'de'], true)
        );
        $this->assertEqualsCanonicalizing(
            [$german->getId(), $swedish->getId()],
            $this->AccountRepository->findAllByLanguage('en', true)
        );
    }

    public function testFindingByLanguageWithEmptyArrayReturnsAllAccounts(): void
    {
        $english = $this->getNewAccount('en-all')
            ->setLanguage('en');
        $german = $this->getNewAccount('de-all')
            ->setLanguage('de');

        $this->persistAccounts($english, $german);

        $this->assertEqualsCanonicalizing(
            [$english->getId(), $german->getId()],
            $this->AccountRepository->findAllByLanguage([])
        );
    }
}

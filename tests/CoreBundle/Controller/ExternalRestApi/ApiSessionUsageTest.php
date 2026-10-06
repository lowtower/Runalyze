<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\ExternalRestApi;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * The API is stateless and is called by scripts without cookies.
 * Nothing on the way to the controller (listeners, firewall, import) may start a session for such a caller.
 */
#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class ApiSessionUsageTest extends AbstractFixturesAwareWebTestCase
{
    private const string IMPORT_URL = '/api/import/activity';

    private const string FIXTURE_FIT_FILE = 'fit/FR265-with-self-evaluation.fit';

    private const string PASSWORD = 'Session-Test-Password-1';

    protected Account $Account;

    private ?string $temporaryUploadCopy = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    protected function tearDown(): void
    {
        if ($this->temporaryUploadCopy !== null) {
            (new Filesystem())->remove($this->temporaryUploadCopy);
        }

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = (new Account())
            ->setUsername('session_test_' . uniqid('', true))
            ->setMail('session_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordHasher);
        $account->setPassword($passwordHasher->hashPassword($account, self::PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function createUploadedFitFile(): UploadedFile
    {
        $this->temporaryUploadCopy = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'session_test_' . uniqid('', true) . '.fit';

        (new Filesystem())->copy(TESTS_ROOT . '/testfiles/' . self::FIXTURE_FIT_FILE, $this->temporaryUploadCopy, true);

        return new UploadedFile($this->temporaryUploadCopy, 'session_test_' . uniqid('', true) . '.fit', 'application/octet-stream', null, true);
    }

    private function nameOfSessionCookie(): string
    {
        $sessionFactory = static::getContainer()->get('session.factory');
        self::assertInstanceOf(SessionFactoryInterface::class, $sessionFactory);

        return $sessionFactory->createSession()
            ->getName();
    }

    /**
     * @param UploadedFile[] $files
     */
    private function requestImport(array $files, ?string $password): void
    {
        $server = $password === null ? [] : [
            'PHP_AUTH_USER' => $this->Account->getUsername(),
            'PHP_AUTH_PW' => $password,
        ];

        $this->Client->request('POST', self::IMPORT_URL, [], $files, $server);
    }

    private function assertResponseSetsNoSessionCookie(): void
    {
        $cookieNames = array_map(
            static fn (Cookie $cookie): string => $cookie->getName(),
            $this->Client->getResponse()
                ->headers->getCookies()
        );

        $this->assertNotContains(
            $this->nameOfSessionCookie(),
            $cookieNames,
            'The stateless API must not start a session. Cookies set by the response: ' . implode(', ', $cookieNames)
        );
    }

    #[DataProvider('requestsWithoutFiles')]
    public function testRequestWithoutFilesDoesNotSetASessionCookie(?string $password, int $expectedStatus): void
    {
        $this->requestImport([], $password);

        $this->assertStatusCode($expectedStatus, $this->Client);
        $this->assertResponseSetsNoSessionCookie();
    }

    public static function requestsWithoutFiles(): iterable
    {
        yield 'without credentials' => [null, 401];
        yield 'with wrong password' => ['wrong-password', 401];
        yield 'with valid credentials' => [self::PASSWORD, 200];
    }

    #[\PHPUnit\Framework\Attributes\Group('import')]
    public function testImportOfAFileDoesNotSetASessionCookie(): void
    {
        $this->requestImport([
            'file1' => $this->createUploadedFitFile(),
        ], self::PASSWORD);

        $this->assertStatusCode(200, $this->Client);
        $this->assertResponseSetsNoSessionCookie();
    }
}

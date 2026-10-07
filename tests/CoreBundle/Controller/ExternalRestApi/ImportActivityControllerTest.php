<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\ExternalRestApi;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('import')]
class ImportActivityControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const string FIXTURE_FIT_FILE = 'fit/FR265-with-self-evaluation.fit';

    private const string IMPORT_URL = '/api/import/activity';

    private const string PASSWORD = 'Import-Test-Password-1';

    protected Account $Account;

    /**
     * @var string[]
     */
    private array $temporaryUploadCopies = [];

    private string|false $previousShellVerbosity = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();

        foreach ($this->temporaryUploadCopies as $path) {
            $filesystem->remove($path);
        }

        if ($this->previousShellVerbosity !== false) {
            putenv('SHELL_VERBOSITY=' . $this->previousShellVerbosity);
            $this->previousShellVerbosity = false;
        }

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('import_test_' . uniqid('', true))
            ->setMail('import_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $account, $passwordHasher);
        $account->setPassword($passwordHasher->hashPassword($account, self::PASSWORD));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function makeUploadedFitFile(?string $clientFileName = null): UploadedFile
    {
        $fixtureFitFile = TESTS_ROOT . '/testfiles/' . self::FIXTURE_FIT_FILE;

        $temporaryPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'import_test_' . uniqid('', true) . '.fit';

        new Filesystem()->copy($fixtureFitFile, $temporaryPath, true);

        $this->temporaryUploadCopies[] = $temporaryPath;

        return new UploadedFile(
            $temporaryPath,
            $clientFileName ?? 'import_test_' . uniqid('', true) . '.fit',
            'application/octet-stream',
            null,
            true
        );
    }

    private function poisonShellVerbosityAsQuiet(): void
    {
        $this->previousShellVerbosity = getenv('SHELL_VERBOSITY');
        putenv('SHELL_VERBOSITY=-1');
    }

    /**
     * @param UploadedFile[] $files
     * @param string|null $password null sends no credentials at all
     */
    private function requestImport(array $files = [], ?string $password = self::PASSWORD): void
    {
        $server = $password === null ? [] : [
            'PHP_AUTH_USER' => $this->Account->getUsername(),
            'PHP_AUTH_PW' => $password,
        ];

        $this->Client->request('POST', self::IMPORT_URL, [], $files, $server);
    }

    private function requestImportOfTwoFilesWithTheSameName(): void
    {
        $this->requestImport([
            'file1' => $this->makeUploadedFitFile('same-name.fit'),
            'file2' => $this->makeUploadedFitFile('same-name.fit'),
        ]);
    }

    /**
     * @return string[]
     */
    private function findTemporaryUploadDirectoriesOfAccount(): array
    {
        return glob(sys_get_temp_dir() . '/' . $this->Account->getUsername() . '_*') ?: [];
    }

    public function testPostImportActivityRespondsWithUnauthorizedWithoutCredentials(): void
    {
        $this->requestImport(password: null);

        $this->assertStatusCode(401, $this->Client);
    }

    public function testPostImportActivityRespondsWithUnauthorizedForWrongPassword(): void
    {
        $this->requestImport([
            'file1' => $this->makeUploadedFitFile(),
        ], 'wrong-password');

        $this->assertStatusCode(401, $this->Client);
    }

    public function testPostImportActivitySuccessfullyImportsAValidFitFile(): void
    {
        $this->requestImport([
            'file1' => $this->makeUploadedFitFile(),
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('successfully imported', $this->Client->getResponse()->getContent());
    }

    public function testPostImportActivityLeavesNoTemporaryUploadDirectoryBehindAfterSuccess(): void
    {
        $this->requestImport([
            'file1' => $this->makeUploadedFitFile(),
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertSame([], $this->findTemporaryUploadDirectoriesOfAccount());
    }

    public function testPostImportActivitySucceedsEvenWhenShellVerbosityWasLeftQuietByAnEarlierCommand(): void
    {
        $this->poisonShellVerbosityAsQuiet();

        $this->requestImport([
            'file1' => $this->makeUploadedFitFile(),
        ]);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('successfully imported', $this->Client->getResponse()->getContent());
    }

    public function testPostImportActivityReturns202WhenTheSameFileIsImportedTwice(): void
    {
        $sharedClientFileName = 'shared-within-this-test-' . uniqid('', true) . '.fit';

        $this->requestImport([
            'file1' => $this->makeUploadedFitFile($sharedClientFileName),
        ]);

        $this->assertStatusCode(200, $this->Client);

        $this->requestImport([
            'file1' => $this->makeUploadedFitFile($sharedClientFileName),
        ]);

        $this->assertStatusCode(202, $this->Client);
        $this->assertStringContainsString('is a duplicate', $this->Client->getResponse()->getContent());
        $this->assertStringContainsString('NOT imported successfully', $this->Client->getResponse()->getContent());
    }

    public function testPostImportActivityReturns500ForTwoFilesWithTheSameClientFileName(): void
    {
        $this->requestImportOfTwoFilesWithTheSameName();

        $this->assertStatusCode(500, $this->Client);
        $this->assertStringContainsString('same-name.fit', $this->Client->getResponse()->getContent());
    }

    public function testPostImportActivityDoesNotExposeStackTraceInErrorResponse(): void
    {
        $this->requestImportOfTwoFilesWithTheSameName();

        $this->assertStatusCode(500, $this->Client);
        $this->assertStringNotContainsString('#0', $this->Client->getResponse()->getContent());
        $this->assertStringNotContainsString('.php', $this->Client->getResponse()->getContent());
    }

    public function testPostImportActivityRemovesTemporaryUploadDirectoryAfterError(): void
    {
        $this->requestImportOfTwoFilesWithTheSameName();

        $this->assertStatusCode(500, $this->Client);
        $this->assertSame([], $this->findTemporaryUploadDirectoriesOfAccount());
    }

    public function testPostImportActivityReturns200ForAnEmptyRequestWithNoFiles(): void
    {
        $this->requestImport();

        $this->assertStatusCode(200, $this->Client);
    }
}

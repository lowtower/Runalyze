<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\ExternalRestApi;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('import')]
class ImportActivityControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const FIXTURE_FIT_FILE = 'fit/FR265-with-self-evaluation.fit';

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
        $account = (new Account())
            ->setUsername('import_test_' . uniqid('', true))
            ->setMail('import_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_encoder.generic');

        $registration = new Registration($this->getEntityManager(), $account, $passwordEncoder);
        $account->setPassword($passwordEncoder->encodePassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function makeUploadedFitFile(?string $clientFileName = null): UploadedFile
    {
        $fixtureFitFile = TESTS_ROOT . '/testfiles/' . self::FIXTURE_FIT_FILE;

        $temporaryPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'import_test_' . uniqid('', true) . '.fit';

        (new Filesystem())->copy($fixtureFitFile, $temporaryPath, true);

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

    public function testPostImportActivityRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/api/import/activity');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testPostImportActivitySuccessfullyImportsAValidFitFile(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/api/import/activity', [], [
            'file1' => $this->makeUploadedFitFile(),
        ]);

        $response = $this->Client->getResponse();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('successfully imported', $response->getContent());
    }

    public function testPostImportActivitySucceedsEvenWhenShellVerbosityWasLeftQuietByAnEarlierCommand(): void
    {
        $this->poisonShellVerbosityAsQuiet();

        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/api/import/activity', [], [
            'file1' => $this->makeUploadedFitFile(),
        ]);

        $response = $this->Client->getResponse();

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('successfully imported', $response->getContent());
    }

    public function testPostImportActivityReturns202WhenTheSameFileIsImportedTwice(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $sharedClientFileName = 'shared-within-this-test-' . uniqid('', true) . '.fit';

        $this->Client->request('POST', '/api/import/activity', [], [
            'file1' => $this->makeUploadedFitFile($sharedClientFileName),
        ]);

        $this->assertStatusCode(200, $this->Client);

        $this->Client->request('POST', '/api/import/activity', [], [
            'file1' => $this->makeUploadedFitFile($sharedClientFileName),
        ]);

        $response = $this->Client->getResponse();

        $this->assertStatusCode(202, $this->Client);
        $this->assertStringContainsString('is a duplicate', $response->getContent());
        $this->assertStringContainsString('NOT imported successfully', $response->getContent());
    }

    public function testPostImportActivityReturns500ForTwoFilesWithTheSameClientFileName(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/api/import/activity', [], [
            'file1' => $this->makeUploadedFitFile('same-name.fit'),
            'file2' => $this->makeUploadedFitFile('same-name.fit'),
        ]);

        $response = $this->Client->getResponse();

        $this->assertStatusCode(500, $this->Client);
        $this->assertStringContainsString('same-name.fit', $response->getContent());
    }

    public function testPostImportActivityReturns500ForAnEmptyRequestWithNoFiles(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/api/import/activity');

        $this->assertStatusCode(200, $this->Client);
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('import')]
final class CreateControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const string FIXTURE_FIT_FILE = 'fit/FR265-with-self-evaluation.fit';

    protected Account $Account;

    /**
     * @var string[]
     */
    private array $temporaryImportCopies = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->registerUniqueAccount();
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();

        foreach ($this->temporaryImportCopies as $path) {
            if ($filesystem->exists($path)) {
                $filesystem->remove($path);
            }
        }

        parent::tearDown();
    }

    private function registerUniqueAccount(): Account
    {
        $account = new Account()
            ->setUsername('create_test_' . uniqid('', true))
            ->setMail('create_test_' . uniqid('', true) . '@example.test');

        /** @var UserPasswordHasherInterface $passwordEncoder */
        $passwordEncoder = self::getContainer()->get('security.user_password_hasher');

        $registration = new Registration($this->getEntityManager(), $passwordEncoder);
        $registration->setAccount($account);

        $account->setPassword($passwordEncoder->hashPassword($account, 'irrelevant-for-this-test'));
        $registeredAccount = $registration->registerAccount();

        return $this->getEntityManager()
            ->getRepository(Account::class)->find($registeredAccount->getId());
    }

    private function setTrainingCreateMode(string $mode): void
    {
        $this->getEntityManager()
            ->getRepository(Conf::class)
            ->updateOrInsert($this->Account, 'activity-form', 'TRAINING_CREATE_MODE', $mode);
    }

    private function importDirectory(): string
    {
        return self::getContainer()->getParameter('app.data_directory') . '/import/';
    }

    private function copyFixtureIntoImportDirectory(string $clientFileName): string
    {
        $fixtureFitFile = TESTS_ROOT . '/testfiles/' . self::FIXTURE_FIT_FILE;
        $targetPath = $this->importDirectory() . $clientFileName;

        new Filesystem()->copy($fixtureFitFile, $targetPath, true);

        $this->temporaryImportCopies[] = $targetPath;

        return $clientFileName;
    }

    private function createUnsupportedDummyFileInImportDirectory(string $clientFileName): string
    {
        $targetPath = $this->importDirectory() . $clientFileName;

        file_put_contents($targetPath, 'this file has an extension no parser is registered for');

        $this->temporaryImportCopies[] = $targetPath;

        return $clientFileName;
    }

    private function responseContent(): string
    {
        return (string) $this->Client->getResponse()
            ->getContent();
    }

    public function testCreateActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/activity/add');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testCreateActionRendersUploadFormByDefault(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/add');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="upload-container"', $this->responseContent());
        $this->assertStringContainsString('FineUploaderBasic', $this->responseContent());
    }

    public function testCreateActionForwardsToCommunicatorActionWhenModeIsGarmin(): void
    {
        $this->setTrainingCreateMode('garmin');
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/add');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testCreateActionForwardsToNewActionWhenModeIsForm(): void
    {
        $this->setTrainingCreateMode('form');
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/add');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="activity"', $this->responseContent());
        $this->assertStringContainsString('Runalyze.ActivityForm.init', $this->responseContent());
    }

    public function testCommunicatorActionIsReachableDirectly(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/communicator');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testCommunicatorIFrameActionIsReachableDirectly(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/communicator/iframe');

        $this->assertStatusCode(200, $this->Client);
    }

    public function testUploadActionWithoutFileOrFilesParameterShowsTheUploadForm(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/upload');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="upload-container"', $this->responseContent());
        $this->assertStringContainsString('*.fit', $this->responseContent());
        $this->assertStringContainsString('*.gpx', $this->responseContent());
        $this->assertStringContainsString('*.zip', $this->responseContent());
    }

    public function testUploadActionImportsASingleValidFitFileAndRendersTheActivityForm(): void
    {
        $fileName = $this->copyFixtureIntoImportDirectory('create_test_' . uniqid('', true) . '.fit');
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/upload?file=' . $fileName);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="activity"', $this->responseContent());
        $this->assertStringContainsString('Garmin Communicator', $this->responseContent());
        $this->assertStringContainsString('>Form</a>', $this->responseContent());
        $this->assertStringContainsString('isNew: true', $this->responseContent());
        $this->assertStringContainsString('id="fieldset-sports"', $this->responseContent());
        $this->assertStringContainsString('id="fieldset-equipment"', $this->responseContent());
    }

    public function testUploadActionWithMultipleFilesFindsTheSuccessfulActivityEvenWhenAnEarlierFileFailedToParse(): void
    {
        $failingFileName = $this->createUnsupportedDummyFileInImportDirectory('unsupported_' . uniqid('', true) . '.unsupportedext');
        $succeedingFileName = $this->copyFixtureIntoImportDirectory('create_test_' . uniqid('', true) . '.fit');
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('GET', '/activity/upload?files=' . $failingFileName . ';' . $succeedingFileName);

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="activity"', $this->responseContent());
        $this->assertStringContainsString('isNew: true', $this->responseContent());
    }

    public function testNewActionRendersTheActivityFormForANewActivity(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/activity/new');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="activity"', $this->responseContent());
        $this->assertStringContainsString('isNew: true', $this->responseContent());
        $this->assertStringContainsString('id="fieldset-general"', $this->responseContent());
        $this->assertStringContainsString('id="fieldset-notes"', $this->responseContent());
        $this->assertStringContainsString('id="fieldset-weather"', $this->responseContent());
    }

    public function testNewActionAcceptsAnExplicitDateQueryParameter(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/activity/new?date=2024-01-15 08:00:00');

        $this->assertStatusCode(200, $this->Client);
        $this->assertStringContainsString('id="activity"', $this->responseContent());
    }
}

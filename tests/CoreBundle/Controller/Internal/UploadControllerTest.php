<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class UploadControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    private function getPathToImportedFiles(): string
    {
        return self::getContainer()->getParameter('app.data_directory') . '/import/';
    }

    private function createTempFileWithContent(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($path, $content);

        return $path;
    }

    public function testUploadActionRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/_internal/upload');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testUploadActionReturnsErrorWhenNoFileGiven(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/_internal/upload');

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(
            [
                'error' => 'No file given.',
            ],
            json_decode($this->Client->getResponse()->getContent(), true)
        );
    }

    public function testUploadActionMovesUploadedFileAndReturnsSuccess(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $sourcePath = $this->createTempFileWithContent('fake activity data');
        $targetPath = $this->getPathToImportedFiles() . 'activity.gpx';
        $uploadedFile = new UploadedFile($sourcePath, 'activity.gpx', null, null, true);

        try {
            $this->Client->request('POST', '/_internal/upload', [], [
                'qqfile' => $uploadedFile,
            ]);

            $this->isSuccessful($this->Client->getResponse());
            $this->assertSame(
                [
                    'success' => true,
                ],
                json_decode($this->Client->getResponse()->getContent(), true)
            );
            $this->assertFileExists($targetPath);
            $this->assertSame('fake activity data', file_get_contents($targetPath));
        } finally {
            new Filesystem()->remove($targetPath);
            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testUploadActionReplacesSemicolonsInStoredFileName(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $sourcePath = $this->createTempFileWithContent('data');
        $targetPath = $this->getPathToImportedFiles() . 'activity_-_with_-_semicolons.gpx';
        $uploadedFile = new UploadedFile($sourcePath, 'activity;with;semicolons.gpx', null, null, true);

        try {
            $this->Client->request('POST', '/_internal/upload', [], [
                'qqfile' => $uploadedFile,
            ]);

            $this->isSuccessful($this->Client->getResponse());
            $this->assertFileExists($targetPath);
        } finally {
            new Filesystem()->remove($targetPath);
            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testUploadActionReturnsErrorForFileNameThatIsNotValidUtf8(): void
    {
        if (! class_exists('Normalizer')) {
            $this->markTestSkipped('ext-intl (Normalizer) is not installed.');
        }

        $this->loginClient($this->Client, $this->Account, 'default');

        $sourcePath = $this->createTempFileWithContent('data');
        $uploadedFile = new UploadedFile($sourcePath, "\xB1\x31-invalid.gpx", null, null, true);

        try {
            $this->Client->request('POST', '/_internal/upload', [], [
                'qqfile' => $uploadedFile,
            ]);

            $this->isSuccessful($this->Client->getResponse());
            $this->assertSame(
                [
                    'error' => 'The given file name is not valid UTF-8 and could not be normalized.',
                ],
                json_decode($this->Client->getResponse()->getContent(), true)
            );
        } finally {
            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testAjaxSaveTcxActionRequiresAuthentication(): void
    {
        $this->Client->request('POST', '/_internal/upload/tcx');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testAjaxSaveTcxActionReturnsErrorWhenActivityIdIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/_internal/upload/tcx', [
            'data' => '<tcx></tcx>',
        ]);

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(
            [
                'error' => 'No data given.',
            ],
            json_decode($this->Client->getResponse()->getContent(), true)
        );
    }

    public function testAjaxSaveTcxActionReturnsErrorWhenDataIsMissing(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');
        $this->Client->request('POST', '/_internal/upload/tcx', [
            'activityId' => 'some-activity-id',
        ]);

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(
            [
                'error' => 'No data given.',
            ],
            json_decode($this->Client->getResponse()->getContent(), true)
        );
    }

    public function testAjaxSaveTcxActionAppendsDataAndReturnsSuccess(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $activityId = 'test-activity-' . uniqid('', true);
        $targetPath = $this->getPathToImportedFiles() . $activityId . '.tcx';

        try {
            $this->Client->request('POST', '/_internal/upload/tcx', [
                'activityId' => $activityId,
                'data' => '<tcx>first-chunk</tcx>',
            ]);

            $this->isSuccessful($this->Client->getResponse());
            $this->assertSame(
                [
                    'success' => true,
                ],
                json_decode($this->Client->getResponse()->getContent(), true)
            );
            $this->assertFileExists($targetPath);
            $this->assertSame('<tcx>first-chunk</tcx>', file_get_contents($targetPath));
        } finally {
            new Filesystem()->remove($targetPath);
        }
    }

    public function testAjaxSaveTcxActionAppendsToExistingFileInsteadOfOverwritingIt(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $activityId = 'test-activity-' . uniqid('', true);
        $targetPath = $this->getPathToImportedFiles() . $activityId . '.tcx';

        try {
            $this->Client->request('POST', '/_internal/upload/tcx', [
                'activityId' => $activityId,
                'data' => 'first-',
            ]);
            $this->Client->request('POST', '/_internal/upload/tcx', [
                'activityId' => $activityId,
                'data' => 'second',
            ]);

            $this->assertSame('first-second', file_get_contents($targetPath));
        } finally {
            new Filesystem()->remove($targetPath);
        }
    }

    public function testAjaxSaveTcxActionReturnsErrorForActivityIdThatIsNotValidUtf8(): void
    {
        if (! class_exists('Normalizer')) {
            $this->markTestSkipped('ext-intl (Normalizer) is not installed.');
        }

        $this->loginClient($this->Client, $this->Account, 'default');

        $this->Client->request('POST', '/_internal/upload/tcx', [
            'activityId' => "\xB1\x31-invalid",
            'data' => 'irrelevant',
        ]);

        $this->isSuccessful($this->Client->getResponse());
        $this->assertSame(
            [
                'error' => 'The given file name is not valid UTF-8 and could not be normalized.',
            ],
            json_decode($this->Client->getResponse()->getContent(), true)
        );
    }

    public function testAjaxSaveTcxActionReturnsErrorWhenTargetDirectoryCannotBeCreated(): void
    {
        $this->loginClient($this->Client, $this->Account, 'default');

        $blockingFileName = 'blocker-' . uniqid('', true);
        $blockingFilePath = $this->getPathToImportedFiles() . $blockingFileName;
        $activityId = $blockingFileName . '/nested-activity';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getPathToImportedFiles());
        $filesystem->dumpFile($blockingFilePath, 'this is a file, not a directory');

        try {
            $this->Client->request('POST', '/_internal/upload/tcx', [
                'activityId' => $activityId,
                'data' => 'irrelevant',
            ]);

            $this->isSuccessful($this->Client->getResponse());
            $responseData = json_decode($this->Client->getResponse()->getContent(), true);

            $this->assertArrayHasKey('error', $responseData);
            $this->assertNotSame('No data given.', $responseData['error']);
            $this->assertNotSame(
                'The given file name is not valid UTF-8 and could not be normalized.',
                $responseData['error']
            );
        } finally {
            $filesystem->remove($blockingFilePath);
        }
    }
}

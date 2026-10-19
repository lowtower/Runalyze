<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackupAnalyzer;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class JsonImportToolControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->getDefaultAccount();
    }

    protected function getImportFilePath(): string
    {
        return self::getContainer()->getParameter('app.data_directory') . '/backup-tool/import/';
    }

    protected function getRunalyzeVersion(): string
    {
        return (string) self::getContainer()->getParameter('RUNALYZE_VERSION');
    }

    /**
     * @return string[]
     */
    protected function allExpectedBackupTables(): array
    {
        return [
            'runalyze_account',
            'runalyze_conf',
            'runalyze_dataset',
            'runalyze_hrv',
            'runalyze_plugin',
            'runalyze_plugin_conf',
            'runalyze_route',
            'runalyze_sport',
            'runalyze_swimdata',
            'runalyze_training',
            'runalyze_trackdata',
            'runalyze_type',
            'runalyze_user',
            'runalyze_equipment_type',
            'runalyze_equipment_sport',
            'runalyze_equipment_to_sport',
            'runalyze_equipment',
            'runalyze_activity_equipment',
            'runalyze_activity_tag',
            'runalyze_tag',
            'runalyze_raceresult',
        ];
    }

    /**
     * Writes a valid, gzip-compressed backup file containing only table
     * headers (zero data rows for every table). This is enough to satisfy
     * JsonBackupAnalyzer::fileIsOkay() and lets JsonImporter run a
     * completely safe, side-effect-free import (nothing to insert), without
     * needing to know the real column schema of any database table.
     *
     * Real backup exports always write a blank line after every table
     * marker, even for empty tables. JsonImporter::importTable() peeks at
     * that next line to decide whether it is a data row, so omitting the
     * blank line would make it misinterpret the following table's marker
     * as a data row of the current table.
     *
     * @param string[] $tableNames
     */
    protected function createBackupGzipFile(string $path, array $tableNames, string $version): void
    {
        $resource = gzopen($path, 'wb9');

        foreach ($tableNames as $tableName) {
            gzwrite($resource, '{"TABLE":"' . $tableName . '"}' . "\n");
            gzwrite($resource, "\n");
        }

        gzwrite($resource, 'version=' . $version . "\n");
        gzclose($resource);
    }

    protected function uploadBackupFile(KernelBrowser $client, string $localPath, string $originalFilename): void
    {
        $uploadedFile = new UploadedFile($localPath, $originalFilename, null, null, true);

        $client->request('POST', '/my/tools/backup-import/upload', [], [
            'qqfile' => $uploadedFile,
        ]);
    }

    public function testUploadFormActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/backup-import');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testUploadFormActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/backup-import');

        $this->assertStatusCode(200, $client);
    }

    public function testBackupUploadActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('POST', '/my/tools/backup-import/upload');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testBackupUploadActionReturnsErrorJsonWhenNoFileUploaded(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('POST', '/my/tools/backup-import/upload');

        $this->assertStatusCode(200, $client);
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'error' => 'File upload did not work.',
            ]),
            $client->getResponse()
                ->getContent()
        );
    }

    public function testBackupUploadActionReturnsErrorJsonForWrongFileExtension(): void
    {
        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.txt';
        file_put_contents($localPath, 'not a real backup');

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, 'not-a-backup.txt');

            $this->assertStatusCode(200, $client);
            $this->assertJsonStringEqualsJsonString(
                json_encode([
                    'error' => 'Wrong file extension.',
                ]),
                $client->getResponse()
                    ->getContent()
            );
        } finally {
            if (file_exists($localPath)) {
                unlink($localPath);
            }
        }
    }

    public function testBackupUploadActionMovesFileAndReturnsSuccessForValidExtension(): void
    {
        $importPath = $this->getImportFilePath();
        $filesystem = new Filesystem();
        $filesystem->mkdir($importPath);

        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.json.gz';
        file_put_contents($localPath, 'dummy content, only the extension is validated at upload time');

        $originalFilename = 'simple-upload-test.json.gz';

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, $originalFilename);

            $this->assertStatusCode(200, $client);
            $this->assertJsonStringEqualsJsonString(
                json_encode([
                    'success' => true,
                ]),
                $client->getResponse()
                    ->getContent()
            );
            $this->assertFileExists($importPath . $originalFilename);
        } finally {
            $filesystem->remove($importPath . $originalFilename);
        }
    }

    public function testBackupImportActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/backup-import/import');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testBackupImportActionRedirectsToUploadFormWhenNoFileWasUploadedBefore(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/backup-import/import');

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/tools/backup-import',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testBackupImportActionRemovesFileWhenVersionMismatches(): void
    {
        $importPath = $this->getImportFilePath();
        $filesystem = new Filesystem();
        $filesystem->mkdir($importPath);

        $matchingVersion = JsonBackupAnalyzer::extractMajorAndMinorVersion($this->getRunalyzeVersion());
        [$major, $minor] = array_map(intval(...), explode('.', $matchingVersion !== '' ? $matchingVersion : '1.0'));
        $wrongVersion = ($major + 1) . '.' . $minor;

        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.json.gz';
        $this->createBackupGzipFile($localPath, $this->allExpectedBackupTables(), $wrongVersion);

        $originalFilename = 'version-mismatch.json.gz';

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, $originalFilename);

            $client->request('GET', '/my/tools/backup-import/import');

            $this->assertStatusCode(200, $client);
            $this->assertFileDoesNotExist($importPath . $originalFilename);
        } finally {
            $filesystem->remove($importPath . $originalFilename);
        }
    }

    /**
     * Regression test for the fixed sort()-comparison bug: before the fix,
     * expectedTablesAreThere() always evaluated to true (comparing the
     * boolean return values of sort() instead of the sorted arrays), so a
     * backup file missing a table would incorrectly have been accepted.
     */
    public function testBackupImportActionRemovesFileWhenAnExpectedTableIsMissing(): void
    {
        $importPath = $this->getImportFilePath();
        $filesystem = new Filesystem();
        $filesystem->mkdir($importPath);

        $tables = $this->allExpectedBackupTables();
        array_pop($tables);

        $matchingVersion = JsonBackupAnalyzer::extractMajorAndMinorVersion($this->getRunalyzeVersion());

        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.json.gz';
        $this->createBackupGzipFile($localPath, $tables, $matchingVersion !== '' ? $matchingVersion : '1.0');

        $originalFilename = 'missing-table.json.gz';

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, $originalFilename);

            $client->request('GET', '/my/tools/backup-import/import');

            $this->assertStatusCode(200, $client);
            $this->assertFileDoesNotExist($importPath . $originalFilename);
        } finally {
            $filesystem->remove($importPath . $originalFilename);
        }
    }

    public function testBackupImportActionKeepsFileForValidBackup(): void
    {
        $importPath = $this->getImportFilePath();
        $filesystem = new Filesystem();
        $filesystem->mkdir($importPath);

        $matchingVersion = JsonBackupAnalyzer::extractMajorAndMinorVersion($this->getRunalyzeVersion());

        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.json.gz';
        $this->createBackupGzipFile($localPath, $this->allExpectedBackupTables(), $matchingVersion !== '' ? $matchingVersion : '1.0');

        $originalFilename = 'valid-empty-backup.json.gz';

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, $originalFilename);

            $client->request('GET', '/my/tools/backup-import/import');

            $this->assertStatusCode(200, $client);
            $this->assertFileExists($importPath . $originalFilename);
        } finally {
            $filesystem->remove($importPath . $originalFilename);
        }
    }

    public function testBackupImportDoActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/backup-import/import/do');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testBackupImportDoActionRedirectsToUploadFormWhenNoFileWasUploadedBefore(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/backup-import/import/do');

        $this->assertStatusCode(302, $client);
        $this->assertSame(
            '/my/tools/backup-import',
            $client->getResponse()
                ->headers->get('Location')
        );
    }

    public function testBackupImportDoActionImportsEmptyBackupSuccessfully(): void
    {
        $importPath = $this->getImportFilePath();
        $filesystem = new Filesystem();
        $filesystem->mkdir($importPath);

        $matchingVersion = JsonBackupAnalyzer::extractMajorAndMinorVersion($this->getRunalyzeVersion());

        $localPath = sys_get_temp_dir() . '/' . uniqid('backup-test-', true) . '.json.gz';
        $this->createBackupGzipFile($localPath, $this->allExpectedBackupTables(), $matchingVersion !== '' ? $matchingVersion : '1.0');

        $originalFilename = 'full-round-trip.json.gz';

        try {
            $client = $this->makeAuthenticatedClient();
            $this->uploadBackupFile($client, $localPath, $originalFilename);

            $client->request('GET', '/my/tools/backup-import/import');
            $this->assertStatusCode(200, $client);

            $client->request('POST', '/my/tools/backup-import/import/do');

            $this->assertStatusCode(200, $client);
            $this->assertStringContainsString('Nothing changed.', (string) $client->getResponse()->getContent());
        } finally {
            $filesystem->remove($importPath . $originalFilename);
        }
    }
}

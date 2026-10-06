<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Poster;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[\PHPUnit\Framework\Attributes\Group('default')]
class FileHandlerTest extends TestCase
{
    private const ACCOUNT_ID = 7;

    private string $dataDirectory;

    private Filesystem $filesystem;

    private FileHandler $fileHandler;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->dataDirectory = sys_get_temp_dir() . '/file-handler-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->dataDirectory);

        $this->fileHandler = new FileHandler($this->dataDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->dataDirectory);
    }

    private function createAccount(int $id = self::ACCOUNT_ID): Account&MockObject
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn($id);

        return $account;
    }

    private function createSport(string $name): Sport
    {
        $sport = new Sport();
        $sport->setName($name);

        return $sport;
    }

    private function createPosterFile(string $name, string $content = 'content', ?int $modifiedAt = null): void
    {
        $path = $this->dataDirectory . '/poster/' . $name;

        $this->filesystem->dumpFile($path, $content);

        if ($modifiedAt !== null) {
            touch($path, $modifiedAt);
        }
    }

    public function testGetFileListReturnsEmptyListWhenPosterDirectoryDoesNotExist(): void
    {
        self::assertSame([], $this->fileHandler->getFileList($this->createAccount()));
    }

    public function testGetFileListReturnsEmptyListWhenAccountHasNoPosters(): void
    {
        $this->createPosterFile('8-other.png');

        self::assertSame([], $this->fileHandler->getFileList($this->createAccount()));
    }

    public function testGetFileListContainsFileSizesByNameWithoutAccountPrefix(): void
    {
        $this->createPosterFile('7-first.png', 'abc');
        $this->createPosterFile('7-second.png', 'abcde');

        self::assertEquals(
            [
                'first.png' => 3,
                'second.png' => 5,
            ],
            $this->fileHandler->getFileList($this->createAccount())
        );
    }

    public function testGetFileListSortsNewestFileFirst(): void
    {
        $this->createPosterFile('7-oldest.png', modifiedAt: 1_000_000);
        $this->createPosterFile('7-newest.png', modifiedAt: 3_000_000);
        $this->createPosterFile('7-middle.png', modifiedAt: 2_000_000);

        self::assertSame(
            ['newest.png', 'middle.png', 'oldest.png'],
            array_keys($this->fileHandler->getFileList($this->createAccount()))
        );
    }

    public function testGetFileListIgnoresFilesOfOtherAccounts(): void
    {
        $this->createPosterFile('7-own.png');
        $this->createPosterFile('8-other.png');
        $this->createPosterFile('17-other.png');
        $this->createPosterFile('77-other.png');

        self::assertSame(
            ['own.png'],
            array_keys($this->fileHandler->getFileList($this->createAccount()))
        );
    }

    public function testGetPosterDownloadResponseReturnsPngWithContentAndHeaders(): void
    {
        $this->createPosterFile('7-poster.png', 'png content');

        $response = $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'poster.png');

        self::assertSame('png content', $response->getContent());
        self::assertSame('image/png', $response->headers->get('Content-type'));
        self::assertSame('11', $response->headers->get('Content-length'));
        self::assertSame('attachment; filename="7-poster.png";', $response->headers->get('Content-Disposition'));
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForMissingFile(): void
    {
        $this->createPosterFile('7-existing.png');

        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'missing.png');
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForMissingPosterDirectory(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'poster.png');
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForEmptyFilename(): void
    {
        $this->createPosterFile('7-.png');

        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), '');
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForFileOfAnotherAccount(): void
    {
        $this->createPosterFile('8-poster.png');

        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'poster.png');
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForNameWithDirectorySeparator(): void
    {
        $this->createPosterFile('7-sub/file.png');

        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'sub/file.png');
    }

    public function testGetPosterDownloadResponseThrowsNotFoundForDirectory(): void
    {
        $this->filesystem->mkdir($this->dataDirectory . '/poster/7-folder');

        $this->expectException(NotFoundHttpException::class);

        $this->fileHandler->getPosterDownloadResponse($this->createAccount(), 'folder');
    }

    public function testBuildFinalFileNameContainsAccountSportYearTypeSizeAndTimestamp(): void
    {
        $fileName = $this->fileHandler->buildFinalFileName(
            $this->createAccount(),
            $this->createSport('Running'),
            2024,
            'heatmap',
            4000
        );

        self::assertMatchesRegularExpression('/^7-Running-2024-heatmap-4000-\d{8}-\d{4}\.png$/', $fileName);
    }

    public function testBuildFinalFileNameAcceptsYearAndSizeAsStrings(): void
    {
        $fileName = $this->fileHandler->buildFinalFileName(
            $this->createAccount(),
            $this->createSport('Running'),
            '2024',
            'circular',
            '4000'
        );

        self::assertMatchesRegularExpression('/^7-Running-2024-circular-4000-\d{8}-\d{4}\.png$/', $fileName);
    }

    #[DataProvider('sportNames')]
    public function testBuildFinalFileNameRemovesCharactersThatAreNotFilesystemFriendly(string $sportName, string $expectedPart): void
    {
        $fileName = $this->fileHandler->buildFinalFileName(
            $this->createAccount(),
            $this->createSport($sportName),
            2024,
            'heatmap',
            4000
        );

        self::assertStringStartsWith('7-' . $expectedPart . '-2024-heatmap-4000-', $fileName);
    }

    public static function sportNames(): iterable
    {
        yield 'plain name' => ['Running', 'Running'];
        yield 'spaces and digits' => ['Rad fahren 2', 'Radfahren2'];
        yield 'special characters' => ['Trail Run & Co.', 'TrailRunCo'];
        yield 'umlaut is removed' => ['Läufen', 'Lufen'];
        yield 'only special characters' => ['###', ''];
        yield 'empty name' => ['', ''];
    }
}

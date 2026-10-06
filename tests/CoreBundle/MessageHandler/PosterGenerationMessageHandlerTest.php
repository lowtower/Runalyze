<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\MessageHandler;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\InkscapeConverter;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\RsvgConverter;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GenerateJsonData;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GeneratePoster;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\PosterGenerationStateResolver;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Bundle\CoreBundle\MessageHandler\PosterGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PosterGenerationMessageHandlerTest extends TestCase
{
    private LoggerInterface&MockObject $loggerMock;

    private AccountRepository&MockObject $accountRepositoryMock;

    private SportRepository&MockObject $sportRepositoryMock;

    private NotificationRepository&MockObject $notificationRepositoryMock;

    private GenerateJsonData&MockObject $generateJsonDataMock;

    private GeneratePoster&MockObject $generatePosterMock;

    private FileHandler&MockObject $fileHandlerMock;

    private AccountMailer&MockObject $accountMailerMock;

    /**
     * @var string[]
     */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->sportRepositoryMock = $this->createMock(SportRepository::class);
        $this->notificationRepositoryMock = $this->createMock(NotificationRepository::class);
        $this->generateJsonDataMock = $this->createMock(GenerateJsonData::class);
        $this->generatePosterMock = $this->createMock(GeneratePoster::class);
        $this->fileHandlerMock = $this->createMock(FileHandler::class);
        $this->accountMailerMock = $this->createMock(AccountMailer::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->temporaryDirectories);
    }

    private function createHandler(?string $dataDirectory = null): PosterGenerationMessageHandler
    {
        return new PosterGenerationMessageHandler(
            $this->loggerMock,
            $this->accountRepositoryMock,
            $this->sportRepositoryMock,
            $this->notificationRepositoryMock,
            $this->generateJsonDataMock,
            $this->generatePosterMock,
            $this->fileHandlerMock,
            $this->accountMailerMock,
            new PosterGenerationStateResolver(),
            $dataDirectory ?? sys_get_temp_dir(),
            '/usr/bin/rsvg-convert',
            '/usr/bin/inkscape'
        );
    }

    private function createMessage(int $accountId = 1, int $sportId = 5): PosterGenerationMessage
    {
        return new PosterGenerationMessage(
            accountId: $accountId,
            year: 2024,
            types: ['circular'],
            sportId: $sportId,
            title: 'Title',
            size: 'A2',
            backgroundColor: '#000000',
            trackColor: '#FF0000',
            textColor: '#FFFFFF',
            raceColor: '#00FF00'
        );
    }

    private function createJsonDirectory(bool $withJsonFile): string
    {
        $directory = sys_get_temp_dir() . '/poster-handler-test-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $this->temporaryDirectories[] = $directory;

        if ($withJsonFile) {
            file_put_contents($directory . '/data.json', '{}');
        }

        return $directory;
    }

    private function createFailedProcessException(): ProcessFailedException
    {
        $process = new Process([PHP_BINARY, '-r', 'fwrite(STDERR, "boom"); exit(3);']);
        $process->run();

        return new ProcessFailedException($process);
    }

    private function arrangeSportOfRequestingAccount(): Account&MockObject
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(1);

        $sport = $this->createMock(Sport::class);
        $sport->method('getAccount')
            ->willReturn($account);

        $this->accountRepositoryMock->method('find')
            ->willReturn($account);
        $this->sportRepositoryMock->method('find')
            ->willReturn($sport);

        return $account;
    }

    public function testConstructorThrowsInvalidArgumentExceptionForMissingDataDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('/this/directory/does/not/exist');

        $this->createHandler('/this/directory/does/not/exist');
    }

    public function testInvokeReturnsEarlyWhenAccountIsNotFound(): void
    {
        $this->accountRepositoryMock->method('find')
            ->willReturn(null);
        $this->sportRepositoryMock->method('find')
            ->willReturn($this->createStub(Sport::class));

        $this->generateJsonDataMock->expects($this->never())
            ->method('createJsonFilesFor');
        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeReturnsEarlyWhenSportIsNotFound(): void
    {
        $account = $this->createStub(Account::class);

        $this->accountRepositoryMock->method('find')
            ->willReturn($account);
        $this->sportRepositoryMock->method('find')
            ->willReturn(null);

        $this->generateJsonDataMock->expects($this->never())
            ->method('createJsonFilesFor');
        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeReturnsEarlyWhenSportBelongsToDifferentAccount(): void
    {
        $requestingAccount = $this->createMock(Account::class);
        $requestingAccount->method('getId')
            ->willReturn(1);

        $otherAccount = $this->createMock(Account::class);
        $otherAccount->method('getId')
            ->willReturn(2);

        $sport = $this->createMock(Sport::class);
        $sport->method('getAccount')
            ->willReturn($otherAccount);

        $this->accountRepositoryMock->method('find')
            ->willReturn($requestingAccount);
        $this->sportRepositoryMock->method('find')
            ->willReturn($sport);

        $this->generateJsonDataMock->expects($this->never())
            ->method('createJsonFilesFor');
        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $handler($this->createMessage(accountId: 1, sportId: 5));
    }

    public function testInvokeCreatesJsonFilesForAccountSportAndYear(): void
    {
        $account = $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));

        $this->generateJsonDataMock->expects($this->once())
            ->method('createJsonFilesFor')
            ->with($account, $this->isInstanceOf(Sport::class), 2024);

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeNotifiesButSendsNoMailWhenThereIsNoDataForPosters(): void
    {
        $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));

        $this->generatePosterMock->expects($this->never())
            ->method('buildCommand');
        $this->notificationRepositoryMock->expects($this->once())
            ->method('save');
        $this->accountMailerMock->expects($this->never())
            ->method('sendPosterGeneratedTo');
        $this->generateJsonDataMock->expects($this->once())
            ->method('deleteGeneratedFiles');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeLogsErrorAndSendsNoMailWhenPosterCommandCannotBeBuilt(): void
    {
        $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: true));

        $exception = new RuntimeException('The poster command could not be built.');
        $this->generatePosterMock->method('buildCommand')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Poster creation failed', [
                'type' => 'circular',
                'exception' => $exception,
            ]);
        $this->fileHandlerMock->expects($this->never())
            ->method('buildFinalFileName');
        $this->notificationRepositoryMock->expects($this->once())
            ->method('save');
        $this->accountMailerMock->expects($this->never())
            ->method('sendPosterGeneratedTo');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeLogsProcessErrorOutputWhenPosterProcessFails(): void
    {
        $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: true));

        $exception = $this->createFailedProcessException();

        $this->generatePosterMock->method('buildCommand')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Poster creation failed', [
                'type' => 'circular',
                'exception' => $exception,
                'processErrorOutput' => 'boom',
            ]);

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeDeletesGeneratedFilesWhenJsonFilesCannotBeCreated(): void
    {
        $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('createJsonFilesFor')
            ->willThrowException(new RuntimeException('JSON files could not be created.'));

        $this->generateJsonDataMock->expects($this->once())
            ->method('deleteGeneratedFiles');
        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON files could not be created.');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeDeletesGeneratedFilesWhenNotificationCannotBeSaved(): void
    {
        $this->arrangeSportOfRequestingAccount();
        $this->generateJsonDataMock->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));
        $this->notificationRepositoryMock->method('save')
            ->willThrowException(new RuntimeException('Database is not available.'));

        $this->generateJsonDataMock->expects($this->once())
            ->method('deleteGeneratedFiles');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database is not available.');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testGetConverterReturnsInkscapeConverterForCircularType(): void
    {
        $handler = $this->createHandler();

        $reflection = new ReflectionObject($handler);
        $method = $reflection->getMethod('getConverter');

        $this->assertInstanceOf(InkscapeConverter::class, $method->invoke($handler, 'circular'));
    }

    #[DataProvider('provideNonCircularPosterTypes')]
    public function testGetConverterReturnsRsvgConverterForAnyOtherType(string $posterType): void
    {
        $handler = $this->createHandler();

        $reflection = new ReflectionObject($handler);
        $method = $reflection->getMethod('getConverter');

        $this->assertInstanceOf(RsvgConverter::class, $method->invoke($handler, $posterType));
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideNonCircularPosterTypes(): array
    {
        return [
            'linear' => ['linear'],
            'unknown/unrecognized type' => ['some-future-type'],
            'empty string' => [''],
        ];
    }

    public function testExportDirectoryAppendsExpectedSubdirectory(): void
    {
        $handler = $this->createHandler();

        $reflection = new ReflectionObject($handler);
        $method = $reflection->getMethod('exportDirectory');

        $this->assertSame(realpath(sys_get_temp_dir()) . '/poster/', $method->invoke($handler));
    }
}

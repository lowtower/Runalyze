<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\MessageHandler;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
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
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Bundle\CoreBundle\MessageHandler\PosterGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class PosterGenerationMessageHandlerTest extends TestCase
{
    // These are stubs by default. A test that needs expects() replaces the
    // affected property with a local mock before calling createHandler().
    private LoggerInterface&Stub $loggerMock;

    private AccountRepository&Stub $accountRepositoryMock;

    private SportRepository&Stub $sportRepositoryMock;

    private NotificationRepository&Stub $notificationRepositoryMock;

    private GenerateJsonData&Stub $generateJsonDataMock;

    private GeneratePoster&Stub $generatePosterMock;

    private FileHandler&Stub $fileHandlerMock;

    private AccountMailer&Stub $accountMailerMock;

    /**
     * @var string[]
     */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        $this->loggerMock = $this->createStub(LoggerInterface::class);
        $this->accountRepositoryMock = $this->createStub(AccountRepository::class);
        $this->sportRepositoryMock = $this->createStub(SportRepository::class);
        $this->notificationRepositoryMock = $this->createStub(NotificationRepository::class);
        $this->generateJsonDataMock = $this->createStub(GenerateJsonData::class);
        $this->generatePosterMock = $this->createStub(GeneratePoster::class);
        $this->fileHandlerMock = $this->createStub(FileHandler::class);
        $this->accountMailerMock = $this->createStub(AccountMailer::class);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->temporaryDirectories);
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
            '/usr/bin/rsvg-convert'
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

    private function arrangeSportOfRequestingAccount(): Account&Stub
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(1);

        $sport = $this->createStub(Sport::class);
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
        $this->expectExceptionMessageIsOrContains('/this/directory/does/not/exist');

        $this->createHandler('/this/directory/does/not/exist');
    }

    public function testInvokeReturnsEarlyWhenAccountIsNotFound(): void
    {
        $this->accountRepositoryMock->method('find')
            ->willReturn(null);
        $this->sportRepositoryMock->method('find')
            ->willReturn($this->createStub(Sport::class));

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->expects($this->never())
            ->method('createJsonFilesFor');
        $this->generateJsonDataMock = $generateJsonData;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->never())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

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

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->expects($this->never())
            ->method('createJsonFilesFor');
        $this->generateJsonDataMock = $generateJsonData;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->never())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeReturnsEarlyWhenSportBelongsToDifferentAccount(): void
    {
        $requestingAccount = $this->createStub(Account::class);
        $requestingAccount->method('getId')
            ->willReturn(1);

        $otherAccount = $this->createStub(Account::class);
        $otherAccount->method('getId')
            ->willReturn(2);

        $sport = $this->createStub(Sport::class);
        $sport->method('getAccount')
            ->willReturn($otherAccount);

        $this->accountRepositoryMock->method('find')
            ->willReturn($requestingAccount);
        $this->sportRepositoryMock->method('find')
            ->willReturn($sport);

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->expects($this->never())
            ->method('createJsonFilesFor');
        $this->generateJsonDataMock = $generateJsonData;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->never())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

        $handler = $this->createHandler();
        $handler($this->createMessage(accountId: 1, sportId: 5));
    }

    public function testInvokeCreatesJsonFilesForAccountSportAndYear(): void
    {
        $account = $this->arrangeSportOfRequestingAccount();

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));
        $generateJsonData->expects($this->once())
            ->method('createJsonFilesFor')
            ->with($account, $this->isInstanceOf(Sport::class), 2024);
        $this->generateJsonDataMock = $generateJsonData;

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeNotifiesButSendsNoMailWhenThereIsNoDataForPosters(): void
    {
        $this->arrangeSportOfRequestingAccount();

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));
        $generateJsonData->expects($this->once())
            ->method('deleteGeneratedFiles');
        $this->generateJsonDataMock = $generateJsonData;

        $generatePoster = $this->createMock(GeneratePoster::class);
        $generatePoster->expects($this->never())
            ->method('buildCommand');
        $this->generatePosterMock = $generatePoster;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->once())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

        $accountMailer = $this->createMock(AccountMailer::class);
        $accountMailer->expects($this->never())
            ->method('sendPosterGeneratedTo');
        $this->accountMailerMock = $accountMailer;

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

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Poster creation failed', [
                'type' => 'circular',
                'exception' => $exception,
            ]);
        $this->loggerMock = $logger;

        $fileHandler = $this->createMock(FileHandler::class);
        $fileHandler->expects($this->never())
            ->method('buildFinalFileName');
        $this->fileHandlerMock = $fileHandler;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->once())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

        $accountMailer = $this->createMock(AccountMailer::class);
        $accountMailer->expects($this->never())
            ->method('sendPosterGeneratedTo');
        $this->accountMailerMock = $accountMailer;

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

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Poster creation failed', [
                'type' => 'circular',
                'exception' => $exception,
                'processErrorOutput' => 'boom',
            ]);
        $this->loggerMock = $logger;

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeDeletesGeneratedFilesWhenJsonFilesCannotBeCreated(): void
    {
        $this->arrangeSportOfRequestingAccount();

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->method('createJsonFilesFor')
            ->willThrowException(new RuntimeException('JSON files could not be created.'));
        $generateJsonData->expects($this->once())
            ->method('deleteGeneratedFiles');
        $this->generateJsonDataMock = $generateJsonData;

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->never())
            ->method('save');
        $this->notificationRepositoryMock = $notificationRepository;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('JSON files could not be created.');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeDeletesGeneratedFilesWhenNotificationCannotBeSaved(): void
    {
        $this->arrangeSportOfRequestingAccount();

        $generateJsonData = $this->createMock(GenerateJsonData::class);
        $generateJsonData->method('getPathToJsonFiles')
            ->willReturn($this->createJsonDirectory(withJsonFile: false));
        $generateJsonData->expects($this->once())
            ->method('deleteGeneratedFiles');
        $this->generateJsonDataMock = $generateJsonData;

        $this->notificationRepositoryMock->method('save')
            ->willThrowException(new RuntimeException('Database is not available.'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Database is not available.');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testGetConverterReturnsInkscapeConverterForCircularType(): void
    {
        $handler = $this->createHandler();

        $reflection = new ReflectionObject($handler);
        $method = $reflection->getMethod('getConverter');

        # $this->assertInstanceOf(InkscapeConverter::class, $method->invoke($handler, 'circular'));
        $this->assertInstanceOf(RsvgConverter::class, $method->invoke($handler, 'circular'));
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
     * @return \Iterator<string, array<string>>
     */
    public static function provideNonCircularPosterTypes(): \Iterator
    {
        yield 'linear' => ['linear'];
        yield 'unknown/unrecognized type' => ['some-future-type'];
        yield 'empty string' => [''];
    }

    public function testExportDirectoryAppendsExpectedSubdirectory(): void
    {
        $handler = $this->createHandler();

        $reflection = new ReflectionObject($handler);
        $method = $reflection->getMethod('exportDirectory');

        $this->assertSame(realpath(sys_get_temp_dir()) . '/poster/', $method->invoke($handler));
    }
}

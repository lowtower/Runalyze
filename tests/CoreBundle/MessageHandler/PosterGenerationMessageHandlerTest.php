<?php

namespace Runalyze\Bundle\CoreBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\AbstractSvgToPngConverter;
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

    private PosterGenerationStateResolver $stateResolver;

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
        $this->stateResolver = new PosterGenerationStateResolver();
    }

    private function createHandler(): PosterGenerationMessageHandler
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
            $this->stateResolver,
            sys_get_temp_dir(),
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

    public function testInvokeReturnsEarlyWhenAccountIsNotFound(): void
    {
        $this->accountRepositoryMock->method('find')
            ->willReturn(null);
        $this->sportRepositoryMock->method('find')
            ->willReturn($this->createMock(Sport::class));

        $this->generateJsonDataMock->expects($this->never())
            ->method('createJsonFilesFor');
        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $handler($this->createMessage());
    }

    public function testInvokeReturnsEarlyWhenSportIsNotFound(): void
    {
        $account = $this->createMock(Account::class);

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

    public function testGetConverterReturnsInkscapeConverterForCircularType(): void
    {
        $handler = $this->createHandler();

        $reflection = new \ReflectionObject($handler);
        $method = $reflection->getMethod('getConverter');

        $result = $method->invoke($handler, 'circular');

        $this->assertInstanceOf(AbstractSvgToPngConverter::class, $result);
        $this->assertStringContainsString('Inkscape', $result::class);
    }

    #[DataProvider('provideNonCircularPosterTypes')]
    public function testGetConverterReturnsRsvgConverterForAnyOtherType(string $posterType): void
    {
        $handler = $this->createHandler();

        $reflection = new \ReflectionObject($handler);
        $method = $reflection->getMethod('getConverter');

        $result = $method->invoke($handler, $posterType);

        $this->assertInstanceOf(AbstractSvgToPngConverter::class, $result);
        $this->assertStringContainsString('Rsvg', $result::class);
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
        $dataDirectory = sys_get_temp_dir();
        $handler = $this->createHandler();

        $reflection = new \ReflectionObject($handler);
        $method = $reflection->getMethod('exportDirectory');

        $result = $method->invoke($handler);

        $this->assertSame(realpath($dataDirectory) . '/poster/', $result);
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Tests\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\Export\File\AbstractFileExporter;
use SessionAccountHandler;
use Symfony\Component\HttpFoundation\Response;

final class ConcreteFileExporterForTest extends AbstractFileExporter
{
    public function extension(): string
    {
        return 'txt';
    }

    protected function createFile(): void
    {
        $this->FileContent = 'fixed test file content';
    }

    public function enum(): int
    {
        return 0;
    }

    public function isPossible(): bool
    {
        return true;
    }

    protected function mimeType(): string
    {
        return 'text/plain';
    }
}

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AbstractFileExporterTest extends TestCase
{
    protected function setUp(): void
    {
        SessionAccountHandler::setAccount([
            'id' => 42,
            'username' => 'test',
            'language' => 'en',
            'timezone' => '43',
            'mail' => 'test@example.test',
        ]);
    }

    private function createExporter(): ConcreteFileExporterForTest
    {
        return new ConcreteFileExporterForTest(null);
    }

    public function testToResponseReturnsASymfonyResponseWithTheFileContentAsBody(): void
    {
        $exporter = $this->createExporter();

        $response = $exporter->toResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('fixed test file content', $response->getContent());
    }

    public function testToResponseSetsTheContentTypeHeaderFromMimeType(): void
    {
        $exporter = $this->createExporter();

        $response = $exporter->toResponse();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    public function testToResponseSetsAnAttachmentContentDispositionWithTheFilename(): void
    {
        $exporter = $this->createExporter();

        $response = $exporter->toResponse();

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment', $contentDisposition);
        $this->assertStringContainsString('undefined.txt', (string) $contentDisposition);
    }

    public function testToResponseCallsCreateFileExactlyOnce(): void
    {
        $exporter = new class(null) extends AbstractFileExporter {
            public int $CreateFileCallCount = 0;

            public function extension(): string
            {
                return 'txt';
            }

            protected function createFile(): void
            {
                ++$this->CreateFileCallCount;
                $this->FileContent = 'content';
            }

            public function enum(): int
            {
                return 0;
            }

            public function isPossible(): bool
            {
                return true;
            }

            protected function mimeType(): string
            {
                return 'text/plain';
            }
        };

        $exporter->toResponse();

        $this->assertSame(1, $exporter->CreateFileCallCount);
    }

    public function testFilenameFallsBackToUndefinedWhenContextIsNull(): void
    {
        $exporter = $this->createExporter();

        $this->assertSame('undefined.txt', $exporter->filename());
    }

    public function testFileContentReturnsEmptyStringBeforeCreateFileWasCalled(): void
    {
        $exporter = $this->createExporter();

        $this->assertSame('', $exporter->fileContent());
    }

    public function testCreateFileWithoutDirectDownloadPopulatesFileContent(): void
    {
        $exporter = $this->createExporter();

        $exporter->createFileWithoutDirectDownload();

        $this->assertSame('fixed test file content', $exporter->fileContent());
    }
}

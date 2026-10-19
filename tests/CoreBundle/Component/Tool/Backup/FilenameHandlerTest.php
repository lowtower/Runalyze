<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;

final class FilenameHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testThatCreatedFilenamesAreUnique(): void
    {
        $Handler = new FilenameHandler(42);

        $this->assertNotSame(
            $Handler->generateInternalFilename('sql.gz'),
            $Handler->generateInternalFilename('sql.gz')
        );
    }

    public function testThatGeneratedInternalFilenameIsValid(): void
    {
        $Handler = new FilenameHandler(42);

        $this->assertTrue($Handler->validateInternalFilename($Handler->generateInternalFilename('sql.gz')));
    }

    public function testThatGeneratedPublicFilenameIsValid(): void
    {
        $Handler = new FilenameHandler(42);
        $internalFilename = $Handler->generateInternalFilename('sql.gz');

        $this->assertTrue($Handler->validatePublicFilename($Handler->generatePublicFilename($internalFilename)));
    }

    public function testThatFilenamesAreUserSpecific(): void
    {
        $HandlerA = new FilenameHandler(42);
        $HandlerB = new FilenameHandler(32);

        $internalFilenameA = $HandlerA->generateInternalFilename('sql.gz');
        $internalFilenameB = $HandlerB->generateInternalFilename('sql.gz');

        $this->assertNotSame($internalFilenameA, $internalFilenameB);
        $this->assertFalse($HandlerA->validateInternalFilename($internalFilenameB));
        $this->assertFalse($HandlerB->validateInternalFilename($internalFilenameA));
    }

    public function testValidateFileExtension(): void
    {
        $this->assertTrue(FilenameHandler::validateFileExtension('foobar.' . FilenameHandler::JSON_FORMAT));
        $this->assertTrue(FilenameHandler::validateFileExtension('foobar.' . FilenameHandler::SQL_FORMAT));
        $this->assertFalse(FilenameHandler::validateFileExtension('foobar.txt'));
    }

    public function testValidateImportFileExtension(): void
    {
        $this->assertTrue(FilenameHandler::validateImportFileExtension('foobar.' . FilenameHandler::JSON_FORMAT));
        $this->assertFalse(FilenameHandler::validateImportFileExtension('foobar.' . FilenameHandler::SQL_FORMAT));
        $this->assertFalse(FilenameHandler::validateImportFileExtension('foobar.txt'));
    }

    public function testGeneratePublicFilenameAndTransformBackAreNotTrueInverses(): void
    {
        $handler = new FilenameHandler(42);
        $internalFilename = $handler->generateInternalFilename(FilenameHandler::SQL_FORMAT);

        $publicFilename = $handler->generatePublicFilename($internalFilename);
        $reconstructed = $handler->transformPublicToInternalFilename($publicFilename);

        $this->assertSame(
            $internalFilename,
            $reconstructed,
            'generatePublicFilename() followed by transformPublicToInternalFilename() ' .
            'must reconstruct the EXACT original internal filename, since ' .
            'BackupToolController relies on this for an exact Filesystem::exists() ' .
            'check.'
        );
    }

    public function testGeneratePublicFilenameDoesNotRetainALeadingDash(): void
    {
        $handler = new FilenameHandler(42);
        $internalFilename = $handler->generateInternalFilename(FilenameHandler::SQL_FORMAT);
        $publicFilename = $handler->generatePublicFilename($internalFilename);

        $this->assertStringStartsNotWith('-', $publicFilename);
    }
}

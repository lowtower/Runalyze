<?php

declare(strict_types=1);

namespace Runalyze\Export\File;

use Runalyze\View\Activity\FakeContext;

class FitlogTest extends \PHPUnit\Framework\TestCase
{
    public function testFileCreationForEmptyContext(): void
    {
        $Exporter = new Fitlog(FakeContext::emptyContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<FitnessWorkbook', $content);

        new \SimpleXMLElement($content);

        // emptyContext() does not have Trackdata -> setTrack() returns early, no <Track>
        $this->assertStringNotContainsString('<Track', $content);
    }

    public function testFileCreationForIndoorActivity(): void
    {
        $Exporter = new Fitlog(FakeContext::indoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<FitnessWorkbook', $content);
        $this->assertStringContainsString('<Track', $content);
        $this->assertStringContainsString('hr="', $content);

        // indoorContext() does not have route/position -> no lat/lon/ele attributes
        $this->assertStringNotContainsString('lat="', $content);
        $this->assertStringNotContainsString('lon="', $content);
        $this->assertStringNotContainsString('ele="', $content);
    }

    public function testFileCreationForOutdoorActivity(): void
    {
        $Exporter = new Fitlog(FakeContext::outdoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<FitnessWorkbook', $content);
        $this->assertStringContainsString('<Track', $content);
        $this->assertStringContainsString('hr="', $content);
        $this->assertStringContainsString('lat="', $content);
        $this->assertStringContainsString('lon="', $content);
        $this->assertStringContainsString('ele="', $content);
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Export\File;

use Runalyze\View\Activity\FakeContext;

class GpxTest extends \PHPUnit\Framework\TestCase
{
    public function testFileCreationForOutdoorActivity(): void
    {
        $Exporter = new Gpx(FakeContext::outdoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<gpx', $content);
        $this->assertStringContainsString('<trkpt', $content);

        $this->assertInstanceOf(\SimpleXMLElement::class, new \SimpleXMLElement($content));
    }
}

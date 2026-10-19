<?php

declare(strict_types=1);

namespace Runalyze\Export\File;

use Runalyze\View\Activity\FakeContext;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldFactory')]
final class KmlTest extends \PHPUnit\Framework\TestCase
{
    public function testFileCreationForOutdoorActivity(): void
    {
        $Exporter = new Kml(FakeContext::outdoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<kml', $content);
        $this->assertStringContainsString('<coordinates>', $content);

        $xml = new \SimpleXMLElement($content);

        // exactly on segment, because outdoorContext() does not contain pauses
        $this->assertCount(1, $xml->Folder->Placemark->MultiGeometry->LineString);

        // all 6 trackpoints from the fixture should exist as coordinate line
        $coordinates = trim((string) $xml->Folder->Placemark->MultiGeometry->LineString->coordinates);
        $lines = explode(NL, $coordinates);
        $this->assertCount(6, array_filter($lines, fn ($line): bool => $line !== ''));
    }
}

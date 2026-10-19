<?php

declare(strict_types=1);

namespace Runalyze\Export\File;

use PHPUnit\Framework\TestCase;
use Runalyze\View\Activity\FakeContext;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;

final class GpxTest extends TestCase
{
    public function testFileCreationForOutdoorActivity(): void
    {
        $Exporter = new Gpx(FakeContext::outdoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<gpx', $content);
        $this->assertStringContainsString('<trkpt', $content);

        $this->assertInstanceOf(SimpleXMLElement::class, new SimpleXMLElement($content));
    }

    public function testToResponseReturnsTheSameContentAsCreateFileWithoutDirectDownload(): void
    {
        $Exporter = new Gpx(FakeContext::outdoorContext());

        $response = $Exporter->toResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('<gpx', (string) $response->getContent());
        $this->assertStringContainsString('<trkpt', (string) $response->getContent());
        $this->assertSame('application/gpx+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.gpx', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function testIsPossibleIsTrueForAnOutdoorActivityWithPositionData(): void
    {
        $Exporter = new Gpx(FakeContext::outdoorContext());

        $this->assertTrue($Exporter->isPossible());
    }
}

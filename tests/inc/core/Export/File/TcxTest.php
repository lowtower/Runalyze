<?php

namespace Runalyze\Export\File;

use Runalyze\Model\Activity;
use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;
use Runalyze\Parser\Activity\FileType\Tcx as TcxParser;
use Runalyze\View\Activity\FakeContext;

class TcxTest extends \PHPUnit\Framework\TestCase
{
    public function testThatEmptyContextIsNotPossible(): void
    {
        $Exporter = new Tcx(FakeContext::emptyContext());

        $this->assertFalse($Exporter->isPossible());
    }

    public function testFileCreationForOutdoorActivity(): void
    {
        $Exporter = new Tcx(FakeContext::outdoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<TrainingCenterDatabase', $content);

        $xml = new \SimpleXMLElement($content);
        $Activity = $xml->Activities->Activity;

        // outdoorContext() has distance of 2.0 km -> at least one lap
        $this->assertGreaterThan(0, count($Activity->Lap));

        // at least one trackpoint should exist over all laps
        $trackpointCount = 0;
        foreach ($Activity->Lap as $Lap) {
            $trackpointCount += count($Lap->Track->Trackpoint);
        }

        $this->assertGreaterThan(0, $trackpointCount);

        // outdoorContext() provides HEARTRATE, CADENCE, GEOHASHES, ELEVATIONS_ORIGINAL -> each once expected in content
        // pace is derived from Distance+Time -> Extensions/TPX/Speed are created
        $this->assertStringContainsString('<HeartRateBpm>', $content);
        $this->assertStringContainsString('<Cadence>', $content);
        $this->assertStringContainsString('<Position>', $content);
        $this->assertStringContainsString('<AltitudeMeters>', $content);
        $this->assertStringContainsString('<Extensions>', $content);
        $this->assertStringContainsString('ns3:Speed', $content);

        // no power-sensor in the fixture
        $this->assertStringNotContainsString('ns3:Watts', $content);
    }

    public function testFileCreationForIndoorActivity(): void
    {
        $Exporter = new Tcx(FakeContext::indoorContext());
        $Exporter->createFileWithoutDirectDownload();

        $content = $Exporter->fileContent();

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('<TrainingCenterDatabase', $content);

        $xml = new \SimpleXMLElement($content);
        $Activity = $xml->Activities->Activity;

        $this->assertGreaterThan(0, count($Activity->Lap));

        $trackpointCount = 0;
        foreach ($Activity->Lap as $Lap) {
            $trackpointCount += count($Lap->Track->Trackpoint);
        }

        $this->assertGreaterThan(0, $trackpointCount);

        // indoorContext() provides HEARTRATE, but no route, no cadence
        $this->assertStringContainsString('<HeartRateBpm>', $content);
        $this->assertStringNotContainsString('<Position>', $content);
        $this->assertStringNotContainsString('<AltitudeMeters>', $content);
        $this->assertStringNotContainsString('<Cadence>', $content);
        $this->assertStringNotContainsString('<Extensions>', $content);
    }

    public function testMinimalExampleWithOnlyTime(): void
    {
        $Exporter = new Tcx(FakeContext::withDefaultSport(
            new Activity\Entity([
                Activity\Entity::TIMESTAMP => time(),
                Activity\Entity::TIMESTAMP_CREATED => time(),
                Activity\Entity::TIME_IN_SECONDS => 600,
            ]),
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [100, 200, 300, 400, 500, 600],
            ])
        ));
        $Exporter->createFileWithoutDirectDownload();

        $parser = new TcxParser();
        $parser->setFileContent($Exporter->fileContent());
        $parser->parse();

        $this->assertSame(1, $parser->getNumberOfActivities());
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Time);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Distance);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->HeartRate);
    }

    public function testMinimalExampleWithTimeAndHeartrate(): void
    {
        $Exporter = new Tcx(FakeContext::withDefaultSport(
            new Activity\Entity([
                Activity\Entity::TIMESTAMP => time(),
                Activity\Entity::TIMESTAMP_CREATED => time(),
                Activity\Entity::TIME_IN_SECONDS => 600,
            ]),
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [100, 200, 300, 400, 500, 600],
                Trackdata\Entity::HEARTRATE => [120, 130, 140, 140, 150, 160],
            ])
        ));
        $Exporter->createFileWithoutDirectDownload();

        $parser = new TcxParser();
        $parser->setFileContent($Exporter->fileContent());
        $parser->parse();

        $this->assertSame(1, $parser->getNumberOfActivities());
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Time);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->HeartRate);
    }

    public function testMinimalExampleWithCompleteTrackdata(): void
    {
        $Exporter = new Tcx(FakeContext::withDefaultSport(
            new Activity\Entity([
                Activity\Entity::TIMESTAMP => time(),
                Activity\Entity::TIMESTAMP_CREATED => time(),
                Activity\Entity::TIME_IN_SECONDS => 600,
            ]),
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [100, 200, 300, 400, 500, 600],
                Trackdata\Entity::HEARTRATE => [120, 130, 140, 140, 150, 160],
                Trackdata\Entity::DISTANCE => [0.4, 0.8, 1.2, 1.6, 2.0, 2.4],
                Trackdata\Entity::CADENCE => [90, 90, 90, 90, 90, 90],
                Trackdata\Entity::POWER => [100, 150, 150, 200, 150, 100],
            ])
        ));
        $Exporter->createFileWithoutDirectDownload();

        $parser = new TcxParser();
        $parser->setFileContent($Exporter->fileContent());
        $parser->parse();

        $this->assertSame(1, $parser->getNumberOfActivities());
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Time);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Distance);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->HeartRate);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Cadence);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Power);
    }

    public function testMinimalExampleWithOnlyElevationForRoute(): void
    {
        $Exporter = new Tcx(FakeContext::withDefaultSport(
            new Activity\Entity([
                Activity\Entity::TIMESTAMP => time(),
                Activity\Entity::TIMESTAMP_CREATED => time(),
                Activity\Entity::TIME_IN_SECONDS => 600,
            ]),
            new Trackdata\Entity([
                Trackdata\Entity::TIME => [100, 200, 300, 400, 500, 600],
                Trackdata\Entity::HEARTRATE => [120, 130, 140, 140, 150, 160],
            ]),
            new Route\Entity([
                Route\Entity::ELEVATIONS_ORIGINAL => [230, 235, 232, 235, 225, 230],
            ])
        ));
        $Exporter->createFileWithoutDirectDownload();

        $parser = new TcxParser();
        $parser->setFileContent($Exporter->fileContent());
        $parser->parse();

        $this->assertSame(1, $parser->getNumberOfActivities());
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Time);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Distance);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->HeartRate);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Altitude);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Latitude);
        $this->assertNotEmpty($parser->getActivityDataContainer()->ContinuousData->Longitude);
    }
}

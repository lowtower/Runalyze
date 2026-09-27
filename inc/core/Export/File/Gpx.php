<?php

namespace Runalyze\Export\File;

use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;
use Runalyze\Util\LocalTime;
use SimpleXMLElement;

/**
 * Create exporter for gpx files
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\File
 */
class Gpx extends AbstractFileExporter
{
    protected SimpleXMLElement $XML;

    protected SimpleXMLElement $Track;

    public function isPossible(): bool
    {
        return $this->Context->hasRoute() && $this->Context->route()
            ->hasPositionData();
    }

    public function extension(): string
    {
        return 'gpx';
    }

    protected function mimeType(): string
    {
        return 'application/gpx+xml';
    }

    public function enum(): int
    {
        return Types::GPX;
    }

    protected function createFile(): void
    {
        $this->XML = new SimpleXMLElement($this->emptyXml());
        $this->Track = $this->XML->trk->trkseg;

        $this->setTrack();

        $this->FileContent = $this->XML->asXML();

        $this->formatFileContentAsXML();
    }

    final protected function timeToString(?int $time): string
    {
        return LocalTime::date('c', $time);
    }

    protected function setTrack(): void
    {
        $Starttime = $this->Context->activity()
            ->timestamp();

        $Trackdata = new Trackdata\Loop($this->Context->trackdata());
        $Route = new Route\Loop($this->Context->route());

        $hasElevation = $this->Context->route()
            ->hasOriginalElevations();
        $hasHeartrate = $this->Context->trackdata()
            ->has(Trackdata\Entity::HEARTRATE);

        do {
            $coordinate = $Route->coordinate();
            $Trackpoint = $this->Track->addChild('trkpt');
            $Trackpoint->addChild('time', $this->timeToString($Starttime + $Trackdata->time()));

            if (abs((float) $coordinate->getLatitude()) > 1e-5 || abs((float) $coordinate->getLongitude()) > 1e-5) {
                $Trackpoint->addAttribute('lat', $coordinate->getLatitude());
                $Trackpoint->addAttribute('lon', $coordinate->getLongitude());
            }

            if ($hasElevation) {
                $Trackpoint->addChild('ele', $Route->current(Route\Entity::ELEVATIONS_ORIGINAL));
            }

            if ($hasHeartrate) {
                $ext = $Trackpoint->addChild('extensions');
                $tpe = $ext->addChild('gpxtpx:TrackPointExtension', '', 'http://www.garmin.com/xmlschemas/TrackPointExtension/v1');
                $tpe->addChild('gpxtpx:hr', $Trackdata->current(Trackdata\Entity::HEARTRATE));
            }
        } while ($Trackdata->nextStep() && $Route->nextStep());
    }

    protected function emptyXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<gpx version="1.1" creator="Runalyze"
  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd"
  xmlns="http://www.topografix.com/GPX/1/1"
  xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1"
  xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <metadata />
 <trk>
  <name />
  <cmt />
  <trkseg>
  </trkseg>
 </trk>
</gpx>';
    }
}

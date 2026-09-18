<?php

/**
 * This file contains class::Kml
 * @package Runalyze\Export\File
 */

namespace Runalyze\Export\File;

use League\Geotools\Coordinate\CoordinateInterface;
use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;

/**
 * Create exporter for kml files
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\File
 */
class Kml extends AbstractFileExporter
{
    /**
     * @var \SimpleXMLElement
     */
    protected $XML;

    /**
     * @var string
     */
    protected $CurrentPath = '';

    /**
     * @var int
     */
    protected $CurrentPauseIndex = 0;

    /**
     * @var int
     */
    protected $CurrentPauseTime = 0;

    /**
     * @var int
     */
    protected $NumPauses = 0;

    /**
     * @var \Runalyze\Model\Route\Loop
     */
    protected $RouteLoop;

    /**
     * @var \Runalyze\Model\Trackdata\Loop
     */
    protected $TrackdataLoop;

    /**
     * @var \Runalyze\Model\Trackdata\Pauses
     */
    protected $Pauses;

    public function isPossible(): bool
    {
        return $this->Context->hasRoute() && $this->Context->route()
            ->hasPositionData();
    }

    /**
     * Get extension
     */
    public function extension(): string
    {
        return 'kml';
    }

    protected function mimeType(): string
    {
        return 'application/vnd.google-earth.kml+xml';
    }

    public function enum(): int
    {
        return Types::KML;
    }

    /**
     * Export
     */
    protected function createFile()
    {
        $this->XML = new \SimpleXMLElement($this->emptyXml());

        $this->setGeneralInfo();
        $this->setTrack();

        $this->FileContent = $this->XML->asXML();

        $this->formatFileContentAsXML();
    }

    protected function setGeneralInfo()
    {
        $this->XML->Folder->name = $this->nameForKml();
        $this->XML->Folder->Placemark->name = $this->nameForKml();
        $this->XML->Folder->Placemark->Style->geomColor = self::rgbToKmlColor('#FF5500');
    }

    protected function setTrack()
    {
        $this->prepareLoop();

        do {
            if ($this->thereWasAPause()) {
                $this->setPauseToXml();
            }

            $this->addCoordinateToCurrentPath($this->RouteLoop->coordinate());
        } while ($this->RouteLoop->nextStep() && $this->TrackdataLoop->nextStep());

        $this->addCurrentPathToXml();
    }

    protected function prepareLoop()
    {
        $this->RouteLoop = new Route\Loop($this->Context->route());
        $this->TrackdataLoop = new Trackdata\Loop($this->Context->trackdata());
        $this->Pauses = $this->Context->trackdata()
            ->pauses();
        $this->NumPauses = $this->Pauses->num();

        if ($this->NumPauses > 0) {
            $this->CurrentPauseTime = $this->Pauses->at(0)
                ->time();
        }
    }

    protected function thereWasAPause(): bool
    {
        return ($this->CurrentPauseIndex < $this->NumPauses) && ($this->CurrentPauseTime < $this->TrackdataLoop->time());
    }

    protected function setPauseToXml()
    {
        $this->addCurrentPathToXml();
        $this->CurrentPauseIndex++;

        if ($this->NumPauses > $this->CurrentPauseIndex) {
            $this->CurrentPauseTime = $this->Pauses->at($this->CurrentPauseIndex)
                ->time();
        }
    }

    protected function addCoordinateToCurrentPath(CoordinateInterface $coordinate)
    {
        if (abs((float) $coordinate->getLatitude()) > 1e-5 || abs((float) $coordinate->getLongitude()) > 1e-5) {
            $this->CurrentPath .= $coordinate->getLongitude() . ',' . $coordinate->getLatitude() . NL;
        }
    }

    /**
     * Add current path as coordinates child
     */
    protected function addCurrentPathToXml()
    {
        $LineString = $this->XML->Folder->Placemark->MultiGeometry->addChild('LineString');
        $LineString->addChild('coordinates', $this->CurrentPath);

        $this->CurrentPath = '';
    }

    /**
     * Get name for route in kml-file
     */
    protected function nameForKml(): string
    {
        return $this->Context->dataview()
            ->date('Y-m-d') . ': ' . $this->Context->dataview()->titleWithComment();
    }

    /**
     * Get empty xml
     */
    protected function emptyXml(): string
    {
        return '<kml xmlns="http://earth.google.com/kml/2.0">
  <Folder>
    <open>1</open>
    <name></name>
    <Placemark>
      <visibility>1</visibility>
      <name></name>
      <Style>
        <geomScale>2</geomScale>
        <geomColor></geomColor>
      </Style>
      <MultiGeometry></MultiGeometry>
    </Placemark>
  </Folder>
</kml>';
    }

    /**
     * Transform rgb-color (i.e. #FF7700 or FF7700) to abgr-color needed for kml
     * @param string $rgb
     */
    public static function rgbToKmlColor($rgb): string
    {
        if (strlen($rgb) === 7) {
            $rgb = substr($rgb, 1);
        }

        return 'ff' . substr($rgb, 4, 2) . substr($rgb, 2, 2) . substr($rgb, 0, 2);
    }
}

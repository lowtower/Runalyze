<?php

/**
 * This file contains class::Route
 * @package Runalyze\View\Leaflet
 */

namespace Runalyze\View\Leaflet;

use League\Geotools\Geohash\Geohash;
use League\Geotools\Geotools;

/**
 * Leaflet route
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Leaflet
 */
class Route
{
    protected ?int $id;

    /**
     * JS-marker
     * @var string[]
     */
    protected $Marker = [];

    /**
     * Paths
     * @var array
     */
    protected $Paths = [];

    /**
     * Infos
     *
     * Information to be displayed on mouseover.
     * First entry has to be distance.
     *
     * @var array
     */
    protected $Infos = [];

    /**
     * Labels for infos
     *
     * Labels for `$Infos`. Can be false to hide on mouseover.
     *
     * @var array
     */
    protected $InfoLabels = [];

    /**
     * Functions to style info
     * @var array
     */
    protected $InfoFunctions = [];

    /**
     * Options
     * @var array
     */
    protected $Options = [];

    /**
     * Construct new route
     */
    public function __construct(string $id)
    {
        $this->id = (int) $id;
    }

    final public function id(): int
    {
        return $this->id;
    }

    public function isEmpty(): bool
    {
        return $this->Paths === [];
    }

    /**
     * Get JS
     */
    final public function js(): string
    {
        $Options = 'segments: ' . json_encode($this->Paths);

        if (count($this->Paths) === count($this->Infos)) {
            $Options .= ', segmentsInfoLabels: ' . json_encode($this->InfoLabels);
            $Options .= ', segmentsInfo: ' . json_encode($this->Infos);
            $Options .= ', segmentsInfoFunctions: [' . implode(',', $this->InfoFunctions) . ']';
        }

        $Options .= ', markertopush: [' . implode(',', $this->Marker) . ']';

        foreach ($this->Options as $property => $value) {
            $Options .= ', ' . $property . ': ' . json_encode($value);
        }

        return "RunalyzeLeaflet.Routes.addRoute('" . $this->id . "', {" . $Options . '});';
    }

    /**
     * @param string $property
     * @param mixed $value
     */
    final public function addOption($property, $value): void
    {
        $this->Options[$property] = $value;
    }

    /**
     * Caution: Set infos for all paths or for none!
     *
     * @param array $Info [optional] must be of the same size as $Path
     */
    final public function addSegment(array $Path, array $Info = []): void
    {
        if ($Path === []) {
            return;
        }

        $this->Paths[] = $Path;

        if ($Info !== []) {
            $this->Infos[] = $Info;
        }
    }

    /**
     * @param float $Lat
     * @param float $Lng
     * @param string $Icon JS-icon
     * @param string $Tooltip [optional]
     */
    final public function addMarker($Lat, $Lng, $Icon, $Tooltip = ''): void
    {
        $this->Marker[] = 'L.marker([' . $Lat . ',' . $Lng . '], {icon: ' . $Icon . ', tooltip: ' . json_encode($Tooltip) . '})';
    }

    /**
     * Add marker
     * @param string $geohash
     * @param string $Icon JS-icon
     * @param string $Tooltip [optional]
     */
    final public function addMarkerGeohash($geohash, $Icon, $Tooltip = ''): void
    {
        if ($geohash == '7zzzzzzzzzzz') {
            return;
        }

        /** @var Geohash $geohashObj */
        $geohashObj = new Geotools()->geohash()
            ->decode($geohash);
        $coordinate = $geohashObj->getCoordinate();

        $this->Marker[] = 'L.marker([' . $coordinate->getLatitude() . ',' . $coordinate->getLongitude() . '], {icon: ' . $Icon . ', tooltip: ' . json_encode($Tooltip) . '})';
    }

    /**
     * @param int $km
     */
    final public function distIcon($km): string
    {
        return 'RunalyzeLeaflet.Routes.distIcon(' . (int) $km . ')';
    }

    final public function startIcon(): string
    {
        return 'RunalyzeLeaflet.Routes.startIcon()';
    }

    final public function endIcon(): string
    {
        return 'RunalyzeLeaflet.Routes.endIcon()';
    }

    final public function pauseIcon(): string
    {
        return 'RunalyzeLeaflet.Routes.pauseIcon()';
    }
}

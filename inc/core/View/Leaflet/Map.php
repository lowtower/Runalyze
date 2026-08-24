<?php

declare(strict_types=1);

/**
 * This file contains class::Map
 * @package Runalyze\View\Leaflet
 */

namespace Runalyze\View\Leaflet;

use Runalyze\Configuration;

/**
 * Leaflet map
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Leaflet
 */
class Map
{
	/**
	 * HTML id
	 * @var string
	 */
	protected $id = '';

	/**
	 * Height
	 * @var int
	 */
	protected $height = 0;

	/**
	 * Width
	 * @var int
	 */
	protected $width = 0;

	/**
	 * Routes
	 * @var \Runalyze\View\Leaflet\Route[]
	 */
	protected $Routes = [];

	/**
	 * Boundaries
	 * @var array
	 */
	protected $Bounds = [];

	/**
	 * Construct new map
	 * @param string $containerID
	 * @param int $height [optional]
	 * @param int $width [optional]
	 */
	public function __construct($containerID, $height = 0, $width = 0) {
		$this->id = $containerID;
		$this->height = $height;
		$this->width = $width;
	}

    public function addRoute(Route $route) {
	    if (! $route->isEmpty()) {
    		$this->Routes[] = $route;
        }
	}

    public function setBounds(array $bounds) {
		$this->Bounds = $bounds;
	}

	public function display() {
		echo $this->code();
	}

    /**
     * Get code
     */
    public function code(): string {
		return $this->html() . $this->js();
	}

    /**
     * Get HTML
     */
    public function html(): string {
		$style = '';

		if ($this->height > 0)
			$style .= 'height:' . (int) $this->height . 'px;';

		if ($this->width > 0)
			$style .= 'width:' . (int) $this->width . 'px;';

		if ($style !== '')
			$style = ' style="' . $style . '"';

		return '<div id="' . $this->id . '" class="map"' . $style . '></div>';
	}

    /**
     * Get JS
     */
    public function js(): string {
	    if ($this->Routes === []) {
	        return $this->getJavaScriptForNoRoutes();
        }

		$scrollOnZoom = Configuration::ActivityView()->mapZoomOnScroll();
		$Code = 'RunalyzeLeaflet.setDefaultLayer("' . Configuration::ActivityView()->mapLayer() . '");';
		$Code .= "RunalyzeLeaflet.init('" . $this->id . "', { scrollWheelZoom: " . ($scrollOnZoom ? 'true' : 'false') . '} );';

		foreach ($this->Routes as $Route) {
			$Code .= $Route->js();
		}

		if ($this->Bounds !== []) {
			$Code .= 'RunalyzeLeaflet.map().fitBounds([[' . $this->Bounds['lat.min'] . ',' . $this->Bounds['lng.min'] . '],[' . $this->Bounds['lat.max'] . ',' . $this->Bounds['lng.max'] . ']]);';
		}

		if ($this->Routes !== []) {
	        $Code .= 'RunalyzeLeaflet.Routes.routeid="' . $this->Routes[0]->id() . '";';
		}

		$Code = '(function(RL,R){' . str_replace('RunalyzeLeaflet', 'RL', str_replace('RunalyzeLeaflet.Routes', 'R', $Code)) . '})(RunalyzeLeaflet, RunalyzeLeaflet.Routes);';

		return '<script>Runalyze.try(' .
            'function(){' . $Code . '}, ' .
            'function(){$("#' . $this->id . '").css("height","auto").html(' . json_encode('<p><em>' . __('There was a problem trying to show the map.') . '</em></p>') . ');' .
        '});</script>';
	}

	protected function getJavaScriptForNoRoutes(): string {
	    return '<script>(function(){$("#' . $this->id . '").remove();})();</script>';
    }
}

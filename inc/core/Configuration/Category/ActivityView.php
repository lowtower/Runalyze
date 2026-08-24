<?php

/**
 * This file contains class::ActivityView
 * @package Runalyze\Configuration\Category
 */

namespace Runalyze\Configuration\Category;

use Ajax;
use Runalyze\Configuration\Fieldset;
use Runalyze\Parameter\Application\ActivityPlotMode;
use Runalyze\Parameter\Application\ActivityPlotPrecision;
use Runalyze\Parameter\Application\ActivityRouteBreak;
use Runalyze\Parameter\Application\ActivityRoutePrecision;
use Runalyze\Parameter\Application\ElevationMethod;
use Runalyze\Parameter\Application\PaceAxisMaximum;
use Runalyze\Parameter\Application\PaceAxisMinimum;
use Runalyze\Parameter\Application\PaceAxisType;
use Runalyze\Parameter\Boolean;
use Runalyze\Parameter\Integer;
use Runalyze\Parameter\Select;
use Runalyze\Parameter\Textline;

/**
 * Configuration category: Activity view
 * @author Hannes Christiansen
 * @package Runalyze\Configuration\Category
 */
class ActivityView extends \Runalyze\Configuration\Category
{
    /**
     * Internal key
     */
    protected function key(): string {
		return 'activity-view';
	}

	protected function createHandles() {
		$this->createMapOptions();
		$this->createPlotOptions();
		$this->createPacePlotOptions();
		$this->createOtherOptions();
	}

	/**
	 * Create: map options
	 * - GMAP_PATH_PRECISION
	 * - GMAP_PATH_BREAK
	 * - TRAINING_LEAFLET_LAYER
	 */
	protected function createMapOptions() {
		$this->createHandle('GMAP_PATH_PRECISION', new ActivityRoutePrecision());
		$this->createHandle('GMAP_PATH_BREAK', new ActivityRouteBreak());

		$this->createHandle('TRAINING_LEAFLET_LAYER', new Textline('OpenStreetMap'));
		$this->createHandle('TRAINING_MAP_SHOW_FIRST', new Boolean(false));
		$this->createHandle('TRAINING_MAP_ZOOM_ON_SCROLL', new Boolean(false));
	}

	/**
	 * @return ActivityRoutePrecision
	 */
	public function routePrecision() {
		return $this->object('GMAP_PATH_PRECISION');
	}

	/**
	 * @return ActivityRouteBreak
	 */
	public function routeBreak() {
		return $this->object('GMAP_PATH_BREAK');
	}

	/**
	 * Show map first
	 * @return string
	 */
	public function mapFirst() {
		return $this->get('TRAINING_MAP_SHOW_FIRST');
	}

	/**
	 * @return string
	 */
	public function mapLayer() {
		return $this->get('TRAINING_LEAFLET_LAYER');
	}

	/**
	 * @param string $layer
	 */
	public function updateLayer($layer) {
		$this->object('TRAINING_LEAFLET_LAYER')
		    ->set($layer);
		$this->updateValue($this->handle('TRAINING_LEAFLET_LAYER'));
	}

	/**
	 * Map: zoom on scroll
	 * @return bool
	 */
	public function mapZoomOnScroll() {
		return $this->get('TRAINING_MAP_ZOOM_ON_SCROLL');
	}

	/**
	 * Create: Plot options
	 * - TRAINING_PLOT_SMOOTH
	 * - TRAINING_PLOT_MODE
	 * - TRAINING_PLOT_PRECISION
	 */
	protected function createPlotOptions() {
		$this->createHandle('TRAINING_PLOT_SMOOTH', new Boolean(false));
		$this->createHandle('TRAINING_PLOT_XAXIS_TIME', new Boolean(false));

		$this->createHandle('TRAINING_PLOT_MODE', new ActivityPlotMode());
		$this->createHandle('TRAINING_PLOT_PRECISION', new ActivityPlotPrecision());

		$this->createHandle('TRAINING_PLOT_SPLITS_ZERO', new Boolean(true));
	}

	/**
	 * Plot: smooth curves
	 * @return bool
	 */
	public function smoothCurves() {
		return $this->get('TRAINING_PLOT_SMOOTH');
	}

	/**
	 * Plot: use time as x-axis
	 * @return bool
	 */
	public function usesTimeAsXAxis() {
		return $this->get('TRAINING_PLOT_XAXIS_TIME');
	}

	/**
	 * @return ActivityPlotMode
	 */
	public function plotMode() {
		return $this->object('TRAINING_PLOT_MODE');
	}

	/**
	 * @return ActivityPlotPrecision
	 */
	public function plotPrecision() {
		return $this->object('TRAINING_PLOT_PRECISION');
	}

	/**
	 * @return bool
	 */
	public function startSplitsAtZero() {
		return $this->get('TRAINING_PLOT_SPLITS_ZERO');
	}

	/**
	 * Create: Plot options for pace
	 * - PACE_Y_LIMIT_MAX
	 * - PACE_Y_LIMIT_MIN
	 * - PACE_Y_AXIS_TYPE
	 * - PACE_HIDE_OUTLIERS
	 */
	protected function createPacePlotOptions() {
		$this->createHandle('PACE_Y_LIMIT_MIN', new PaceAxisMinimum());
		$this->createHandle('PACE_Y_LIMIT_MAX', new PaceAxisMaximum());

		$this->createHandle('PACE_Y_AXIS_TYPE', new PaceAxisType());

		$this->createHandle('PACE_HIDE_OUTLIERS', new Boolean(false));
	}

	/**
	 * Pace: Y axis minimum
	 * @return PaceAxisMinimum
	 */
	public function paceYaxisMinimum() {
		return $this->object('PACE_Y_LIMIT_MIN');
	}

	/**
	 * Pace: Y axis maximum
	 * @return PaceAxisMaximum
	 */
	public function paceYaxisMaximum() {
		return $this->object('PACE_Y_LIMIT_MAX');
	}

	/**
	 * Reverse pace axis
	 * @return PaceAxisType
	 */
	public function paceAxisType() {
		return $this->object('PACE_Y_AXIS_TYPE');
	}

	/**
	 * @return bool
	 */
	public function ignorePaceOutliers() {
		return $this->get('PACE_HIDE_OUTLIERS');
	}

	/**
	 * Create: Plot options
	 * - TRAINING_DECIMALS
	 * - SHOW_SECTIONS_FULLHEIGHT
	 * - ELEVATION_METHOD
	 * - ELEVATION_TRESHOLD
	 */
	protected function createOtherOptions() {
		$this->createHandle('TRAINING_DECIMALS', new Select('1', [
    'options' => ['0', '1', '2', '3'],
]));

		$this->createHandle('SHOW_SECTIONS_FULLHEIGHT', new Boolean(false));

		$this->createHandle('ELEVATION_METHOD', new ElevationMethod());

		$this->createHandle('ELEVATION_TRESHOLD', new Integer(3));
	}

    /**
     * Decimals to display
     */
    public function decimals(): int {
		return (int) $this->get('TRAINING_DECIMALS');
	}

	/**
	 * Show sections always in fullheight
	 * @return bool
	 */
	public function showSectionsFullheight() {
		return $this->get('SHOW_SECTIONS_FULLHEIGHT');
	}

	/**
	 * @return ElevationMethod
	 */
	public function elevationMethod() {
		return $this->object('ELEVATION_METHOD');
	}

	/**
	 * @return int
	 */
	public function elevationMinDiff() {
		return $this->get('ELEVATION_TRESHOLD');
	}

	protected function registerOnchangeEvents() {
		$this->handle('TRAINING_PLOT_MODE')
		    ->registerOnchangeFlag(Ajax::$RELOAD_DATABROWSER_AND_TRAINING);
		$this->handle('TRAINING_MAP_SHOW_FIRST')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('TRAINING_MAP_ZOOM_ON_SCROLL')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);

		$this->handle('TRAINING_PLOT_XAXIS_TIME')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('TRAINING_PLOT_MODE')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('TRAINING_PLOT_SMOOTH')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('PACE_Y_LIMIT_MIN')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('PACE_Y_LIMIT_MAX')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('PACE_Y_AXIS_TYPE')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('PACE_HIDE_OUTLIERS')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('TRAINING_PLOT_SPLITS_ZERO')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);

		$this->handle('TRAINING_PLOT_PRECISION')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);

		$this->handle('GMAP_PATH_BREAK')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);
		$this->handle('GMAP_PATH_PRECISION')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);

		$this->handle('SHOW_SECTIONS_FULLHEIGHT')
		    ->registerOnchangeFlag(Ajax::$RELOAD_TRAINING);

		$this->handle('ELEVATION_METHOD')
		    ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::useCleanup');
		$this->handle('ELEVATION_TRESHOLD')
		    ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::useCleanup');
	}

    public function Fieldset(): \Runalyze\Configuration\Fieldset {
		$Fieldset = new Fieldset(__('Activity view'));

		$Fieldset->addHandle($this->handle('TRAINING_DECIMALS'), [
		    'label' => __('Number of decimals'),
		]);

		$Fieldset->addHandle($this->handle('SHOW_SECTIONS_FULLHEIGHT'), [
		    'label' => __('Activity view: show rows in full height'),
		    'tooltip' => __('By default e.g. lap data will be scrollable and not shown completely.'),
		]);

		$this->addHandlesForMapsTo($Fieldset);
		$this->addHandlesForPlotsTo($Fieldset);
		$this->addHandlesForElevationTo($Fieldset);

		return $Fieldset;
	}

    /**
     * Add handles for plots to fieldset
     */
    private function addHandlesForPlotsTo(Fieldset &$Fieldset) {
		$Fieldset->addHandle($this->handle('TRAINING_PLOT_MODE'), [
		    'label' => __('Plots: combination'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_PLOT_PRECISION'), [
		    'label' => __('Plots: precision'),
		    'tooltip' => __('How many data points should be plotted?'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_PLOT_SMOOTH'), [
		    'label' => __('Plots: smooth curves'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_PLOT_XAXIS_TIME'), [
		    'label' => __('Plots: use time as x-axis'),
		]);

		$Fieldset->addHandle($this->handle('PACE_Y_LIMIT_MIN'), [
		    'label' => __('Pace plot: y-axis minimum'),
		    'tooltip' => __('Data points below this limit will be ignored. (only for min/km)'),
		]);

		$Fieldset->addHandle($this->handle('PACE_Y_LIMIT_MAX'), [
		    'label' => __('Pace plot: y-axis maximum'),
		    'tooltip' => __('Data points above this limit will be ignored. (only for min/km)'),
		]);

		$Fieldset->addHandle($this->handle('PACE_Y_AXIS_TYPE'), [
		    'label' => __('Pace plot: y-axis scale type'),
		    'tooltip' => __('Scale type that will be used to show pace on y-axis.'),
		]);

		$Fieldset->addHandle($this->handle('PACE_HIDE_OUTLIERS'), [
		    'label' => __('Pace plot: Ignore outliers'),
		    'tooltip' => __('Try to ignore outliers in the pace plot.'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_PLOT_SPLITS_ZERO'), [
		    'label' => __('Splits plot: use zero as axis minimum'),
		]);
	}

    /**
     * Add handles for maps to fieldset
     */
    private function addHandlesForMapsTo(Fieldset &$Fieldset) {
		$Fieldset->addHandle($this->handle('GMAP_PATH_BREAK'), [
		    'label' => __('Map: interrupt route'),
		    'tooltip' => __('The gps path can be interrupted in case of <em>interruptions</em> (e.g. by car/train/...).' .
		    	'Finding these interruptions is not easy. You can define up to which distance (in seconds by average pace)' .
		    	'between two data points can be interpolated.'),
		]);

		$Fieldset->addHandle($this->handle('GMAP_PATH_PRECISION'), [
		    'label' => __('Map: precision'),
		    'tooltip' => __('How many data points shoud be displayed?'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_MAP_SHOW_FIRST'), [
		    'label' => __('Map: show map first'),
		    'tooltip' => __('show map before plots'),
		]);

		$Fieldset->addHandle($this->handle('TRAINING_MAP_ZOOM_ON_SCROLL'), [
		    'label' => __('Map: zoom on scroll'),
		]);
	}

    /**
     * Add handles for elevation to fieldset
     */
    private function addHandlesForElevationTo(Fieldset &$Fieldset) {
		$Fieldset->addHandle($this->handle('ELEVATION_METHOD'), [
		    'label' => __('Elevation: smoothing'),
		    'tooltip' => __('Choose the algorithm to smooth the elevation data'),
		]);

		$Fieldset->addHandle($this->handle('ELEVATION_TRESHOLD'), [
		    'label' => __('Elevation: threshold'),
		    'tooltip' => __('Threshold for the smoothing algorithm'),
		]);
	}
}

<?php

/**
 * This file contains class::Trimp
 * @package Runalyze\Configuration\Category
 */

namespace Runalyze\Configuration\Category;

use Runalyze\Configuration\Messages;
use Runalyze\Configuration\Fieldset;
use Runalyze\Parameter\Integer;
use Runalyze\Parameter\Boolean;
use Ajax;

/**
 * Configuration category: Trimp
 * @author Hannes Christiansen
 * @package Runalyze\Configuration\Category
 */
class Trimp extends \Runalyze\Configuration\Category
{
    /**
     * Flag: recalculation triggered?
     * @var bool
     */
    private static $TRIGGERED = false;

    /**
     * Internal key
     */
    protected function key(): string {
		return 'trimp';
	}

	protected function createHandles() {
		$this->createHandle('ATL_DAYS', new Integer(7));
		$this->createHandle('CTL_DAYS', new Integer(42));
		$this->createHandle('TRIMP_MODEL_IN_PERCENT', new Boolean(true));
		$this->createHandle('TSB_IN_PERCENT', new Boolean(false));
	}

	/**
	 * Days for ATL
	 * @return int
	 */
	public function daysForATL() {
		return $this->get('ATL_DAYS');
	}

	/**
	 * Days for CTL
	 * @return int
	 */
	public function daysForCTL() {
		return $this->get('CTL_DAYS');
	}

	/**
	 * Show ATL/CTL in percent?
	 * @return bool
	 */
	public function showInPercent() {
		return $this->get('TRIMP_MODEL_IN_PERCENT');
	}

	/**
	 * Show TSB in percent?
	 * @return bool
	 */
	public function showTSBinPercent() {
		return $this->get('TSB_IN_PERCENT');
	}

	protected function registerOnchangeEvents() {
		$this->handle('ATL_DAYS')
		    ->registerOnchangeEvent(self::class . '::triggerRecalculation');
		$this->handle('ATL_DAYS')
		    ->registerOnchangeFlag(Ajax::$RELOAD_PLUGINS);

		$this->handle('CTL_DAYS')
		    ->registerOnchangeEvent(self::class . '::triggerRecalculation');
		$this->handle('CTL_DAYS')
		    ->registerOnchangeFlag(Ajax::$RELOAD_PLUGINS);

		$this->handle('TRIMP_MODEL_IN_PERCENT')
		    ->registerOnchangeFlag(Ajax::$RELOAD_PLUGINS);
		$this->handle('TSB_IN_PERCENT')
		    ->registerOnchangeFlag(Ajax::$RELOAD_PLUGINS);
	}

    public function Fieldset(): \Runalyze\Configuration\Fieldset {
		$Fieldset = new Fieldset( __('TRIMP') );

		$Fieldset->addHandle( $this->handle('ATL_DAYS'), [
    'label' => __('Days for ATL'),
		    'tooltip' => __('Number of days for ATL time constant'),
]);

		$Fieldset->addHandle( $this->handle('CTL_DAYS'), [
    'label' => __('Days for CTL'),
		    'tooltip' => __('Number of days for CTL time constant'),
]);

		$Fieldset->addHandle( $this->handle('TRIMP_MODEL_IN_PERCENT'), [
    'label' => __('Show ATL/CTL in percent of your maximum'),
		    'tooltip' => __('By default ATL/CTL are scaled based on your historical maximum. ' .
		    				'This can lead to wrong assumptions if you were overtrained. ' .
		    				'Deactivate this option in that case.'),
]);

		$Fieldset->addHandle( $this->handle('TSB_IN_PERCENT'), [
    'label' => __('Show TSB in percent of ATL/CTL'),
		    'tooltip' => __('The scale of TSB values depends on your ATL/CTL. ' .
		    				'You can display TSB as percentage of your current ATL/CTL to keep consistency.'),
]);

		return $Fieldset;
	}

	public static function triggerRecalculation() {
		if (! self::$TRIGGERED) {
			self::$TRIGGERED = true;

			$Data = \Runalyze\Configuration::Data();

			$oldCTL = $Data->maxCTL();
			$oldATL = $Data->maxATL();

			$Data->recalculateMaxValues();

			$newCTL = $Data->maxCTL();
			$newATL = $Data->maxATL();

			Messages::addValueRecalculated(__('Maximal CTL'), $newCTL, $oldCTL);
			Messages::addValueRecalculated(__('Maximal ATL'), $newATL, $oldATL);
		}
	}
}

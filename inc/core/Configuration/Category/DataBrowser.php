<?php

/**
 * This file contains class::DataBrowser
 * @package Runalyze\Configuration\Category
 */

namespace Runalyze\Configuration\Category;

use Ajax;
use Runalyze\Configuration\Fieldset;
use Runalyze\Parameter\Application\DataBrowserMode;
use Runalyze\Parameter\Boolean;

/**
 * Configuration category: Data browser
 * @author Hannes Christiansen
 * @package Runalyze\Configuration\Category
 */
class DataBrowser extends \Runalyze\Configuration\Category
{
    /**
     * Internal key
     */
    protected function key(): string
    {
        return 'data-browser';
    }

    protected function createHandles()
    {
        $this->createHandle('DB_DISPLAY_MODE', new DataBrowserMode());
        $this->createHandle('DB_SHOW_DATASET_LABELS', new Boolean(true));
        $this->createHandle('DB_SHOW_CREATELINK_FOR_DAYS', new Boolean(false));
        $this->createHandle('DB_SHOW_ACTIVE_DAYS_ONLY', new Boolean(false));
        $this->createHandle('DB_REVERSE_MODE', new Boolean(false));
    }

    /**
     * @return DataBrowserMode
     */
    public function mode()
    {
        return $this->object('DB_DISPLAY_MODE');
    }

    /**
     * Show dataset labels
     * @return bool
     */
    public function showLabels()
    {
        return $this->get('DB_SHOW_DATASET_LABELS');
    }

    /**
     * @return bool
     */
    public function showCreateLink()
    {
        return $this->get('DB_SHOW_CREATELINK_FOR_DAYS');
    }

    /**
     * Show days with activities only
     * @return bool
     */
    public function showActiveDaysOnly()
    {
        return $this->get('DB_SHOW_ACTIVE_DAYS_ONLY');
    }

    /**
     * Show days with activities only
     * @return bool
     */
    public function reverseMode()
    {
        return $this->get('DB_REVERSE_MODE');
    }

    protected function registerOnchangeEvents()
    {
        $this->handle('DB_SHOW_DATASET_LABELS')
            ->registerOnchangeFlag(Ajax::$RELOAD_DATABROWSER);
        $this->handle('DB_SHOW_CREATELINK_FOR_DAYS')
            ->registerOnchangeFlag(Ajax::$RELOAD_DATABROWSER);
        $this->handle('DB_SHOW_ACTIVE_DAYS_ONLY')
            ->registerOnchangeFlag(Ajax::$RELOAD_DATABROWSER);
        $this->handle('DB_REVERSE_MODE')
            ->registerOnchangeFlag(Ajax::$RELOAD_DATABROWSER);

        $this->handle('DB_DISPLAY_MODE')
            ->registerOnchangeEvent(self::class . '::showNewTimerangeInDB');
    }

    public function Fieldset(): \Runalyze\Configuration\Fieldset
    {
        $Fieldset = new Fieldset(__('Calendar view'));

        $Fieldset->addHandle($this->handle('DB_DISPLAY_MODE'), [
            'label' => __('Calendar: mode'),
            'tooltip' => __('Default mode for the calendar'),
        ]);

        $Fieldset->addHandle($this->handle('DB_SHOW_DATASET_LABELS'), [
            'label' => __('Calendar: show labels for dataset'),
        ]);

        $Fieldset->addHandle($this->handle('DB_SHOW_ACTIVE_DAYS_ONLY'), [
            'label' => __('Calendar: show days with activities only'),
        ]);

        $Fieldset->addHandle($this->handle('DB_SHOW_CREATELINK_FOR_DAYS'), [
            'label' => __('Calendar: create button'),
            'tooltip' => __('Add a link for every day to create a new activity.'),
        ]);

        $Fieldset->addHandle($this->handle('DB_REVERSE_MODE'), [
            'label' => __('Calendar') . ': ' . __('reverse order'),
            'tooltip' => __('Show days in reverse orders'),
        ]);

        return $Fieldset;
    }

    /**
     * Reload data browser for new timerange
     */
    public static function showNewTimerangeInDB()
    {
        $mode = \Runalyze\Configuration::DataBrowser()->mode();

        $rel = $mode->showMonth() ? 'month-link' : 'week-link';

        echo Ajax::wrapJSasFunction('$("#data-browser .panel-heading a[rel=\'' . $rel . '\']").click();');
    }
}

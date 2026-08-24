<?php

declare(strict_types=1);

/**
 * This file contains the class of the RunalyzePluginPanel "Sports".
 * @package Runalyze\Plugins\Panels
 */
$PLUGINKEY = 'RunalyzePluginPanel_Sports';

class RunalyzePluginPanel_Sports extends PluginPanel
{
    final public function name(): string
    {
        return __('Sports');
    }

    final public function description(): string
    {
        return __('Summary of your activities for each sport.');
    }

    protected function displayContent()
    {
        // deprecated, see Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController::getResponseForSportsPanel
    }
}

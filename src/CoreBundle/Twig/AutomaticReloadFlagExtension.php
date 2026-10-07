<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class AutomaticReloadFlagExtension
{
    #[\Twig\Attribute\AsTwigFunction(name: 'automaticJavaScriptReload')]
    public function automaticJavaScriptReload(FlashBag $flashBag): string
    {
        $reloadFlag = $flashBag->get(AutomaticReloadFlagSetter::FLASH_BAG_KEY);
        $javaScriptCommands = [
            AutomaticReloadFlagSetter::FLAG_DATA_BROWSER => 'Runalyze.DataBrowser.reload();',
            AutomaticReloadFlagSetter::FLAG_TRAINING => 'Runalyze.Training.reload();',
            AutomaticReloadFlagSetter::FLAG_TRAINING_AND_DATA_BROWSER => 'Runalyze.reloadDataBrowserAndTraining();',
            AutomaticReloadFlagSetter::FLAG_PLUGINS => 'Runalyze.reloadAllPlugins();',
            AutomaticReloadFlagSetter::FLAG_ALL => 'Runalyze.reloadContent();',
            AutomaticReloadFlagSetter::FLAG_PAGE => 'Runalyze.reloadPage();',
        ];

        if ($reloadFlag !== [] && isset($javaScriptCommands[$reloadFlag[0]])) {
            return $javaScriptCommands[$reloadFlag[0]];
        }

        return '';
    }
}

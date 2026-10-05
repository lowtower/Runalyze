<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;

trait ConfigurationManagerAwareTrait
{
    protected ConfigurationManager $ConfigurationManager;

    public function setConfigurationManager(ConfigurationManager $manager): void
    {
        $this->ConfigurationManager = $manager;
    }

    public function getConfigurationList(): RunalyzeConfigurationList
    {
        return $this->ConfigurationManager->getList();
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;

class ConfigurationExtension
{
    public function __construct(
        protected ConfigurationManager $ConfigurationManager
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'configVar')]
    public function configVar(string $key): mixed
    {
        return $this->ConfigurationManager->getList()
            ->get($key);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'config')]
    public function config(?Account $account = null): ?RunalyzeConfigurationList
    {
        return $this->ConfigurationManager->getList($account);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'unitSystem')]
    public function unitSystem(?Account $account = null): UnitSystem
    {
        return $this->ConfigurationManager->getList($account)
            ->getUnitSystem();
    }
}

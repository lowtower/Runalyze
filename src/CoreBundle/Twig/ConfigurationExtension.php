<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigurationExtension extends AbstractExtension
{
    public function __construct(
        protected ConfigurationManager $ConfigurationManager
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return 'runalyze.configuration_extension';
    }

    /**
     * @return TwigFunction[]
     *
     * @codeCoverageIgnore
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('configVar', $this->configVar(...)),
            new TwigFunction('config', $this->config(...)),
            new TwigFunction('unitSystem', $this->unitSystem(...)),
        ];
    }

    /**
     * Get config variable from current user
     */
    public function configVar(string $key): mixed
    {
        return $this->ConfigurationManager->getList()
            ->get($key);
    }

    public function config(?Account $account = null): ?RunalyzeConfigurationList
    {
        return $this->ConfigurationManager->getList($account);
    }

    public function unitSystem(?Account $account = null): UnitSystem
    {
        return $this->ConfigurationManager->getList($account)
            ->getUnitSystem();
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Entity\Account;

class ConfigurationExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager $ConfigurationManager
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
     * @return \Twig\TwigFunction[]
     *
     * @codeCoverageIgnore
     */
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('configVar', $this->configVar(...)),
            new \Twig\TwigFunction('config', $this->config(...)),
            new \Twig\TwigFunction('unitSystem', $this->unitSystem(...)),
        ];
    }

    /**
     * Get config variable from current user
     *
     * @return mixed
     */
    public function configVar(string $key)
    {
        return $this->ConfigurationManager->getList()
            ->get($key);
    }

    public function config(?Account $account = null): ?\Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList
    {
        return $this->ConfigurationManager->getList($account);
    }

    public function unitSystem(?Account $account = null): \Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem
    {
        return $this->ConfigurationManager->getList($account)
            ->getUnitSystem();
    }
}

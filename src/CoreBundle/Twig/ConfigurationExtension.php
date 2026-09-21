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
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return 'runalyze.configuration_extension';
    }

    /**
     * @return \Twig\TwigFunction[]
     *
     * @codeCoverageIgnore
     */
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('configVar', [$this, 'configVar']),
            new \Twig\TwigFunction('config', [$this, 'config']),
            new \Twig\TwigFunction('unitSystem', [$this, 'unitSystem']),
        ];
    }

    /**
     * Get config variable from current user
     *
     * @param string $key
     * @return mixed
     */
    public function configVar($key)
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

<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;

class ConfigurationExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @var ConfigurationManager
     */
    protected $ConfigurationManager;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->ConfigurationManager = $configurationManager;
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

    /**
     * @return RunalyzeConfigurationList
     */
    public function config(?Account $account = null)
    {
        return $this->ConfigurationManager->getList($account);
    }

    /**
     * @return \Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem
     */
    public function unitSystem(?Account $account = null)
    {
        return $this->ConfigurationManager->getList($account)
            ->getUnitSystem();
    }
}

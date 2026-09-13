<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_conf')]
#[ORM\Index(columns: ['pluginid'], name: 'pluginid')]
#[ORM\Entity(repositoryClass: PluginConfRepository::class)]
class PluginConf
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'config', type: 'string', length: 100, nullable: false)]
    private $config;

    /**
     * @var string
     */
    #[ORM\Column(name: 'value', type: 'string', length: 255, nullable: false)]
    private $value;

    /**
     * @var Plugin
     */
    #[ORM\JoinColumn(name: 'pluginid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Plugin::class)]
    private $plugin;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $config
     */
    public function setConfig($config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $value
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setPlugin(\Runalyze\Bundle\CoreBundle\Entity\Plugin $plugin): self
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * @return Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }
}

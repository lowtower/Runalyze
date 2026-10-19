<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;

#[ORM\Table(name: 'plugin_conf')]
#[ORM\Index(name: 'pluginid', columns: ['pluginid'])]
#[ORM\Entity(repositoryClass: PluginConfRepository::class)]
class PluginConf
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'config', type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: false)]
    private string $config;

    #[ORM\Column(name: 'value', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false)]
    private string $value;

    #[ORM\JoinColumn(name: 'pluginid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Plugin::class)]
    private Plugin $plugin;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setConfig(string $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): string
    {
        return $this->config;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setPlugin(Plugin $plugin): self
    {
        $this->plugin = $plugin;

        return $this;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;

final class DoctrinePluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function loadForPlugins(array $pluginIds): array
    {
        return $this->entityManager->getRepository(PluginConf::class)
            ->findValuesForPlugins($pluginIds);
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        $configuration = (new PluginConf())
            ->setPlugin($this->entityManager->getReference(Plugin::class, $pluginId))
            ->setConfig($key)
            ->setValue($value);

        $this->entityManager->getRepository(PluginConf::class)->save($configuration);
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        $configuration = $this->entityManager->getRepository(PluginConf::class)
            ->findForPluginAndKey($pluginId, $key);

        if ($configuration instanceof PluginConf) {
            $configuration->setValue($value);
            $this->entityManager->getRepository(PluginConf::class)->save($configuration);
        }
    }
}

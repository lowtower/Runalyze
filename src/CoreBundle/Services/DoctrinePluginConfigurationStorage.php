<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;

final class DoctrinePluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    private readonly PluginConfRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        /** @var PluginConfRepository $repository */
        $repository = $entityManager->getRepository(PluginConf::class);
        $this->repository = $repository;
    }

    public function loadForPlugins(array $pluginIds): array
    {
        return $this->repository->findValuesForPlugins($pluginIds);
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        $plugin = $this->entityManager->getReference(Plugin::class, $pluginId);
        assert($plugin instanceof Plugin);

        $configuration = (new PluginConf())
            ->setPlugin($plugin)
            ->setConfig($key)
            ->setValue($value);

        $this->repository->save($configuration);
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        $configuration = $this->repository->findForPluginAndKey($pluginId, $key);

        if ($configuration instanceof PluginConf) {
            $configuration->setValue($value);
            $this->entityManager->getRepository(PluginConf::class)->save($configuration);
        }
    }
}

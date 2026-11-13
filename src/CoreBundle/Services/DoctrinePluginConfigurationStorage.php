<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Services;

use Doctrine\\ORM\\EntityManagerInterface;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;
use Runalyze\\Bundle\\CoreBundle\\Entity\\PluginConf;

final class DoctrinePluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function loadForPlugins(array $pluginIds): array
    {
        if ($pluginIds === []) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(configuration.plugin) AS pluginId', 'configuration.config', 'configuration.value')
            ->from(PluginConf::class, 'configuration')
            ->where('IDENTITY(configuration.plugin) IN (:pluginIds)')
            ->setParameter('pluginIds', $pluginIds)
            ->getQuery()
            ->getArrayResult();

        $values = [];
        foreach ($rows as $row) {
            $values[(int) $row['pluginId']][$row['config']] = $row['value'];
        }

        return $values;
    }

    public function insert(int $pluginId, string $key, string $value): void
    {
        $configuration = (new PluginConf())
            ->setPlugin($this->entityManager->getReference(Plugin::class, $pluginId))
            ->setConfig($key)
            ->setValue($value);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }

    public function update(int $pluginId, string $key, string $value): void
    {
        $configuration = $this->entityManager->getRepository(PluginConf::class)->findOneBy([
            'plugin' => $pluginId,
            'config' => $key,
        ]);

        if ($configuration instanceof PluginConf) {
            $configuration->setValue($value);
            $this->entityManager->flush();
        }
    }

    public function clearCache(): void
    {
    }
}

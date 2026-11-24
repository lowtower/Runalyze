<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;

final readonly class DoctrinePluginConfigurationStorage implements PluginConfigurationStorageInterface
{
    private PluginConfRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
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

        $configuration = new PluginConf()
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
            $this->repository->save($configuration);
        }
    }

    public function saveValue(Plugin $installation, string $key, string $value): void
    {
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($installation, $key, $value): void {
            // A setting may not exist yet. Lock the installation before looking up its settings.
            $entityManager->lock($installation, LockMode::PESSIMISTIC_WRITE);
            $settings = $this->repository->findBy([
                'plugin' => $installation,
                'config' => $key,
            ]);

            if ($settings === []) {
                $entityManager->persist(new PluginConf()
                    ->setPlugin($installation)
                    ->setConfig($key)
                    ->setValue($value));
            }

            foreach ($settings as $setting) {
                $setting->setValue($value);
            }
        });
    }
}

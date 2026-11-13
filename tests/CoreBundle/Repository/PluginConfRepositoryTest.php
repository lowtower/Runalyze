<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class PluginConfRepositoryTest extends AbstractRepositoryTestCase
{
    private PluginConfRepository $repository;

    private Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getEntityManager()->getRepository(PluginConf::class);

        $this->plugin = (new Plugin())
            ->setAccount($this->getEmptyAccount())
            ->setKey('PluginConfRepositoryTest')
            ->setType(Plugin::TYPE_STAT);

        $this->getEntityManager()->persist($this->plugin);
        $this->getEntityManager()->flush();
    }

    public function testFindValuesForPluginsGroupsValuesByPluginId(): void
    {
        $this->repository->save(
            (new PluginConf())
                ->setPlugin($this->plugin)
                ->setConfig('first')
                ->setValue('one')
        );
        $this->repository->save(
            (new PluginConf())
                ->setPlugin($this->plugin)
                ->setConfig('second')
                ->setValue('two')
        );

        $this->assertSame([
            $this->plugin->getId() => [
                'first' => 'one',
                'second' => 'two',
            ],
        ], $this->repository->findValuesForPlugins([$this->plugin->getId()]));
    }

    public function testFindValuesForPluginsReturnsEmptyArrayWithoutPluginIds(): void
    {
        $this->assertSame([], $this->repository->findValuesForPlugins([]));
    }

    public function testFindForPluginAndKeyReturnsMatchingConfiguration(): void
    {
        $configuration = (new PluginConf())
            ->setPlugin($this->plugin)
            ->setConfig('setting')
            ->setValue('enabled');
        $this->repository->save($configuration);

        $this->assertSame(
            $configuration,
            $this->repository->findForPluginAndKey($this->plugin->getId(), 'setting')
        );
    }
}

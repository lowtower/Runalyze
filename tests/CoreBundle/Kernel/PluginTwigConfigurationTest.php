<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Kernel;

use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Filesystem\Filesystem;

#[Group('plugin')]
final class PluginTwigConfigurationTest extends TestCase
{
    public function testDiscoversTemplateNamespacesFromBothPluginTypes(): void
    {
        $projectDir = sys_get_temp_dir() . '/runalyze-plugin-twig-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();
        $panelDir = $projectDir . '/plugins/Panels/ExamplePanel/templates';
        $statisticsDir = $projectDir . '/plugins/Statistics/ExampleStats/templates';

        try {
            $filesystem->mkdir([$panelDir, $statisticsDir]);

            $paths = $this->loadTwigPaths($projectDir);

            $this->assertSame('ExamplePanel', $paths[realpath($panelDir)]);
            $this->assertSame('ExampleStats', $paths[realpath($statisticsDir)]);
        } finally {
            $filesystem->remove($projectDir);
        }
    }

    public function testRejectsDuplicateTemplateNamespacesAcrossPluginTypes(): void
    {
        $projectDir = sys_get_temp_dir() . '/runalyze-plugin-twig-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        try {
            $filesystem->mkdir([
                $projectDir . '/plugins/Panels/Example/templates',
                $projectDir . '/plugins/Statistics/Example/templates',
            ]);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessageIsOrContains('The plugin Twig namespace "Example" is used by both');

            $this->loadTwigPaths($projectDir);
        } finally {
            $filesystem->remove($projectDir);
        }
    }

    /**
     * @return array<string, string>
     */
    private function loadTwigPaths(string $projectDir): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->registerExtension(new TwigExtension());

        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config/packages'));
        $loader->load('twig.php');

        return $container->getExtensionConfig('twig')[0]['paths'];
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Kernel;

use App\Kernel;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Filesystem\Filesystem;

#[Group('requiresKernel')]
final class ServiceConfigurationCacheTest extends TestCase
{
    public function testNonDebugKernelRebuildsTheContainerAfterServiceConfigurationChanges(): void
    {
        $projectDir = sys_get_temp_dir() . '/runalyze-service-cache-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();
        $servicesFile = $projectDir . '/config/services.php';
        $configuration = <<<'PHP'
            <?php

            use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

            return static function (ContainerConfigurator $container): void {
                $container->parameters()->set('records.registered', false);
            };
            PHP;

        try {
            $filesystem->dumpFile($servicesFile, $configuration);
            $kernel = $this->createKernel($projectDir);
            $kernel->boot();
            $this->assertFalse($kernel->getContainer()->getParameter('records.registered'));
            $kernel->shutdown();

            $filesystem->dumpFile($servicesFile, str_replace('false', 'true', $configuration));
            $kernel = $this->createKernel($projectDir);
            $kernel->boot();
            $this->assertTrue($kernel->getContainer()->getParameter('records.registered'));
            $kernel->shutdown();
        } finally {
            $filesystem->remove($projectDir);
        }
    }

    private function createKernel(string $projectDir): Kernel
    {
        return new class($projectDir) extends Kernel {
            public function __construct(
                private readonly string $testProjectDir
            ) {
                parent::__construct('prod', false);
            }

            public function getProjectDir(): string
            {
                return $this->testProjectDir;
            }

            public function registerBundles(): iterable
            {
                yield new FrameworkBundle();
            }
        };
    }
}

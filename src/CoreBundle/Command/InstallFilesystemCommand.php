<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class InstallFilesystemCommand extends Command
{
    private Filesystem $filesystem;

    private KernelInterface $kernel;

    public function __construct(Filesystem $filesystem, KernelInterface $kernel)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:install:filesystem')
            ->setDescription('Setup RUNALYZE file system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Setup RUNALYZE file system...</info>');
        $output->writeln('');

        $this->tryToCopyHtaccess($output);

        $output->writeln('<info>... done.</info>');
        $output->writeln('');
        return 0;
    }

    protected function tryToCopyHtaccess(OutputInterface $output)
    {
        $output->writeln('  <info>Copying .htaccess.dist to .htaccess ...</info>');

        try {
            $root = $this->kernel->getProjectDir() . '/';
            $this->filesystem->copy($root . '.htaccess.dist', $root . '.htaccess');
        } catch (IOException $ioException) {
            $output->writeln(sprintf('  <comment>%s</comment>', $ioException->getMessage()));
        }

        $output->writeln('');
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'runalyze:install:filesystem', description: 'Setup RUNALYZE file system.')]
class InstallFilesystemCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Setup RUNALYZE file system...</info>');
        $output->writeln('');

        $this->tryToCopyHtaccess($output);

        $output->writeln('<info>... done.</info>');
        $output->writeln('');
        return \Symfony\Component\Console\Command\Command::SUCCESS;
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

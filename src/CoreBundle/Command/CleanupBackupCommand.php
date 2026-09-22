<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanupBackupCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:cleanup:backups')
            ->setDescription('Cleanup user backups older than parameter: backup_storage_period')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age backups')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days') ?: $this->parameterBag->get('app.backup_storage_period');
        $output->writeln(sprintf('<info>Delete all backups older than %s days</info>', $days));

        $dataDirectory = $this->parameterBag->get('app.data_directory');
        $backupPath = $dataDirectory . '/backup-tool/backup';

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.gz')
            ->in($backupPath)
            ->date(sprintf('until %s days ago', $days));

        $deleted = $finder->count();

        foreach ($finder as $file) {
            $this->filesystem->remove($file);
        }

        $output->writeln(sprintf('<info>%s deleted backups</info>', $deleted));
        $output->writeln('');

        return 0;
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'runalyze:cleanup:posters', description: 'Cleanup posters older than parameter: poster_storage_period')]
class CleanupPostersCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Filesystem $filesystem,
        private readonly FinderFactoryInterface $finderFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age posters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days') ?: $this->parameterBag->get('app.poster_storage_period');
        $output->writeln(sprintf('<info>Delete all posters older than %s days</info>', $days));

        $finder = $this->finderFactory->create();
        $finder
            ->files()
            ->name('*.png')
            ->in($this->parameterBag->get('app.data_directory') . '/poster/')
            ->date(sprintf('until %s days ago', $days));

        $deleted = $finder->count();

        foreach ($finder as $file) {
            $this->filesystem->remove($file);
        }

        $output->writeln(sprintf('<info>%s deleted posters</info>', $deleted));
        $output->writeln('');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

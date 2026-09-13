<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupRegistrationsCommand extends Command
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:cleanup:registrations')
            ->setDescription('Delete not activated account (default: older than 7 days).')
            ->addArgument('days', InputArgument::OPTIONAL, 'min. age of not activated accounts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days') ?: 7;

        $output->writeln('<info>Delete all not activated accounts older than ' . $days . ' days</info>');
        $output->writeln('');

        /** @var AccountRepository $repository */
        $repository = $this->doctrine->getRepository(Account::class);
        $delete = $repository->deleteNotActivatedAccounts($days);
        $output->writeln('<info>' . count($delete) . ' deleted accounts</info>');
        $output->writeln('');

        return 0;
    }
}

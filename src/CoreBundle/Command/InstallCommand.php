<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'runalyze:install', description: 'Install RUNALYZE and setup database.')]
class InstallCommand extends Command
{
    protected ?Application $Application = null;

    /**
     * @var array<mixed, array<string, string>>
     */
    protected array $Commands = [
        [
            'command' => 'check',
            'message' => 'Check requirements',
        ],
        [
            'command' => 'database',
            'message' => 'Setting up the database',
        ],
        [
            'command' => 'filesystem',
            'message' => 'Setting up the file system',
        ],
    ];

    protected function configure(): void
    {
        $this
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Steps to skip',
                ['']
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->Application = $this->getApplication();
        $this->removeCommandsThatShouldBeSkipped($input);
    }

    protected function removeCommandsThatShouldBeSkipped(InputInterface $input): void
    {
        if ($input->hasOption('skip') && is_array($input->getOption('skip'))) {
            $skippedCommands = $input->getOption('skip');

            foreach ($this->Commands as $i => $command) {
                $this->Commands[$i]['skip'] = in_array($command['command'], $skippedCommands);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Installing RUNALYZE...</info>');
        $output->writeln('');

        foreach ($this->Commands as $step => $command) {
            $output->writeln(sprintf(
                '<comment>Step %d of %d.</comment> <info>%s</info>',
                $step + 1,
                count($this->Commands),
                $command['message']
            ));

            if ($command['skip'] ?? false) {
                $output->writeln('skipped');
                $output->writeln('');
                continue;
            }

            $subCommand = $this->Application->find('runalyze:install:' . $command['command']);
            $subInput = new ArrayInput([
                'command' => $subCommand->getName(),
            ]);
            $subInput->setInteractive($input->isInteractive());

            $exitCode = $subCommand->run($subInput, $output);
            $output->writeln('');

            if ($exitCode > 0) {
                return $exitCode;
            }
        }

        $output->writeln('<info>RUNALYZE has been successfully installed.</info>');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

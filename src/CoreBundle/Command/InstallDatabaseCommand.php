<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InstallDatabaseCommand extends Command
{
    public const DATABASE_STRUCTURE_FILE = 'inc/install/structure.sql';

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('runalyze:install:database')
            ->setDescription('Setup RUNALYZE database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Setup RUNALYZE database...</info>');
        $output->writeln('');
        $output->writeln(sprintf('  <info>Importing %s ...</info>', self::DATABASE_STRUCTURE_FILE));
        $output->writeln('');

        $this->importDatabaseStructure();

        $output->writeln('  <info>Database has been successfully initialized.</info>');

        $output->writeln('');
        $this->addAllMigrationsToDatabase();

        $output->writeln('  <info>Migrations have been successfully applied to database.</info>');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * @throws RuntimeException if the structure file contains no executable statements
     */
    protected function importDatabaseStructure(): void
    {
        $fileName = $this->parameterBag->get('kernel.project_dir') . '/' . self::DATABASE_STRUCTURE_FILE;
        $queries = $this->getSqlFileAsArray($fileName, $this->parameterBag->get('app.database_prefix'));

        if ($queries === []) {
            throw new RuntimeException(sprintf('Database structure file "%s" contains no executable statements.', $fileName));
        }

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();

        foreach ($queries as $query) {
            $connection->executeStatement($query);
        }
    }

    private function addAllMigrationsToDatabase(): void
    {
        $app = $this->getApplication();
        $app->setAutoExit(false);

        $input = new StringInput('doctrine:migrations:version --add --all -n -q');
        $output = new NullOutput();

        $previousShellVerbosity = getenv('SHELL_VERBOSITY');

        try {
            $app->run($input, $output);
        } finally {
            putenv($previousShellVerbosity === false ? 'SHELL_VERBOSITY' : 'SHELL_VERBOSITY=' . $previousShellVerbosity);
        }
    }

    /**
     * Import a sql-file
     *
     * @return string[]
     */
    public function getSqlFileAsArray(string $filename, string $databasePrefix, bool $removeDelimiter = true): array
    {
        $MRK = ['DELIMITER', 'USE', 'SET', 'LOCK', 'SHOW', 'DROP', 'GRANT', 'ALTER', 'UNLOCK', 'CREATE', 'INSERT', 'UPDATE', 'DELETE', 'REVOKE', 'REPLACE', 'RENAME', 'TRUNCATE'];
        $SQL = @file($filename);
        $query = '';
        $array = [];
        $inDelimiter = false;

        if (! is_array($SQL)) {
            $SQL = [];
        }

        foreach ($SQL as $line) {
            $line = trim($line);
            $line = str_replace('runalyze_', $databasePrefix, $line);

            if ($inDelimiter) {
                if (mb_substr($line, 0, 9) === 'DELIMITER') {
                    $inDelimiter = false;
                    $query .= $removeDelimiter ? '' : ' ' . $line;
                    $array[] = $query;
                } elseif (trim($line) !== '//') {
                    $query .= ' ' . $line;
                }
            } else {
                $AA = explode(' ', $line);
                if (in_array(strtoupper($AA[0]), $MRK, true)) {
                    if ($AA[0] == 'DELIMITER') {
                        $inDelimiter = true;
                        $query = $removeDelimiter ? '' : $line;
                    } else {
                        $query = $line;
                    }
                } elseif (strlen($query) > 1) {
                    $query .= ' ' . $line;
                }

                $x = strlen($query) - 1;
                if (mb_substr($query, $x) === ';') {
                    $array[] = $query;
                    $query = '';
                }
            }
        }

        return $array;
    }
}

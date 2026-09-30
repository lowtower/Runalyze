<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InstallCheckCommand extends Command
{
    /**
     * @var string
     */
    public const REQUIRED_PHP_VERSION = '8.1.0';

    /**
     * @var int exit code
     */
    public const CHECK_FAILED = 1;

    /**
     * @var int bit flag for return code
     */
    public const OKAY = 0x00;

    /**
     * @var int bit flag for return code
     */
    public const WARNING = 0x01;

    /**
     * @var int bit flag for return code
     */
    public const ERROR = 0x10;

    /**
     * @var int current return code
     */
    protected int $ReturnCode = 0x00;

    protected array $Checks = [
        [
            'method' => 'checkPhpVersion',
            'message' => 'Check PHP version',
        ],
        [
            'method' => 'prefixIsNotUsed',
            'message' => 'Check that database prefix is not used',
            'hint' => [
                'There must not exist any tables with the chosen prefix.',
                'Maybe RUNALYZE is already installed.',
            ],
        ],
        [
            'method' => 'directoriesAreWritable',
            'message' => 'Check that directories for cache, log and import are writable',
            'hint' => [
                'Respective directories in data/ and var/ must be writable.',
            ],
        ],
    ];

    protected array $DirectoriesThatMustBeWritable = [
        'data/backup-tool/backup/',
        'data/backup-tool/import/',
        'data/cache/',
        'data/import/',
        'data/poster/',
        'data/sessions/',
        'var/cache/',
        'var/log/',
        'var/poster/',
        'var/queue/',
        'var/tmp/',
    ];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:install:check')
            ->setDescription('Check requirements for installing RUNALYZE.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Check requirements...</info>');
        $output->writeln('');

        foreach ($this->Checks as $check) {
            $returnCode = $this->{$check['method']}();
            $this->ReturnCode |= $returnCode;

            $output->writeln(sprintf('  * %s ... %s', $check['message'], $this->styleReturnCode($returnCode)));

            if ($returnCode != self::OKAY && isset($check['hint'])) {
                $check['hint'] = ! is_array($check['hint']) ? [$check['hint']] : $check['hint'];

                foreach ($check['hint'] as $hint) {
                    $output->writeln('    <comment>' . $hint . '</comment>');
                }
            }

            $output->writeln('');
        }

        $output->writeln('  ' . $this->getFinalMessage());

        if ($this->ReturnCode & self::ERROR) {
            return self::CHECK_FAILED;
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function getFinalMessage(): string
    {
        if ($this->ReturnCode & self::ERROR) {
            return '<error>Not all requirements are fulfilled, installation not possible.</error>';
        }

        if ($this->ReturnCode & self::WARNING) {
            return '<comment>There were some warnings, installation may still be possible.</comment>';
        }

        return '<info>All requirements are fulfilled.</info>';
    }

    protected function styleReturnCode(int $returnCode, ?string $message = null): string
    {
        switch ($returnCode) {
            case self::WARNING:
                $tag = 'comment';
                $message = $message ?: 'warning';
                break;
            case self::ERROR:
                $tag = 'error';
                break;
            case self::OKAY:
            default:
                $tag = 'info';
                $message = $message ?: $tag;
        }

        return '<' . $tag . '>' . ($message ?: $tag) . '</' . $tag . '>';
    }

    protected function checkPhpVersion(): int
    {
        return $this->evaluatePhpVersion(PHP_VERSION);
    }

    protected function evaluatePhpVersion(string $runningVersion): int
    {
        if (version_compare($runningVersion, self::REQUIRED_PHP_VERSION, '<')) {
            return self::ERROR;
        }

        return self::OKAY;
    }

    protected function prefixIsNotUsed(): int
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->doctrine->getConnection();
        $prefix = $this->parameterBag->get('app.database_prefix');

        if ($connection->executeQuery('SHOW TABLES LIKE "' . $prefix . '%"')->rowCount() !== 0) {
            return self::ERROR;
        }

        return self::OKAY;
    }

    protected function directoriesAreWritable(): int
    {
        $root = $this->parameterBag->get('kernel.project_dir') . '/';

        foreach ($this->DirectoriesThatMustBeWritable as $directory) {
            if (! is_writable($root . $directory)) {
                return self::WARNING;
            }
        }

        return self::OKAY;
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
final class InstallDatabaseCommandTest extends KernelTestCase
{
    protected Connection $Connection;

    protected string $DatabasePrefix;

    protected function setUp(): void
    {
        self::bootKernel([
            'environment' => 'test_empty',
        ]);

        $this->Connection = self::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->DatabasePrefix = self::$kernel->getContainer()->getParameter('database_prefix');

        $this->dropAllTables();
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();

        parent::tearDown();
    }

    protected function dropAllTables(): void
    {
        $query = 'SHOW TABLES LIKE "' . $this->DatabasePrefix . '%"';
        $tables = $this->Connection->executeQuery($query)
            ->fetchFirstColumn();

        if ($tables === []) {
            return;
        }

        $this->Connection->executeStatement('SET foreign_key_checks = 0');
        try {
            foreach ($tables as $table) {
                $quotedTable = $this->Connection->quoteSingleIdentifier($table);
                $this->Connection->executeStatement('DROP TABLE ' . $quotedTable);
            }
        } finally {
            $this->Connection->executeStatement('SET foreign_key_checks = 1');
        }
    }

    public function testExecute(): void
    {
        $application = new Application(self::$kernel);

        $command = $application->find('runalyze:install:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $query = 'SHOW TABLES LIKE "' . $this->DatabasePrefix . '%"';
        $tableCount = count($this->Connection->executeQuery($query)->fetchAllAssociative());

        $this->assertSame(24, $tableCount);
        $this->assertStringContainsString('Database has been successfully initialized.', $commandTester->getDisplay());
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group requiresKernel
 * @group requiresDoctrine
 */
class InstallDatabaseCommandTest extends KernelTestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $Connection;

    /**
     * @var string
     */
    protected $DatabasePrefix;

    protected function setUp(): void
    {
        static::bootKernel([
            'environment' => 'test_empty',
        ]);

        $this->Connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->DatabasePrefix = static::$kernel->getContainer()->getParameter('database_prefix');

        if ($this->Connection === null) {
            $this->markTestSkipped('No doctrine connection available, maybe cache needs to be cleared.');
        }

        $this->dropAllTables();
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();

        parent::tearDown();
    }

    protected function dropAllTables()
    {
        if ($this->Connection !== null) {
            $query = 'SHOW TABLES LIKE "' . $this->DatabasePrefix . '%"';
            $stmt = $this->Connection->executeQuery($query);
            $tables = $stmt->fetchFirstColumn();

            if (! empty($tables)) {
                $this->Connection->executeStatement('SET foreign_key_checks = 0');
                foreach ($tables as $table) {
                    $this->Connection->executeStatement('DROP TABLE `' . $table . '`');
                }

                $this->Connection->executeStatement('SET foreign_key_checks = 1');
            }
        }
    }

    public function testExecute()
    {
        $application = new Application(static::$kernel);

        $command = $application->find('runalyze:install:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $query = 'SHOW TABLES LIKE "' . $this->DatabasePrefix . '%"';
        $tableCount = count($this->Connection->executeQuery($query)->fetchAllAssociative());

        $this->assertEquals(24, $tableCount);
        $this->assertRegExp('/Database has been successfully initialized./', $commandTester->getDisplay());
    }
}

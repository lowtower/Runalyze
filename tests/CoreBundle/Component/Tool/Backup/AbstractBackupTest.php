<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use PDOStatement;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\AbstractBackup;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class AbstractBackupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    public const TESTFILE = '/../../../../../data/backup-tool/backup/test.json.gz';

    protected AbstractBackup $Backup;

    protected function setUp(): void
    {
        // AbstractBackup::run() is final and has to run for real. Only the one abstract method is implemented, and
        // it does nothing: the test is about the queries of run(), not about how the rows are written.
        $this->Backup = new class(__DIR__ . self::TESTFILE, 1, \DB::getInstance(), 'runalyze_', '3.2.0') extends AbstractBackup {
            protected function saveRowsFromStatement(string $tableName, array $columnInfo, PDOStatement $statement): void
            {
            }
        };
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . self::TESTFILE)) {
            unlink(__DIR__ . self::TESTFILE);
        }
    }

    public function testThatBackupQueriesDoWork(): void
    {
        $this->Backup->run();

        $this->assertFileExists(__DIR__ . self::TESTFILE, 'Backup file should exist after running backup');
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Backup;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\AbstractBackup;

/**
 * @group dependsOn
 * @group dependsOnOldDatabase
 */
class AbstractBackupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    public const TESTFILE = '/../../../../../data/backup-tool/backup/test.json.gz';

    /**
     * @var AbstractBackup
     */
    protected $Backup;

    protected function setUp(): void
    {
        $mockBuilder = $this->getMockBuilder(AbstractBackup::class);
        $mockBuilder->setConstructorArgs([__DIR__ . self::TESTFILE, 1, \DB::getInstance(), 'runalyze_', '3.2.0']);

        $this->Backup = $mockBuilder->getMockForAbstractClass();
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . self::TESTFILE)) {
            unlink(__DIR__ . self::TESTFILE);
        }
    }

    public function testThatBackupQueriesDoWork()
    {
        $this->Backup->run();
    }
}

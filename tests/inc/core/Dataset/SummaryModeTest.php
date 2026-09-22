<?php

namespace Runalyze\Dataset;

use PDO;

#[\PHPUnit\Framework\Attributes\Group('requiresSqlite')]
class SummaryModeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PDO
     */
    protected $PDO;

    protected function setUp(): void
    {
        $this->PDO = new PDO('sqlite::memory:');
        $this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->PDO->exec('CREATE TABLE IF NOT EXISTS `testtable` (`value` float, `s` float);');
    }

    protected function tearDown(): void
    {
        $this->PDO->exec('DROP TABLE `testtable`');
    }

    protected function fetchResult(int $mode)
    {
        return $this->PDO->query('SELECT ' . SummaryMode::query($mode, 'value') . ' FROM `testtable`')->fetchColumn();
    }

    /**
     * @param array<int, int[]> $dataArrays
     */
    protected function insertData(array $dataArrays)
    {
        foreach ($dataArrays as $data) {
            $this->PDO->exec('INSERT INTO `testtable` VALUES (' . $data[0] . ', ' . $data[1] . ')');
        }
    }

    public function testNoSummary(): void
    {
        $this->assertEquals('', SummaryMode::query(SummaryMode::NO, 'value'));
    }

    public function testAvg(): void
    {
        $this->insertData([[1, 1], [2, 1], [3, 1]]);

        $this->assertEquals(2, $this->fetchResult(SummaryMode::AVG));
    }

    public function testAvgBasedOnDuration(): void
    {
        $this->insertData([[10, 7], [20, 2], [30, 1], [513, 0]]);

        $this->assertEquals(14, $this->fetchResult(SummaryMode::AVG));
    }

    public function testSum(): void
    {
        $this->insertData([[1, 1], [2, 1], [3, 1]]);

        $this->assertEquals(6, $this->fetchResult(SummaryMode::SUM));
    }

    public function testMax(): void
    {
        $this->insertData([[10, 7], [20, 2], [30, 1], [513, 0]]);

        $this->assertEquals(513, $this->fetchResult(SummaryMode::MAX));
    }

    public function testMin(): void
    {
        $this->insertData([[10, 7], [20, 2], [30, 1], [513, 0]]);

        $this->assertEquals(10, $this->fetchResult(SummaryMode::MIN));
    }

    public function testAvgWithoutNull(): void
    {
        $this->insertData([[5, 1], [0, 1]]);

        $this->assertEquals(5, $this->fetchResult(SummaryMode::AVG));
    }
}

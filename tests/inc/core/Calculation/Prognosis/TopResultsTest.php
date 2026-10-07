<?php

namespace Runalyze\Tests\Calculation\Prognosis;

use Runalyze\Calculation\Prognosis\TopResults;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class TopResultsTest extends \PHPUnit\Framework\TestCase
{
    protected \PDOforRunalyze $DB;

    protected function setUp(): void
    {
        $this->DB = \DB::getInstance();

        $this->DB->exec('DELETE FROM `runalyze_training`');

        $runningSport = \Runalyze\Configuration::General()->runningSport();

        $this->DB->insert(
            'training',
            ['time', 'distance', 'sportid', 's', 'vo2max_by_time'],
            [1000, 5.0, $runningSport, 1, 50.0]
        );

        $this->DB->insert(
            'training',
            ['time', 'distance', 'sportid', 's', 'vo2max_by_time'],
            [1100, 10.0, $runningSport, 1, 60.0]
        );

        $this->DB->insert(
            'training',
            ['time', 'distance', 'sportid', 's', 'vo2max_by_time'],
            [1200, 21.1, $runningSport, 1, 55.0]
        );
    }

    protected function tearDown(): void
    {
        $this->DB->exec('DELETE FROM `runalyze_training`');
    }

    public function testReturnsSingleTopResultByDefault(): void
    {
        $result = new TopResults()->getTopResults();

        $this->assertSame(10.0, (float) $result['distance']);
        $this->assertSame(60.0, (float) $result['vo2max_by_time']);
    }

    public function testReturnsMultipleTopResults(): void
    {
        $results = new TopResults()->getTopResults(2);

        $this->assertCount(2, $results);

        $this->assertSame(10.0, (float) $results[0]['distance']);
        $this->assertSame(60.0, (float) $results[0]['vo2max_by_time']);
    }

    public function testMinimalDistanceFilter(): void
    {
        $results = new TopResults()->getTopResults(10, 15.0);

        $this->assertCount(1, $results);

        $this->assertSame(21.1, (float) $results[0]['distance']);
    }

    public function testResultsAreGroupedByDistance(): void
    {
        $results = new TopResults()->getTopResults(10);

        $tenKilometerResults = array_filter(
            $results,
            static fn (array $row): bool => (float) $row['distance'] === 10.0
        );

        $this->assertCount(1, $tenKilometerResults);
    }
}

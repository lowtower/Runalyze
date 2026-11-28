<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Search;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Component\Search\SearchResults;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Tests\DatabaseTestCase;
use SessionAccountHandler;

#[Group('dependsOn')]
#[Group('dependsOnOldDatabase')]
final class SearchResultsTest extends DatabaseTestCase
{
    #[DataProvider('dateRanges')]
    public function testDateRangeFiltersInLocalTimeWithoutRewritingTheForm(
        string|array $from,
        string|array $to,
        int $midnight,
        bool $valid
    ): void {
        $sportId = 765432;
        $statement = $this->DB->prepare('INSERT INTO `' . PREFIX . 'training`
            (`accountid`, `sportid`, `time`, `s`, `distance`) VALUES (?, ?, ?, 1500, 5)');
        $ids = [];
        foreach ([$midnight - 1, $midnight, $midnight + 86399, $midnight + 86401] as $time) {
            $statement->execute([SessionAccountHandler::getId(), $sportId, $time]);
            $ids[] = (int) $this->DB->lastInsertId();
        }

        $previousPost = $_POST;
        $previousTimezone = date_default_timezone_get();
        try {
            date_default_timezone_set('Europe/Berlin');
            $_POST = [
                'send-to-multi-editor' => '1',
                'sportid' => $sportId,
                'date-from' => $from,
                'date-to' => $to,
            ];

            $results = new SearchResults($this->createStub(ConfigurationManager::class));

            $this->assertSame($valid ? [$ids[2], $ids[1]] : [], $results->getIdsForMultiEditor());
            $this->assertSame($from, $_POST['date-from']);
            $this->assertSame($to, $_POST['date-to']);
        } finally {
            $_POST = $previousPost;
            date_default_timezone_set($previousTimezone);
        }
    }

    public static function dateRanges(): iterable
    {
        $startOfDst = gmmktime(0, 0, 0, 3, 31, 2024);
        yield 'date across daylight saving time' => ['31.3.24', '31.3.2024', $startOfDst, true];
        yield 'omitted year' => ['1.1.', '1.1.', gmmktime(0, 0, 0, 1, 1, (int) date('Y')), true];
        yield 'reversed range' => ['1.4.2024', '31.3.2024', $startOfDst, false];
        yield 'impossible calendar date' => ['31.3.2024', '31.4.2024', $startOfDst, false];
        yield 'array input' => [['31.3.2024'], '31.3.2024', $startOfDst, false];
        yield 'future range end' => ['31.3.2024', '31.3.' . ((int) date('Y') + 1), $startOfDst, false];
    }

    public function testPaceFilterRoundsEqualityAndUsesRawPaceForComparisons(): void
    {
        $sportId = 765432;
        $insert = $this->DB->prepare('INSERT INTO `' . PREFIX . 'training`
            (`accountid`, `sportid`, `time`, `s`, `distance`) VALUES (?, ?, ?, ?, ?)');
        $accountId = SessionAccountHandler::getId();
        $insert->execute([$accountId, $sportId, 1700000000, 1504, 5]);
        $faster = (int) $this->DB->lastInsertId();
        $insert->execute([$accountId, $sportId, 1700000001, 1506, 5]);
        $slower = (int) $this->DB->lastInsertId();
        $insert->execute([$accountId, $sportId, 1700000002, 1506, 0]);

        $previousPost = $_POST;
        $configuration = $this->createStub(ConfigurationManager::class);
        try {
            $_POST = [
                'send-to-multi-editor' => '1',
                'sportid' => $sportId,
                'pace' => '5:00,6',
                'opt' => [
                    'pace' => 'is',
                ],
            ];

            $this->assertSame([$slower, $faster], new SearchResults($configuration)->getIdsForMultiEditor());

            $_POST['opt']['pace'] = 'gt';
            $this->assertSame([$slower], new SearchResults($configuration)->getIdsForMultiEditor());
        } finally {
            $_POST = $previousPost;
        }
    }
}

<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Search;

use PHPUnit\Framework\Attributes\Group;
use Runalyze\Bundle\CoreBundle\Component\Search\SearchResults;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Tests\DatabaseTestCase;
use SessionAccountHandler;

#[Group('dependsOn')]
#[Group('dependsOnOldDatabase')]
final class SearchResultsTest extends DatabaseTestCase
{
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

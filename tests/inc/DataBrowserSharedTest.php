<?php

declare(strict_types=1);

use Runalyze\Tests\DatabaseTestCase;
use Runalyze\Util\LocalTime;
use Symfony\Component\HttpFoundation\RequestStack;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
final class DataBrowserSharedTest extends DatabaseTestCase
{
    private const int ACCOUNT_ID = 123456;

    private const string USERNAME = 'databrowser_shared_test';

    private const int SPORT_ID = 654321;

    private const int PUBLIC_TRAINING_ID = 111111;

    private const int PRIVATE_TRAINING_ID = 222222;

    private string $previousRequestUri;

    /**
     * @var array<string, mixed>
     */
    private array $previousGet;

    protected function setUp(): void
    {
        parent::setUp();

        // The shared athlete page reads its context (username, timerange) from the
        // request. Reset the request bridge so that this test does not depend on
        // whatever request state preceding tests have left behind.
        Request::setRequestStack(new RequestStack());

        $this->previousRequestUri = $_SERVER['REQUEST_URI'];
        $this->previousGet = $_GET;

        $_SERVER['REQUEST_URI'] = '/athlete/' . self::USERNAME;
        $_GET = [
            'user' => self::USERNAME,
            'start' => (string) LocalTime::fromString('2020-01-13 00:00:00')->getTimestamp(),
            'end' => (string) LocalTime::fromString('2020-01-19 23:59:59')->getTimestamp(),
        ];

        SharedLinker::$USER_ID = 0;

        // Statements that the legacy db wrapper rewrites get the id of the shared
        // athlete, the same way a shared-page Frontend request sets it up.
        $this->DB->setAccountID(self::ACCOUNT_ID);

        $this->insertAccount();
        $this->insertSport();
        $this->insertTraining(self::PUBLIC_TRAINING_ID, '2020-01-15 10:00:00', true);
        $this->insertTraining(self::PRIVATE_TRAINING_ID, '2020-01-16 10:00:00', false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_SERVER['REQUEST_URI'] = $this->previousRequestUri;
        $_GET = $this->previousGet;
        Request::setRequestStack(new RequestStack());

        SharedLinker::$USER_ID = 0;
        SessionAccountHandler::setAccount([
            'id' => 0,
            'username' => 'runalyze',
            'language' => 'de',
            'timezone' => '43',
            'mail' => 'noreply@runalyze.com',
        ]);

        // Reset the legacy db wrapper to its pristine state: as long as no
        // account id has been set, no statement is rewritten with an accountid.
        // Tests that run later in the same process depend on that.
        $accountID = new ReflectionProperty(PDOforRunalyze::class, 'accountID');
        $accountID->setValue($this->DB, false);

        $addsAccountID = new ReflectionProperty(PDOforRunalyze::class, 'addsAccountID');
        $addsAccountID->setValue($this->DB, true);
    }

    public function testThatAPublicActivityIsRenderedWithThePublicLinkColumn(): void
    {
        $data = new DataBrowserShared()->templateData();

        $this->assertFalse($data['all_days_empty']);

        $publicRow = null;

        foreach ($data['days'] as $row) {
            if (str_contains((string) $row['row_attributes'], 'id="training_' . self::PUBLIC_TRAINING_ID . '"')) {
                $publicRow = $row;
            }
        }

        $this->assertNotNull($publicRow, 'The public activity must be rendered as a clickable training row.');
        $this->assertSame('<td class="link">' . Icon::$ADD_SMALL_GREEN . '</td>', $publicRow['additional_columns_html']);
    }

    private function insertAccount(): void
    {
        $this->DB->exec('INSERT INTO `' . PREFIX . 'account` (`id`, `username`, `mail`) VALUES (' . self::ACCOUNT_ID . ', "' . self::USERNAME . '", "' . self::USERNAME . '@example.test")');
    }

    private function insertSport(): void
    {
        $this->DB->exec('INSERT INTO `' . PREFIX . 'sport` (`id`, `name`, `accountid`) VALUES (' . self::SPORT_ID . ', "Running", ' . self::ACCOUNT_ID . ')');
    }

    private function insertTraining(int $id, string $localStartTime, bool $isPublic): void
    {
        $this->DB->exec(
            'INSERT INTO `' . PREFIX . 'training` (`id`, `accountid`, `sportid`, `time`, `s`, `distance`, `is_public`) '
            . 'VALUES (' . $id . ', ' . self::ACCOUNT_ID . ', ' . self::SPORT_ID . ', '
            . LocalTime::fromString($localStartTime)->getTimestamp() . ', 1800, 10, ' . ($isPublic ? 1 : 0) . ')'
        );
    }
}

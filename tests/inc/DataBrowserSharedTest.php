<?php

declare(strict_types=1);

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DataBrowserMode;
use Runalyze\Tests\DatabaseTestCase;
use Runalyze\Util\LocalTime;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
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

    protected function setUp(): void
    {
        parent::setUp();

        $url = '/athlete/' . self::USERNAME;
        $request = SymfonyRequest::create($url);
        $request->attributes->set(SharedLinker::ACCOUNT_ATTRIBUTE, self::ACCOUNT_ID);
        $request->attributes->set(DataBrowserShared::BASE_URL_ATTRIBUTE, $url);

        $requestStack = new RequestStack([$request]);
        Request::setRequestStack($requestStack);

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

        Request::setRequestStack(new RequestStack());
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
        $data = $this->dataBrowser(false)->templateData();

        $this->assertFalse($data['all_days_empty']);

        $publicRow = null;

        foreach ($data['days'] as $row) {
            if (str_contains((string) $row['row_attributes'], 'id="training_' . self::PUBLIC_TRAINING_ID . '"')) {
                $publicRow = $row;
            }
        }

        $this->assertNotNull($publicRow, 'The public activity must be rendered as a clickable training row.');
        $this->assertSame('<td class="link">' . Icon::$ADD_SMALL_GREEN . '</td>', $publicRow['additional_columns_html']);
        $this->assertCount(1, array_filter($data['days'], static fn (array $row): bool => ! $row['is_empty']));
    }

    public function testOwnerCanIncludeAReadOnlyRowForPrivateActivity(): void
    {
        $data = $this->dataBrowser(true)->templateData();
        $rows = array_values(array_filter($data['days'], static fn (array $row): bool => ! $row['is_empty']));

        $this->assertCount(2, $rows);
        $this->assertCount(1, array_filter($rows, static fn (array $row): bool => $row['row_attributes'] === ''));
    }

    public function testPublicListSportLinkSelectsAnUnfilteredSport(): void
    {
        $data = new DataBrowserShared(self::ACCOUNT_ID, [
            'start' => LocalTime::fromString('2020-01-13 00:00:00')->getTimestamp(),
            'end' => LocalTime::fromString('2020-01-19 23:59:59')->getTimestamp(),
        ], false)->templateData();

        $this->assertCount(1, $data['sport_links']);
        $this->assertStringContainsString('&sport=' . self::SPORT_ID, (string) $data['sport_links'][0]);
    }

    public function testDataBrowserKeepsExplicitDatesAndUsesMonthPreferenceForInvalidDates(): void
    {
        $start = LocalTime::fromString('2020-01-13 00:00:00')->getTimestamp();
        $end = LocalTime::fromString('2020-01-19 23:59:59')->getTimestamp();
        $data = new DataBrowser(self::ACCOUNT_ID, [
            'start' => (string) $start,
            'end' => (string) $end,
        ])->templateData();
        $this->assertSame($start, $data['timestamp_start']);
        $this->assertSame($end, $data['timestamp_end']);

        $mode = Configuration::DataBrowser()->mode();
        $previous = $mode->valueAsString();
        try {
            $mode->set(DataBrowserMode::MONTH);
            $data = new DataBrowser(self::ACCOUNT_ID, [
                'start' => 'invalid',
                'end' => (string) $end,
            ])->templateData();

            $this->assertSame(LocalTime::fromString('first day of this month 00:00:00')->getTimestamp(), $data['timestamp_start']);
            $this->assertSame(LocalTime::fromString('last day of this month 23:59:59')->getTimestamp(), $data['timestamp_end']);
        } finally {
            $mode->set($previous);
        }
    }

    private function dataBrowser(bool $showPrivateActivities): DataBrowserShared
    {
        return new DataBrowserShared(self::ACCOUNT_ID, [
            'start' => (string) LocalTime::fromString('2020-01-13 00:00:00')->getTimestamp(),
            'end' => (string) LocalTime::fromString('2020-01-19 23:59:59')->getTimestamp(),
            'sport' => (string) self::SPORT_ID,
        ], $showPrivateActivities);
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

<?php

declare(strict_types=1);

use Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector;
use Runalyze\Model\Factory;
use Runalyze\Tests\DatabaseTestCase;
use Runalyze\Util\LocalTime;
use Symfony\Component\HttpFoundation\RequestStack;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
final class TrainingViewTest extends DatabaseTestCase
{
    private const int ACCOUNT_ID = 234567;

    private const string USERNAME = 'training_view_test';

    private const int SPORT_ID = 765432;

    private const int TRAINING_ID = 1234567;

    private const int NEXT_TRAINING_ID = 1234568;

    private string $previousRequestUri;

    /**
     * @var array<string, mixed>
     */
    private array $previousGet;

    protected function setUp(): void
    {
        parent::setUp();

        // The legacy request bridge reads the current request. Reset it so that
        // this test does not depend on whatever request state preceding tests
        // have left behind.
        Request::setRequestStack(new RequestStack());

        $this->previousRequestUri = $_SERVER['REQUEST_URI'];
        $this->previousGet = $_GET;

        $_SERVER['REQUEST_URI'] = '/activity/' . self::TRAINING_ID;
        $_GET = [];

        // The training view loads sport, title and neighbouring activities
        // through the legacy account handler.
        SessionAccountHandler::setAccount([
            'id' => self::ACCOUNT_ID,
            'username' => self::USERNAME,
            'gender' => 0,
            'birthyear' => 1990,
        ]);

        // Normally done by Frontend::defineConsts() for every request.
        LegacyEffectiveVO2maxCorrector::setGlobalFactor(
            \Runalyze\Configuration::Data()->vo2maxCorrectionFactor()
        );

        $this->clearLegacyCaches();

        $this->insertAccount();
        $this->insertSport();
        $this->insertTraining(self::TRAINING_ID, '2020-01-15 10:00:00');
        $this->insertTraining(self::NEXT_TRAINING_ID, '2020-01-16 10:00:00');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_SERVER['REQUEST_URI'] = $this->previousRequestUri;
        $_GET = $this->previousGet;
        Request::setRequestStack(new RequestStack());

        FrontendShared::$IS_SHOWN = false;
        SharedLinker::$USER_ID = 0;
        SessionAccountHandler::setAccount([
            'id' => 0,
            'username' => 'runalyze',
            'language' => 'de',
            'timezone' => '43',
            'mail' => 'noreply@runalyze.com',
        ]);
        \Runalyze\Context::reset();

        // Reset the legacy db wrapper to its pristine state: as long as no
        // account id has been set, no statement is rewritten with an accountid.
        // Tests that run later in the same process depend on that.
        $accountID = new ReflectionProperty(PDOforRunalyze::class, 'accountID');
        $accountID->setValue($this->DB, false);

        $addsAccountID = new ReflectionProperty(PDOforRunalyze::class, 'addsAccountID');
        $addsAccountID->setValue($this->DB, true);

        $this->clearLegacyCaches();
    }

    public function testThatTheActivityViewProvidesTitleToolbarNavigationAndSections(): void
    {
        $data = $this->trainingViewFor(self::TRAINING_ID)->templateData();

        $this->assertFalse($data['is_shared']);
        $this->assertSame('Running: My Test Activity', $data['title']);

        $this->assertNotEmpty($data['toolbar_links']);
        $this->assertContains(
            '<li class="with-submenu"><span class="link"><i class="fa fa-fw fa-wrench"></i></span><ul class="submenu">',
            $data['toolbar_links']
        );

        $this->assertSame(
            '<i class="transparent-70 fa-grey fa fa-fw fa-chevron-left"></i>',
            $data['navigation_prev']
        );
        $this->assertStringContainsString('activity/' . self::NEXT_TRAINING_ID, (string) $data['navigation_next']);
        $this->assertStringNotContainsString('transparent-70', (string) $data['navigation_next']);

        $this->assertSame(Icon::$REFRESH_SMALL, $data['refresh_icon']);

        $this->assertThatSectionsProvideTheOverviewRow($data);
        $this->assertStringContainsString('RunalyzePlot.resizeTrainingCharts();', (string) $data['plots_js']);
    }

    public function testThatTheSharedActivityViewShowsTheSharedToolbarAndNoNavigation(): void
    {
        $_SERVER['REQUEST_URI'] = '/shared/training';
        FrontendShared::$IS_SHOWN = true;
        SharedLinker::$USER_ID = self::ACCOUNT_ID;

        $data = $this->trainingViewFor(self::TRAINING_ID)->templateData();

        $this->assertTrue($data['is_shared']);

        $this->assertCount(2, $data['toolbar_links']);
        $this->assertStringContainsString('shared/', (string) $data['toolbar_links'][0]);
        $this->assertStringContainsString('athlete/' . self::USERNAME, (string) $data['toolbar_links'][1]);

        $this->assertSame('', $data['navigation_prev']);
        $this->assertSame('', $data['navigation_next']);

        $this->assertThatSectionsProvideTheOverviewRow($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertThatSectionsProvideTheOverviewRow(array $data): void
    {
        $this->assertNotEmpty($data['sections']);

        $overviewRow = null;
        $tabbedPlotRows = 0;
        $rowsWithBoxedValues = 0;

        foreach ($data['sections'] as $section) {
            $this->assertArrayHasKey('header', $section);
            $this->assertNotEmpty($section['rows']);

            foreach ($section['rows'] as $row) {
                if (($row['data']['id'] ?? '') === 'training-overview') {
                    $overviewRow = $row['data'];
                }

                if (($row['data']['plot']['kind'] ?? '') === 'tabbed_plot') {
                    ++$tabbedPlotRows;

                    foreach ($row['data']['plot']['tabs'] as $tab) {
                        $this->assertArrayHasKey('key', $tab);
                        $this->assertTrue(isset($tab['html']) || isset($tab['table']));

                        if (isset($tab['table'])) {
                            $this->assertArrayHasKey('header', $tab['table']);
                            $this->assertTrue(isset($tab['table']['rows']) || isset($tab['table']['bodies']));
                        }
                    }
                }

                if (($row['data']['kind'] ?? '') === 'row' && ($row['data']['info']['boxed_values'] ?? []) !== []) {
                    ++$rowsWithBoxedValues;
                }
            }
        }

        $this->assertNotNull($overviewRow, 'The overview row must be part of the sections.');
        $this->assertSame('fullwidth', $overviewRow['kind']);
        $this->assertNotEmpty($overviewRow['boxed_values']);

        foreach ($overviewRow['boxed_values'] as $boxedValue) {
            $this->assertTrue($boxedValue['floating']);
            $this->assertStringStartsWith('boxed-value-container', (string) $boxedValue['class']);
        }

        $this->assertGreaterThan(0, $tabbedPlotRows, 'At least one tabbed plot row must be part of the sections.');
        $this->assertGreaterThan(0, $rowsWithBoxedValues, 'At least one row with boxed values must be part of the sections.');
    }

    private function trainingViewFor(int $trainingId): TrainingView
    {
        return new TrainingView(new \Runalyze\View\Activity\Context($trainingId, self::ACCOUNT_ID));
    }

    /**
     * The legacy model factory keeps a static cache for all sports and writes
     * fetched activities to the file cache. Both outlive a single test, so the
     * caches are cleared before and after each test.
     */
    private function clearLegacyCaches(): void
    {
        // Model\Factory::clearCache() is not static, the static cache itself is
        // public and can be unset directly.
        unset(Factory::$StaticCache['sport']);

        foreach ([self::TRAINING_ID, self::NEXT_TRAINING_ID] as $trainingId) {
            \Cache::delete('training' . $trainingId);
            \Cache::delete('trackdata' . $trainingId);
            \Cache::delete('swimdata' . $trainingId);
            \Cache::delete('hrv' . $trainingId);
            \Cache::delete('raceresult' . $trainingId);
        }
    }

    private function insertAccount(): void
    {
        $this->DB->exec('INSERT INTO `' . PREFIX . 'account` (`id`, `username`, `mail`) VALUES (' . self::ACCOUNT_ID . ', "' . self::USERNAME . '", "' . self::USERNAME . '@example.test")');
    }

    private function insertSport(): void
    {
        $this->DB->exec('INSERT INTO `' . PREFIX . 'sport` (`id`, `name`, `accountid`) VALUES (' . self::SPORT_ID . ', "Running", ' . self::ACCOUNT_ID . ')');
    }

    private function insertTraining(int $id, string $localStartTime): void
    {
        // The overview row passes `kcal` to `Runalyze\Activity\Energy` whose
        // constructor is not nullable, so the value must always be set (as it
        // always is for imported activities).
        $this->DB->exec(
            'INSERT INTO `' . PREFIX . 'training` (`id`, `accountid`, `sportid`, `time`, `s`, `distance`, `kcal`, `title`) '
            . 'VALUES (' . $id . ', ' . self::ACCOUNT_ID . ', ' . self::SPORT_ID . ', '
            . LocalTime::fromString($localStartTime)->getTimestamp() . ', 1800, 10, 300, "My Test Activity")'
        );
    }
}
